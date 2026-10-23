<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Services\RolloverService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use Throwable;

final class RolloverController
{
    private RolloverService $service;

    public function __construct()
    {
        $this->service = new RolloverService(Database::defaultConnection());
    }

    public function handle(): void
    {
        if (!AuthMiddleware::isAuthenticated()) {
            Response::json(['success'=>false,'message'=>'Não autenticado.'],401);
            return;
        }
        $method = Request::method();
        if (in_array($method,['POST','PUT','PATCH','DELETE'],true) && !CsrfMiddleware::validate()) {
            Response::json(['success'=>false,'message'=>'Token CSRF inválido.'],419);
            return;
        }
        $user = SessionManager::user();
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
        $parts = explode('/', $path);
        try {
            if ($method === 'GET') {
                $uuid = isset($parts[2]) && $parts[2] !== '' ? $parts[2] : null;
                $history = $this->service->history((int)$user['id'],$uuid);
                if ($uuid !== null && $history === []) {
                    Response::json(['success'=>false,'message'=>'Rolagem não encontrada.'],404);
                    return;
                }
                Response::json(['success'=>true,'data'=>$history]);
                return;
            }
            if ($method !== 'POST') {
                header('Allow: GET, POST');
                Response::json(['success'=>false,'message'=>'Método não permitido.'],405);
                return;
            }
            $result = $this->service->register((int)$user['id'],Request::all());
            $status = $result['status'] ?? 200;
            unset($result['status']);
            Response::json($result,$status);
        } catch (Throwable $e) {
            Response::json(['success'=>false,'message'=>'Erro interno ao registrar rolagem.'],500);
        }
    }
}
