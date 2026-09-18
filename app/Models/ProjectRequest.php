<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Demande « Démarrer un projet » reçue sur le site public.
 *
 * @method static array|null findByPublicId(string $publicId)
 */
final class ProjectRequest extends Model
{
    protected static string $table = 'project_requests';

    protected static array $fillable = [
        'public_id',
        'user_id',
        'intent',
        'project_type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'locality',
        'budget',
        'currency',
        'description',
        'status',
        'handled_by',
        'handled_at',
    ];

    public const INTENTS = ['je_cherche_un_bien', 'jai_un_projet', 'accompagnement'];
    public const STATUSES = ['nouveau', 'qualification', 'converti', 'archive'];
}