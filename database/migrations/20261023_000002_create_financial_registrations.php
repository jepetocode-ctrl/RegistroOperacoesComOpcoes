<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

/**
 * Adds user-owned financial master data. IDs are scoped in repository queries;
 * foreign keys prevent contracts and strategies from referencing missing assets.
 */
final class CreateFinancialRegistrations
{
    public function up(PDO $pdo): void
    {
        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'SERIAL PRIMARY KEY';
        $bool = $sqlite ? 'INTEGER NOT NULL DEFAULT 1' : 'BOOLEAN NOT NULL DEFAULT TRUE';
        $timestamp = $sqlite ? 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $json = $sqlite ? 'TEXT' : 'JSONB';

        $pdo->exec("CREATE TABLE IF NOT EXISTS assets (
            id {$id},
            user_id INTEGER NOT NULL,
            symbol VARCHAR(30) NOT NULL,
            name VARCHAR(120) NOT NULL,
            asset_type VARCHAR(30) NOT NULL DEFAULT 'STOCK',
            status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
            created_at {$timestamp},
            updated_at {$timestamp},
            UNIQUE (user_id, symbol),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS option_contracts (
            id {$id},
            user_id INTEGER NOT NULL,
            asset_id INTEGER NOT NULL,
            code VARCHAR(60) NOT NULL,
            option_type VARCHAR(4) NOT NULL,
            strike_price NUMERIC(18,6) NOT NULL,
            expiration_date DATE NOT NULL,
            contract_multiplier INTEGER NOT NULL DEFAULT 100,
            status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
            created_at {$timestamp},
            updated_at {$timestamp},
            UNIQUE (user_id, code),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS strategies (
            id {$id},
            user_id INTEGER NOT NULL,
            name VARCHAR(120) NOT NULL,
            description TEXT NULL,
            legs {$json} NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
            created_at {$timestamp},
            updated_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_assets_user_status ON assets(user_id, status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_options_user_status ON option_contracts(user_id, status)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_strategies_user_status ON strategies(user_id, status)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS strategies');
        $pdo->exec('DROP TABLE IF EXISTS option_contracts');
        $pdo->exec('DROP TABLE IF EXISTS assets');
    }
}
