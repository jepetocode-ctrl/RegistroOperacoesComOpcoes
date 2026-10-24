<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Repositories\UserRepository;
use App\Support\Database;
use App\Support\Request;
use App\Support\Response;
use App\Support\AuditLogger;
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
            'temporaryPassword' => $_SESSION['admin_users_temporary_password'] ?? null,
            'resetUsername' => $_SESSION['admin_users_reset_username'] ?? null,
        ]);

        unset($_SESSION['admin_users_error'], $_SESSION['admin_users_success'], $_SESSION['admin_users_temporary_password'], $_SESSION['admin_users_reset_username']);
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
        if (!in_array($profile, ['ADMIN', 'MANAGER', 'USER'], true)) {
            $_SESSION['admin_users_error'] = 'Perfil inválido.';
            header('Location: /admin/users');
            exit;
        }
        $target = $this->repository->findById($targetUserId);
        if ($target === null) {
            $_SESSION['admin_users_error'] = 'Usuário não encontrado.';
            header('Location: /admin/users');
            exit;
        }
        if ($target['status'] === 'DELETED') {
            $_SESSION['admin_users_error'] = 'As permissões de uma conta excluída não podem ser alteradas.';
            header('Location: /admin/users');
            exit;
        }
        if ($target['profile'] === 'ADMIN' && $target['status'] === 'ACTIVE' && $profile !== 'ADMIN' && $this->repository->countActiveAdmins() <= 1) {
            $_SESSION['admin_users_error'] = 'Não é possível remover o perfil do último administrador ativo.';
            header('Location: /admin/users');
            exit;
        }
        $this->repository->updateProfile($targetUserId, $profile);
        $this->repository->updatePermissions($targetUserId, $this->normalizePermissions((array) Request::input('permissions', [])));

        $_SESSION['admin_users_success'] = 'Permissões e perfil atualizados com sucesso.';
        header('Location: /admin/users');
        exit;
    }

    public function setStatus(): void
    {
        $admin = $this->requireAdmin();
        if (!CsrfMiddleware::validate()) {
            $this->redirectWithError('Token CSRF inválido.');
        }

        $target = $this->targetUserFromUri('#/admin/users/(\d+)/status$#');
        if ($target === null) {
            $this->redirectWithError('Usuário não encontrado.');
        }

        $status = strtoupper(trim((string) Request::input('status', '')));
        if (!in_array($status, ['ACTIVE', 'BLOCKED'], true)) {
            $this->redirectWithError('Status de usuário inválido.');
        }
        if ($target['status'] === 'DELETED') {
            $this->redirectWithError('Uma conta excluída não pode ser reativada por esta ação.');
        }
        if ((int) $target['id'] === (int) $admin['id'] && $status !== 'ACTIVE') {
            $this->redirectWithError('Você não pode bloquear sua própria conta.');
        }
        if ($target['profile'] === 'ADMIN' && $target['status'] === 'ACTIVE' && $status !== 'ACTIVE' && $this->repository->countActiveAdmins() <= 1) {
            $this->redirectWithError('Não é possível bloquear o último administrador ativo.');
        }

        $this->repository->updateStatus((int) $target['id'], $status);
        AuditLogger::log((int) $admin['id'], $status === 'BLOCKED' ? 'user_blocked' : 'user_unblocked', 'users', (int) $target['id'], [
            'target_username' => $target['username'],
        ]);
        $_SESSION['admin_users_success'] = $status === 'BLOCKED' ? 'Usuário bloqueado.' : 'Usuário desbloqueado.';
        header('Location: /admin/users');
        exit;
    }

    public function delete(): void
    {
        $admin = $this->requireAdmin();
        if (!CsrfMiddleware::validate()) {
            $this->redirectWithError('Token CSRF inválido.');
        }

        $target = $this->targetUserFromUri('#/admin/users/(\d+)/delete$#');
        if ($target === null) {
            $this->redirectWithError('Usuário não encontrado.');
        }
        if ((int) $target['id'] === (int) $admin['id']) {
            $this->redirectWithError('Você não pode excluir sua própria conta.');
        }
        if ($target['profile'] === 'ADMIN' && $target['status'] === 'ACTIVE' && $this->repository->countActiveAdmins() <= 1) {
            $this->redirectWithError('Não é possível excluir o último administrador ativo.');
        }

        $this->repository->updateStatus((int) $target['id'], 'DELETED');
        AuditLogger::log((int) $admin['id'], 'user_deleted', 'users', (int) $target['id'], [
            'target_username' => $target['username'],
            'deletion_mode' => 'soft',
        ]);
        $_SESSION['admin_users_success'] = 'Conta excluída e arquivada. Os dados financeiros foram preservados.';
        header('Location: /admin/users');
        exit;
    }

    public function resetPassword(): void
    {
        $admin = $this->requireAdmin();
        if (!CsrfMiddleware::validate()) {
            $this->redirectWithError('Token CSRF inválido.');
        }

        $target = $this->targetUserFromUri('#/admin/users/(\d+)/reset-password$#');
        if ($target === null) {
            $this->redirectWithError('Usuário não encontrado.');
        }
        if ((int) $target['id'] === (int) $admin['id']) {
            $this->redirectWithError('Use a tela de alteração de senha para atualizar sua própria senha.');
        }
        if ($target['status'] === 'DELETED') {
            $this->redirectWithError('Não é possível redefinir a senha de uma conta excluída.');
        }

        $temporaryPassword = rtrim(strtr(base64_encode(random_bytes(12)), '+/', '-_'), '=');
        $this->repository->updatePassword(
            (int) $target['id'],
            password_hash($temporaryPassword, PASSWORD_DEFAULT),
            true
        );
        AuditLogger::log((int) $admin['id'], 'user_password_reset', 'users', (int) $target['id'], [
            'target_username' => $target['username'],
        ]);
        $_SESSION['admin_users_temporary_password'] = $temporaryPassword;
        $_SESSION['admin_users_reset_username'] = $target['username'];
        $_SESSION['admin_users_success'] = 'Senha temporária gerada. Copie-a agora; ela será exibida apenas uma vez.';
        header('Location: /admin/users');
        exit;
    }

    private function requireAdmin(): array
    {
        AuthMiddleware::requireAuthentication();
        $user = SessionManager::user();
        if (($user['profile'] ?? 'USER') !== 'ADMIN') {
            header('Location: /dashboard');
            exit;
        }

        return $user;
    }

    private function targetUserFromUri(string $pattern): ?array
    {
        preg_match($pattern, Request::uri(), $matches);
        $targetUserId = (int) ($matches[1] ?? 0);
        return $targetUserId > 0 ? $this->repository->findById($targetUserId) : null;
    }

    private function redirectWithError(string $message): never
    {
        $_SESSION['admin_users_error'] = $message;
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
