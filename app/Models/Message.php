<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Message d'une conversation.
 */
final class Message extends Model
{
    protected static string $table = 'messages';

    protected static array $fillable = [
        'public_id', 'conversation_id', 'sender_id', 'body', 'is_read', 'read_at',
    ];
}