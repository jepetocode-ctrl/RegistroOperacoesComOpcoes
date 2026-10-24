<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\FinancialRegistrationRepository;
use App\Services\FinancialRegistrationService;
use App\Services\StrategyLegService;
use PDO;
use PHPUnit\Framework\TestCase;

final class StrategyLegServiceTest extends TestCase
{
    private PDO $pdo;
    private StrategyLegService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec('PRAGMA foreign_keys = ON');
        $this->pdo->exec("CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT NOT NULL UNIQUE, password_hash TEXT NOT NULL)");
        $this->pdo->exec("CREATE TABLE strategies (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, name TEXT NOT NULL, description TEXT NULL, legs TEXT NULL, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE option_contracts (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NOT NULL, asset_id INTEGER NOT NULL, code TEXT NOT NULL, option_type TEXT NOT NULL, strike_price NUMERIC NOT NULL, expiration_date TEXT NOT NULL, contract_multiplier INTEGER NOT NULL DEFAULT 100, status TEXT NOT NULL DEFAULT 'ACTIVE', FOREIGN KEY(user_id) REFERENCES users(id))");
        $this->pdo->exec("CREATE TABLE strategy_legs (id INTEGER PRIMARY KEY AUTOINCREMENT, strategy_id INTEGER NOT NULL, position INTEGER NOT NULL, option_contract_id INTEGER NULL, instrument_type TEXT NOT NULL DEFAULT 'OPTION', option_type TEXT NOT NULL, strike_price NUMERIC NOT NULL, expiration_date TEXT NOT NULL, side TEXT NOT NULL, quantity INTEGER NOT NULL, premium NUMERIC NOT NULL, contract_multiplier INTEGER NOT NULL DEFAULT 100, costs NUMERIC NOT NULL DEFAULT 0, created_at TEXT NULL, updated_at TEXT NULL, UNIQUE(strategy_id, position), FOREIGN KEY(strategy_id) REFERENCES strategies(id) ON DELETE CASCADE)");
        $this->pdo->exec("INSERT INTO users (username, password_hash) VALUES ('owner', 'hash')");
        $this->pdo->exec("INSERT INTO strategies (user_id, name) VALUES (1, 'Long Straddle')");
        $this->service = new StrategyLegService($this->pdo);
    }

    public function testItPersistsAndReturnsStrategyLegsInPositionOrder(): void
    {
        $saved = $this->service->sync(1, 1, [
            [
                'position' => 1,
                'option_type' => 'CALL',
                'side' => 'BUY',
                'strike_price' => 100,
                'expiration_date' => '2027-01-15',
                'quantity' => 2,
                'premium' => 8.5,
                'contract_multiplier' => 100,
                'costs' => 3.5,
            ],
            [
                'position' => 2,
                'option_type' => 'PUT',
                'side' => 'BUY',
                'strike_price' => 100,
                'expiration_date' => '2027-01-15',
                'quantity' => 2,
                'premium' => 7.75,
                'contract_multiplier' => 100,
                'costs' => 0,
            ],
        ]);

        self::assertTrue($saved['success']);
        self::assertCount(2, $saved['data']);
        self::assertSame(1, (int) $saved['data'][0]['position']);
        self::assertSame('CALL', $saved['data'][0]['option_type']);
        self::assertSame(2, (int) $saved['data'][1]['quantity']);

        $loaded = $this->service->listForStrategy(1, 1);
        self::assertCount(2, $loaded);
        self::assertSame(1, (int) $loaded[0]['position']);
        self::assertSame('PUT', $loaded[1]['option_type']);
    }

    public function testFinancialRegistrationServiceSyncsStrategyLegsWhenSavingTheStrategy(): void
    {
        $service = new FinancialRegistrationService(new FinancialRegistrationRepository($this->pdo), $this->pdo);

        $created = $service->save('strategies', 1, [
            'name' => 'Bull Call Spread',
            'description' => 'Spread vertical',
            'legs' => [
                ['option_type' => 'CALL', 'side' => 'BUY', 'strike_price' => 100, 'expiration_date' => '2027-01-15', 'quantity' => 1, 'premium' => 6.5, 'contract_multiplier' => 100, 'costs' => 0],
                ['option_type' => 'CALL', 'side' => 'SELL', 'strike_price' => 110, 'expiration_date' => '2027-01-15', 'quantity' => 1, 'premium' => 2.5, 'contract_multiplier' => 100, 'costs' => 0],
            ],
        ]);

        self::assertTrue($created['success']);
        $rows = $this->pdo->query('SELECT COUNT(*) FROM strategy_legs WHERE strategy_id = ' . (int) $created['data']['id'])->fetchColumn();
        self::assertSame('2', (string) $rows);

        $stored = $this->pdo->query('SELECT option_type, side, position FROM strategy_legs WHERE strategy_id = ' . (int) $created['data']['id'] . ' ORDER BY position ASC')->fetchAll();
        self::assertSame('CALL', $stored[0]['option_type']);
        self::assertSame('BUY', $stored[0]['side']);
        self::assertSame('CALL', $stored[1]['option_type']);
        self::assertSame('SELL', $stored[1]['side']);
    }
}
