<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Notification applicative destinée à un utilisateur.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class Notification extends Model
{
    protected static string $table = 'notifications';

    protected static array $fillable = [
        'public_id',
        'user_id',
        'project_id',
        'type',
        'title',
        'body',
        'is_read',
        'read_at',
    ];

    public static function listFor(int $userId, int $limit = 20, bool $unreadOnly = false): array
    {
        return self::where(
            ['user_id' => $userId] + ($unreadOnly ? ['is_read' => 0] : []),
            [['created_at', 'DESC']],
            $limit
        );
    }

    public static function countUnread(int $userId): int
    {
        return self::count(['user_id' => $userId, 'is_read' => 0]);
    }

    public static function markRead(int $id): bool
    {
        return self::update($id, [
            'is_read' => 1,
            'read_at' => date('Y-m-d H:i:s'),
        ]);
    }
}