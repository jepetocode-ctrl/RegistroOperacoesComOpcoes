<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\SessionManager;

final class UserManagementController extends BaseController
{
    private const PERMISSIONS = ['dashboard', 'users', 'operations', 'rollovers', 'reports'];

    private UserRepository $repository;

    public function __construct()
    {
        $this->repository = new UserRepository(Database::defaultConnection());
    }

    public function index(): void
    {
        AuthMiddleware::requireAuthentication();
        $user = SessionManager::user();
        if (($user['profile'] ?? 'USER') !== 'ADMIN') {
            header('Location: /dashboard');
            exit;
        }

        $html = $this->view('admin/users', [
            'user' => $user,
            'users' => $this->repository->all(),
            'permissions' => self::PERMISSIONS,
            'csrf' => SessionManager::csrfToken(),
            'error' => $_SESSION['admin_users_error'] ?? null,
            'success' => $_SESSION['admin_users_success'] ?? null,
        ]);

        unset($_SESSION['admin_users_error'], $_SESSION['admin_users_success']);
        Response::html($html, 200);
    }

    public function store(): void
    {
        AuthMiddleware::requireAuthentication();
        $user = SessionManager::user();
        if (($user['profile'] ?? 'USER') !== 'ADMIN') {
            header('Location: /dashboard');
            exit;
        }

        if (!CsrfMiddleware::validate()) {
            $_SESSION['admin_users_error'] = 'Token CSRF inválido.';
            header('Location: /admin/users');
            exit;
        }

        $payload = Request::all();
        $username = trim((string) ($payload['username'] ?? ''));
        $password = (string) ($payload['password'] ?? '');
        $profile = strtoupper(trim((string) ($payload['profile'] ?? 'USER')));

        if ($username === '' || strlen($password) < 6) {
            $_SESSION['admin_users_error'] = 'Informe um nome de usuário e uma senha com pelo menos 6 caracteres.';
            header('Location: /admin/users');
            exit;
        }

        if ($this->repository->findByUsername($username) !== null) {
            $_SESSION['admin_users_error'] = 'Já existe um usuário com este nome.';
            header('Location: /admin/users');
            exit;
        }

        $this->repository->create([
            'username' => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'profile' => in_array($profile, ['ADMIN', 'MANAGER', 'USER'], true) ? $profile : 'USER',
            'status' => 'ACTIVE',
            'must_change_password' => true,
            'permissions' => $this->normalizePermissions((array) ($payload['permissions'] ?? [])),
        ]);

        $_SESSION['admin_users_success'] = 'Usuário criado com sucesso.';
        header('Location: /admin/users');
        exit;
    }

    public function updatePermissions(): void
    {
        AuthMiddleware::requireAuthentication();
        $user = SessionManager::user();
        if (($user['profile'] ?? 'USER') !== 'ADMIN') {
            header('Location: /dashboard');
            exit;
        }

        if (!CsrfMiddleware::validate()) {
            $_SESSION['admin_users_error'] = 'Token CSRF inválido.';
            header('Location: /admin/users');
            exit;
        }

        $uri = Request::uri();
        preg_match('#/admin/users/(\d+)/permissions$#', $uri, $matches);
        $targetUserId = (int) ($matches[1] ?? 0);

        if ($targetUserId <= 0) {
            $_SESSION['admin_users_error'] = 'Usuário inválido.';
            header('Location: /admin/users');
            exit;
        }

        $profile = strtoupper(trim((string) Request::input('profile', 'USER')));
        $this->repository->updateProfile($targetUserId, $profile);
        $this->repository->updatePermissions($targetUserId, $this->normalizePermissions((array) Request::input('permissions', [])));

        $_SESSION['admin_users_success'] = 'Permissões e perfil atualizados com sucesso.';
        header('Location: /admin/users');
        exit;
    }

    private function normalizePermissions(array $permissions): array
    {
        $allowed = self::PERMISSIONS;
        $normalized = [];

        foreach ($permissions as $permission) {
            $value = strtolower(trim((string) $permission));
            if ($value !== '' && in_array($value, $allowed, true) && !in_array($value, $normalized, true)) {
                $normalized[] = $value;
            }
        }

        return $normalized;
    }
}
