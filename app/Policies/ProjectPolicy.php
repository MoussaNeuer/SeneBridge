<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Support\Gate;

/**
 * Politiques d'accès aux ressources métier (protection IDOR/BOLA).
 *
 * Règle de moindre privilège : être connecté ne suffit jamais ; on vérifie
 * rôle, permission ET rattachement de la ressource à l'utilisateur.
 */
final class ProjectPolicy
{
    /**
     * Consultation : admin/manager, conseiller attribué, ou client propriétaire.
     */
    public static function view(?array $user, ?array $project): bool
    {
        if ($user === null || $project === null) {
            return false;
        }

        if (Gate::isRole($user, 'admin', 'manager')) {
            return true;
        }

        if (Gate::isRole($user, 'counselor')) {
            return (int) $project['counselor_id'] === (int) $user['id'];
        }

        if (Gate::isRole($user, 'client')) {
            return Gate::allows('projects.view', $user)
                && (int) $project['client_id'] === (int) $user['id'];
        }

        return false;
    }

    /**
     * Gestion : admin/manager ; ou conseiller uniquement sur ses projets.
     */
    public static function manage(?array $user, ?array $project = null): bool
    {
        if ($user === null) {
            return false;
        }

        if (Gate::isRole($user, 'admin', 'manager')) {
            return true;
        }

        return Gate::isRole($user, 'counselor')
            && $project !== null
            && (int) $project['counselor_id'] === (int) $user['id'];
    }

    /**
     * Mise à jour des étapes : pas de permission globale sans rattachement.
     */
    public static function updateSteps(?array $user, ?array $project = null): bool
    {
        return Gate::allows('steps.update', $user) && self::manage($user, $project);
    }

    public static function project(?string $publicId): ?array
    {
        return Project::findByPublicId($publicId);
    }
}