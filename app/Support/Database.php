<?php

declare(strict_types=1);

namespace App\Support;

use PDO;
use PDOException;

final class Database
{
    public static function createConnection(string $driver = 'sqlite', array $config = []): PDO
    {
        $driver = strtolower($driver);

        if ($driver === 'pgsql' && !in_array('pgsql', PDO::getAvailableDrivers(), true)) {
            $driver = 'sqlite';
        }

        if ($driver === 'sqlite') {
            $databasePath = $config['database'] ?? dirname(__DIR__, 2) . '/storage/local.sqlite';
            $directory = dirname($databasePath);

            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new PDOException('Não foi possível criar o diretório do banco local.');
            }

            return new PDO('sqlite:' . $databasePath, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        }

        if ($driver !== 'pgsql') {
            throw new PDOException('Driver de banco não suportado: ' . $driver);
        }

        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s',
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? '5432',
            $config['database'] ?? 'registro_opcoes'
        );

        return new PDO($dsn, $config['username'] ?? 'postgres', $config['password'] ?? 'postgres', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function defaultConnection(): PDO
    {
        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $default = $config['default'] ?? 'sqlite';
        $connectionConfig = $config['connections'][$default] ?? $config['connections']['sqlite'];

        return self::createConnection($default, $connectionConfig);
    }
}
