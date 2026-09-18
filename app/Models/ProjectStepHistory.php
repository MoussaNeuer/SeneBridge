<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Historique des changements d'une étape de projet.
 */
final class ProjectStepHistory extends Model
{
    protected static string $table = 'project_step_history';

    protected static array $fillable = [
        'project_step_id',
        'user_id',
        'action',
        'comment',
        'from_status',
        'to_status',
    ];
}