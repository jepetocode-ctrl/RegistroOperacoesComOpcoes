<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Database\LegacyMigrations\AddUserPermissions;
use App\Database\LegacyMigrations\CreateAppBaseTables;
use App\Database\LegacyMigrations\CreateFinancialRegistrations;
use App\Database\LegacyMigrations\CreateOperationsAndPositions;
use App\Database\LegacyMigrations\CreateRollovers;
use App\Database\LegacyMigrations\CreateStructuredStrategyDomain;
use App\Repositories\StrategyTemplateRepository;
use App\Repositories\StructuredPositionRepository;
use App\Services\StrategyTemplateService;
use App\Services\StructuredPositionService;
use App\Services\RolloverService;
use PDO;
use PHPUnit\Framework\TestCase;

final class StructuredPositionTest extends TestCase
{
    private PDO $pdo;
    private StrategyTemplateRepository $templates;
    private StructuredPositionService $positions;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        (new CreateAppBaseTables())->up($this->pdo);
        (new CreateFinancialRegistrations())->up($this->pdo);
        (new CreateOperationsAndPositions())->up($this->pdo);
        (new CreateRollovers())->up($this->pdo);
        (new AddUserPermissions())->up($this->pdo);
        $this->pdo->exec("INSERT INTO users (username,password_hash) VALUES ('owner','hash'),('other','hash')");
        $this->pdo->exec("INSERT INTO assets (user_id,symbol,name) VALUES (1,'PETR4','Petrobras'),(2,'PETR4','Petrobras')");
        $this->pdo->exec("INSERT INTO option_contracts (user_id,asset_id,code,option_type,strike_price,expiration_date,contract_multiplier) VALUES
            (1,1,'PETRC90','CALL',90,'2026-12-18',100),
            (1,1,'PETRC100','CALL',100,'2026-12-18',100),
            (1,1,'PETRC110','CALL',110,'2026-12-18',100),
            (1,1,'PETRC90J','CALL',90,'2027-01-15',100),
            (1,1,'PETRC100J','CALL',100,'2027-01-15',100),
            (1,1,'PETRC110J','CALL',110,'2027-01-15',100),
            (1,1,'PETRP90','PUT',90,'2026-12-18',100),
            (2,2,'OTHER-C100','CALL',100,'2026-12-18',100)");
        (new CreateStructuredStrategyDomain())->up($this->pdo);
        $this->templates = new StrategyTemplateRepository($this->pdo);
        $this->positions = new StructuredPositionService($this->pdo, $this->templates, new StructuredPositionRepository($this->pdo));
    }

    public function testCatalogSeedsOnceAndCallSpreadCreatesLinkedOperationsAtomically(): void
    {
        $firstCount = (int) $this->pdo->query('SELECT COUNT(*) FROM strategy_templates WHERE user_id IS NULL')->fetchColumn();
        (new CreateStructuredStrategyDomain())->up($this->pdo);
        self::assertSame($firstCount, (int) $this->pdo->query('SELECT COUNT(*) FROM strategy_templates WHERE user_id IS NULL')->fetchColumn());

        $templateId = $this->catalogId('call_spread_buy');
        $result = $this->positions->register(1, $this->callSpread($templateId));
        self::assertTrue($result['success'], json_encode($result));
        $position = $result['data'];
        self::assertSame('BULL_CALL_SPREAD', $position['inferred_strategy']);
        self::assertSame('RECOGNIZED', $position['classification_status']);
        self::assertSame('OPEN', $position['status']);
        self::assertSame(-600.0, $position['initial_cash_flow']);
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM operations WHERE structured_position_id IS NOT NULL')->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM structured_position_legs')->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM operation_history WHERE action = 'STRUCTURED_POSITION_OPENED'")->fetchColumn());
        self::assertSame(103.0, $position['payoff']['break_even_points'][0]);
        self::assertSame(1400.0, $position['payoff']['max_profit']);
    }

    public function testRatioAndGeneralCostsAreAllocatedAcrossLegs(): void
    {
        $templates = new StrategyTemplateService($this->pdo, $this->templates);
        $created = $templates->create(1, [
            'name' => 'Fly própria',
            'slots' => [
                ['slot_key' => 'low', 'side' => 'BUY', 'option_type' => 'CALL', 'ratio' => 1],
                ['slot_key' => 'middle', 'side' => 'SELL', 'option_type' => 'CALL', 'ratio' => 2],
                ['slot_key' => 'high', 'side' => 'BUY', 'option_type' => 'CALL', 'ratio' => 1],
            ],
            'constraints' => [['type' => 'strike_order', 'slots' => ['low', 'middle', 'high']]],
        ]);
        self::assertTrue($created['success'], json_encode($created));
        $result = $this->positions->register(1, [
            'strategy_template_id' => $created['data']['id'],
            'asset_id' => 1,
            'base_quantity' => 3,
            'executed_at' => '2026-10-24 10:00:00',
            'general_costs' => 3,
            'legs' => [
                ['slot_key' => 'low', 'option_contract_id' => 1, 'unit_price' => 12, 'costs' => 1],
                ['slot_key' => 'middle', 'option_contract_id' => 2, 'unit_price' => 6, 'costs' => 0],
                ['slot_key' => 'high', 'option_contract_id' => 3, 'unit_price' => 2, 'costs' => 0],
            ],
            'confirm_classification' => true,
        ]);
        self::assertTrue($result['success'], json_encode($result));
        self::assertSame([3, 6, 3], array_column($result['data']['legs'], 'quantity'));
        self::assertSame(4.0, $result['data']['initial_costs']);
        self::assertSame([1.75, 1.5, 0.75], array_column($result['data']['legs'], 'costs'));
        $updated = $templates->update(1, (int) $created['data']['id'], [
            'name' => 'Fly alterada',
            'slots' => $created['data']['slots'],
            'constraints' => $created['data']['constraints'],
        ]);
        self::assertFalse($updated['success']);
        self::assertSame(409, $updated['status']);
    }

    public function testTemplateConstraintAndForeignContractAreRejectedWithoutWrites(): void
    {
        $templateId = $this->catalogId('call_spread_buy');
        $wrongStrikeOrder = $this->positions->register(1, [
            'strategy_template_id' => $templateId,
            'asset_id' => 1,
            'base_quantity' => 1,
            'legs' => [
                ['slot_key' => 'long_call', 'option_contract_id' => 3, 'unit_price' => 1],
                ['slot_key' => 'short_call', 'option_contract_id' => 2, 'unit_price' => 1],
            ],
        ]);
        self::assertFalse($wrongStrikeOrder['success']);
        self::assertArrayHasKey('constraints.0', $wrongStrikeOrder['errors']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());

        $foreignContract = $this->positions->register(1, [
            'strategy_template_id' => $templateId,
            'asset_id' => 1,
            'base_quantity' => 1,
            'legs' => [
                ['slot_key' => 'long_call', 'option_contract_id' => 8, 'unit_price' => 1],
                ['slot_key' => 'short_call', 'option_contract_id' => 8, 'unit_price' => 1],
            ],
        ]);
        self::assertFalse($foreignContract['success']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
    }

    public function testClosingPartOfStructureUsesOperationClosuresAndUpdatesStatus(): void
    {
        $opened = $this->positions->register(1, $this->callSpread($this->catalogId('call_spread_buy')));
        self::assertTrue($opened['success'], json_encode($opened));
        $uuid = $opened['data']['uuid'];
        $closed = $this->positions->close(1, $uuid, [
            'lots' => 1,
            'legs' => [
                ['slot_key' => 'long_call', 'unit_price' => 7, 'costs' => 0],
                ['slot_key' => 'short_call', 'unit_price' => 1, 'costs' => 0],
            ],
        ]);
        self::assertTrue($closed['success'], json_encode($closed));
        self::assertSame('PARTIAL', $closed['data']['status']);
        self::assertSame(300.0, $closed['data']['realized_result']);
        self::assertSame([1, 1], array_column($closed['data']['legs'], 'open_quantity'));
        self::assertSame(700.0, $closed['data']['payoff']['max_profit']);
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM operation_closures')->fetchColumn());
    }

    public function testRolloverClosesAndReopensWithinSamePosition(): void
    {
        $opened = $this->positions->register(1, $this->callSpread($this->catalogId('call_spread_buy')));
        self::assertTrue($opened['success'], json_encode($opened));
        $rolled = $this->positions->roll(1, $opened['data']['uuid'], [
            'lots' => 1,
            'legs' => [
                ['slot_key' => 'long_call', 'option_contract_id' => 5, 'unit_price' => 4, 'costs' => 0],
                ['slot_key' => 'short_call', 'option_contract_id' => 6, 'unit_price' => 1, 'costs' => 0],
            ],
        ]);
        self::assertTrue($rolled['success'], json_encode($rolled));
        self::assertSame('PARTIAL', $rolled['data']['position']['status']);
        self::assertCount(2, $rolled['data']['rollover_ids']);
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM rollovers')->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query("SELECT COUNT(*) FROM structured_position_legs WHERE entry_kind = 'ROLLED'")->fetchColumn());
    }

    public function testExistingPerLegRolloverKeepsTheStructuredPositionLink(): void
    {
        $opened = $this->positions->register(1, $this->callSpread($this->catalogId('call_spread_buy')));
        self::assertTrue($opened['success'], json_encode($opened));
        $leg = $opened['data']['legs'][0];
        $result = (new RolloverService($this->pdo))->register(1, [
            'source_operation_id' => $leg['operation_id'],
            'closing_contract_id' => $leg['option_contract_id'],
            'opening_contract_id' => 5,
            'quantity' => 1,
            'closing_unit_price' => 6,
            'opening_unit_price' => 4,
            'executed_at' => '2026-10-24 11:00:00',
        ]);
        self::assertTrue($result['success'], json_encode($result));
        self::assertSame('PARTIAL', $this->positions->find(1, $opened['data']['uuid'])['status']);
        self::assertSame(1, (int) $this->pdo->query("SELECT COUNT(*) FROM structured_position_legs WHERE entry_kind = 'ROLLED'")->fetchColumn());
    }

    public function testLegacyStrategiesArePreservedAndInvalidJsonNeedsReview(): void
    {
        (new CreateStructuredStrategyDomain())->down($this->pdo);
        $insert = $this->pdo->prepare('INSERT INTO strategies (user_id,name,description,legs) VALUES (1,:name,NULL,:legs)');
        $valid = '[{"side":"BUY","option_type":"CALL","quantity":1}]';
        $invalid = '[{"side":"BUY",';
        $insert->execute(['name' => 'Legada válida', 'legs' => $valid]);
        $insert->execute(['name' => 'Legada inválida', 'legs' => $invalid]);
        (new CreateStructuredStrategyDomain())->up($this->pdo);
        $rows = $this->pdo->query('SELECT * FROM strategy_templates WHERE origin = \'LEGACY\' ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        self::assertCount(2, $rows);
        self::assertSame(0, (int) $rows[0]['needs_review']);
        self::assertSame(1, (int) $rows[1]['needs_review']);
        self::assertSame($invalid, $rows[1]['legacy_legs']);
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM strategy_template_slots WHERE template_id = {$rows[1]['id']}")->fetchColumn());
        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM strategies')->fetchColumn());
    }

    public function testRollbackKeepsOperationsAfterRemovingStructuredDomain(): void
    {
        $opened = $this->positions->register(1, $this->callSpread($this->catalogId('call_spread_buy')));
        self::assertTrue($opened['success'], json_encode($opened));
        (new CreateStructuredStrategyDomain())->down($this->pdo);

        self::assertSame(2, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
        $columns = array_column($this->pdo->query('PRAGMA table_info(operations)')->fetchAll(PDO::FETCH_ASSOC), 'name');
        self::assertNotContains('structured_position_id', $columns);
        self::assertSame(0, (int) $this->pdo->query("SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name='structured_positions'")->fetchColumn());
    }

    public function testInvalidDateNeedsExplicitClassificationConfirmationAndRollbackOnWriteFailure(): void
    {
        $invalidDate = $this->positions->register(1, array_replace($this->callSpread($this->catalogId('call_spread_buy')), [
            'executed_at' => '2026-02-31 12:00:00',
        ]));
        self::assertFalse($invalidDate['success']);
        self::assertArrayHasKey('executed_at', $invalidDate['errors']);

        $input = $this->callSpread($this->catalogId('risk_reversal_bullish'));
        $input['legs'] = [
            ['slot_key' => 'short_put', 'option_contract_id' => 7, 'unit_price' => 1],
            ['slot_key' => 'long_call', 'option_contract_id' => 2, 'unit_price' => 1],
        ];
        $input['strategy_template_id'] = $this->catalogId('risk_reversal_bullish');
        $conflict = $this->positions->register(1, $input);
        self::assertFalse($conflict['success']);
        self::assertSame(409, $conflict['status']);
        self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());

        $this->pdo->exec("CREATE TRIGGER fail_structured_history BEFORE INSERT ON operation_history WHEN NEW.action = 'STRUCTURED_POSITION_OPENED' BEGIN SELECT RAISE(ABORT, 'forced failure'); END");
        try {
            $this->positions->register(1, $this->callSpread($this->catalogId('call_spread_buy')));
            self::fail('Expected the forced history failure to be thrown.');
        } catch (\PDOException) {
            self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM structured_positions')->fetchColumn());
            self::assertSame(0, (int) $this->pdo->query('SELECT COUNT(*) FROM operations')->fetchColumn());
        }
    }

    private function callSpread(int $templateId): array
    {
        return [
            'strategy_template_id' => $templateId,
            'asset_id' => 1,
            'base_quantity' => 2,
            'executed_at' => '2026-10-24 10:00:00',
            'currency' => 'BRL',
            'general_costs' => 0,
            'legs' => [
                ['slot_key' => 'long_call', 'option_contract_id' => 2, 'unit_price' => '5.00'],
                ['slot_key' => 'short_call', 'option_contract_id' => 3, 'unit_price' => '2.00'],
            ],
        ];
    }

    private function catalogId(string $code): int
    {
        $statement = $this->pdo->prepare('SELECT id FROM strategy_templates WHERE code = :code AND user_id IS NULL');
        $statement->execute(['code' => $code]);
        return (int) $statement->fetchColumn();
    }
}
