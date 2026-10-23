<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

final class CreateAppBaseTables
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS users (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    username TEXT NOT NULL UNIQUE,
                    password_hash TEXT NOT NULL,
                    profile TEXT NOT NULL DEFAULT 'USER',
                    status TEXT NOT NULL DEFAULT 'ACTIVE',
                    must_change_password INTEGER NOT NULL DEFAULT 1,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            SQL);

            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS settings (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    key_name TEXT NOT NULL UNIQUE,
                    value TEXT,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
            SQL);

            $pdo->exec(<<<'SQL'
                CREATE TABLE IF NOT EXISTS audit_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER NULL,
                    action TEXT NOT NULL,
                    entity_type TEXT NULL,
                    entity_id INTEGER NULL,
                    metadata TEXT NULL,
                    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                );
            SQL);

            return;
        }

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(100) NOT NULL UNIQUE,
                password_hash VARCHAR(255) NOT NULL,
                profile VARCHAR(50) NOT NULL DEFAULT 'USER',
                status VARCHAR(30) NOT NULL DEFAULT 'ACTIVE',
                must_change_password BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS settings (
                id SERIAL PRIMARY KEY,
                key_name VARCHAR(100) NOT NULL UNIQUE,
                value TEXT,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        SQL);

        $pdo->exec(<<<'SQL'
            CREATE TABLE IF NOT EXISTS audit_logs (
                id SERIAL PRIMARY KEY,
                user_id INTEGER NULL,
                action VARCHAR(120) NOT NULL,
                entity_type VARCHAR(120) NULL,
                entity_id INTEGER NULL,
                metadata JSONB NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
            );
        SQL);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS audit_logs;');
        $pdo->exec('DROP TABLE IF EXISTS settings;');
        $pdo->exec('DROP TABLE IF EXISTS users;');
    }
}
