<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

/** Persistent identity and audit trail for atomic option rollovers. */
final class CreateRollovers
{
    public function up(PDO $pdo): void
    {
        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'SERIAL PRIMARY KEY';
        $timestamp = $sqlite ? 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $uuid = $sqlite ? 'VARCHAR(36) NOT NULL UNIQUE' : 'UUID NOT NULL UNIQUE';
        $json = $sqlite ? 'TEXT NOT NULL' : 'JSONB NOT NULL';

        $pdo->exec("CREATE TABLE IF NOT EXISTS rollovers (
            id {$id},
            rollover_uuid {$uuid},
            user_id INTEGER NOT NULL,
            strategy_id INTEGER NULL,
            source_operation_id INTEGER NOT NULL,
            closing_operation_id INTEGER NOT NULL,
            opening_operation_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL CHECK (quantity > 0),
            closing_contract_id INTEGER NOT NULL,
            opening_contract_id INTEGER NOT NULL,
            closing_cash_flow NUMERIC(20,6) NOT NULL,
            opening_cash_flow NUMERIC(20,6) NOT NULL,
            net_cash_flow NUMERIC(20,6) NOT NULL,
            costs NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (costs >= 0),
            history {$json},
            created_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (strategy_id) REFERENCES strategies(id) ON DELETE SET NULL,
            FOREIGN KEY (source_operation_id) REFERENCES operations(id) ON DELETE RESTRICT,
            FOREIGN KEY (closing_operation_id) REFERENCES operations(id) ON DELETE RESTRICT,
            FOREIGN KEY (opening_operation_id) REFERENCES operations(id) ON DELETE RESTRICT,
            FOREIGN KEY (closing_contract_id) REFERENCES option_contracts(id) ON DELETE RESTRICT,
            FOREIGN KEY (opening_contract_id) REFERENCES option_contracts(id) ON DELETE RESTRICT
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rollovers_user_created ON rollovers(user_id, created_at, id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_rollovers_strategy ON rollovers(user_id, strategy_id, created_at)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS rollovers');
    }
}
