<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Property;
use App\Support\Gate;

/**
 * Politique d'accès aux biens (protection IDOR).
 */
final class PropertyPolicy
{
    /**
     * Consultation : admin/manager toujours ; conseiller/client si rattaché au
     * projet (autorisé), sinon si le bien appartient au client demandeur.
     */
    public static function view(?array $user, ?array $property, ?array $project = null): bool
    {
        if ($user === null || $property === null) {
            return false;
        }

        if (Gate::isRole($user, 'admin', 'manager')) {
            return true;
        }

        if ($project !== null && ProjectPolicy::view($user, $project)) {
            return true;
        }

        return Gate::isRole($user, 'client')
            && (int) $property['owner_client_id'] === (int) $user['id'];
    }

    /**
     * Gestion des biens : role disposant de properties.manage.
     */
    public static function manage(?array $user, ?array $property = null): bool
    {
        return $user !== null && Gate::allows('properties.manage', $user);
    }

    public static function property(?string $publicId): ?array
    {
        return Property::findByPublicId($publicId);
    }
}