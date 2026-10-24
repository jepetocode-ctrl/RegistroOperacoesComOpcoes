<?php

use App\Controllers\StrategyLegController;
use App\Support\LegacyRouteBridge;
use Illuminate\Support\Facades\Route;

Route::get('/strategies/{strategy}/legs', [StrategyLegController::class, 'index']);
Route::post('/strategies/{strategy}/legs', [StrategyLegController::class, 'store']);

foreach (require __DIR__ . '/legacy_api.php' as [$method, $uri, $handler]) {
    $uri = preg_replace('#^/api#', '', $uri) ?: '/';
    Route::match([$method], $uri, fn () => app(LegacyRouteBridge::class)->dispatch($handler));
}
