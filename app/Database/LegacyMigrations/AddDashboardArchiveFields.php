<?php

declare(strict_types=1);

namespace App\Database\LegacyMigrations;

use PDO;

final class AddDashboardArchiveFields
{
    public function up(PDO $pdo): void
    {
        if ($this->tableExists($pdo, 'operations')) {
            $this->addColumn($pdo, 'operations', 'archived_at', 'TIMESTAMP NULL');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_operations_user_archived ON operations(user_id, archived_at)');
        }
        if ($this->tableExists($pdo, 'structured_positions')) {
            $this->addColumn($pdo, 'structured_positions', 'archived_at', 'TIMESTAMP NULL');
            $pdo->exec('CREATE INDEX IF NOT EXISTS idx_structured_positions_user_archived ON structured_positions(user_id, archived_at)');
        }
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP INDEX IF EXISTS idx_operations_user_archived');
        $pdo->exec('DROP INDEX IF EXISTS idx_structured_positions_user_archived');
        $this->dropColumn($pdo, 'operations', 'archived_at');
        $this->dropColumn($pdo, 'structured_positions', 'archived_at');
    }

    private function addColumn(PDO $pdo, string $table, string $column, string $definition): void
    {
        if (!$this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function dropColumn(PDO $pdo, string $table, string $column): void
    {
        if ($this->columnExists($pdo, $table, $column)) {
            $pdo->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }

    private function columnExists(PDO $pdo, string $table, string $column): bool
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            foreach ($pdo->query("PRAGMA table_info({$table})")->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if ($row['name'] === $column) return true;
            }
            return false;
        }

        $query = $pdo->prepare('SELECT 1 FROM information_schema.columns WHERE table_name = :table AND column_name = :column');
        $query->execute(['table' => $table, 'column' => $column]);
        return (bool) $query->fetchColumn();
    }

    private function tableExists(PDO $pdo, string $table): bool
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :table");
            $statement->execute(['table' => $table]);
            return (bool) $statement->fetchColumn();
        }
        $statement = $pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_name = :table');
        $statement->execute(['table' => $table]);
        return (bool) $statement->fetchColumn();
    }
}
