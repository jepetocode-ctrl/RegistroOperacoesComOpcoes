<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        [$key, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
        $value = trim($value);
        if (preg_match('/^".*"$/', $value) || preg_match("/^'.*'$/", $value)) {
            $value = substr($value, 1, -1);
        }

        $_ENV[$key] = $value;
        putenv("{$key}={$value}");
        $_SERVER[$key] = $value;
    }
}

$config = require dirname(__DIR__) . '/config/app.php';
$databaseConfig = require dirname(__DIR__) . '/config/database.php';

$router = new App\Support\Router(
    require dirname(__DIR__) . '/routes/web.php',
    require dirname(__DIR__) . '/routes/api.php',
    $config,
    $databaseConfig,
);

$router->dispatch();
