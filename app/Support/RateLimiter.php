<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Rate limiting par clé (IP, email, …) stocké en base.
 * Simple et persistant, adapté au MVP et extensible vers un cache distribué.
 */
final class RateLimiter
{
    private static string $tableName = 'rate_limits';

    private static function ensureTable(): void
    {
        Database::statement(
            "CREATE TABLE IF NOT EXISTS rate_limits (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                `key` VARCHAR(191) NOT NULL,
                attempts INT UNSIGNED NOT NULL DEFAULT 0,
                reset_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY rate_limits_key_unique (`key`),
                KEY rate_limits_reset_at_index (reset_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /**
     * Tente une nouvelle tentative.
     *
     * @return bool true si l'action est autorisée, false si limitée.
     */
    public static function attempt(string $key, int $maxAttempts, int $decaySeconds): bool
    {
        self::ensureTable();

        $now = time();

        return Database::transaction(function () use ($key, $maxAttempts, $decaySeconds, $now) {
            $row = Database::first('SELECT id, attempts, reset_at FROM rate_limits WHERE `key` = ? FOR UPDATE', [$key]);

            if ($row === null) {
                Database::insert(
                    'INSERT INTO rate_limits (`key`, attempts, reset_at, created_at) VALUES (?, 1, ?, NOW())',
                    [$key, date('Y-m-d H:i:s', $now + $decaySeconds)]
                );

                return true;
            }

            $resetAt = strtotime((string) $row['reset_at']);

            if ($resetAt === false || $now >= $resetAt) {
                Database::statement(
                    'UPDATE rate_limits SET attempts = 1, reset_at = ? WHERE id = ?',
                    [date('Y-m-d H:i:s', $now + $decaySeconds), $row['id']]
                );

                return true;
            }

            if ((int) $row['attempts'] >= $maxAttempts) {
                return false;
            }

            Database::statement('UPDATE rate_limits SET attempts = attempts + 1 WHERE id = ?', [$row['id']]);

            return true;
        });
    }

    /**
     * Retourne la clé de limitation par adresse IP.
     */
    public static function ipKey(Request $request): string
    {
        return 'ip:' . $request->ip();
    }
}