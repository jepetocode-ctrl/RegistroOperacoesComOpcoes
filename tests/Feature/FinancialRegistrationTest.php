<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\FinancialRegistrationRepository;
use App\Services\FinancialRegistrationService;
use App\Support\Database;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

final class FinancialRegistrationTest extends TestCase
{
    private PDO $pdo;
    private FinancialRegistrationService $service;

    protected function setUp(): void
    {
        $this->pdo = Database::createConnection('sqlite', [
            'database' => __DIR__ . '/../../storage/test-financial.sqlite',
        ]);

        $this->pdo->exec('DROP TABLE IF EXISTS strategies;');
        $this->pdo->exec('DROP TABLE IF EXISTS option_contracts;');
        $this->pdo->exec('DROP TABLE IF EXISTS assets;');
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

        $this->pdo->exec(<<<'SQL'
            CREATE TABLE assets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                symbol VARCHAR(30) NOT NULL,
                name VARCHAR(120) NOT NULL,
                asset_type VARCHAR(30) NOT NULL DEFAULT 'STOCK',
                status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, symbol),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            );
        SQL);

        $this->pdo->exec(<<<'SQL'
            CREATE TABLE option_contracts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                asset_id INTEGER NOT NULL,
                code VARCHAR(60) NOT NULL,
                option_type VARCHAR(4) NOT NULL,
                strike_price NUMERIC(18,6) NOT NULL,
                expiration_date DATE NOT NULL,
                contract_multiplier INTEGER NOT NULL DEFAULT 100,
                status VARCHAR(20) NOT NULL DEFAULT 'ACTIVE',
                created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE (user_id, code),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                FOREIGN KEY (asset_id) REFERENCES assets(id) ON DELETE RESTRICT
            );
        SQL);

        $this->service = new FinancialRegistrationService(new FinancialRegistrationRepository($this->pdo), $this->pdo);
    }

    public function testSqliteConnectionEnforcesForeignKeysForFinancialReferences(): void
    {
        $this->pdo->exec("INSERT INTO users (username, password_hash) VALUES ('owner', 'hash')");

        $this->expectException(PDOException::class);

        $this->pdo->exec("INSERT INTO option_contracts (user_id, asset_id, code, option_type, strike_price, expiration_date) VALUES (1, 999, 'PETR2201C', 'CALL', 25.50, '2027-01-15')");
    }

    public function testUserCanOnlyListHisOwnFinancialRecords(): void
    {
        $this->pdo->exec("INSERT INTO users (username, password_hash) VALUES ('owner', 'hash')");
        $this->pdo->exec("INSERT INTO users (username, password_hash) VALUES ('other', 'hash-2')");

        $created = $this->service->save('assets', 1, [
            'symbol' => 'PETR3',
            'name' => 'Petrobras',
            'asset_type' => 'STOCK',
            'status' => 'ACTIVE',
        ]);

        $this->assertTrue($created['success']);
        $this->assertSame([], $this->service->list('assets', 2));
        $this->assertCount(1, $this->service->list('assets', 1));
    }
}
