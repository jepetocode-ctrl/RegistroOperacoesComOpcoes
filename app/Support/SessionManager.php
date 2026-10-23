<?php

declare(strict_types=1);

namespace App\Support;

final class SessionManager
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $cookieConfig = $config['session'] ?? [];

        session_name($cookieConfig['cookie_name'] ?? 'registro_opcoes_session');
        session_set_cookie_params([
            'lifetime' => $cookieConfig['lifetime'] ?? 1800,
            'path' => '/',
            'domain' => '',
            'secure' => (bool) ($cookieConfig['secure'] ?? false),
            'httponly' => true,
            'samesite' => $cookieConfig['same_site'] ?? 'Lax',
        ]);

        session_start();
    }

    public static function isAuthenticated(): bool
    {
        self::start();
        return !empty($_SESSION['user_id']);
    }

    public static function user(): ?array
    {
        if (!self::isAuthenticated()) {
            return null;
        }

        return [
            'id' => (int) ($_SESSION['user_id'] ?? 0),
            'username' => $_SESSION['username'] ?? '',
            'profile' => $_SESSION['profile'] ?? 'USER',
            'must_change_password' => (bool) ($_SESSION['must_change_password'] ?? false),
        ];
    }

    public static function loginUser(array $user): void
    {
        self::start();
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['profile'] = $user['profile'] ?? 'USER';
        $_SESSION['must_change_password'] = (bool) ($user['must_change_password'] ?? false);
        session_regenerate_id(true);
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function csrfToken(): string
    {
        self::start();
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function validateCsrf(string $token): bool
    {
        self::start();
        $stored = $_SESSION['csrf_token'] ?? '';

        return hash_equals($stored, $token);
    }
}
