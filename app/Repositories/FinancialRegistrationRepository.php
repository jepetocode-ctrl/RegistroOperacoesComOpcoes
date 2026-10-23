<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

/** Shared financial master data with creator-scoped updates. */
final class FinancialRegistrationRepository
{
    private const TABLES = [
        'assets' => ['symbol', 'name', 'asset_type', 'status'],
        'option_contracts' => ['asset_id', 'code', 'option_type', 'strike_price', 'expiration_date', 'contract_multiplier', 'status'],
        'strategies' => ['name', 'description', 'legs', 'status'],
    ];

    public function __construct(private readonly PDO $pdo) {}

    public function all(string $resource, int $userId, bool $includeInactive = true): array
    {
        $table = $this->table($resource);
        $sql = "SELECT * FROM {$table}";
        if (!$includeInactive) $sql .= " WHERE status = 'ACTIVE'";
        $sql .= ' ORDER BY id DESC';
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function find(string $resource, int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->table($resource) . ' WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $record = $stmt->fetch();
        return $record === false ? null : $record;
    }

    public function findOwned(string $resource, int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM ' . $this->table($resource) . ' WHERE user_id = :user_id AND id = :id');
        $stmt->execute(['user_id' => $userId, 'id' => $id]);
        $record = $stmt->fetch();
        return $record === false ? null : $record;
    }

    public function create(string $resource, int $userId, array $data): array
    {
        $table = $this->table($resource);
        $data = array_intersect_key($data, array_flip(self::TABLES[$table]));
        if ($table === 'strategies' && isset($data['legs']) && is_array($data['legs'])) {
            $data['legs'] = json_encode($data['legs'], JSON_THROW_ON_ERROR);
        }
        $data['user_id'] = $userId;
        $columns = array_keys($data);
        $sql = 'INSERT INTO ' . $table . ' (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        return $this->find($resource, $userId, (int) $this->pdo->lastInsertId()) ?? [];
    }

    public function update(string $resource, int $userId, int $id, array $data): ?array
    {
        $table = $this->table($resource);
        $data = array_intersect_key($data, array_flip(self::TABLES[$table]));
        if ($table === 'strategies' && isset($data['legs']) && is_array($data['legs'])) {
            $data['legs'] = json_encode($data['legs'], JSON_THROW_ON_ERROR);
        }
        if ($data === []) return $this->find($resource, $userId, $id);
        $data['id'] = $id;
        $data['user_id'] = $userId;
        $sets = [];
        foreach (array_keys($data) as $key) {
            if (!in_array($key, ['id', 'user_id'], true)) $sets[] = "{$key} = :{$key}";
        }
        $stmt = $this->pdo->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ', updated_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id');
        $stmt->execute($data);
        return $this->find($resource, $userId, $id);
    }

    private function table(string $resource): string
    {
        if (!array_key_exists($resource, self::TABLES)) throw new InvalidArgumentException('Cadastro inválido.');
        return $resource;
    }
}
