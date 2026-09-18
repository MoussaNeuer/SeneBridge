<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Database;

/**
 * @method static array|null findByPublicId(string $publicId)
 */
final class User extends Model
{
    protected static string $table = 'users';

    protected static array $fillable = [
        'public_id',
        'role_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'password_hash',
        'status',
        'email_verified_at',
        'locality',
        'language',
        'timezone',
        'last_login_at',
    ];

    public static function findByEmail(string $email): ?array
    {
        return self::firstWhere(['email' => $email]);
    }

    public static function findByPhone(string $phone): ?array
    {
        return self::firstWhere(['phone' => $phone]);
    }

    public static function isEmailVerified(array $user): bool
    {
        return !empty($user['email_verified_at']);
    }

    /**
     * Retourne l'utilisateur avec son rôle principal joint.
     *
     * @return array<string, mixed>|null
     */
    public static function withRole(int $id): ?array
    {
        $row = Database::first(
            'SELECT u.*, r.name AS role_name, r.public_id AS role_public_id
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.id = ? LIMIT 1',
            [$id]
        );

        return $row;
    }

    /**
     * Rôle principal de l'utilisateur (nom) ou null.
     */
    public static function roleName(array $user): ?string
    {
        return $user['role_name'] ?? null;
    }
}