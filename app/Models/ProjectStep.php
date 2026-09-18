<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Étape d'un workflow de projet.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class ProjectStep extends Model
{
    protected static string $table = 'project_steps';

    protected static array $fillable = [
        'project_id',
        'name',
        'description',
        'position',
        'status',
        'responsible_id',
        'due_date',
        'started_at',
        'completed_at',
    ];

    public const STATUSES = ['a_venir', 'en_cours', 'termine', 'bloque'];
}