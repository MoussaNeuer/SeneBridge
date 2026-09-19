<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;
use App\Support\Config;
use App\Support\Database;

/**
 * Dépôt des réglages vivants de la plateforme (table `settings`, clé/valeur typée).
 * Chargé une fois par requête (cache statique) et appliqué au runtime via Config::set.
 */
final class SettingsService
{
    public const TYPES = ['string', 'int', 'bool', 'email', 'url', 'json', 'secret', 'timezone'];

    /** @var array<string, array<string, mixed>>|null */
    private static ?array $rows = null;

    /**
     * @return array<string, array<string, mixed>> clé => ligne complète
     */
    public static function rows(bool $refresh = false): array
    {
        if (self::$rows === null || $refresh) {
            $indexed = [];

            foreach (Database::select('SELECT * FROM settings') ?: [] as $row) {
                $indexed[(string) $row['key']] = $row;
            }

            self::$rows = $indexed;
        }

        return self::$rows;
    }

    /**
     * Valeur typée d'un réglage (défaut si absent).
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            $rows = self::rows();
        } catch (\Throwable $e) {
            return $default;
        }

        $row = $rows[$key] ?? null;

        if ($row === null) {
            return $default;
        }

        return self::decode($row['value'] ?? '', (string) ($row['type'] ?? 'string'), $default);
    }

    /**
     * Tous les réglages en valeurs typées.
     *
     * @return array<string, mixed>
     */
    public static function all(): array
    {
        $out = [];

        foreach (self::rows() as $key => $row) {
            $out[$key] = self::decode($row['value'] ?? '', (string) ($row['type'] ?? 'string'), null);
        }

        return $out;
    }

    /**
     * Réglages groupés par section pour l'affichage du hub Paramètres.
     *
     * @return array<string, array<string, array<string, mixed>>>
     */
    public static function grouped(): array
    {
        $groups = [];

        foreach (self::rows() as $key => $row) {
            $section = (string) ($row['section'] ?? 'general');
            $groups[$section][$key] = $row;
        }

        return $groups;
    }

    /**
     * Création ou mise à jour d'un réglage (upsert sur la clé unique).
     */
    public static function set(string $key, mixed $value, string $type = 'string', array $meta = [], ?int $userId = null): bool
    {
        $row = Setting::findByKey($key);

        $data = [
            'key' => $key,
            'value' => self::encode($value, $type),
            'type' => in_array($type, self::TYPES, true) ? $type : 'string',
            'section' => (string) ($meta['section'] ?? $row['section'] ?? 'general'),
            'label' => $meta['label'] ?? $row['label'] ?? $key,
            'description' => $meta['description'] ?? $row['description'] ?? null,
            'is_secret' => $type === 'secret' ? 1 : (int) ($meta['is_secret'] ?? $row['is_secret'] ?? 0),
            'updated_by' => $userId ?? null,
        ];

        if ($row !== null) {
            $data['key'] = (string) $row['key'];
            Setting::update((int) $row['id'], $data);
        } else {
            Setting::create($data);
        }

        self::$rows = null;

        return true;
    }

    public static function delete(string $key): bool
    {
        $row = Setting::findByKey($key);

        if ($row === null) {
            self::$rows = null;

            return true;
        }

        $deleted = Setting::delete((int) $row['id']);
        self::$rows = null;

        return $deleted;
    }

    /**
     * Applique les réglages vivants à la configuration (lancé au démarrage).
     * Ne remplace jamais une valeur config absente si le réglage est vide.
     */
    public static function applyToConfig(): void
    {
        try {
            $rows = self::rows();
        } catch (\Throwable $e) {
            return;
        }

        $has = static function (string $key) use ($rows): bool { return isset($rows[$key]) && (string) $rows[$key]['value'] !== ''; };
        $str = static fn (string $key): string => (string) ($rows[$key]['value'] ?? '');

        if ($has('site.name')) {
            Config::set('app.name', $str('site.name'));
        }

        if ($has('site.tagline')) {
            Config::set('app.tagline', $str('site.tagline'));
        }

        if ($has('site.timezone') && in_array($str('site.timezone'), timezone_identifiers_list(), true)) {
            Config::set('app.timezone', $str('site.timezone'));
        }

        if ($has('mail.from_address')) {
            Config::set('mail.from.address', $str('mail.from_address'));
        }

        if ($has('mail.from_name')) {
            Config::set('mail.from.name', $str('mail.from_name'));
        }

        $smtpEnabled = isset($rows['mail.smtp_enabled']) && $rows['mail.smtp_enabled']['value'] === '1';

        if (isset($rows['mail.smtp_enabled'])) {
            Config::set('mail.default', $smtpEnabled ? 'smtp' : 'log');
        }

        if ($smtpEnabled) {
            foreach ([
                'mail.mailers.smtp.host' => 'mail.smtp_host',
                'mail.mailers.smtp.port' => 'mail.smtp_port',
                'mail.mailers.smtp.username' => 'mail.smtp_user',
                'mail.mailers.smtp.password' => 'mail.smtp_pass',
                'mail.mailers.smtp.encryption' => 'mail.smtp_encryption',
            ] as $configKey => $settingKey) {
                $value = (string) ($rows[$settingKey]['value'] ?? '');
                if ($value === 'null') {
                    $value = '';
                }

                Config::set($configKey, $configKey === 'mail.mailers.smtp.port' ? (int) $value : $value);
            }
            Config::set('mail.mailers.smtp.auth', true);
            Config::set('mail.mailers.smtp.timeout', (int) config('mail.mailers.smtp.timeout', 15));
        }
    }

    /**
     * Conversion « stockage » (string canonique).
     */
    private static function encode(mixed $value, string $type): string
    {
        return match ($type) {
            'bool' => $value ? '1' : '0',
            'json' => is_string($value) ? $value : (string) json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            default => (string) $value,
        };
    }

    /**
     * Conversion « lecture » (typée).
     */
    private static function decode(string $value, string $type, mixed $default): mixed
    {
        return match ($type) {
            'bool' => $value === '1' ? true : ($value === '' ? $default : false),
            'int' => $value === '' ? $default : (int) $value,
            'json' => $value === '' ? $default : (json_decode($value, true) ?? $default),
            default => $value,
        };
    }
}