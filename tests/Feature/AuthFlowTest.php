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
                permissions TEXT NOT NULL DEFAULT '[]',
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

    public function testRepositoryCanCreateUserAndPersistPermissions(): void
    {
        $repository = new UserRepository($this->pdo);

        $userId = $repository->create([
            'username' => 'analista',
            'password_hash' => password_hash('senha123', PASSWORD_DEFAULT),
            'profile' => 'USER',
            'status' => 'ACTIVE',
            'must_change_password' => false,
            'permissions' => ['dashboard', 'operations'],
        ]);

        $repository->updatePermissions($userId, ['dashboard', 'operations', 'reports']);
        $repository->updateProfile($userId, 'MANAGER');

        $user = $repository->findById($userId);

        $this->assertNotNull($user);
        $this->assertSame('MANAGER', $user['profile']);
        $this->assertSame(['dashboard', 'operations', 'reports'], $user['permissions']);
    }

    public function testBlockedAndDeletedUsersRemainStoredButCannotAuthenticate(): void
    {
        $repository = new UserRepository($this->pdo);
        $userId = $repository->create([
            'username' => 'conta',
            'password_hash' => password_hash('senha123', PASSWORD_DEFAULT),
            'profile' => 'USER',
            'status' => 'ACTIVE',
            'must_change_password' => false,
        ]);
        $service = new AuthService($repository);

        $repository->updateStatus($userId, 'BLOCKED');
        self::assertSame('BLOCKED', $repository->findById($userId)['status']);
        self::assertSame('Usuário inativo.', $service->login('conta', 'senha123')['message']);

        $repository->updateStatus($userId, 'DELETED');
        self::assertSame('DELETED', $repository->findById($userId)['status']);
        self::assertSame('Usuário inativo.', $service->login('conta', 'senha123')['message']);
    }

    public function testPasswordResetRequiresUserToChangeTemporaryPassword(): void
    {
        $repository = new UserRepository($this->pdo);
        $userId = $repository->create([
            'username' => 'conta',
            'password_hash' => password_hash('senha-antiga', PASSWORD_DEFAULT),
            'profile' => 'USER',
            'status' => 'ACTIVE',
            'must_change_password' => false,
        ]);
        $temporaryPassword = 'temporaria-segura';
        $repository->updatePassword($userId, password_hash($temporaryPassword, PASSWORD_DEFAULT), true);

        $result = (new AuthService($repository))->login('conta', $temporaryPassword);

        self::assertTrue($result['success']);
        self::assertTrue($result['must_change_password']);
    }
}
