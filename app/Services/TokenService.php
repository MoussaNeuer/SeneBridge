<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\Database;
use App\Support\Str;

/**
 * Gestion des jetons temporaires (vérification e-mail, réinitialisation).
 * Seul le hash du jeton est stocké (jamais le jeton brut).
 */
final class TokenService
{
    /**
     * Émet un jeton pour une table donnée. Retourne le jeton brut à envoyer.
     */
    public static function issue(string $table, int $userId, int $lifetimeMinutes): string
    {
        $raw = Str::token(32);
        $hash = hash('sha256', $raw);

        // On invalide les jetons précédents du même type/utilisateur.
        Database::statement(
            "UPDATE `{$table}` SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL AND expires_at > NOW()",
            [$userId]
        );

        Database::insert(
            "INSERT INTO `{$table}` (user_id, token_hash, expires_at, created_at) VALUES (?, ?, ?, ?)",
            [$userId, $hash, date('Y-m-d H:i:s', time() + $lifetimeMinutes * 60), date('Y-m-d H:i:s')]
        );

        return $raw;
    }

    /**
     * Consomme un jeton : retourne l'utilisateur si valide et non utilisé, sinon null.
     */
    public static function consume(string $table, string $raw, ?string $expectedEmail = null): ?int
    {
        $hash = hash('sha256', $raw);

        $row = Database::first(
            "SELECT t.user_id, u.email
             FROM `{$table}` t
             JOIN users u ON u.id = t.user_id
             WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()
             LIMIT 1",
            [$hash]
        );

        if ($row === null) {
            return null;
        }

        if ($expectedEmail !== null && mb_strtolower($row['email']) !== mb_strtolower($expectedEmail)) {
            return null;
        }

        Database::statement("UPDATE `{$table}` SET used_at = NOW() WHERE user_id = ? AND token_hash = ?", [
            (int) $row['user_id'],
            $hash,
        ]);

        return (int) $row['user_id'];
    }

    public static function isValid(string $table, string $raw): bool
    {
        $hash = hash('sha256', $raw);

        return Database::first(
            "SELECT id FROM `{$table}` WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW() LIMIT 1",
            [$hash]
        ) !== null;
    }
}