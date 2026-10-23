<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;

final class AuthMiddleware
{
    public static function requireAuthentication(): void
    {
        if (SessionManager::isAuthenticated()) {
            return;
        }

        if (str_starts_with(Request::uri(), '/api/')) {
            Response::json([
                'success' => false,
                'message' => 'Não autenticado.',
            ], 401);
            exit;
        }

        header('Location: /login');
        exit;
    }

    public static function isAuthenticated(): bool
    {
        return SessionManager::isAuthenticated();
    }
}
