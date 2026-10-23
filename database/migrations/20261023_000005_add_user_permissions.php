<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

final class AddUserPermissions
{
    public function up(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT NOT NULL DEFAULT '[]';");
            return;
        }

        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions TEXT NOT NULL DEFAULT '[]';");
    }

    public function down(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $pdo->exec('CREATE TABLE users_backup AS SELECT id, username, password_hash, profile, status, must_change_password, created_at, updated_at FROM users;');
            $pdo->exec('DROP TABLE users;');
            $pdo->exec(<<<'SQL'
                CREATE TABLE users (
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
            $pdo->exec('INSERT INTO users (id, username, password_hash, profile, status, must_change_password, created_at, updated_at) SELECT id, username, password_hash, profile, status, must_change_password, created_at, updated_at FROM users_backup;');
            $pdo->exec('DROP TABLE users_backup;');
            return;
        }

        $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS permissions;');
    }
}
