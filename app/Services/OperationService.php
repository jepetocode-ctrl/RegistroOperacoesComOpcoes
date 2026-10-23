<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\OperationRepository;
use PDO;
use Throwable;

final class OperationService
{
    public function __construct(private readonly PDO $pdo, private readonly OperationRepository $repository) {}

    public function register(int $userId, array $input): array
    {
        $errors = $this->validate($input);
        if ($errors !== []) return ['success' => false, 'status' => 422, 'errors' => $errors];

        $contractId = (int)$input['option_contract_id'];
        $quantity = (int)$input['quantity'];
        $side = strtoupper((string)$input['side']);
        $price = (float)$input['unit_price'];
        $costs = (float)($input['costs'] ?? 0);
        $kind = strtoupper((string)($input['operation_kind'] ?? 'TRADE'));
        $referenceId = isset($input['reference_operation_id']) ? (int)$input['reference_operation_id'] : null;
        $gross = round($price * $quantity * (int)($input['contract_multiplier'] ?? 1), 6) * ($side === 'BUY' ? -1 : 1);

        try {
            $this->pdo->beginTransaction();
            $contract = $this->pdo->prepare('SELECT id, contract_multiplier FROM option_contracts WHERE id = :id AND user_id = :user_id AND status = :status');
            $contract->execute(['id' => $contractId, 'user_id' => $userId, 'status' => 'ACTIVE']);
            $contractRow = $contract->fetch(PDO::FETCH_ASSOC);
            if (!$contractRow) { $this->pdo->rollBack(); return ['success' => false, 'status' => 422, 'errors' => ['option_contract_id' => 'Contrato inexistente, inativo ou de outro usuário.']]; }
            $multiplier = (int)$contractRow['contract_multiplier'];
            $gross = round($price * $quantity * $multiplier, 6) * ($side === 'BUY' ? -1 : 1);

            $openings = $this->openPositions($userId, $contractId);
            $openQty = array_sum(array_column($openings, 'open_quantity'));
            $signedOpen = array_sum(array_map(static fn(array $p): int => $p['side'] === 'BUY' ? $p['open_quantity'] : -$p['open_quantity'], $openings));
            $closingSide = $side === 'BUY' ? 'SELL' : 'BUY';
            $closeQty = 0;
            $allocation = [];
            if ($openQty > 0 && $signedOpen !== 0 && (($signedOpen > 0 && $side === 'SELL') || ($signedOpen < 0 && $side === 'BUY'))) {
                $closeQty = min($quantity, abs($signedOpen));
                $remaining = $closeQty;
                foreach ($openings as $opening) {
                    if ($remaining <= 0) break;
                    if ($opening['side'] === $side || $opening['open_quantity'] <= 0) continue;
                    $take = min($remaining, $opening['open_quantity']);
                    if ($take > 0) { $allocation[] = ['opening' => $opening, 'quantity' => $take]; $remaining -= $take; }
                }
            }
            if (in_array($kind, ['CLOSE','PARTIAL_CLOSE'], true) && $closeQty !== $quantity) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Quantidade de encerramento excede a posição disponível.'];
            }
            if ($kind === 'REVERSAL' && $closeQty !== abs($signedOpen)) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Inversão exige quantidade igual à posição líquida aberta.'];
            }
            $strategyId = isset($input['strategy_id']) ? (int)$input['strategy_id'] : null;
            if ($strategyId !== null) {
                $s = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :id AND user_id = :user_id');
                $s->execute(['id' => $strategyId, 'user_id' => $userId]);
                if (!$s->fetchColumn()) { $this->pdo->rollBack(); return ['success' => false, 'status' => 422, 'errors' => ['strategy_id' => 'Estratégia inexistente ou de outro usuário.']]; }
            }
            $data = [
                'user_id' => $userId, 'option_contract_id' => $contractId, 'strategy_id' => $strategyId,
                'side' => $side, 'quantity' => $quantity, 'unit_price' => $price,
                'gross_cash_flow' => $gross, 'costs' => $costs,
                'executed_at' => (string)($input['executed_at'] ?? date('Y-m-d H:i:s')),
                'reference_operation_id' => $referenceId, 'operation_kind' => $kind,
                'notes' => isset($input['notes']) ? trim((string)$input['notes']) : null,
            ];
            $id = $this->repository->insert($data);
            foreach ($allocation as $item) {
                $opening = $item['opening'];
                $openSign = $opening['side'] === 'BUY' ? 1 : -1;
                $closeSign = $side === 'BUY' ? 1 : -1;
                $realized = round(($price - (float)$opening['unit_price']) * $item['quantity'] * $multiplier * $openSign, 6);
                $realized -= round($costs * ($item['quantity'] / $quantity), 6);
                $this->repository->insertClosure([
                    'user_id' => $userId, 'opening_operation_id' => $opening['id'],
                    'closing_operation_id' => $id, 'quantity' => $item['quantity'], 'realized_result' => $realized,
                ]);
            }
            $this->repository->insertHistory($userId, $id, 'CREATED', null, $data);
            $this->pdo->commit();
            return ['success' => true, 'status' => 201, 'data' => $this->repository->find($userId, $id)];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function positions(int $userId, ?int $contractId = null): array
    {
        $operations = $this->repository->list($userId, $contractId);
        $contracts = [];
        foreach ($operations as $op) $contracts[(int)$op['option_contract_id']][] = $op;
        $positions = [];
        foreach ($contracts as $id => $rows) {
            $opens = $this->openPositions($userId, (int)$id);
            $net = 0;
            $realized = 0.0;
            $flow = 0.0;
            $costs = 0.0;
            foreach ($rows as $row) {
                $flow += (float)$row['gross_cash_flow'];
                $costs += (float)$row['costs'];
            }
            foreach ($opens as $open) $net += ($open['side'] === 'BUY' ? 1 : -1) * $open['open_quantity'];
            $quote = $this->repository->latestQuote($userId, (int)$id);
            $unrealized = null;
            if ($quote !== null) {
                $unrealized = 0.0;
                foreach ($opens as $open) {
                    $direction = $open['side'] === 'BUY' ? 1 : -1;
                    $unrealized += (((float)$quote['quote_price'] - (float)$open['unit_price']) * $direction * (int)$open['open_quantity'] * $multiplier);
                    $unrealized -= (float)$open['costs'] * ((int)$open['open_quantity'] / max(1, (int)$open['quantity']));
                }
                $unrealized = round($unrealized, 6);
            }
            $stmt = $this->pdo->prepare('SELECT COALESCE(SUM(realized_result),0) FROM operation_closures WHERE user_id = :user_id AND closing_operation_id IN (SELECT id FROM operations WHERE user_id = :user_id2 AND option_contract_id = :contract_id)');
            $stmt->execute(['user_id' => $userId, 'user_id2' => $userId, 'contract_id' => $id]);
            $realized = (float)$stmt->fetchColumn();
            $positions[] = ['option_contract_id' => (int)$id, 'open_quantity' => $net, 'gross_cash_flow' => round($flow, 6), 'costs' => round($costs, 6), 'realized_result' => round($realized, 6), 'unrealized_result' => $unrealized, 'latest_quote' => $quote, 'open_lots' => $opens];
        }
        return $positions;
    }

    public function recordQuote(int $userId, array $input): array
    {
        $contractId = filter_var($input['option_contract_id'] ?? null, FILTER_VALIDATE_INT);
        $price = $input['quote_price'] ?? null;
        if ($contractId === false || $contractId === null || $contractId < 1 || !is_numeric($price) || (float)$price < 0) {
            return ['success' => false, 'status' => 422, 'errors' => ['quote' => 'Contrato e cotação não negativos são obrigatórios.']];
        }
        $quotedAt = (string)($input['quoted_at'] ?? date('Y-m-d H:i:s'));
        if (strtotime($quotedAt) === false) return ['success' => false, 'status' => 422, 'errors' => ['quoted_at' => 'Data/hora inválida.']];
        $stmt = $this->pdo->prepare('SELECT id FROM option_contracts WHERE id = :id AND user_id = :user_id');
        $stmt->execute(['id' => $contractId, 'user_id' => $userId]);
        if (!$stmt->fetchColumn()) return ['success' => false, 'status' => 422, 'errors' => ['option_contract_id' => 'Contrato inexistente ou de outro usuário.']];
        $this->pdo->beginTransaction();
        try {
            $id = $this->repository->insertQuote($userId, (int)$contractId, (float)$price, $quotedAt);
            $this->pdo->commit();
            return ['success' => true, 'status' => 201, 'data' => ['id' => $id, 'option_contract_id' => (int)$contractId, 'quote_price' => (float)$price, 'quoted_at' => $quotedAt]];
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
    }

    public function history(int $userId, int $operationId): array
    {
        if ($this->repository->find($userId, $operationId) === null) return [];
        return $this->repository->history($userId, $operationId);
    }

    private function openPositions(int $userId, int $contractId): array
    {
        $rows = $this->repository->list($userId, $contractId);
        $openings = [];
        foreach ($rows as $row) {
            $row['id'] = (int)$row['id'];
            $row['quantity'] = (int)$row['quantity'];
            $row['open_quantity'] = $row['quantity'];
            $openings[$row['id']] = $row;
        }
        $ids = array_keys($openings);
        $stmt = $this->pdo->prepare('SELECT opening_operation_id, quantity FROM operation_closures WHERE user_id = :user_id AND opening_operation_id IN (' . ($ids ? implode(',', array_map('intval', $ids)) : 'NULL') . ')');
        $stmt->execute(['user_id' => $userId]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $closure) {
            $key = (int)$closure['opening_operation_id'];
            if (isset($openings[$key])) $openings[$key]['open_quantity'] -= (int)$closure['quantity'];
        }
        // A closing execution is not itself a new opening lot for the portion allocated to closes.
        $closing = $this->pdo->prepare('SELECT closing_operation_id, SUM(quantity) AS closed_quantity FROM operation_closures WHERE user_id = :user_id AND closing_operation_id IN (' . ($ids ? implode(',', array_map('intval', $ids)) : 'NULL') . ') GROUP BY closing_operation_id');
        $closing->execute(['user_id' => $userId]);
        foreach ($closing->fetchAll(PDO::FETCH_ASSOC) as $closure) {
            $key = (int)$closure['closing_operation_id'];
            if (isset($openings[$key])) $openings[$key]['open_quantity'] -= (int)$closure['closed_quantity'];
        }
        return array_values(array_filter($openings, static fn(array $row): bool => $row['open_quantity'] > 0));
    }

    private function validate(array $input): array
    {
        $errors = [];
        foreach (['option_contract_id', 'side', 'quantity', 'unit_price'] as $key) if (!isset($input[$key]) || $input[$key] === '') $errors[$key] = 'Campo obrigatório.';
        if (isset($input['side']) && !in_array(strtoupper((string)$input['side']), ['BUY','SELL'], true)) $errors['side'] = 'Use BUY ou SELL.';
        if (isset($input['quantity']) && filter_var($input['quantity'], FILTER_VALIDATE_INT) === false || isset($input['quantity']) && (int)$input['quantity'] < 1) $errors['quantity'] = 'Quantidade deve ser inteiro positivo.';
        foreach (['unit_price','costs'] as $key) if (isset($input[$key]) && (!is_numeric($input[$key]) || (float)$input[$key] < 0)) $errors[$key] = 'Valor deve ser numérico e não negativo.';
        if (isset($input['operation_kind']) && !in_array(strtoupper((string)$input['operation_kind']), ['TRADE','OPEN','CLOSE','PARTIAL_CLOSE','REVERSAL','ADJUSTMENT'], true)) $errors['operation_kind'] = 'Tipo de operação inválido.';
        if (isset($input['executed_at']) && strtotime((string)$input['executed_at']) === false) $errors['executed_at'] = 'Data/hora inválida.';
        return $errors;
    }
}
