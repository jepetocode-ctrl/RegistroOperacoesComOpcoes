<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Repositories\FinancialRegistrationRepository;
use App\Services\FinancialRegistrationService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use Throwable;

final class FinancialRegistrationController
{
    private FinancialRegistrationService $service;

    public function __construct()
    {
        $pdo = Database::defaultConnection();
        $this->service = new FinancialRegistrationService(new FinancialRegistrationRepository($pdo), $pdo);
    }

    public function handle(): void
    {
        if (!AuthMiddleware::isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Não autenticado.'], 401);
            return;
        }
        $user = SessionManager::user();
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $parts = explode('/', $path);
        $resource = $parts[1] ?? '';
        if (!in_array($resource, ['assets', 'option-contracts', 'strategies'], true)) {
            Response::json(['success' => false, 'message' => 'Cadastro inválido.'], 404);
            return;
        }
        $resource = ['option-contracts' => 'option_contracts'][$resource] ?? $resource;
        $id = isset($parts[2]) && ctype_digit($parts[2]) ? (int) $parts[2] : null;
        $method = Request::method();
        try {
            if ($method === 'GET') {
                $record = $id === null ? $this->service->list($resource, $user['id']) : $this->service->get($resource, $user['id'], $id);
                if ($id !== null && $record === null) {
                    Response::json(['success' => false, 'message' => 'Cadastro não encontrado.'], 404);
                    return;
                }
                Response::json(['success' => true, 'data' => $record]);
                return;
            }
            if (!in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
                header('Allow: GET, POST, PUT, PATCH');
                Response::json(['success' => false, 'message' => 'Método não permitido.'], 405);
                return;
            }
            if ($method !== 'POST' && $id === null) {
                Response::json(['success' => false, 'message' => 'Informe o ID do cadastro.'], 422);
                return;
            }
            $result = $this->service->save($resource, $user['id'], Request::all(), $id);
            $status = $result['status'] ?? 200;
            unset($result['status']);
            Response::json($result, $status);
        } catch (Throwable $exception) {
            Response::json(['success' => false, 'message' => 'Erro interno ao processar cadastro.'], 500);
        }
    }
}
