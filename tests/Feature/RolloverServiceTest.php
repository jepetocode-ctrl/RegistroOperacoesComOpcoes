<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\RolloverService;
use App\Database\LegacyMigrations\CreateOperationsAndPositions;
use App\Database\LegacyMigrations\CreateRollovers;
use PDO;
use PHPUnit\Framework\TestCase;

final class RolloverServiceTest extends TestCase
{
    private PDO $pdo;
    private RolloverService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT UNIQUE NOT NULL, password_hash TEXT NOT NULL)");
        $this->pdo->exec("CREATE TABLE assets (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, symbol TEXT NOT NULL, name TEXT NOT NULL, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE strategies (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL, FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE option_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, asset_id INTEGER NOT NULL, code TEXT NOT NULL, option_type TEXT NOT NULL, strike_price NUMERIC NOT NULL, expiration_date TEXT NOT NULL, contract_multiplier INTEGER NOT NULL DEFAULT 100, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id), FOREIGN KEY(asset_id) REFERENCES assets(id))");
        $this->pdo->exec("INSERT INTO users (username,password_hash) VALUES ('owner','hash')");
        $this->pdo->exec("INSERT INTO users (username,password_hash) VALUES ('other','hash')");
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (1,'PETR4','Petrobras')");
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (2,'VALE3','Vale')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date) VALUES (1,1,'PETR4C','CALL',10,'2027-01-15')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date) VALUES (1,1,'PETR4C2','CALL',12,'2027-02-19')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date) VALUES (2,2,'VALE3C','CALL',12,'2027-02-19')");
        (new CreateOperationsAndPositions())->up($this->pdo);
        (new CreateRollovers())->up($this->pdo);
        $this->pdo->exec("INSERT INTO operations (user_id,option_contract_id,side,quantity,unit_price,gross_cash_flow,costs,executed_at,operation_kind) VALUES (1,1,'BUY',10,10,-10000,2,'2026-10-23 10:00:00','OPEN')");
        $this->service = new RolloverService($this->pdo);
    }

    public function testRolloverLinksBothLegsTracksNetCashAndLeavesCorrectRemainder(): void
    {
        $result = $this->service->register(1, [
            'source_operation_id'=>1, 'closing_contract_id'=>1, 'opening_contract_id'=>2,
            'quantity'=>4, 'closing_unit_price'=>12, 'opening_unit_price'=>8,
            'closing_costs'=>2, 'opening_costs'=>3, 'strategy_id'=>null,
        ]);

        self::assertTrue($result['success']);
        self::assertSame(201, $result['status']);
        self::assertSame(1595.0, $result['data']['net_cash_flow']);
        self::assertSame(5.0, $result['data']['costs']);
        self::assertNotSame('', $result['data']['rollover_uuid']);
        self::assertSame(3, (int)$this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM rollovers')->fetchColumn());
        self::assertSame(2, (int)$this->pdo->query('SELECT COUNT(*) FROM operation_history')->fetchColumn());
        self::assertSame(1, count($this->service->history(1, $result['data']['rollover_uuid'])));
        self::assertSame(6, 10 - (int)$this->pdo->query('SELECT COALESCE(SUM(quantity),0) FROM operation_closures WHERE opening_operation_id=1')->fetchColumn());
        self::assertStringContainsString('não lucro automático', $result['data']['note']);
    }

    public function testFailureWritingHistoryRollsBackBothLegsAndRolloverRecord(): void
    {
        $this->pdo->exec("CREATE TRIGGER fail_rollover_history BEFORE INSERT ON operation_history BEGIN SELECT RAISE(ABORT, 'forced history failure'); END");
        try {
            $this->service->register(1, [
                'source_operation_id'=>1, 'closing_contract_id'=>1, 'opening_contract_id'=>2,
                'quantity'=>4, 'closing_unit_price'=>12, 'opening_unit_price'=>8,
                'closing_costs'=>2, 'opening_costs'=>3,
            ]);
            self::fail('Expected forced history write to fail.');
        } catch (\PDOException $e) {
            self::assertStringContainsString('forced history failure', $e->getMessage());
        }
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
        self::assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM rollovers')->fetchColumn());
        self::assertSame(0, (int)$this->pdo->query('SELECT COUNT(*) FROM operation_closures')->fetchColumn());
    }

    public function testRolloverRejectsForeignContractAndSideChange(): void
    {
        $foreignContract = $this->service->register(1, [
            'source_operation_id'=>1, 'closing_contract_id'=>1, 'opening_contract_id'=>3,
            'quantity'=>4, 'closing_unit_price'=>12, 'opening_unit_price'=>8,
        ]);
        self::assertFalse($foreignContract['success']);
        self::assertArrayHasKey('contract', $foreignContract['errors']);

        $changedSide = $this->service->register(1, [
            'source_operation_id'=>1, 'closing_contract_id'=>1, 'opening_contract_id'=>2,
            'quantity'=>4, 'closing_unit_price'=>12, 'opening_unit_price'=>8,
            'opening_side'=>'SELL',
        ]);
        self::assertFalse($changedSide['success']);
        self::assertArrayHasKey('opening_side', $changedSide['errors']);
        self::assertSame(1, (int)$this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
    }
}
