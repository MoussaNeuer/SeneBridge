<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Dépôt de configuration centralisé, chargé une seule fois (immutable à chaud).
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    private static bool $loaded = false;

    public static function load(string $configDir): void
    {
        if (self::$loaded) {
            return;
        }

        $files = glob(rtrim($configDir, '/\\') . '/*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            $key = pathinfo($file, PATHINFO_FILENAME);
            self::$items[$key] = require $file;
        }

        self::$loaded = true;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$items;

        foreach (explode('.', $key) as $segment) {
            if (is_array($value) && array_key_exists($segment, $value)) {
                $value = $value[$segment];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Écriture d'une valeur à chaud (utilisée par le hub Paramètres pour
     * appliquer les réglages vivants sans redéploiement).
     *
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        $segments = explode('.', $key);
        $target = &self::$items;

        foreach ($segments as $segment) {
            if (!is_array($target)) {
                $target = [];
            }

            if (!isset($target[$segment]) || !is_array($target[$segment])) {
                $target[$segment] = [];
            }

            $target = &$target[$segment];
        }

        $target = $value;
        unset($target);
    }
}