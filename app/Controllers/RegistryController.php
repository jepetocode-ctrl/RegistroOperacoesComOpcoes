<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\FinancialRegistrationRepository;
use App\Repositories\OperationRepository;
use App\Services\FinancialRegistrationService;
use App\Services\OperationService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use PDO;

final class RegistryController extends BaseController
{
    private PDO $pdo;
    private FinancialRegistrationService $financialService;
    private OperationService $operationService;
    private OperationRepository $operationRepository;

    public function __construct()
    {
        $this->pdo = Database::defaultConnection();
        $this->financialService = new FinancialRegistrationService(new FinancialRegistrationRepository($this->pdo), $this->pdo);
        $this->operationService = new OperationService($this->pdo, new OperationRepository($this->pdo));
        $this->operationRepository = new OperationRepository($this->pdo);
    }

    public function index(): void
    {
        AuthMiddleware::requireAuthentication();

        $user = SessionManager::user();
        $userId = (int) ($user['id'] ?? 0);

        $assets = $this->financialService->list('assets', $userId);
        $contracts = $this->financialService->list('option_contracts', $userId);
        $strategies = $this->financialService->list('strategies', $userId);
        $operations = $this->operationRepository->list($userId);

        $html = $this->view('registry/index', [
            'user' => $user,
            'assets' => $assets,
            'contracts' => $contracts,
            'strategies' => $strategies,
            'operations' => $operations,
            'csrf' => SessionManager::csrfToken(),
            'error' => $_SESSION['registry_error'] ?? null,
            'success' => $_SESSION['registry_success'] ?? null,
        ]);

        unset($_SESSION['registry_error'], $_SESSION['registry_success']);
        Response::html($html, 200);
    }

    public function storeAsset(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $payload = Request::all();
        $result = $this->financialService->save('assets', $this->currentUserId(), $payload);
        $this->redirectWithStatus($result, 'Ativo cadastrado com sucesso.', '/registros');
    }

    public function storeContract(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $payload = Request::all();
        $result = $this->financialService->save('option_contracts', $this->currentUserId(), $payload);
        $this->redirectWithStatus($result, 'Contrato cadastrado com sucesso.', '/registros');
    }

    public function storeStrategy(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $payload = Request::all();
        $strategyId = isset($payload['strategy_id']) && $payload['strategy_id'] !== '' ? (int) $payload['strategy_id'] : null;

        if (!isset($payload['legs']) || $payload['legs'] === '') {
            $payload['legs'] = [];
        } elseif (is_string($payload['legs'])) {
            $payload['legs'] = json_decode($payload['legs'], true) ?: [];
        }

        $result = $strategyId !== null
            ? $this->financialService->save('strategies', $this->currentUserId(), $payload, $strategyId)
            : $this->financialService->save('strategies', $this->currentUserId(), $payload);

        $message = $strategyId !== null ? 'Estratégia atualizada com sucesso.' : 'Estratégia cadastrada com sucesso.';
        $this->redirectWithStatus($result, $message, '/registros');
    }

    public function storeOperation(): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $result = $this->operationService->register($this->currentUserId(), Request::all());
        $this->redirectWithStatus($result, 'Posição registrada com sucesso.', '/registros');
    }

    private function requireAuth(): void
    {
        AuthMiddleware::requireAuthentication();
    }

    private function requireCsrf(): void
    {
        if (!CsrfMiddleware::validate()) {
            $_SESSION['registry_error'] = 'Token CSRF inválido.';
            header('Location: /registros');
            exit;
        }
    }

    private function currentUserId(): int
    {
        return (int) (SessionManager::user()['id'] ?? 0);
    }

    private function redirectWithStatus(array $result, string $successMessage, string $location): void
    {
        if (($result['success'] ?? false) === true) {
            $_SESSION['registry_success'] = $successMessage;
            header('Location: ' . $location);
            exit;
        }

        $message = $result['message'] ?? 'Dados inválidos.';
        $errors = $result['errors'] ?? [];
        if (is_array($errors) && $errors !== []) {
            $message = implode(' ', array_map(static fn ($value) => is_array($value) ? implode(', ', $value) : (string) $value, $errors));
        }

        $_SESSION['registry_error'] = $message;
        header('Location: ' . $location);
        exit;
    }
}
