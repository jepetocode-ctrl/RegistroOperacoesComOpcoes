<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StrategyTemplateRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function listVisible(int $userId): array
    {
        $statement = $this->pdo->prepare("SELECT * FROM strategy_templates WHERE status = 'ACTIVE' AND (user_id IS NULL OR user_id = :user_id) ORDER BY (user_id IS NOT NULL), name, id");
        $statement->execute(['user_id' => $userId]);
        return array_map(fn(array $row): array => $this->hydrate($row), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findVisible(int $userId, int $id): ?array
    {
        $statement = $this->pdo->prepare("SELECT * FROM strategy_templates WHERE id = :id AND status = 'ACTIVE' AND (user_id IS NULL OR user_id = :user_id)");
        $statement->execute(['id' => $id, 'user_id' => $userId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return $row === false ? null : $this->hydrate($row);
    }

    public function insert(int $userId, array $template): int
    {
        $statement = $this->pdo->prepare("INSERT INTO strategy_templates (user_id, name, description, category, origin, support_level, constraints) VALUES (:user_id,:name,:description,:category,'USER',:support_level,:constraints)");
        $statement->execute([
            'user_id' => $userId,
            'name' => $template['name'],
            'description' => $template['description'],
            'category' => $template['category'],
            'support_level' => $template['support_level'],
            'constraints' => json_encode($template['constraints'], JSON_THROW_ON_ERROR),
        ]);
        $id = (int) $this->pdo->lastInsertId();
        $this->replaceSlots($id, $template['slots']);
        return $id;
    }

    public function update(int $userId, int $id, array $template): bool
    {
        $statement = $this->pdo->prepare("UPDATE strategy_templates SET name = :name, description = :description, category = :category, constraints = :constraints, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id AND origin = 'USER'");
        $statement->execute([
            'name' => $template['name'],
            'description' => $template['description'],
            'category' => $template['category'],
            'constraints' => json_encode($template['constraints'], JSON_THROW_ON_ERROR),
            'id' => $id,
            'user_id' => $userId,
        ]);
        if ($statement->rowCount() === 0) {
            $exists = $this->pdo->prepare("SELECT 1 FROM strategy_templates WHERE id = :id AND user_id = :user_id AND origin = 'USER'");
            $exists->execute(['id' => $id, 'user_id' => $userId]);
            if (!$exists->fetchColumn()) return false;
        }
        $this->replaceSlots($id, $template['slots']);
        return true;
    }

    public function clone(int $userId, array $template): int
    {
        return $this->insert($userId, [
            'name' => $template['name'],
            'description' => $template['description'],
            'category' => $template['category'],
            'support_level' => $template['support_level'],
            'constraints' => $template['constraints'],
            'slots' => $template['slots'],
        ]);
    }

    private function replaceSlots(int $templateId, array $slots): void
    {
        $this->pdo->prepare('DELETE FROM strategy_template_slots WHERE template_id = :id')->execute(['id' => $templateId]);
        $insert = $this->pdo->prepare('INSERT INTO strategy_template_slots (template_id, position, slot_key, label, instrument_type, side, option_type, ratio) VALUES (:template_id,:position,:slot_key,:label,:instrument_type,:side,:option_type,:ratio)');
        foreach ($slots as $index => $slot) {
            $insert->execute([
                'template_id' => $templateId,
                'position' => $index + 1,
                'slot_key' => $slot['slot_key'],
                'label' => $slot['label'],
                'instrument_type' => $slot['instrument_type'],
                'side' => $slot['side'],
                'option_type' => $slot['option_type'] === '' ? null : $slot['option_type'],
                'ratio' => $slot['ratio'],
            ]);
        }
    }

    private function hydrate(array $template): array
    {
        try {
            $template['constraints'] = $template['constraints'] === null
                ? []
                : json_decode((string) $template['constraints'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException('Restrições persistidas do modelo estão corrompidas.', 0, $exception);
        }
        $slots = $this->pdo->prepare('SELECT slot_key, label, instrument_type, side, option_type, ratio, position FROM strategy_template_slots WHERE template_id = :id ORDER BY position');
        $slots->execute(['id' => $template['id']]);
        $template['slots'] = array_map(static function (array $slot): array {
            $slot['ratio'] = (int) $slot['ratio'];
            $slot['position'] = (int) $slot['position'];
            return $slot;
        }, $slots->fetchAll(PDO::FETCH_ASSOC));
        $template['id'] = (int) $template['id'];
        $template['user_id'] = $template['user_id'] === null ? null : (int) $template['user_id'];
        $template['needs_review'] = (bool) $template['needs_review'];
        return $template;
    }
}
