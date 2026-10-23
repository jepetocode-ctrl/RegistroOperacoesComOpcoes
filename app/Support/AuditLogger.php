<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

final class AuditLogger
{
    public static function log(?int $userId, string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $pdo = Database::defaultConnection();
            $pdo->prepare(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, metadata) VALUES (?, ?, ?, ?, ?)' 
            )->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ]);
        } catch (\Throwable $exception) {
            // Log silencioso para não quebrar fluxos de autenticação e sessão.
        }
    }
}
