<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use PDO;
use PDOException;

final class StrategyLegService
{
    private PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::defaultConnection();
    }

    public function listForStrategy(int $userId, int $strategyId): array
    {
        $this->assertStrategyOwnership($userId, $strategyId);

        $stmt = $this->pdo->prepare(
            'SELECT * FROM strategy_legs WHERE strategy_id = :strategy_id ORDER BY position ASC, id ASC'
        );
        $stmt->execute(['strategy_id' => $strategyId]);

        return $stmt->fetchAll();
    }

    public function sync(int $userId, int $strategyId, array $legs): array
    {
        $this->assertStrategyOwnership($userId, $strategyId);

        $normalized = [];
        $startedTransaction = false;

        try {
            foreach ($legs as $index => $leg) {
                $normalizedLeg = $this->normalizeLeg($index, $leg);
                if ($normalizedLeg['option_contract_id'] !== null) {
                    $contract = $this->pdo->prepare(
                        'SELECT id, option_type, strike_price, expiration_date, contract_multiplier
                         FROM option_contracts WHERE id = :id AND user_id = :user_id LIMIT 1'
                    );
                    $contract->execute(['id' => $normalizedLeg['option_contract_id'], 'user_id' => $userId]);
                    $contractRow = $contract->fetch(PDO::FETCH_ASSOC);
                    if (!$contractRow) {
                        throw new PDOException('O contrato selecionado não existe ou não pertence ao usuário.');
                    }
                    if (strtoupper((string) $contractRow['option_type']) !== $normalizedLeg['option_type']
                        || abs((float) $contractRow['strike_price'] - $normalizedLeg['strike_price']) > 0.000001
                        || (string) $contractRow['expiration_date'] !== $normalizedLeg['expiration_date']
                        || (int) $contractRow['contract_multiplier'] !== $normalizedLeg['contract_multiplier']) {
                        throw new PDOException('Tipo, strike, vencimento e multiplicador da perna devem corresponder ao contrato selecionado.');
                    }
                }
                $normalized[] = $normalizedLeg;
            }
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
                $startedTransaction = true;
            }

            $delete = $this->pdo->prepare('DELETE FROM strategy_legs WHERE strategy_id = :strategy_id');
            $delete->execute(['strategy_id' => $strategyId]);

            $insert = $this->pdo->prepare(
                'INSERT INTO strategy_legs (
                    strategy_id, position, option_contract_id, instrument_type, option_type,
                    strike_price, expiration_date, side, quantity, premium, contract_multiplier, costs,
                    created_at, updated_at
                ) VALUES (
                    :strategy_id, :position, :option_contract_id, :instrument_type, :option_type,
                    :strike_price, :expiration_date, :side, :quantity, :premium, :contract_multiplier, :costs,
                    :created_at, :updated_at
                )'
            );

            foreach ($normalized as $leg) {
                $insert->execute([
                    'strategy_id' => $strategyId,
                    'position' => $leg['position'],
                    'option_contract_id' => $leg['option_contract_id'] ?? null,
                    'instrument_type' => $leg['instrument_type'],
                    'option_type' => $leg['option_type'],
                    'strike_price' => $leg['strike_price'],
                    'expiration_date' => $leg['expiration_date'],
                    'side' => $leg['side'],
                    'quantity' => $leg['quantity'],
                    'premium' => $leg['premium'],
                    'contract_multiplier' => $leg['contract_multiplier'],
                    'costs' => $leg['costs'],
                    'created_at' => $leg['created_at'],
                    'updated_at' => $leg['updated_at'],
                ]);
            }

            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->commit();
            }

            return ['success' => true, 'data' => $this->listForStrategy($userId, $strategyId)];
        } catch (PDOException $exception) {
            if ($startedTransaction && $this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            return ['success' => false, 'status' => 422, 'message' => $exception->getMessage()];
        }
    }

    private function normalizeLeg(int $index, array $leg): array
    {
        $position = isset($leg['position']) ? (int) $leg['position'] : $index + 1;
        if ($position < 1) {
            throw new PDOException('A posição da perna deve ser um número positivo.');
        }

        $optionType = strtoupper((string) ($leg['option_type'] ?? ''));
        if (!in_array($optionType, ['CALL', 'PUT'], true)) {
            throw new PDOException('A perna deve informar option_type válido: CALL ou PUT.');
        }

        $side = strtoupper((string) ($leg['side'] ?? ''));
        if (!in_array($side, ['BUY', 'SELL'], true)) {
            throw new PDOException('A perna deve informar side válido: BUY ou SELL.');
        }

        $quantityRaw = $leg['quantity'] ?? null;
        if (filter_var($quantityRaw, FILTER_VALIDATE_INT) === false || (int) $quantityRaw <= 0) {
            throw new PDOException('A quantidade da perna deve ser um inteiro positivo.');
        }
        foreach (['strike_price', 'premium'] as $numericField) {
            if (!isset($leg[$numericField]) || !is_numeric($leg[$numericField]) || !is_finite((float) $leg[$numericField])) {
                throw new PDOException('Informe valores numéricos válidos para strike e prêmio.');
            }
        }
        $costsRaw = $leg['costs'] ?? 0;
        if (!is_numeric($costsRaw) || !is_finite((float) $costsRaw)) {
            throw new PDOException('Informe um custo numérico válido.');
        }
        $multiplierRaw = $leg['contract_multiplier'] ?? 100;
        if (filter_var($multiplierRaw, FILTER_VALIDATE_INT) === false || (int) $multiplierRaw <= 0) {
            throw new PDOException('O multiplicador deve ser um inteiro positivo.');
        }

        $quantity = (int) $quantityRaw;
        $strikePrice = (float) $leg['strike_price'];
        $premium = (float) $leg['premium'];
        $contractMultiplier = (int) $multiplierRaw;
        $costs = (float) $costsRaw;
        $expirationDate = (string) ($leg['expiration_date'] ?? '');

        if ($strikePrice <= 0) throw new PDOException('O strike deve ser positivo.');
        if ($premium < 0) throw new PDOException('O prêmio não pode ser negativo.');
        if ($contractMultiplier <= 0) throw new PDOException('O multiplicador deve ser positivo.');
        if ($costs < 0) throw new PDOException('Os custos não podem ser negativos.');
        $parsedDate = \\DateTimeImmutable::createFromFormat('!Y-m-d', $expirationDate);
        if (!$parsedDate || $parsedDate->format('Y-m-d') !== $expirationDate) {
            throw new PDOException('O vencimento deve ser uma data válida no formato AAAA-MM-DD.');
        }

        return [
            'position' => $position,
            'option_contract_id' => isset($leg['option_contract_id']) ? (int) $leg['option_contract_id'] : null,
            'instrument_type' => strtoupper((string) ($leg['instrument_type'] ?? 'OPTION')),
            'option_type' => $optionType,
            'strike_price' => $strikePrice,
            'expiration_date' => $expirationDate,
            'side' => $side,
            'quantity' => $quantity,
            'premium' => $premium,
            'contract_multiplier' => $contractMultiplier,
            'costs' => $costs,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];
    }

    private function assertStrategyOwnership(int $userId, int $strategyId): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM strategies WHERE id = :strategy_id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['strategy_id' => $strategyId, 'user_id' => $userId]);

        if (!$stmt->fetch()) {
            throw new PDOException('Estratégia inexistente ou sem acesso para o usuário informado.');
        }
    }
}
