<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;

/**
 * Notifications applicatives en base (Phase 11 extensible).
 */
final class Notifier
{
    public const TYPE_STEP = 'etape';
    public const TYPE_DOCUMENT = 'document';
    public const TYPE_INVOICE = 'facture';
    public const TYPE_PAYMENT = 'paiement';
    public const TYPE_MESSAGE = 'message';
    public const TYPE_APPOINTMENT = 'rendez_vous';
    public const TYPE_ALERT = 'alerte';

    public static function push(
        int $userId,
        string $type,
        string $title,
        string $body = '',
        ?int $projectId = null
    ): void {
        Notification::create([
            'user_id' => $userId,
            'project_id' => $projectId,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'is_read' => 0,
        ]);
    }
}