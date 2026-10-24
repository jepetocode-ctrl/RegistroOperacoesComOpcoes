<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\StrategyTemplateRepository;
use App\Repositories\StructuredPositionRepository;
use App\Services\StrategyTemplateService;
use App\Services\StructuredPositionService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use Throwable;

final class StructuredPositionController
{
    private StrategyTemplateService $templates;
    private StructuredPositionService $positions;

    public function __construct()
    {
        $pdo = Database::defaultConnection();
        $templateRepository = new StrategyTemplateRepository($pdo);
        $this->templates = new StrategyTemplateService($pdo, $templateRepository);
        $this->positions = new StructuredPositionService($pdo, $templateRepository, new StructuredPositionRepository($pdo));
    }

    public function handle(): void
    {
        if (!AuthMiddleware::isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Não autenticado.'], 401);
            return;
        }
        $method = Request::method();
        if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true) && !CsrfMiddleware::validate()) {
            Response::json(['success' => false, 'message' => 'Token CSRF inválido.'], 419);
            return;
        }

        $userId = (int) (SessionManager::user()['id'] ?? 0);
        $parts = explode('/', trim(Request::uri(), '/'));
        $resource = $parts[1] ?? '';
        $ref = $parts[2] ?? null;
        try {
            if ($resource === 'strategy-templates') {
                if ($method === 'GET' && $ref === null) {
                    Response::json(['success' => true, 'data' => $this->templates->list($userId)]);
                    return;
                }
                if ($method === 'GET' && ctype_digit((string) $ref)) {
                    $template = $this->templates->find($userId, (int) $ref);
                    Response::json($template === null
                        ? ['success' => false, 'message' => 'Modelo não encontrado.']
                        : ['success' => true, 'data' => $template], $template === null ? 404 : 200);
                    return;
                }
                if ($method === 'POST' && $ref === null) {
                    $this->respond($this->templates->create($userId, Request::all()));
                    return;
                }
                if ($method === 'PUT' && ctype_digit((string) $ref)) {
                    $this->respond($this->templates->update($userId, (int) $ref, Request::all()));
                    return;
                }
                if ($method === 'POST' && ctype_digit((string) $ref) && ($parts[3] ?? '') === 'clone') {
                    $this->respond($this->templates->clone($userId, (int) $ref));
                    return;
                }
            }
            if ($resource === 'structured-positions') {
                if ($method === 'GET' && $ref === null) {
                    Response::json(['success' => true, 'data' => $this->positions->list($userId)]);
                    return;
                }
                if ($method === 'GET' && $ref !== null && ($parts[3] ?? null) === null) {
                    $position = $this->positions->find($userId, $ref);
                    Response::json($position === null
                        ? ['success' => false, 'message' => 'Posição estruturada não encontrada.']
                        : ['success' => true, 'data' => $position], $position === null ? 404 : 200);
                    return;
                }
                if ($method === 'POST' && $ref === null) {
                    $this->respond($this->positions->register($userId, Request::all()));
                    return;
                }
                if ($method === 'PUT' && $ref !== null && ($parts[3] ?? null) === null) {
                    $this->respond($this->positions->update($userId, $ref, Request::all()));
                    return;
                }
                if ($method === 'DELETE' && $ref !== null && ($parts[3] ?? null) === null) {
                    $this->respond($this->positions->archive($userId, $ref));
                    return;
                }
                if ($method === 'POST' && $ref !== null && ($parts[3] ?? '') === 'close') {
                    $this->respond($this->positions->close($userId, $ref, Request::all()));
                    return;
                }
                if ($method === 'POST' && $ref !== null && ($parts[3] ?? '') === 'roll') {
                    $this->respond($this->positions->roll($userId, $ref, Request::all()));
                    return;
                }
            }
            if ($resource === 'unstructured-operations' && $method === 'GET') {
                Response::json(['success' => true, 'data' => $this->positions->unstructuredOperations($userId)]);
                return;
            }
            Response::json(['success' => false, 'message' => 'Rota não encontrada.'], 404);
        } catch (Throwable $exception) {
            error_log('Structured position API failed: ' . get_class($exception));
            Response::json(['success' => false, 'message' => 'Erro interno ao processar modelo ou posição estruturada.'], 500);
        }
    }

    private function respond(array $result): void
    {
        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);
        Response::json($result, $status);
    }
}
