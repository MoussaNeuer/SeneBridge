<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Conversation de messagerie (un fil par dossier projet).
 */
final class Conversation extends Model
{
    protected static string $table = 'conversations';

    protected static array $fillable = [
        'public_id', 'project_id', 'client_id', 'title', 'status', 'created_by',
    ];
}