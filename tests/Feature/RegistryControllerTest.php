<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Controllers\RegistryController;
use PHPUnit\Framework\TestCase;

final class RegistryControllerTest extends TestCase
{
    public function testItNormalizesMalformedStrategyLegPayloadsSafely(): void
    {
        self::assertSame([], RegistryController::normalizeLegsForView('not-json'));
        self::assertSame([], RegistryController::normalizeLegsForView(null));
        self::assertSame([
            ['option_contract_id' => 10, 'side' => 'BUY'],
        ], RegistryController::normalizeLegsForView([
            ['option_contract_id' => 10, 'side' => 'BUY'],
        ]));
    }
}
