<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\TemplateConstraints;
use App\Repositories\StrategyTemplateRepository;
use PDO;
use Throwable;

final class StrategyTemplateService
{
    public function __construct(private readonly PDO $pdo, private readonly StrategyTemplateRepository $repository) {}

    public function list(int $userId): array
    {
        return $this->repository->listVisible($userId);
    }

    public function find(int $userId, int $id): ?array
    {
        return $this->repository->findVisible($userId, $id);
    }

    public function create(int $userId, array $input): array
    {
        $normalized = $this->normalize($input);
        if ($normalized['errors'] !== []) return ['success' => false, 'status' => 422, 'errors' => $normalized['errors']];
        try {
            $this->pdo->beginTransaction();
            $id = $this->repository->insert($userId, $normalized['data']);
            $this->recordAudit($userId, 'STRATEGY_TEMPLATE_CREATED', $id);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 201, 'data' => $this->find($userId, $id)];
    }

    public function update(int $userId, int $id, array $input): array
    {
        $existing = $this->repository->findVisible($userId, $id);
        if ($existing === null || $existing['user_id'] !== $userId || $existing['origin'] !== 'USER') {
            return ['success' => false, 'status' => 404, 'message' => 'Modelo próprio não encontrado.'];
        }
        $position = $this->pdo->prepare('SELECT 1 FROM structured_positions WHERE strategy_template_id = :template_id AND user_id = :user_id LIMIT 1');
        $position->execute(['template_id' => $id, 'user_id' => $userId]);
        if ($position->fetchColumn()) {
            return ['success' => false, 'status' => 409, 'message' => 'Este modelo já registra posições e não pode mudar suas regras. Clone-o para criar uma nova versão.'];
        }
        $used = $this->pdo->prepare('SELECT 1 FROM structured_positions WHERE strategy_template_id = :template_id AND user_id = :user_id LIMIT 1');
        $used->execute(['template_id' => $id, 'user_id' => $userId]);
        if ($used->fetchColumn()) {
            return ['success' => false, 'status' => 409, 'message' => 'Modelo já utilizado por posições registradas; clone-o para criar uma nova versão.'];
        }
        $normalized = $this->normalize($input);
        if ($normalized['errors'] !== []) return ['success' => false, 'status' => 422, 'errors' => $normalized['errors']];
        try {
            $this->pdo->beginTransaction();
            if (!$this->repository->update($userId, $id, $normalized['data'])) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 404, 'message' => 'Modelo próprio não encontrado.'];
            }
            $this->recordAudit($userId, 'STRATEGY_TEMPLATE_UPDATED', $id);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 200, 'data' => $this->find($userId, $id)];
    }

    public function clone(int $userId, int $id): array
    {
        $source = $this->repository->findVisible($userId, $id);
        if ($source === null || $source['needs_review']) return ['success' => false, 'status' => 404, 'message' => 'Modelo não disponível para clonagem.'];
        try {
            $this->pdo->beginTransaction();
            $newId = $this->repository->clone($userId, [
                'name' => mb_substr($source['name'] . ' (cópia)', 0, 120),
                'description' => $source['description'],
                'category' => $source['category'],
                'support_level' => $source['support_level'],
                'constraints' => $source['constraints'],
                'slots' => $source['slots'],
            ]);
            $this->recordAudit($userId, 'STRATEGY_TEMPLATE_CLONED', $newId);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $exception;
        }
        return ['success' => true, 'status' => 201, 'data' => $this->find($userId, $newId)];
    }

    private function normalize(array $input): array
    {
        $errors = [];
        $name = trim((string) ($input['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 120) $errors['name'] = 'Informe um nome de até 120 caracteres.';
        $slots = $input['slots'] ?? null;
        $cleanSlots = [];
        $keys = [];
        if (!is_array($slots) || $slots === [] || !array_is_list($slots)) {
            $errors['slots'] = 'Adicione ao menos um slot.';
        } else {
            foreach ($slots as $index => $slot) {
                $field = "slots.{$index}";
                if (!is_array($slot)) {
                    $errors[$field] = 'Slot inválido.';
                    continue;
                }
                $key = (string) ($slot['slot_key'] ?? '');
                $side = strtoupper((string) ($slot['side'] ?? ''));
                $instrument = strtoupper((string) ($slot['instrument_type'] ?? 'OPTION'));
                $type = strtoupper((string) ($slot['option_type'] ?? ''));
                $ratio = filter_var($slot['ratio'] ?? 1, FILTER_VALIDATE_INT);
                if (!preg_match('/^[a-z][a-z0-9_]{0,39}$/', $key)) $errors["{$field}.slot_key"] = 'Use minúsculas, números e _ na chave.';
                elseif (in_array($key, $keys, true)) $errors["{$field}.slot_key"] = 'Chave repetida.';
                if (!in_array($side, ['BUY', 'SELL'], true)) $errors["{$field}.side"] = 'Lado deve ser BUY ou SELL.';
                if (!in_array($instrument, ['OPTION', 'ACTION'], true)) $errors["{$field}.instrument_type"] = 'Instrumento não suportado.';
                if ($instrument === 'OPTION' && !in_array($type, ['CALL', 'PUT'], true)) $errors["{$field}.option_type"] = 'Tipo deve ser CALL ou PUT.';
                if ($instrument === 'ACTION') $type = '';
                if ($ratio === false || $ratio < 1 || $ratio > 100) $errors["{$field}.ratio"] = 'Proporção deve ser inteiro entre 1 e 100.';
                $keys[] = $key;
                $label = trim((string) ($slot['label'] ?? ''));
                $cleanSlots[] = [
                    'slot_key' => $key,
                    'label' => $label === '' ? ($key !== '' ? $key : 'Slot ' . ($index + 1)) : mb_substr($label, 0, 120),
                    'instrument_type' => $instrument,
                    'side' => $side,
                    'option_type' => $type,
                    'ratio' => $ratio === false ? 1 : $ratio,
                ];
            }
        }
        $constraints = $input['constraints'] ?? [];
        if ($errors === []) $errors += TemplateConstraints::validateDefinition($constraints, $keys);
        if (!is_array($constraints) || ($constraints !== [] && !array_is_list($constraints))) $constraints = [];

        return [
            'errors' => $errors,
            'data' => [
                'name' => $name,
                'description' => isset($input['description']) ? trim((string) $input['description']) : null,
                'category' => mb_substr(trim((string) ($input['category'] ?? 'OPTIONS')) ?: 'OPTIONS', 0, 30),
                'support_level' => count(array_filter($cleanSlots, static fn(array $slot): bool => $slot['instrument_type'] !== 'OPTION')) > 0 ? 'UNSUPPORTED' : 'REGISTRATION',
                'slots' => $cleanSlots,
                'constraints' => $constraints,
            ],
        ];
    }

    private function recordAudit(int $userId, string $action, int $id): void
    {
        $statement = $this->pdo->prepare('INSERT INTO audit_logs (user_id,action,entity_type,entity_id,metadata) VALUES (:user_id,:action,:entity_type,:entity_id,:metadata)');
        $statement->execute([
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => 'strategy_template',
            'entity_id' => $id,
            'metadata' => '{}',
        ]);
    }
}
