<?php

declare(strict_types=1);

namespace App\Support;

final class Router
{
    private array $webRoutes;
    private array $apiRoutes;
    private array $config;
    private array $databaseConfig;

    public function __construct(array $webRoutes, array $apiRoutes, array $config, array $databaseConfig)
    {
        $this->webRoutes = $webRoutes;
        $this->apiRoutes = $apiRoutes;
        $this->config = $config;
        $this->databaseConfig = $databaseConfig;
    }

    public function dispatch(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $routeCollection = $this->resolveRouteCollection($uri);

        foreach ($routeCollection as [$routeMethod, $routePath, $handler]) {
            if ($method === $routeMethod && $this->matchPath($uri, $routePath)) {
                $this->callHandler($handler);
                return;
            }
        }

        Response::json([
            'success' => false,
            'message' => 'Rota não encontrada.',
        ], 404);
    }

    private function resolveRouteCollection(string $uri): array
    {
        return str_starts_with($uri, '/api/') ? $this->apiRoutes : $this->webRoutes;
    }

    private function matchPath(string $uri, string $routePath): bool
    {
        if ($uri === $routePath) {
            return true;
        }

        $pattern = preg_replace('#\{([^}]+)\}#', '([^/]+)', $routePath);
        if ($pattern === null) {
            return false;
        }

        return (bool) preg_match('#^' . $pattern . '$#', $uri);
    }

    private function callHandler(mixed $handler): void
    {
        if (is_callable($handler)) {
            $handler();
            return;
        }

        if (!is_array($handler) || count($handler) !== 2) {
            Response::json([
                'success' => false,
                'message' => 'Handler de rota inválido.',
            ], 500);
            return;
        }

        [$controllerName, $method] = $handler;

        if (!class_exists($controllerName)) {
            Response::json([
                'success' => false,
                'message' => 'Controlador não encontrado.',
            ], 500);
            return;
        }

        $controller = new $controllerName();
        if (!method_exists($controller, $method)) {
            Response::json([
                'success' => false,
                'message' => 'Método do controlador não encontrado.',
            ], 500);
            return;
        }

        $controller->$method();
    }
}
