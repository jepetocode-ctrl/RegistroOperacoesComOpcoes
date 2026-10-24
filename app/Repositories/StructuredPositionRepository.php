<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class StructuredPositionRepository
{
    public function __construct(private readonly PDO $pdo) {}

    public function insertPosition(array $data): int
    {
        $statement = $this->pdo->prepare('INSERT INTO structured_positions (uuid,user_id,strategy_template_id,asset_id,name,base_quantity,executed_at,currency,broker,general_costs,declared_strategy,inferred_strategy,classification_status,notes) VALUES (:uuid,:user_id,:template_id,:asset_id,:name,:base_quantity,:executed_at,:currency,:broker,:general_costs,:declared_strategy,:inferred_strategy,:classification_status,:notes)');
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function updatePosition(int $userId, int $positionId, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE structured_positions SET strategy_template_id = :template_id, name = :name, base_quantity = :base_quantity, executed_at = :executed_at, general_costs = :general_costs, declared_strategy = :declared_strategy, inferred_strategy = :inferred_strategy, classification_status = :classification_status, notes = :notes WHERE id = :id AND user_id = :user_id');
        $statement->execute($data + ['id' => $positionId, 'user_id' => $userId]);
    }

    public function archive(int $userId, int $positionId): bool
    {
        $statement = $this->pdo->prepare('UPDATE structured_positions SET archived_at = CURRENT_TIMESTAMP WHERE id = :id AND user_id = :user_id AND archived_at IS NULL');
        $statement->execute(['id' => $positionId, 'user_id' => $userId]);
        return $statement->rowCount() > 0;
    }

    public function insertOperation(array $data): int
    {
        $statement = $this->pdo->prepare("INSERT INTO operations (user_id,option_contract_id,strategy_id,structured_position_id,side,quantity,unit_price,gross_cash_flow,costs,executed_at,operation_kind,notes) VALUES (:user_id,:contract_id,:strategy_id,:structured_id,:side,:quantity,:unit_price,:gross,:costs,:executed_at,:operation_kind,:notes)");
        $statement->execute($data);
        return (int) $this->pdo->lastInsertId();
    }

    public function updateOperation(int $userId, int $positionId, int $operationId, array $data): void
    {
        $statement = $this->pdo->prepare('UPDATE operations SET option_contract_id = :contract_id, strategy_id = :strategy_id, side = :side, quantity = :quantity, unit_price = :unit_price, gross_cash_flow = :gross, costs = :costs, executed_at = :executed_at, notes = :notes WHERE id = :id AND user_id = :user_id AND structured_position_id = :position_id');
        $statement->execute($data + ['id' => $operationId, 'user_id' => $userId, 'position_id' => $positionId]);
    }

    public function insertLeg(int $positionId, string $slotKey, int $operationId, int $contractId, string $entryKind = 'INITIAL'): void
    {
        $statement = $this->pdo->prepare('INSERT INTO structured_position_legs (structured_position_id,slot_key,entry_kind,operation_id,option_contract_id) VALUES (:position_id,:slot_key,:entry_kind,:operation_id,:contract_id)');
        $statement->execute([
            'position_id' => $positionId,
            'slot_key' => $slotKey,
            'entry_kind' => $entryKind,
            'operation_id' => $operationId,
            'contract_id' => $contractId,
        ]);
    }

    public function updateLeg(int $positionId, int $operationId, string $slotKey, int $contractId): void
    {
        $statement = $this->pdo->prepare('UPDATE structured_position_legs SET slot_key = :slot_key, option_contract_id = :contract_id WHERE structured_position_id = :position_id AND operation_id = :operation_id AND entry_kind = :entry_kind');
        $statement->execute([
            'position_id' => $positionId,
            'operation_id' => $operationId,
            'slot_key' => $slotKey,
            'contract_id' => $contractId,
            'entry_kind' => 'INITIAL',
        ]);
    }

    public function list(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT sp.*, a.symbol AS asset_symbol, st.name AS template_name, st.code AS template_code, st.support_level FROM structured_positions sp JOIN assets a ON a.id = sp.asset_id AND a.user_id = sp.user_id JOIN strategy_templates st ON st.id = sp.strategy_template_id WHERE sp.user_id = :user_id AND sp.archived_at IS NULL ORDER BY sp.executed_at DESC, sp.id DESC');
        $statement->execute(['user_id' => $userId]);
        return array_map(fn(array $position): array => $this->hydrate($userId, $position), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    public function find(int $userId, string $uuid): ?array
    {
        $statement = $this->pdo->prepare('SELECT sp.*, a.symbol AS asset_symbol, st.name AS template_name, st.code AS template_code, st.support_level FROM structured_positions sp JOIN assets a ON a.id = sp.asset_id AND a.user_id = sp.user_id JOIN strategy_templates st ON st.id = sp.strategy_template_id WHERE sp.user_id = :user_id AND sp.uuid = :uuid AND sp.archived_at IS NULL');
        $statement->execute(['user_id' => $userId, 'uuid' => $uuid]);
        $position = $statement->fetch(PDO::FETCH_ASSOC);
        return $position === false ? null : $this->hydrate($userId, $position);
    }

    public function findById(int $userId, int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT sp.*, a.symbol AS asset_symbol, st.name AS template_name, st.code AS template_code, st.support_level FROM structured_positions sp JOIN assets a ON a.id = sp.asset_id AND a.user_id = sp.user_id JOIN strategy_templates st ON st.id = sp.strategy_template_id WHERE sp.user_id = :user_id AND sp.id = :id AND sp.archived_at IS NULL');
        $statement->execute(['user_id' => $userId, 'id' => $id]);
        $position = $statement->fetch(PDO::FETCH_ASSOC);
        return $position === false ? null : $this->hydrate($userId, $position);
    }

    public function linkedOperations(int $userId, int $positionId): array
    {
        $statement = $this->pdo->prepare('SELECT o.*, l.slot_key, l.entry_kind, c.code AS contract_code, c.option_type, c.strike_price, c.expiration_date, c.contract_multiplier, s.label, s.ratio, COALESCE((SELECT SUM(cl.quantity) FROM operation_closures cl WHERE cl.user_id = o.user_id AND cl.opening_operation_id = o.id),0) AS closed_quantity, COALESCE((SELECT SUM(cl.realized_result) FROM operation_closures cl WHERE cl.user_id = o.user_id AND cl.opening_operation_id = o.id),0) AS realized_result, (SELECT q.quote_price FROM market_quotes q WHERE q.user_id = o.user_id AND q.option_contract_id = o.option_contract_id ORDER BY q.quoted_at DESC, q.id DESC LIMIT 1) AS quote_price FROM structured_position_legs l JOIN operations o ON o.id = l.operation_id AND o.user_id = :user_id JOIN option_contracts c ON c.id = l.option_contract_id AND c.user_id = o.user_id LEFT JOIN strategy_template_slots s ON s.template_id = (SELECT strategy_template_id FROM structured_positions WHERE id = l.structured_position_id AND user_id = :user_id2) AND s.slot_key = l.slot_key WHERE l.structured_position_id = :position_id ORDER BY s.position,l.id');
        $statement->execute(['user_id' => $userId, 'user_id2' => $userId, 'position_id' => $positionId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    public function unstructuredOperations(int $userId): array
    {
        $statement = $this->pdo->prepare('SELECT o.*, c.code AS contract_code, c.option_type, c.strike_price, c.expiration_date, c.contract_multiplier FROM operations o JOIN option_contracts c ON c.id = o.option_contract_id AND c.user_id = o.user_id WHERE o.user_id = :user_id AND o.structured_position_id IS NULL AND o.archived_at IS NULL ORDER BY o.executed_at DESC, o.id DESC');
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }

    private function hydrate(int $userId, array $position): array
    {
        $position['id'] = (int) $position['id'];
        $position['base_quantity'] = (int) $position['base_quantity'];
        $position['asset_id'] = (int) $position['asset_id'];
        $position['legs'] = $this->linkedOperations($userId, $position['id']);
        return $position;
    }
}
