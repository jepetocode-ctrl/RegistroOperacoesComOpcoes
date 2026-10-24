<?php

declare(strict_types=1);

namespace App\Database\LegacyMigrations;

use App\Domain\LegacyStrategyConverter;
use App\Domain\StrategyCatalog;
use PDO;

final class CreateStructuredStrategyDomain
{
    public function up(PDO $pdo): void
    {
        $sqlite = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
        $id = $sqlite ? 'INTEGER PRIMARY KEY AUTOINCREMENT' : 'SERIAL PRIMARY KEY';
        $timestamp = $sqlite ? 'TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP' : 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP';
        $json = $sqlite ? 'TEXT' : 'JSONB';
        $uuid = $sqlite ? 'VARCHAR(36) NOT NULL UNIQUE' : 'UUID NOT NULL UNIQUE';

        $pdo->exec("CREATE TABLE IF NOT EXISTS strategy_templates (
            id {$id},
            user_id INTEGER NULL,
            code VARCHAR(60) NULL,
            name VARCHAR(120) NOT NULL,
            description TEXT NULL,
            category VARCHAR(30) NOT NULL DEFAULT 'OPTIONS',
            origin VARCHAR(10) NOT NULL DEFAULT 'USER' CHECK (origin IN ('SYSTEM','USER','LEGACY')),
            support_level VARCHAR(20) NOT NULL DEFAULT 'REGISTRATION',
            constraints {$json} NULL,
            legacy_strategy_id INTEGER NULL,
            legacy_legs TEXT NULL,
            needs_review INTEGER NOT NULL DEFAULT 0,
            review_reason TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
            created_at {$timestamp},
            updated_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (legacy_strategy_id) REFERENCES strategies(id) ON DELETE SET NULL
        )");
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_strategy_templates_system_code ON strategy_templates(code) WHERE user_id IS NULL AND code IS NOT NULL');
        $pdo->exec('CREATE UNIQUE INDEX IF NOT EXISTS uq_strategy_templates_legacy ON strategy_templates(legacy_strategy_id) WHERE legacy_strategy_id IS NOT NULL');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_strategy_templates_user_status ON strategy_templates(user_id, status)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS strategy_template_slots (
            id {$id},
            template_id INTEGER NOT NULL,
            position INTEGER NOT NULL,
            slot_key VARCHAR(40) NOT NULL,
            label VARCHAR(120) NOT NULL,
            instrument_type VARCHAR(10) NOT NULL DEFAULT 'OPTION' CHECK (instrument_type IN ('OPTION','ACTION')),
            side VARCHAR(4) NOT NULL CHECK (side IN ('BUY','SELL')),
            option_type VARCHAR(4) NULL CHECK (option_type IS NULL OR option_type IN ('CALL','PUT')),
            ratio INTEGER NOT NULL DEFAULT 1 CHECK (ratio > 0),
            UNIQUE (template_id, slot_key),
            FOREIGN KEY (template_id) REFERENCES strategy_templates(id) ON DELETE CASCADE
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS structured_positions (
            id {$id},
            uuid {$uuid},
            user_id INTEGER NOT NULL,
            strategy_template_id INTEGER NOT NULL,
            asset_id INTEGER NOT NULL,
            name VARCHAR(120) NOT NULL,
            base_quantity INTEGER NOT NULL CHECK (base_quantity > 0),
            executed_at TIMESTAMP NOT NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'BRL',
            broker VARCHAR(120) NULL,
            general_costs NUMERIC(20,6) NOT NULL DEFAULT 0 CHECK (general_costs >= 0),
            declared_strategy VARCHAR(120) NOT NULL,
            inferred_strategy VARCHAR(120) NULL,
            classification_status VARCHAR(20) NOT NULL DEFAULT 'UNRECOGNIZED',
            notes TEXT NULL,
            created_at {$timestamp},
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (strategy_template_id) REFERENCES strategy_templates(id) ON DELETE RESTRICT,
            FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_structured_positions_user_date ON structured_positions(user_id, executed_at, id)');
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_structured_positions_user_template ON structured_positions(user_id, strategy_template_id)');

        $pdo->exec("CREATE TABLE IF NOT EXISTS structured_position_legs (
            id {$id},
            structured_position_id INTEGER NOT NULL,
            slot_key VARCHAR(40) NOT NULL,
            entry_kind VARCHAR(12) NOT NULL DEFAULT 'INITIAL' CHECK (entry_kind IN ('INITIAL','ROLLED')),
            operation_id INTEGER NULL UNIQUE,
            option_contract_id INTEGER NULL,
            asset_id INTEGER NULL,
            FOREIGN KEY (structured_position_id) REFERENCES structured_positions(id) ON DELETE CASCADE,
            FOREIGN KEY (operation_id) REFERENCES operations(id) ON DELETE RESTRICT,
            FOREIGN KEY (option_contract_id) REFERENCES option_contracts(id) ON DELETE RESTRICT,
            FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT
        )");
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_structured_position_legs_position_slot ON structured_position_legs(structured_position_id, slot_key)');

        if (!$this->columnExists($pdo, 'operations', 'structured_position_id')) {
            $pdo->exec('ALTER TABLE operations ADD COLUMN structured_position_id INTEGER NULL REFERENCES structured_positions(id) ON DELETE SET NULL');
        }
        $pdo->exec('CREATE INDEX IF NOT EXISTS idx_operations_structured_position ON operations(structured_position_id)');

        $this->seedCatalog($pdo);
        $this->convertLegacyStrategies($pdo);
    }

    public function down(PDO $pdo): void
    {
        $pdo->exec('DROP INDEX IF EXISTS idx_operations_structured_position');
        if ($this->columnExists($pdo, 'operations', 'structured_position_id')) {
            $pdo->exec('ALTER TABLE operations DROP COLUMN structured_position_id');
        }
        $pdo->exec('DROP TABLE IF EXISTS structured_position_legs');
        $pdo->exec('DROP TABLE IF EXISTS structured_positions');
        $pdo->exec('DROP TABLE IF EXISTS strategy_template_slots');
        $pdo->exec('DROP TABLE IF EXISTS strategy_templates');
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

    private function seedCatalog(PDO $pdo): void
    {
        foreach (StrategyCatalog::all() as $template) {
            $exists = $pdo->prepare('SELECT id FROM strategy_templates WHERE user_id IS NULL AND code = :code');
            $exists->execute(['code' => $template['code']]);
            if ($exists->fetchColumn()) continue;
            $this->insertTemplate($pdo, null, $template, 'SYSTEM', null, null, false, null);
        }
    }

    private function convertLegacyStrategies(PDO $pdo): void
    {
        $strategies = $pdo->query('SELECT id, user_id, name, description, legs FROM strategies ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($strategies as $strategy) {
            $exists = $pdo->prepare('SELECT id FROM strategy_templates WHERE legacy_strategy_id = :id');
            $exists->execute(['id' => $strategy['id']]);
            if ($exists->fetchColumn()) continue;

            $raw = $strategy['legs'] === null ? null : (string) $strategy['legs'];
            $converted = LegacyStrategyConverter::convert($raw);
            $template = [
                'code' => null,
                'name' => (string) $strategy['name'],
                'description' => $strategy['description'],
                'category' => 'OPTIONS',
                'support_level' => 'REGISTRATION',
                'constraints' => $converted['constraints'],
                'slots' => $converted['slots'],
            ];
            $this->insertTemplate(
                $pdo,
                (int) $strategy['user_id'],
                $template,
                'LEGACY',
                (int) $strategy['id'],
                $raw,
                $converted['needs_review'],
                $converted['reason']
            );
        }
    }

    private function insertTemplate(
        PDO $pdo,
        ?int $userId,
        array $template,
        string $origin,
        ?int $legacyId,
        ?string $legacyLegs,
        bool $needsReview,
        ?string $reason
    ): void {
        $insert = $pdo->prepare('INSERT INTO strategy_templates (user_id, code, name, description, category, origin, support_level, constraints, legacy_strategy_id, legacy_legs, needs_review, review_reason) VALUES (:user_id,:code,:name,:description,:category,:origin,:support_level,:constraints,:legacy_id,:legacy_legs,:needs_review,:reason)');
        $insert->execute([
            'user_id' => $userId,
            'code' => $template['code'],
            'name' => $template['name'],
            'description' => $template['description'],
            'category' => $template['category'],
            'origin' => $origin,
            'support_level' => $template['support_level'],
            'constraints' => json_encode($template['constraints'], JSON_THROW_ON_ERROR),
            'legacy_id' => $legacyId,
            'legacy_legs' => $legacyLegs,
            'needs_review' => $needsReview ? 1 : 0,
            'reason' => $reason,
        ]);
        $templateId = (int) $pdo->lastInsertId();
        $slotInsert = $pdo->prepare('INSERT INTO strategy_template_slots (template_id, position, slot_key, label, instrument_type, side, option_type, ratio) VALUES (:template_id,:position,:slot_key,:label,:instrument_type,:side,:option_type,:ratio)');
        foreach (array_values($template['slots']) as $index => $slot) {
            $slotInsert->execute([
                'template_id' => $templateId,
                'position' => $index + 1,
                'slot_key' => $slot['slot_key'],
                'label' => $slot['label'],
                'instrument_type' => $slot['instrument_type'] ?? 'OPTION',
                'side' => $slot['side'],
                'option_type' => ($slot['option_type'] ?? '') === '' ? null : $slot['option_type'],
                'ratio' => $slot['ratio'],
            ]);
        }
    }
}
