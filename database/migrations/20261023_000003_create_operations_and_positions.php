<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

/** Append-only ledger of trade executions and their realized allocation history. */
final class CreateOperationsAndPositions
{
    public function up(PDO $pdo): void
    {
        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'SERIAL PRIMARY KEY';
        $timestamp = $sqlite ? 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $json = $sqlite ? 'TEXT' : 'JSONB';

        $pdo->exec("CREATE TABLE IF NOT EXISTS operations (
            id {$id},
            user_id INTEGER NOT NULL,
            option_contract_id INTEGER NOT NULL,
            strategy_id INTEGER NULL,
            side VARCHAR(4) NOT NULL CHECK (side IN ('BUY','SELL')),
            quantity INTEGER NOT NULL CHECK (quantity > 0),
            unit_price NUMERIC(18,6) NOT NULL CHECK (unit_price >= 0),
            gross_cash_flow NUMERIC(20,6) NOT NULL,
            costs NUMERIC(18,6) NOT NULL DEFAULT 0 CHECK (costs >= 0),
            executed_at TIMESTAMP NOT NULL,
            reference_operation_id INTEGER NULL,
            operation_kind VARCHAR(20) NOT NULL DEFAULT 'TRADE' CHECK (operation_kind IN ('TRADE','OPEN','CLOSE','PARTIAL_CLOSE','REVERSAL','ADJUSTMENT')),
            notes TEXT NULL,
            created_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (option_contract_id) REFERENCES option_contracts(id) ON DELETE RESTRICT,
            FOREIGN KEY (strategy_id) REFERENCES strategies(id) ON DELETE SET NULL,
            FOREIGN KEY (reference_operation_id) REFERENCES operations(id) ON DELETE RESTRICT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS operation_closures (
            id {$id},
            user_id INTEGER NOT NULL,
            opening_operation_id INTEGER NOT NULL,
            closing_operation_id INTEGER NOT NULL,
            quantity INTEGER NOT NULL CHECK (quantity > 0),
            realized_result NUMERIC(20,6) NOT NULL,
            created_at {$timestamp},
            UNIQUE (closing_operation_id, opening_operation_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (opening_operation_id) REFERENCES operations(id) ON DELETE RESTRICT,
            FOREIGN KEY (closing_operation_id) REFERENCES operations(id) ON DELETE RESTRICT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS market_quotes (
            id {$id},
            user_id INTEGER NOT NULL,
            option_contract_id INTEGER NOT NULL,
            quote_price NUMERIC(18,6) NOT NULL CHECK (quote_price >= 0),
            quoted_at TIMESTAMP NOT NULL,
            created_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (option_contract_id) REFERENCES option_contracts(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS operation_history (
            id {$id},
            user_id INTEGER NOT NULL,
            operation_id INTEGER NOT NULL,
            action VARCHAR(30) NOT NULL,
            before_data {$json} NULL,
            after_data {$json} NULL,
            created_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE RESTRICT
        )");

        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_operations_user_contract_date ON operations(user_id, option_contract_id, executed_at, id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_closures_user_opening ON operation_closures(user_id, opening_operation_id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_quotes_user_contract_date ON market_quotes(user_id, option_contract_id, quoted_at)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_operation_history_user_operation ON operation_history(user_id, operation_id, created_at)');
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP TABLE IF EXISTS operation_history');
        $pdo->exec('DROP TABLE IF EXISTS market_quotes');
        $pdo->exec('DROP TABLE IF EXISTS operation_closures');
        $pdo->exec('DROP TABLE IF EXISTS operations');
    }
}
