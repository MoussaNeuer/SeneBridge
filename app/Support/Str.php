<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Utilitaires de chaînes et de génération de jetons.
 */
final class Str
{
    public static function random(int $length = 32): string
    {
        return bin2hex(random_bytes(intdiv($length + 1, 2)));
    }

    /**
     * Jeton opaque adapté au stockage de la version hashée d'un secret envoyé par email.
     */
    public static function token(int $bytes = 32): string
    {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Génère une référence de dossier lisible, ex. SEN-2026-0007.
     */
    public static function projectReference(string $prefix, int $sequence): string
    {
        return sprintf(
            '%s-%d-%04d',
            strtoupper($prefix),
            (int) date('Y'),
            $sequence
        );
    }

    public static function limit(string $value, int $length = 100, string $end = '…'): string
    {
        if (mb_strlen($value) <= $length) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $length - mb_strlen($end))) . $end;
    }

    public static function slug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = str_replace(['’', "'", '"'], '', $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return $value;
    }

    public static function isEmail(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
}