<?php

declare(strict_types=1);

namespace App\Models;

/**
 * @method static array|null findByPublicId(string $publicId)
 */
final class Permission extends Model
{
    protected static string $table = 'permissions';

    protected static array $fillable = [
        'public_id',
        'name',
        'label',
        'description',
    ];

    public static function findByName(string $name): ?array
    {
        return self::firstWhere(['name' => $name]);
    }
}