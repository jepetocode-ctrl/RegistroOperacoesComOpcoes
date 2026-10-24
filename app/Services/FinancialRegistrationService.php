<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\FinancialRegistrationRepository;
use App\Validators\FinancialRegistrationValidator;
use PDO;
use PDOException;

final class FinancialRegistrationService
{
    public function __construct(private readonly FinancialRegistrationRepository $repository, private readonly PDO $pdo) {}

    public function list(string $resource, int $userId): array
    {
        return $this->repository->all($resource, $userId);
    }

    public function get(string $resource, int $userId, int $id): ?array
    {
        return $this->repository->find($resource, $userId, $id);
    }

    public function save(string $resource, int $userId, array $data, ?int $id = null): array
    {
        $validation = FinancialRegistrationValidator::validate($resource, $data, $id !== null);
        if (!$validation['valid']) return ['success' => false, 'status' => 422, 'errors' => $validation['errors']];
        if ($id !== null && $this->repository->findOwned($resource, $userId, $id) === null) return ['success' => false, 'status' => 404, 'message' => 'Cadastro não encontrado.'];

        if ($resource === 'option_contracts' && isset($data['asset_id'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM assets WHERE id = :id');
            $stmt->execute(['id' => (int) $data['asset_id']]);
            if (!$stmt->fetchColumn()) return ['success' => false, 'status' => 422, 'errors' => ['asset_id' => 'O ativo não existe.']];
        }

        try {
            $this->pdo->beginTransaction();

            $record = $id === null
                ? $this->repository->create($resource, $userId, $data)
                : $this->repository->update($resource, $userId, $id, $data);

            if ($resource === 'strategies') {
                $strategyId = (int) ($record['id'] ?? $id ?? 0);
                $legs = $data['legs'] ?? [];
                if ($this->tableExists('strategy_legs') && $strategyId > 0) {
                    $strategyLegService = new StrategyLegService($this->pdo);
                    $syncResult = $strategyLegService->sync($userId, $strategyId, is_array($legs) ? $legs : []);
                    if (!$syncResult['success']) {
                        $this->pdo->rollBack();
                        return ['success' => false, 'status' => $syncResult['status'] ?? 422, 'message' => $syncResult['message'] ?? 'Legs inválidas.'];
                    }
                }
            }

            $this->pdo->commit();
            return ['success' => true, 'data' => $record, 'status' => $id === null ? 201 : 200];
        } catch (PDOException $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)) {
                return ['success' => false, 'status' => 422, 'message' => 'Registro duplicado ou relação inválida.'];
            }
            throw $exception;
        }
    }

    private function tableExists(string $table): bool
    {
        $driver = strtolower((string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME));

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = '{$table}' LIMIT 1");
            return $stmt !== false && $stmt->fetchColumn() !== false;
        }

        if ($driver === 'pgsql') {
            $stmt = $this->pdo->prepare('SELECT 1 FROM information_schema.tables WHERE table_name = :table_name LIMIT 1');
            $stmt->execute(['table_name' => $table]);
            return $stmt->fetchColumn() !== false;
        }

        return false;
    }
}
