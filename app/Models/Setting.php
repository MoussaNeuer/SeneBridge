<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Réglage de la plateforme (clé/valeur typée, édité via le hub Paramètres).
 */
final class Setting extends Model
{
    protected static string $table = 'settings';

    protected static array $fillable = [
        'key',
        'value',
        'type',
        'section',
        'label',
        'description',
        'is_secret',
        'updated_by',
    ];

    public static function findByKey(string $key): ?array
    {
        return self::firstWhere(['key' => $key]);
    }
}