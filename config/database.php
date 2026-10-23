<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$defaultDriver = in_array('pgsql', PDO::getAvailableDrivers(), true) ? 'pgsql' : 'sqlite';

return [
    'default' => getenv('DB_CONNECTION') ?: $defaultDriver,
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '5432',
            'database' => getenv('DB_DATABASE') ?: 'registro_opcoes',
            'username' => getenv('DB_USERNAME') ?: 'postgres',
            'password' => getenv('DB_PASSWORD') ?: 'postgres',
            'charset' => 'utf8',
            'schema' => 'public',
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'database' => $projectRoot . '/storage/local.sqlite',
            'charset' => 'utf8',
        ],
    ],
];
