<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$defaultDriver = in_array('pgsql', PDO::getAvailableDrivers(), true) ? 'pgsql' : 'sqlite';
$sqlitePath = env('DB_DATABASE', $projectRoot . '/storage/local.sqlite');
if ($sqlitePath === 'registro_opcoes' || $sqlitePath === '') {
    $sqlitePath = $projectRoot . '/storage/local.sqlite';
}

return [
    'default' => env('DB_CONNECTION', $defaultDriver),
    'migrations' => [
        'table' => 'migrations',
        'update_date_on_publish' => true,
    ],
    'connections' => [
        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DB_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'registro_opcoes'),
            'username' => env('DB_USERNAME', 'postgres'),
            'password' => env('DB_PASSWORD', 'postgres'),
            'charset' => 'utf8',
            'schema' => 'public',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => env('DB_SSLMODE', 'prefer'),
        ],
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DB_URL'),
            'database' => $sqlitePath,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'foreign_key_constraints' => true,
            'busy_timeout' => null,
            'journal_mode' => null,
            'synchronous' => null,
        ],
    ],
];
