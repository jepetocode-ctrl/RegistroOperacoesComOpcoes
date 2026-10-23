<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;
use App\Validators\AuthValidator;

final class AuthController extends BaseController
{
    private AuthService $authService;

    public function __construct()
    {
        $pdo = Database::defaultConnection();
        $this->authService = new AuthService(new UserRepository($pdo));
    }

    public function loginPage(): void
    {
        $this->authService->ensureDefaultAdmin();
        $csrf = SessionManager::csrfToken();

        $html = $this->view('auth/login', [
            'csrf' => $csrf,
            'error' => $_SESSION['login_error'] ?? null,
            'username' => $_SESSION['login_username'] ?? '',
        ]);

        unset($_SESSION['login_error'], $_SESSION['login_username']);

        Response::html($html, 200);
    }

    public function login(): void
    {
        if (!CsrfMiddleware::validate()) {
            $_SESSION['login_error'] = 'Token CSRF inválido.';
            header('Location: /login');
            exit;
        }

        $payload = Request::all();
        $validation = AuthValidator::validateLogin($payload);

        if (!$validation['valid']) {
            $_SESSION['login_error'] = $validation['errors']['username'] ?? $validation['errors']['password'] ?? 'Dados inválidos.';
            $_SESSION['login_username'] = $validation['username'];
            header('Location: /login');
            exit;
        }

        $result = $this->authService->login((string) ($payload['username'] ?? ''), (string) ($payload['password'] ?? ''));

        if (!$result['success']) {
            $_SESSION['login_error'] = $result['message'];
            $_SESSION['login_username'] = $validation['username'];
            header('Location: /login');
            exit;
        }

        SessionManager::loginUser($result['user']);

        if ($result['must_change_password']) {
            header('Location: /change-password');
            exit;
        }

        header('Location: /dashboard');
        exit;
    }

    public function apiLogin(): void
    {
        if (!CsrfMiddleware::validate()) {
            Response::json(['success' => false, 'message' => 'Token CSRF inválido.'], 419);
            return;
        }

        $payload = Request::all();
        $validation = AuthValidator::validateLogin($payload);

        if (!$validation['valid']) {
            Response::json([
                'success' => false,
                'message' => $validation['errors']['username'] ?? $validation['errors']['password'] ?? 'Dados inválidos.',
            ], 422);
            return;
        }

        $result = $this->authService->login((string) ($payload['username'] ?? ''), (string) ($payload['password'] ?? ''));

        if (!$result['success']) {
            Response::json(['success' => false, 'message' => $result['message']], 401);
            return;
        }

        SessionManager::loginUser($result['user']);

        Response::json([
            'success' => true,
            'message' => 'Login realizado com sucesso.',
            'user' => $result['user'],
            'must_change_password' => $result['must_change_password'],
        ]);
    }

    public function apiMe(): void
    {
        if (!AuthMiddleware::isAuthenticated()) {
            Response::json(['success' => false, 'message' => 'Não autenticado.'], 401);
            return;
        }

        Response::json([
            'success' => true,
            'user' => SessionManager::user(),
        ]);
    }

    public function logout(): void
    {
        if (!CsrfMiddleware::validate()) {
            header('Location: /login');
            exit;
        }

        SessionManager::logout();
        header('Location: /login');
        exit;
    }

    public function apiLogout(): void
    {
        if (!CsrfMiddleware::validate()) {
            Response::json(['success' => false, 'message' => 'Token CSRF inválido.'], 419);
            return;
        }

        SessionManager::logout();
        Response::json(['success' => true, 'message' => 'Logout realizado com sucesso.']);
    }

    public function changePasswordPage(): void
    {
        if (!AuthMiddleware::isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $html = $this->view('auth/change-password', [
            'csrf' => SessionManager::csrfToken(),
            'error' => $_SESSION['password_error'] ?? null,
        ]);

        unset($_SESSION['password_error']);
        Response::html($html, 200);
    }

    public function changePassword(): void
    {
        if (!CsrfMiddleware::validate()) {
            $_SESSION['password_error'] = 'Token CSRF inválido.';
            header('Location: /change-password');
            exit;
        }

        $user = SessionManager::user();
        if ($user === null) {
            header('Location: /login');
            exit;
        }

        $payload = Request::all();
        $result = $this->authService->changePassword(
            (int) $user['id'],
            (string) ($payload['current_password'] ?? ''),
            (string) ($payload['new_password'] ?? ''),
            (string) ($payload['confirm_password'] ?? '')
        );

        if (!$result['success']) {
            $_SESSION['password_error'] = $result['message'];
            header('Location: /change-password');
            exit;
        }

        SessionManager::regenerate();
        $_SESSION['must_change_password'] = false;
        header('Location: /dashboard');
        exit;
    }
}
