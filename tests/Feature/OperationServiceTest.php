<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\OperationRepository;
use App\Services\OperationService;
use Database\Migrations\CreateOperationsAndPositions;
use PDO;
use PHPUnit\Framework\TestCase;

final class OperationServiceTest extends TestCase
{
    private PDO $pdo;
    private OperationService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL)");
        $this->pdo->exec("CREATE TABLE assets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, symbol TEXT NOT NULL, name TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE strategies (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE option_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, asset_id INTEGER NOT NULL, code TEXT NOT NULL, option_type TEXT NOT NULL, strike_price NUMERIC NOT NULL, expiration_date TEXT NOT NULL, contract_multiplier INTEGER NOT NULL DEFAULT 100, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(asset_id) REFERENCES assets(id))");
        $this->pdo->exec("INSERT INTO users (username,password_hash) VALUES ('owner','hash')");
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (1,'PETR4','Petrobras')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date,contract_multiplier) VALUES (1,1,'PETR4C','CALL',10,'2027-01-15',100)");
        require_once dirname(__DIR__, 2) . '/database/migrations/20261023_000003_create_operations_and_positions.php';
        (new CreateOperationsAndPositions())->up($this->pdo);
        $this->service = new OperationService($this->pdo, new OperationRepository($this->pdo));
    }

    public function testPartialClosePreservesRemainingQuantityAndSeparatesCashFlowFromRealizedResult(): void
    {
        $buy = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>10,'unit_price'=>10,'costs'=>2,'operation_kind'=>'OPEN']);
        self::assertTrue($buy['success']);

        $sell = $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>4,'unit_price'=>12,'costs'=>1,'operation_kind'=>'PARTIAL_CLOSE']);
        self::assertTrue($sell['success']);

        $positions = $this->service->positions(1);
        self::assertSame(6, $positions[0]['open_quantity']);
        self::assertSame(-5200.0, $positions[0]['gross_cash_flow']);
        self::assertSame(798.0, $positions[0]['realized_result']);
    }

    public function testCloseCannotExceedAvailablePosition(): void
    {
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $result = $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>4,'unit_price'=>12,'operation_kind'=>'CLOSE']);
        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertSame(1, count($this->service->positions(1)));
        self::assertSame(3, $this->service->positions(1)[0]['open_quantity']);
    }
}
