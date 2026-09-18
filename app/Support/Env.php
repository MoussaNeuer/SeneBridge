<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Chargeur minimal et sécurisé de fichiers .env.
 *
 * - Ne réécrit jamais une variable déjà définie (les vraies variables
 *   d'environnement du système priment).
 * - Supporte valeurs nues, entre guillemets simples/doubles, et commentaires.
 * - Ignore les lignes invalides silencieusement (avec log).
 */
final class Env
{
    private const VALUE_PATTERN = '/^[A-Z0-9_]+(\s*=\s*.*)?$/i';
    private const KEY_PATTERN = '/^[A-Z0-9_]+$/i';

    private static bool $loaded = false;

    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Fichier d\'environnement introuvable : %s', $path));
        }

        foreach (self::parse($path) as $key => $value) {
            // Les variables système existantes ne sont jamais écrasées.
            if (getenv($key) !== false) {
                continue;
            }
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }

        self::$loaded = true;
    }

    /**
     * @return array<string,string>
     */
    private static function parse(string $path): array
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            throw new RuntimeException(sprintf('Impossible de lire le fichier .env : %s', $path));
        }

        $result = [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (!preg_match(self::VALUE_PATTERN, $line)) {
                continue;
            }

            [$rawKey, $rawValue] = array_pad(explode('=', $line, 2), 2, '');

            $key = trim($rawKey);
            $value = trim($rawValue);

            if ($value === '') {
                $result[$key] = '';
                continue;
            }

            if (preg_match('/^"(.*)"$/s', $value, $m) === 1) {
                $value = self::unescape($m[1]);
            } elseif (preg_match("/^'(.*)'$/s", $value, $m) === 1) {
                $value = $m[1];
            } else {
                $value = preg_replace('/\s+#.*$/', '', $value) ?? '';
                $value = trim($value);
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function unescape(string $value): string
    {
        return str_replace(['\\n', '\\r', '\\t', '\\\\', '\\"'], ["\n", "\r", "\t", '\\', '"'], $value);
    }
}