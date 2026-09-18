<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Bien immobilier / autre rattaché à un projet.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class Property extends Model
{
    protected static string $table = 'properties';

    protected static array $fillable = [
        'public_id',
        'project_id',
        'owner_client_id',
        'type',
        'name',
        'description',
        'features',
        'locality',
        'price',
        'currency',
        'status',
    ];

    public const TYPES = ['terrain', 'appartement', 'maison', 'villa', 'local_commercial', 'autre'];
    public const STATUSES = ['disponible', 'sous_offre', 'reserve', 'vendu', 'loue', 'archive'];
}