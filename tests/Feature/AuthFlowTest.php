<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\UserRepository;
use App\Services\AuthService;
use App\Support\Database;
use PDO;
use PHPUnit\Framework\TestCase;

final class AuthFlowTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = Database::createConnection('sqlite', [
            'database' => __DIR__ . '/../../storage/test-auth.sqlite',
        ]);

        $this->pdo->exec('DROP TABLE IF EXISTS audit_logs;');
        $this->pdo->exec('DROP TABLE IF EXISTS settings;');
        $this->pdo->exec('DROP TABLE IF EXISTS users;');

        $this->pdo->exec(<<<'SQL'
            CREATE TABLE users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                profile TEXT NOT NULL DEFAULT 'ADMIN',
                status TEXT NOT NULL DEFAULT 'ACTIVE',
                must_change_password INTEGER NOT NULL DEFAULT 1,
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
            );
        SQL);
    }

    public function testAdminUserCanAuthenticateWithTemporaryPassword(): void
    {
        $repository = new UserRepository($this->pdo);
        $service = new AuthService($repository);

        $service->ensureDefaultAdmin();

        $result = $service->login('administrador', 'admin123');

        $this->assertTrue($result['success']);
        $this->assertTrue($result['must_change_password']);
        $this->assertSame('ADMIN', $result['user']['profile']);
    }

    public function testLoginRejectsInvalidPassword(): void
    {
        $repository = new UserRepository($this->pdo);
        $service = new AuthService($repository);

        $service->ensureDefaultAdmin();

        $result = $service->login('administrador', 'senha-errada');

        $this->assertFalse($result['success']);
        $this->assertSame('Credenciais inválidas.', $result['message']);
    }
}
