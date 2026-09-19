<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Token d'accès à l'API REST (seul le hash est conservé).
 */
final class ApiToken extends Model
{
    protected static string $table = 'api_tokens';

    protected static array $fillable = [
        'user_id', 'name', 'token_hash', 'last_used_at', 'expires_at', 'revoked_at',
    ];
}