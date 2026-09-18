<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Projet / dossier client.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class Project extends Model
{
    protected static string $table = 'projects';

    protected static array $fillable = [
        'public_id',
        'client_id',
        'reference',
        'name',
        'type',
        'description',
        'locality',
        'status',
        'budget',
        'currency',
        'counselor_id',
        'start_date',
        'expected_end_date',
        'closed_at',
        'archived_at',
    ];

    public const TYPES = ['immobilier', 'gestion_projets', 'import_export', 'auto', 'conciergerie', 'investissement'];
    public const STATUSES = ['en_cours', 'bloque', 'termine', 'archive'];
}