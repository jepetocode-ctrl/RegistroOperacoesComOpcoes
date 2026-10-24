<?php

declare(strict_types=1);

namespace App\Database\LegacyMigrations;

use PDO;

final class AddUserPermissions
{
    public function up(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $columns = $pdo->query('PRAGMA table_info(users)')->fetchAll(PDO::FETCH_ASSOC);
            if (!in_array('permissions', array_column($columns, 'name'), true)) {
                $pdo->exec("ALTER TABLE users ADD COLUMN permissions TEXT NOT NULL DEFAULT '[]'");
            }
            return;
        }

        $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS permissions TEXT NOT NULL DEFAULT '[]'");
    }

    public function down(PDO $pdo): void
    {
        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'sqlite') {
            $pdo->exec('ALTER TABLE users DROP COLUMN IF EXISTS permissions');
        }
    }
}