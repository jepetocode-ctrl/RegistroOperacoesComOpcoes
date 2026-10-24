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
            if (!$contractRow) { $this->pdo->rollBack(); return ['success' => false, 'status' => 422, 'errors' => ['option_contract_id' => 'Contrato inexistente ou inativo.']]; }
            $multiplier = (int)$contractRow['contract_multiplier'];
            $gross = round($price * $quantity * $multiplier, 6) * ($side === 'BUY' ? -1 : 1);

            $openings = $this->openPositions($userId, $contractId);
            $openQty = array_sum(array_column($openings, 'open_quantity'));
            $signedOpen = array_sum(array_map(static fn(array $p): int => $p['side'] === 'BUY' ? $p['open_quantity'] : -$p['open_quantity'], $openings));
            $closingSide = $side === 'BUY' ? 'SELL' : 'BUY';
            $closeQty = 0;
            $allocation = [];
            if ($openQty > 0 && $signedOpen !== 0 && (($signedOpen > 0 && $side === 'SELL') || ($signedOpen < 0 && $side === 'BUY'))) {
                if ($referenceId !== null) {
                    $selectedOpening = null;
                    foreach ($openings as $opening) {
                        if ($opening['id'] === $referenceId && $opening['side'] !== $side) {
                            $selectedOpening = $opening;
                            break;
                        }
                    }
                    if ($selectedOpening === null) {
                        $this->pdo->rollBack();
                        return ['success' => false, 'status' => 422, 'message' => 'O lote selecionado não pertence a esta posição ou não possui saldo aberto compatível.'];
                    }
                    $closeQty = min($quantity, abs($signedOpen));
                    $remaining = $closeQty;
                    $take = min($remaining, $selectedOpening['open_quantity']);
                    $allocation[] = ['opening' => $selectedOpening, 'quantity' => $take];
                    $remaining -= $take;
                    foreach ($openings as $opening) {
                        if ($remaining <= 0) break;
                        if ($opening['id'] === $referenceId || $opening['side'] === $side || $opening['open_quantity'] <= 0) continue;
                        $take = min($remaining, $opening['open_quantity']);
                        if ($take > 0) { $allocation[] = ['opening' => $opening, 'quantity' => $take]; $remaining -= $take; }
                    }
                } else {
                    $closeQty = min($quantity, abs($signedOpen));
                    $remaining = $closeQty;
                    foreach ($openings as $opening) {
                        if ($remaining <= 0) break;
                        if ($opening['side'] === $side || $opening['open_quantity'] <= 0) continue;
                        $take = min($remaining, $opening['open_quantity']);
                        if ($take > 0) { $allocation[] = ['opening' => $opening, 'quantity' => $take]; $remaining -= $take; }
                    }
                }
            }
            if (in_array($kind, ['CLOSE','PARTIAL_CLOSE'], true) && $closeQty !== $quantity) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Quantidade de encerramento excede a posição disponível.'];
            }
            if ($kind === 'CLOSE' && $closeQty !== abs($signedOpen)) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'O fechamento integral deve encerrar toda a posição líquida do contrato.'];
            }
            if ($kind === 'REVERSAL' && (
                $signedOpen === 0
                || ($signedOpen > 0 && $side !== 'SELL')
                || ($signedOpen < 0 && $side !== 'BUY')
                || $quantity <= abs($signedOpen)
            )) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Inversão exige operação contrária maior que a posição líquida atual, para encerrar e abrir a posição inversa.'];
            }
            foreach ($allocation as $item) {
                if (($item['opening']['structured_position_id'] ?? null) !== null) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'status' => 422, 'message' => 'Posições estruturadas devem ser encerradas ou roladas pelas ações da própria posição.'];
                }
            }
            $strategyId = isset($input['strategy_id']) ? (int)$input['strategy_id'] : null;
            if ($strategyId !== null) {
                $s = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :id AND user_id = :user_id');
                $s->execute(['id' => $strategyId, 'user_id' => $userId]);
                if (!$s->fetchColumn()) { $this->pdo->rollBack(); return ['success' => false, 'status' => 422, 'errors' => ['strategy_id' => 'Estratégia inexistente.']]; }
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
                $realized -= round((float)$opening['costs'] * ($item['quantity'] / max(1, (int)$opening['quantity'])), 6);
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

    public function update(int $userId, int $id, array $input): array
    {
        try {
            $this->pdo->beginTransaction();
            $operation = $this->repository->find($userId, $id);
            if ($operation === null) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 404, 'message' => 'Operação não encontrada.'];
            }
            if (isset($operation['structured_position_id']) && $operation['structured_position_id'] !== null) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Operações vinculadas a uma posição estruturada não podem ser editadas; registre encerramento ou rolagem.'];
            }

            $closures = $this->pdo->prepare('SELECT 1 FROM operation_closures WHERE user_id = :user_id AND (opening_operation_id = :opening_id OR closing_operation_id = :closing_id) LIMIT 1');
            $closures->execute(['user_id' => $userId, 'opening_id' => $id, 'closing_id' => $id]);
            if ($closures->fetchColumn()) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'message' => 'Esta operação participa de um fechamento e não pode ser editada sem recalcular o histórico.'];
            }

            $data = [
                'option_contract_id' => (int) $operation['option_contract_id'],
                'side' => strtoupper((string) ($input['side'] ?? $operation['side'])),
                'quantity' => $input['quantity'] ?? $operation['quantity'],
                'unit_price' => $input['unit_price'] ?? $operation['unit_price'],
                'costs' => $input['costs'] ?? $operation['costs'],
                'executed_at' => $input['executed_at'] ?? $operation['executed_at'],
                'operation_kind' => strtoupper((string) ($input['operation_kind'] ?? $operation['operation_kind'])),
            ];
            $errors = $this->validate($data);
            if ($errors !== []) {
                $this->pdo->rollBack();
                return ['success' => false, 'status' => 422, 'errors' => $errors];
            }

            $strategyId = ($input['strategy_id'] ?? $operation['strategy_id']) === '' ? null : ($input['strategy_id'] ?? $operation['strategy_id']);
            if ($strategyId !== null) {
                $strategyId = filter_var($strategyId, FILTER_VALIDATE_INT);
                if ($strategyId === false || $strategyId < 1) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'status' => 422, 'errors' => ['strategy_id' => 'Estratégia inválida.']];
                }
                $strategy = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :id AND user_id = :user_id');
                $strategy->execute(['id' => $strategyId, 'user_id' => $userId]);
                if (!$strategy->fetchColumn()) {
                    $this->pdo->rollBack();
                    return ['success' => false, 'status' => 422, 'errors' => ['strategy_id' => 'Estratégia inexistente.']];
                }
            }

            $contract = $this->pdo->prepare('SELECT contract_multiplier FROM option_contracts WHERE id = :id');
            $contract->execute(['id' => (int) $operation['option_contract_id']]);
            $multiplier = (int) $contract->fetchColumn();
            $side = $data['side'];
            $quantity = (int) $data['quantity'];
            $price = (float) $data['unit_price'];
            $costs = (float) $data['costs'];
            $after = [
                'strategy_id' => $strategyId,
                'side' => $side,
                'quantity' => $quantity,
                'unit_price' => $price,
                'gross_cash_flow' => round($price * $quantity * $multiplier, 6) * ($side === 'BUY' ? -1 : 1),
                'costs' => $costs,
                'executed_at' => (string) $data['executed_at'],
                'operation_kind' => $data['operation_kind'],
                'notes' => array_key_exists('notes', $input) ? trim((string) $input['notes']) : $operation['notes'],
            ];
            $this->repository->update($userId, $id, $after);
            $this->repository->insertHistory($userId, $id, 'UPDATED', $operation, $after);
            $this->pdo->commit();

            return ['success' => true, 'status' => 200, 'data' => $this->repository->find($userId, $id)];
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
            $contractStmt = $this->pdo->prepare('SELECT contract_multiplier FROM option_contracts WHERE id = :id');
            $contractStmt->execute(['id' => (int)$id]);
            $multiplier = (int)($contractStmt->fetchColumn() ?: 1);
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
        if (!$stmt->fetchColumn()) return ['success' => false, 'status' => 422, 'errors' => ['option_contract_id' => 'Contrato inexistente.']];
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

    public function transactionHistory(int $userId, int $contractId): ?array
    {
        $contract = $this->pdo->prepare('SELECT id FROM option_contracts WHERE id = :id AND user_id = :user_id');
        $contract->execute(['id' => $contractId, 'user_id' => $userId]);
        if (!$contract->fetchColumn()) return null;

        return $this->repository->transactionHistory($userId, $contractId);
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
