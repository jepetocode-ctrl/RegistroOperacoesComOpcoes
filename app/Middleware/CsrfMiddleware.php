<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;

final class CsrfMiddleware
{
    public static function validate(): bool
    {
        if (Request::method() === 'GET') {
            return true;
        }

        $token = Request::input('_csrf_token', '');
        if (!is_string($token) || !SessionManager::validateCsrf($token)) {
            return false;
        }

        return true;
    }

    public static function enforce(): void
    {
        if (!self::validate()) {
            if (str_starts_with(Request::uri(), '/api/')) {
                Response::json([
                    'success' => false,
                    'message' => 'Token CSRF inválido.',
                ], 419);
                exit;
            }

            http_response_code(419);
            echo 'Token CSRF inválido.';
            exit;
        }
    }
}
