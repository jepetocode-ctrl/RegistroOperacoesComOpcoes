<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;
use App\Support\Database;
use App\Support\SessionManager;
use App\Repositories\UserRepository;

final class AuthMiddleware
{
    public static function requireAuthentication(): void
    {
        if (self::isAuthenticated()) {
            $user = SessionManager::user();
            if (($user['must_change_password'] ?? false) && Request::uri() !== '/change-password') {
                if (str_starts_with(Request::uri(), '/api/')) {
                    Response::json([
                        'success' => false,
                        'message' => 'Altere sua senha temporária antes de continuar.',
                    ], 403);
                    exit;
                }
                header('Location: /change-password');
                exit;
            }
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
        if (!SessionManager::isAuthenticated()) {
            return false;
        }

        $user = SessionManager::user();
        $current = $user === null
            ? null
            : (new UserRepository(Database::defaultConnection()))->findById((int) $user['id']);
        if ($current === null || ($current['status'] ?? 'ACTIVE') !== 'ACTIVE') {
            SessionManager::logout();
            return false;
        }

        SessionManager::refreshUser($current);
        if (($current['must_change_password'] ?? false) && str_starts_with(Request::uri(), '/api/')) {
            return false;
        }
        return true;
    }
}
