<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\OperationRepository;
use App\Services\OperationService;
use App\Database\LegacyMigrations\CreateOperationsAndPositions;
use App\Database\LegacyMigrations\AddDashboardArchiveFields;
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
        $this->pdo->exec("INSERT INTO users (username,password_hash) VALUES ('other','hash-2')");
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (1,'PETR4','Petrobras')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date,contract_multiplier) VALUES (1,1,'PETR4C','CALL',10,'2027-01-15',100)");
        (new CreateOperationsAndPositions())->up($this->pdo);
        (new AddDashboardArchiveFields())->up($this->pdo);
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
        self::assertEqualsWithDelta(798.2, $positions[0]['realized_result'], 0.000001);
    }

    public function testCloseCannotExceedAvailablePosition(): void
    {
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $result = $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>4,'unit_price'=>12,'operation_kind'=>'CLOSE']);
        self::assertFalse($result['success']);
    }

    public function testOpenUnstructuredOperationCanBeArchivedWithoutDeletingItsHistory(): void
    {
        $opened = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $result = $this->service->archive(1, (int) $opened['data']['id']);
        self::assertTrue($result['success']);
        self::assertSame([], $this->service->positions(1));
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM operations WHERE archived_at IS NOT NULL")->fetchColumn());
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM operation_history WHERE action = 'ARCHIVED'")->fetchColumn());
        self::assertSame(200, $result['status']);
    }

    public function testDeclaredOpenCannotSilentlyCloseAnOppositePosition(): void
    {
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $result = $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>1,'unit_price'=>11,'operation_kind'=>'OPEN']);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertSame(3, $this->service->positions(1)[0]['open_quantity']);
        self::assertSame(1, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
    }

    public function testOwnerCanEditAnUnclosedOperationAndTheChangeIsRecorded(): void
    {
        $created = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>2,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $operationId = (int) $created['data']['id'];

        $updated = $this->service->update(1, $operationId, [
            'side'=>'BUY', 'quantity'=>4, 'unit_price'=>12, 'costs'=>3,
            'executed_at'=>'2026-10-23 11:30:00', 'operation_kind'=>'OPEN', 'notes'=>'Ajuste',
        ]);

        self::assertTrue($updated['success']);
        self::assertSame(4, (int) $updated['data']['quantity']);
        self::assertSame(-4800.0, (float) $updated['data']['gross_cash_flow']);
        self::assertSame('UPDATED', $this->service->history(1, $operationId)[1]['action']);
        self::assertSame(404, $this->service->update(2, $operationId, ['quantity'=>3])['status']);
    }

    public function testOperationWithClosureCannotBeEdited(): void
    {
        $opening = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>5,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>2,'unit_price'=>11,'operation_kind'=>'PARTIAL_CLOSE']);

        $result = $this->service->update(1, (int) $opening['data']['id'], ['unit_price'=>12]);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
    }

    public function testPartialCloseCanTargetTheSelectedOpenLot(): void
    {
        $first = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $second = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>4,'unit_price'=>11,'operation_kind'=>'OPEN']);

        $closed = $this->service->register(1, [
            'option_contract_id'=>1, 'side'=>'SELL', 'quantity'=>2, 'unit_price'=>12,
            'operation_kind'=>'PARTIAL_CLOSE', 'reference_operation_id'=>$second['data']['id'],
        ]);

        self::assertTrue($closed['success']);
        $lots = $this->service->positions(1)[0]['open_lots'];
        self::assertSame((int) $first['data']['id'], $lots[0]['id']);
        self::assertSame(3, $lots[0]['open_quantity']);
        self::assertSame((int) $second['data']['id'], $lots[1]['id']);
        self::assertSame(2, $lots[1]['open_quantity']);
    }

    public function testFullCloseCanStartFromASelectedLotAndCloseTheWholePosition(): void
    {
        $first = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $second = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>4,'unit_price'=>11,'operation_kind'=>'OPEN']);

        $closed = $this->service->register(1, [
            'option_contract_id'=>1, 'side'=>'SELL', 'quantity'=>7, 'unit_price'=>12,
            'operation_kind'=>'CLOSE', 'reference_operation_id'=>$second['data']['id'],
        ]);

        self::assertTrue($closed['success']);
        self::assertSame([], $this->service->positions(1)[0]['open_lots']);
        self::assertNotSame($first['data']['id'], $second['data']['id']);
    }

    public function testSelectedLotDismantleCanReverseTheRemainingPosition(): void
    {
        $first = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $second = $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>4,'unit_price'=>11,'operation_kind'=>'OPEN']);

        $reversal = $this->service->register(1, [
            'option_contract_id'=>1, 'side'=>'SELL', 'quantity'=>9, 'unit_price'=>12,
            'operation_kind'=>'REVERSAL', 'reference_operation_id'=>$second['data']['id'],
        ]);

        self::assertTrue($reversal['success']);
        $positions = $this->service->positions(1);
        self::assertSame(-2, $positions[0]['open_quantity']);
        self::assertCount(1, $positions[0]['open_lots']);
        self::assertSame('SELL', $positions[0]['open_lots'][0]['side']);
        self::assertSame(2, $positions[0]['open_lots'][0]['open_quantity']);
        self::assertNotSame($first['data']['id'], $second['data']['id']);
    }

    public function testContractTransactionHistoryIsFilteredByAuthenticatedUser(): void
    {
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (2,'VALE3','Vale')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date,contract_multiplier) VALUES (2,2,'VALE3C','CALL',10,'2027-01-15',100)");
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>2,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>1,'unit_price'=>12,'operation_kind'=>'PARTIAL_CLOSE']);
        $this->service->register(2, ['option_contract_id'=>2,'side'=>'BUY','quantity'=>4,'unit_price'=>9,'operation_kind'=>'OPEN']);

        $ownerHistory = $this->service->transactionHistory(1, 1);
        $otherHistory = $this->service->transactionHistory(2, 2);

        self::assertCount(2, $ownerHistory);
        self::assertCount(1, $otherHistory);
        self::assertSame(1, (int) $ownerHistory[0]['quantity']);
        self::assertEqualsWithDelta(200.0, (float) $ownerHistory[0]['realized_result'], 0.000001);
        self::assertSame(4, (int) $otherHistory[0]['quantity']);
    }

    public function testUsersCannotRegisterOperationsAgainstAnotherUsersContract(): void
    {
        $result = $this->service->register(2, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>2,'unit_price'=>10,'operation_kind'=>'OPEN']);

        self::assertFalse($result['success']);
        self::assertSame(422, $result['status']);
        self::assertSame([], $this->service->positions(1));
        self::assertSame([], $this->service->positions(2));
    }

    public function testReversalClosesCurrentPositionAndOpensInverseRemainder(): void
    {
        $this->service->register(1, ['option_contract_id'=>1,'side'=>'BUY','quantity'=>3,'unit_price'=>10,'operation_kind'=>'OPEN']);
        $result = $this->service->register(1, ['option_contract_id'=>1,'side'=>'SELL','quantity'=>5,'unit_price'=>11,'operation_kind'=>'REVERSAL']);
        self::assertTrue($result['success']);
        $positions = $this->service->positions(1);
        self::assertSame(-2, $positions[0]['open_quantity']);
    }

}
