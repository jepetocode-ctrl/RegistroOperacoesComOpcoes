<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

if (file_exists(dirname(__DIR__) . '/.env')) {
    $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
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

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

$projectRoot = dirname(__DIR__);
$databaseConfig = require $projectRoot . '/config/database.php';
$connectionConfig = $databaseConfig['connections'][$databaseConfig['default']] ?? $databaseConfig['connections']['pgsql'];

$dsn = match ($connectionConfig['driver']) {
    'pgsql' => sprintf(
        'pgsql:host=%s;port=%s;dbname=%s;user=%s;password=%s',
        $connectionConfig['host'],
        $connectionConfig['port'],
        $connectionConfig['database'],
        $connectionConfig['username'],
        $connectionConfig['password'],
    ),
    'sqlite' => 'sqlite:' . $connectionConfig['database'],
    default => throw new RuntimeException('Driver de banco não suportado: ' . $connectionConfig['driver']),
};

$pdo = new PDO($dsn);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
if ($connectionConfig['driver'] === 'sqlite') {
    $pdo->exec('PRAGMA foreign_keys = ON');
}

$migrationFiles = glob($projectRoot . '/database/migrations/*.php');
sort($migrationFiles);

foreach ($migrationFiles as $file) {
    $declaredBefore = get_declared_classes();
    require $file;

    $declaredAfter = get_declared_classes();
    $newClasses = array_values(array_diff($declaredAfter, $declaredBefore));
    $className = $newClasses[0] ?? null;

    if ($className === null || !class_exists($className)) {
        throw new RuntimeException('Classe de migração não encontrada em: ' . basename($file));
    }

    $migration = new $className();
    $migration->up($pdo);
    echo 'Migrated: ' . basename($file) . PHP_EOL;
}
