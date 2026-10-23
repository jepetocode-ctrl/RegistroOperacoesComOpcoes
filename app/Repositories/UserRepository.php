<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    private const DEFAULT_PERMISSIONS = ['dashboard'];

    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => trim($username)]);
        $user = $stmt->fetch();

        return $user ? $this->hydrateUser($user) : null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ? $this->hydrateUser($user) : null;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users ORDER BY id ASC');
        $rows = $stmt ? $stmt->fetchAll() : [];

        return array_map(fn (array $row) => $this->hydrateUser($row), $rows);
    }

    public function create(array $user): int
    {
        $permissions = $this->normalizePermissions((array) ($user['permissions'] ?? self::DEFAULT_PERMISSIONS));
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, profile, status, must_change_password, permissions, created_at, updated_at) VALUES (:username, :password_hash, :profile, :status, :must_change_password, :permissions, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':username' => $user['username'],
            ':password_hash' => $user['password_hash'],
            ':profile' => $user['profile'] ?? 'ADMIN',
            ':status' => $user['status'] ?? 'ACTIVE',
            ':must_change_password' => (int) ($user['must_change_password'] ?? 1),
            ':permissions' => json_encode($permissions, JSON_UNESCAPED_UNICODE),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function updatePermissions(int $id, array $permissions): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET permissions = :permissions, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            ':permissions' => json_encode($this->normalizePermissions($permissions), JSON_UNESCAPED_UNICODE),
            ':id' => $id,
        ]);
    }

    public function updateProfile(int $id, string $profile): void
    {
        $safeProfile = strtoupper(trim($profile));
        if ($safeProfile === '') {
            $safeProfile = 'USER';
        }

        $stmt = $this->pdo->prepare('UPDATE users SET profile = :profile, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            ':profile' => $safeProfile,
            ':id' => $id,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash, bool $mustChangePassword): void
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :password_hash, must_change_password = :must_change_password, updated_at = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute([
            ':password_hash' => $passwordHash,
            ':must_change_password' => $mustChangePassword ? 1 : 0,
            ':id' => $id,
        ]);
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $user = $this->findById($userId);
        if ($user === null) {
            return false;
        }

        return $user['profile'] === 'ADMIN' || in_array(strtolower($permission), $user['permissions'] ?? [], true);
    }

    private function hydrateUser(array $row): array
    {
        $rawPermissions = $row['permissions'] ?? '[]';
        $permissions = is_string($rawPermissions) ? json_decode($rawPermissions, true) : $rawPermissions;
        $row['permissions'] = is_array($permissions) ? $this->normalizePermissions($permissions) : [];

        return $row;
    }

    private function normalizePermissions(array $permissions): array
    {
        $bucket = [];
        foreach ($permissions as $permission) {
            $normalized = strtolower(trim((string) $permission));
            if ($normalized === '' || in_array($normalized, $bucket, true)) {
                continue;
            }

            $bucket[] = $normalized;
        }

        return $bucket;
    }
}
