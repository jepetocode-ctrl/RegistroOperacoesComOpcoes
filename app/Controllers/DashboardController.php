<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Support\Response;
use App\Support\SessionManager;

final class DashboardController extends BaseController
{
    public function index(): void
    {
        AuthMiddleware::requireAuthentication();

        $html = $this->view('dashboard/index', [
            'user' => SessionManager::user(),
        ]);

        Response::html($html, 200);
    }
}
