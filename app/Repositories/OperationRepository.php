<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class OperationRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function list(int $userId, ?int $contractId = null): array
    {
        $sql = 'SELECT * FROM operations WHERE user_id = :user_id';
        $params = ['user_id' => $userId];
        if ($contractId !== null) { $sql .= ' AND option_contract_id = :contract_id'; $params['contract_id'] = $contractId; }
        $sql .= ' ORDER BY executed_at, id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function transactionHistory(int $userId, int $contractId): array
    {
        $stmt = $this->pdo->prepare("SELECT o.*, COALESCE((SELECT SUM(c.realized_result) FROM operation_closures c WHERE c.user_id = o.user_id AND c.closing_operation_id = o.id), 0) AS realized_result FROM operations o WHERE o.user_id = :user_id AND o.option_contract_id = :contract_id ORDER BY o.executed_at DESC, o.id DESC");
        $stmt->execute(['user_id' => $userId, 'contract_id' => $contractId]);
        return $stmt->fetchAll();
    }

    public function find(int $userId, int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM operations WHERE user_id = :user_id AND id = :id');
        $stmt->execute(['user_id' => $userId, 'id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function insert(array $data): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO operations (user_id, option_contract_id, strategy_id, side, quantity, unit_price, gross_cash_flow, costs, executed_at, reference_operation_id, operation_kind, notes) VALUES (:user_id, :option_contract_id, :strategy_id, :side, :quantity, :unit_price, :gross_cash_flow, :costs, :executed_at, :reference_operation_id, :operation_kind, :notes)');
        $stmt->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $userId, int $id, array $data): void
    {
        $stmt = $this->pdo->prepare('UPDATE operations SET strategy_id = :strategy_id, side = :side, quantity = :quantity, unit_price = :unit_price, gross_cash_flow = :gross_cash_flow, costs = :costs, executed_at = :executed_at, operation_kind = :operation_kind, notes = :notes WHERE user_id = :user_id AND id = :id');
        $stmt->execute([
            'user_id' => $userId, 'id' => $id, 'strategy_id' => $data['strategy_id'],
            'side' => $data['side'], 'quantity' => $data['quantity'],
            'unit_price' => $data['unit_price'], 'gross_cash_flow' => $data['gross_cash_flow'],
            'costs' => $data['costs'], 'executed_at' => $data['executed_at'],
            'operation_kind' => $data['operation_kind'], 'notes' => $data['notes'],
        ]);
    }

    public function insertClosure(array $data): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO operation_closures (user_id, opening_operation_id, closing_operation_id, quantity, realized_result) VALUES (:user_id, :opening_operation_id, :closing_operation_id, :quantity, :realized_result)');
        $stmt->execute($data);
    }

    public function insertHistory(int $userId, int $operationId, string $action, ?array $before, array $after): void
    {
        $stmt = $this->pdo->prepare('INSERT INTO operation_history (user_id, operation_id, action, before_data, after_data) VALUES (:user_id, :operation_id, :action, :before_data, :after_data)');
        $stmt->execute([
            'user_id' => $userId, 'operation_id' => $operationId, 'action' => $action,
            'before_data' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after_data' => json_encode($after, JSON_THROW_ON_ERROR),
        ]);
    }

    public function closureQuantities(int $userId, array $openingIds): array
    {
        if ($openingIds === []) return [];
        $marks = implode(',', array_fill(0, count($openingIds), '?'));
        $stmt = $this->pdo->prepare("SELECT opening_operation_id, SUM(quantity) AS closed_quantity FROM operation_closures WHERE user_id = ? AND opening_operation_id IN ({$marks}) GROUP BY opening_operation_id");
        $stmt->execute(array_merge([$userId], $openingIds));
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[(int)$row['opening_operation_id']] = (int)$row['closed_quantity'];
        return $out;
    }

    public function quotes(int $userId, array $contractIds): array
    {
        if ($contractIds === []) return [];
        $marks = implode(',', array_fill(0, count($contractIds), '?'));
        $stmt = $this->pdo->prepare("SELECT q.* FROM market_quotes q JOIN (SELECT option_contract_id, MAX(quoted_at) AS latest FROM market_quotes WHERE user_id = ? AND option_contract_id IN ({$marks}) GROUP BY option_contract_id) latest ON latest.option_contract_id = q.option_contract_id AND latest.latest = q.quoted_at WHERE q.user_id = ?");
        $stmt->execute(array_merge([$userId], $contractIds, [$userId]));
        $out = [];
        foreach ($stmt->fetchAll() as $row) $out[(int)$row['option_contract_id']] = $row;
        return $out;
    }

    public function insertQuote(int $userId, int $contractId, float $price, string $quotedAt): int
    {
        $stmt = $this->pdo->prepare('INSERT INTO market_quotes (user_id, option_contract_id, quote_price, quoted_at) VALUES (:user_id, :contract_id, :price, :quoted_at)');
        $stmt->execute(['user_id' => $userId, 'contract_id' => $contractId, 'price' => $price, 'quoted_at' => $quotedAt]);
        return (int) $this->pdo->lastInsertId();
    }

    public function latestQuote(int $userId, int $contractId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM market_quotes WHERE user_id = :user_id AND option_contract_id = :contract_id ORDER BY quoted_at DESC, id DESC LIMIT 1');
        $stmt->execute(['user_id' => $userId, 'contract_id' => $contractId]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function history(int $userId, int $operationId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM operation_history WHERE user_id = :user_id AND operation_id = :operation_id ORDER BY id');
        $stmt->execute(['user_id' => $userId, 'operation_id' => $operationId]);
        return $stmt->fetchAll();
    }
}
