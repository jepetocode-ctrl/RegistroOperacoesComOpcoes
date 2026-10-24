<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Repositories\StrategyTemplateRepository;
use App\Repositories\StructuredPositionRepository;
use App\Services\StructuredPositionService;
use App\Support\Database;
use App\Support\Response;
use App\Support\SessionManager;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireAuthentication();

        $user = SessionManager::user();
        $pdo = Database::defaultConnection();
        $templates = new StrategyTemplateRepository($pdo);
        $positions = (new StructuredPositionService(
            $pdo,
            $templates,
            new StructuredPositionRepository($pdo)
        ))->list((int) ($user['id'] ?? 0));
        $html = $this->view('dashboard/index', ['user' => $user, 'structuredPositions' => $positions]);

        Response::html($html, 200);
    }
}
