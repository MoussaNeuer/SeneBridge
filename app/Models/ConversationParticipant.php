<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Rattachment d'un utilisateur à une conversation (suivi de lecture).
 */
final class ConversationParticipant extends Model
{
    protected static string $table = 'conversation_participants';

    protected static array $fillable = [
        'conversation_id', 'user_id', 'last_read_at',
    ];

    public static function touchRead(int $conversationId, int $userId): bool
    {
        return self::update(
            (int) (self::firstWhere(['conversation_id' => $conversationId, 'user_id' => $userId])['id'] ?? 0),
            ['last_read_at' => date('Y-m-d H:i:s')]
        );
    }
}