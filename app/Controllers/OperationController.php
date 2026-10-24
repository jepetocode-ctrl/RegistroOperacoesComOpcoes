<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\OperationRepository;
use App\Services\OperationService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use Throwable;

final class OperationController
{
    private OperationService $service;

    public function __construct()
    {
        $pdo = Database::defaultConnection();
        $this->service = new OperationService($pdo, new OperationRepository($pdo));
    }

    public function handle(): void
    {
        if (!AuthMiddleware::isAuthenticated()) { Response::json(['success'=>false,'message'=>'Não autenticado.'],401); return; }
        $method = Request::method();
        if (in_array($method, ['POST','PUT','PATCH','DELETE'], true) && !CsrfMiddleware::validate()) { Response::json(['success'=>false,'message'=>'Token CSRF inválido.'],419); return; }
        $user = SessionManager::user();
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $parts = explode('/', $path);
        try {
            if (($parts[1] ?? '') === 'quotes' && $method === 'POST') {
                $result = $this->service->recordQuote((int)$user['id'], Request::all());
                $status = $result['status'] ?? 200; unset($result['status']);
                Response::json($result, $status);
                return;
            }
            if (($parts[1] ?? '') === 'positions' && $method === 'GET') {
                $contractId = isset($parts[2]) && ctype_digit($parts[2]) ? (int)$parts[2] : null;
                Response::json(['success'=>true,'data'=>$this->service->positions((int)$user['id'],$contractId)]);
                return;
            }
            if (($parts[1] ?? '') === 'contracts' && isset($parts[2], $parts[3]) && ctype_digit($parts[2]) && $parts[3] === 'transactions' && $method === 'GET') {
                $history = $this->service->transactionHistory((int) $user['id'], (int) $parts[2]);
                if ($history === null) {
                    Response::json(['success' => false, 'message' => 'Contrato não encontrado.'], 404);
                    return;
                }
                Response::json(['success' => true, 'data' => $history]);
                return;
            }
            if (($parts[1] ?? '') === 'operations' && isset($parts[2]) && ctype_digit($parts[2]) && ($parts[3] ?? '') === 'history' && $method === 'GET') {
                $history = $this->service->history((int)$user['id'],(int)$parts[2]);
                Response::json(['success'=>true,'data'=>$history]);
                return;
            }
            if (($parts[1] ?? '') === 'operations' && isset($parts[2]) && ctype_digit($parts[2]) && $method === 'PUT') {
                $result = $this->service->update((int)$user['id'], (int)$parts[2], Request::all());
                $status = $result['status'] ?? 200; unset($result['status']);
                Response::json($result, $status);
                return;
            }
            if (($parts[1] ?? '') === 'operations' && isset($parts[2]) && ctype_digit($parts[2]) && $method === 'DELETE') {
                $result = $this->service->archive((int) $user['id'], (int) $parts[2]);
                $status = $result['status'] ?? 200; unset($result['status']);
                Response::json($result, $status);
                return;
            }
            if ($method === 'GET') {
                Response::json(['success'=>true,'data'=>$this->service->positions((int)$user['id'])]);
                return;
            }
            if ($method !== 'POST') { header('Allow: GET, POST, PUT, DELETE'); Response::json(['success'=>false,'message'=>'Método não permitido.'],405); return; }
            $result = $this->service->register((int)$user['id'],Request::all());
            $status = $result['status'] ?? 200; unset($result['status']);
            Response::json($result,$status);
        } catch (Throwable $e) {
            Response::json(['success'=>false,'message'=>'Erro interno ao processar operação.'],500);
        }
    }
}
