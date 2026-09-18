<?php

declare(strict_types=1);

namespace App\Models;

/**
 * @method static array|null findByPublicId(string $publicId)
 */
final class Role extends Model
{
    protected static string $table = 'roles';

    protected static array $fillable = [
        'public_id',
        'name',
        'label',
        'description',
        'is_system',
    ];

    public const ADMIN = 'admin';
    public const MANAGER = 'manager';
    public const COUNSELOR = 'counselor';
    public const ACCOUNTING = 'accounting';
    public const CLIENT = 'client';

    public static function findByName(string $name): ?array
    {
        return self::firstWhere(['name' => $name]);
    }
}