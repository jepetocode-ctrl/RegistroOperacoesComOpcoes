<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Router;
use PHPUnit\Framework\TestCase;

final class BootstrapTest extends TestCase
{
    public function testRouterCanMatchBasicWebRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $router = new Router([
            ['GET', '/', ['App\\Controllers\\HomeController', 'index']],
        ], [], [], []);

        $this->expectOutputRegex('/Registro de Operações com Opções/i');
        $router->dispatch();
    }
}
