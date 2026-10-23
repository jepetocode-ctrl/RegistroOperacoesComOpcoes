<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\HomeController;

return [
    ['GET', '/api/status', [HomeController::class, 'apiStatus']],
    ['POST', '/api/auth/login', [AuthController::class, 'apiLogin']],
    ['GET', '/api/auth/me', [AuthController::class, 'apiMe']],
    ['POST', '/api/auth/logout', [AuthController::class, 'apiLogout']],
];
