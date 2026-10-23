<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use App\Support\AuditLogger;
use App\Support\Database;
use App\Validators\AuthValidator;

final class AuthService
{
    public function __construct(private UserRepository $repository)
    {
    }

    public function ensureDefaultAdmin(): array
    {
        $existing = $this->repository->findByUsername('administrador');
        if ($existing) {
            return $existing;
        }

        $userId = $this->repository->create([
            'username' => 'administrador',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'profile' => 'ADMIN',
            'status' => 'ACTIVE',
            'must_change_password' => true,
        ]);

        $user = $this->repository->findById($userId);
        if ($user) {
            AuditLogger::log((int) $user['id'], 'admin_created', 'users', (int) $user['id'], ['username' => $user['username']]);
        }

        return $user ?? [];
    }

    public function login(string $username, string $password): array
    {
        $username = trim($username);
        $user = $this->repository->findByUsername($username);

        if ($user === null || !password_verify($password, $user['password_hash'])) {
            AuditLogger::log(null, 'login_failed', 'users', null, ['username' => $username]);
            return [
                'success' => false,
                'message' => 'Credenciais inválidas.',
            ];
        }

        if (($user['status'] ?? 'ACTIVE') !== 'ACTIVE') {
            return [
                'success' => false,
                'message' => 'Usuário inativo.',
            ];
        }

        AuditLogger::log((int) $user['id'], 'login_success', 'users', (int) $user['id'], ['username' => $user['username']]);

        return [
            'success' => true,
            'user' => [
                'id' => (int) $user['id'],
                'username' => $user['username'],
                'profile' => $user['profile'],
                'status' => $user['status'],
                'must_change_password' => (bool) $user['must_change_password'],
            ],
            'must_change_password' => (bool) $user['must_change_password'],
        ];
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword, string $confirmPassword): array
    {
        $user = $this->repository->findById($userId);
        if ($user === null) {
            return ['success' => false, 'message' => 'Usuário não encontrado.'];
        }

        if (!password_verify($currentPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Senha atual incorreta.'];
        }

        $validator = AuthValidator::validatePasswordChange([
            'current_password' => $currentPassword,
            'new_password' => $newPassword,
            'confirm_password' => $confirmPassword,
        ]);

        if (!$validator['valid']) {
            return ['success' => false, 'message' => array_values($validator['errors'])[0]];
        }

        if (password_verify('admin123', $user['password_hash']) && $newPassword === 'admin123') {
            return ['success' => false, 'message' => 'A nova senha não pode ser igual à senha inicial temporária.'];
        }

        if ($currentPassword === $newPassword) {
            return ['success' => false, 'message' => 'A nova senha deve ser diferente da senha atual.'];
        }

        $this->repository->updatePassword($userId, password_hash($newPassword, PASSWORD_DEFAULT), false);
        AuditLogger::log($userId, 'password_changed', 'users', $userId, ['username' => $user['username']]);

        return ['success' => true, 'message' => 'Senha alterada com sucesso.'];
    }

    public static function getDefaultAdminCredentials(): array
    {
        return [
            'username' => 'administrador',
            'password' => 'admin123',
            'profile' => 'ADMIN',
        ];
    }
}
