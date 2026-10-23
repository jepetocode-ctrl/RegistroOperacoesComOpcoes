<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\FinancialRegistrationController;
use App\Controllers\HomeController;
use App\Controllers\OperationController;

return [
    ['GET', '/api/status', [HomeController::class, 'apiStatus']],
    ['POST', '/api/auth/login', [AuthController::class, 'apiLogin']],
    ['GET', '/api/auth/me', [AuthController::class, 'apiMe']],
    ['POST', '/api/auth/logout', [AuthController::class, 'apiLogout']],
    ['GET', '/api/assets', [FinancialRegistrationController::class, 'handle']],
    ['POST', '/api/assets', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/assets/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PUT', '/api/assets/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PATCH', '/api/assets/{id}', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/option-contracts', [FinancialRegistrationController::class, 'handle']],
    ['POST', '/api/option-contracts', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/option-contracts/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PUT', '/api/option-contracts/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PATCH', '/api/option-contracts/{id}', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/strategies', [FinancialRegistrationController::class, 'handle']],
    ['POST', '/api/strategies', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/strategies/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PUT', '/api/strategies/{id}', [FinancialRegistrationController::class, 'handle']],
    ['PATCH', '/api/strategies/{id}', [FinancialRegistrationController::class, 'handle']],
    ['GET', '/api/operations', [OperationController::class, 'handle']],
    ['POST', '/api/operations', [OperationController::class, 'handle']],
    ['GET', '/api/operations/{id}/history', [OperationController::class, 'handle']],
    ['POST', '/api/quotes', [OperationController::class, 'handle']],
    ['GET', '/api/positions', [OperationController::class, 'handle']],
    ['GET', '/api/positions/{id}', [OperationController::class, 'handle']],
];
