<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\HomeController;
use App\Controllers\UserManagementController;

return [
    ['GET', '/', [HomeController::class, 'index']],
    ['GET', '/health', [HomeController::class, 'health']],
    ['GET', '/login', [AuthController::class, 'loginPage']],
    ['POST', '/login', [AuthController::class, 'login']],
    ['POST', '/logout', [AuthController::class, 'logout']],
    ['GET', '/change-password', [AuthController::class, 'changePasswordPage']],
    ['POST', '/change-password', [AuthController::class, 'changePassword']],
    ['GET', '/dashboard', [DashboardController::class, 'index']],
    ['GET', '/admin/users', [UserManagementController::class, 'index']],
    ['POST', '/admin/users', [UserManagementController::class, 'store']],
    ['POST', '/admin/users/{id}/permissions', [UserManagementController::class, 'updatePermissions']],
];
