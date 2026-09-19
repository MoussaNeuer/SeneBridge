<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ApiToken;
use App\Support\App;
use App\Support\Audit;
use App\Support\Database;

/**
 * Cycle de vie des tokens API REST (issue, authentification, révocation).
 * Seuls les hash SHA-256 sont stockés en base.
 */
final class ApiTokenService
{
    public const HASH_ALGO = 'sha256';

    /**
     * Génère une paire {token brut, hash} — le brut n'est rendu qu'une fois.
     *
     * @return array{token: string, hash: string}
     */
    public static function generateTokenPair(): array
    {
        $token = bin2hex(random_bytes(32));
        $hash = hash(self::HASH_ALGO, $token);

        return ['token' => $token, 'hash' => $hash];
    }

    /**
     * Crée un token pour un utilisateur.
     *
     * @return array<string, mixed>|null Rendu contenant le jeton brut (une seule fois).
     */
    public function issue(int $userId, string $name = 'api', ?int $ttlDays = null): ?array
    {
        $ttlDays ??= (int) config('security.api.token_ttl_days', 30);
        $raw = bin2hex(random_bytes(32));
        $prefix = (string) config('security.api.token_prefix', '');
        $token = $prefix . $raw;
        $hash = hash(self::HASH_ALGO, $token);
        $expiresAt = $ttlDays > 0 ? date('Y-m-d H:i:s', time() + $ttlDays * 86400) : null;

        $row = ApiToken::create([
            'user_id' => $userId,
            'name' => $name,
            'token_hash' => $hash,
            'expires_at' => $expiresAt,
        ]);

        if ($row === null) {
            return null;
        }

        Audit::log('api_tokens.created', 'api_tokens', (int) $row['id'], [], ['name' => $name, 'user_id' => $userId]);

        return [
            'id' => (int) $row['id'],
            'token' => $token,
            'name' => $name,
            'expires_at' => $expiresAt,
        ];
    }

    /**
     * Valide un jeton brut et retourne le token + l'utilisateur associé.
     *
     * @return array{token: array<string, mixed>, user: array<string, mixed>}|null
     */
    public function authenticate(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        $hash = hash(self::HASH_ALGO, $token);

        $row = Database::first(
            'SELECT * FROM api_tokens WHERE token_hash = ? LIMIT 1',
            [$hash]
        );

        if ($row === null || ($row['revoked_at'] ?? null) !== null) {
            return null;
        }

        $expiresAt = $row['expires_at'] ?? null;
        if ($expiresAt !== null && strtotime((string) $expiresAt) < time()) {
            return null;
        }

        $user = Database::first('SELECT * FROM users WHERE id = ? LIMIT 1', [(int) $row['user_id']]);

        if ($user === null || ($user['status'] ?? 'active') === 'suspended') {
            return null;
        }

        $this->touch((int) $row['id']);

        return ['token' => $row, 'user' => $user];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForUser(int $userId): array
    {
        return Database::select(
            'SELECT id, name, last_used_at, expires_at, revoked_at, created_at
             FROM api_tokens WHERE user_id = ? ORDER BY id DESC',
            [$userId]
        );
    }

    public function revoke(int $tokenId, int $userId): bool
    {
        $updated = Database::statement(
            'UPDATE api_tokens SET revoked_at = ? WHERE id = ? AND user_id = ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s'), $tokenId, $userId]
        ) > 0;

        if ($updated) {
            Audit::log('api_tokens.revoked', 'api_tokens', $tokenId, [], ['user_id' => $userId]);
        }

        return $updated;
    }

    public function revokeAllForUser(int $userId): int
    {
        $revoked = Database::statement(
            'UPDATE api_tokens SET revoked_at = ? WHERE user_id = ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s'), $userId]
        );

        if ($revoked > 0) {
            Audit::log('api_tokens.revoked_all', 'api_tokens', null, [], ['user_id' => $userId]);
        }

        return $revoked;
    }

    /**
     * Baisse de sécurité sur le changement de mot de passe.
     */
    public function revokeForUser(int $userId, ?int $exceptTokenId = null): int
    {
        if ($exceptTokenId === null) {
            return $this->revokeAllForUser($userId);
        }

        $revoked = Database::statement(
            'UPDATE api_tokens SET revoked_at = ?
             WHERE user_id = ? AND id != ? AND revoked_at IS NULL',
            [date('Y-m-d H:i:s'), $userId, $exceptTokenId]
        );

        return $revoked;
    }

    private function touch(int $tokenId): void
    {
        Database::statement(
            'UPDATE api_tokens SET last_used_at = ? WHERE id = ?',
            [date('Y-m-d H:i:s'), $tokenId]
        );
    }
}