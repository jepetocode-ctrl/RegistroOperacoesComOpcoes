<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => trim($username)]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();

        return $user ?: null;
    }

    public function create(array $user): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, password_hash, profile, status, must_change_password, created_at, updated_at) VALUES (:username, :password_hash, :profile, :status, :must_change_password, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([
            ':username' => $user['username'],
            ':password_hash' => $user['password_hash'],
            ':profile' => $user['profile'] ?? 'ADMIN',
            ':status' => $user['status'] ?? 'ACTIVE',
            ':must_change_password' => (int) ($user['must_change_password'] ?? 1),
        ]);

        return (int) $this->pdo->lastInsertId();
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
}
