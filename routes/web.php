<?php

use App\Support\LegacyRouteBridge;
use Illuminate\Support\Facades\Route;

foreach (require __DIR__ . '/legacy_web.php' as [$method, $uri, $handler]) {
    Route::match([$method], $uri, fn () => app(LegacyRouteBridge::class)->dispatch($handler));
}
