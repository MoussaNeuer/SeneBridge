<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Journal d'audit : tracabilité des actions sensibles (jamais de secrets).
 */
final class Audit
{
    public static function log(
        string $action,
        ?string $entityType = null,
        mixed $entityId = null,
        array $oldValues = [],
        array $newValues = [],
        ?int $userId = null
    ): void {
        try {
            Database::statement(
                'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $userId ?? App::id(),
                    $action,
                    $entityType,
                    $entityId !== null ? (string) $entityId : null,
                    $oldValues !== [] ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                    $newValues !== [] ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                    App::request()->ip(),
                    Str::limit(App::request()->userAgent(), 255),
                    date('Y-m-d H:i:s'),
                ]
            );
        } catch (\Throwable $e) {
            // L'audit ne doit jamais faire échouer le flux métier.
            Log::error('Écriture du journal d\'audit impossible', ['error' => $e->getMessage()]);
        }
    }
}