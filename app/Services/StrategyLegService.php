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
        foreach ($legs as $index => $leg) {
            $normalized[] = $this->normalizeLeg($index, $leg);
        }

        $startedTransaction = false;

        try {
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

        $quantity = (int) ($leg['quantity'] ?? 0);
        if ($quantity <= 0) {
            throw new PDOException('A quantidade da perna deve ser maior que zero.');
        }

        $strikePrice = (float) ($leg['strike_price'] ?? 0);
        $premium = (float) ($leg['premium'] ?? 0);
        $contractMultiplier = (int) ($leg['contract_multiplier'] ?? 100);
        $costs = (float) ($leg['costs'] ?? 0);
        $expirationDate = (string) ($leg['expiration_date'] ?? date('Y-m-d'));

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
