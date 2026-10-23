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
        if ($id !== null && $this->repository->find($resource, $userId, $id) === null) return ['success' => false, 'status' => 404, 'message' => 'Cadastro não encontrado.'];

        if ($resource === 'option_contracts' && isset($data['asset_id'])) {
            $stmt = $this->pdo->prepare('SELECT id FROM assets WHERE id = :id AND user_id = :user_id');
            $stmt->execute(['id' => (int) $data['asset_id'], 'user_id' => $userId]);
            if (!$stmt->fetchColumn()) return ['success' => false, 'status' => 422, 'errors' => ['asset_id' => 'O ativo não existe ou não pertence ao usuário.']];
        }
        try {
            $record = $id === null
                ? $this->repository->create($resource, $userId, $data)
                : $this->repository->update($resource, $userId, $id, $data);
            return ['success' => true, 'data' => $record, 'status' => $id === null ? 201 : 200];
        } catch (PDOException $exception) {
            if (in_array((string) $exception->getCode(), ['23000', '23505', '19'], true)) {
                return ['success' => false, 'status' => 422, 'message' => 'Registro duplicado ou relação inválida.'];
            }
            throw $exception;
        }
    }
}
