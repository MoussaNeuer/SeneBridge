<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Role;

/**
 * Moteur d'autorisation (RBAC) : vérifie les permissions du rôle dans la base,
 * de manière centralisée et mise en cache par requête.
 *
 * Ne jamais se fier au front : toute ressource passe par ici (moindre privilège).
 */
final class Gate
{
    /** @var array<int, array<int, string>> */
    private static array $permissionsCache = [];

    /** @var array<int, string> */
    private static array $roleNamesCache = [];

    public static function loggedIn(): bool
    {
        return App::user() !== null;
    }

    /**
     * Permissions portées par le rôle de l'utilisateur.
     *
     * @return array<int, string>
     */
    public static function permissionsFor(?array $user): array
    {
        if ($user === null) {
            return [];
        }

        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId <= 0) {
            return [];
        }

        if (isset(self::$permissionsCache[$roleId])) {
            return self::$permissionsCache[$roleId];
        }

        $rows = Database::select(
            'SELECT p.name
             FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = ?
             ORDER BY p.name',
            [$roleId]
        );

        return self::$permissionsCache[$roleId] = array_map(
            static fn (array $row): string => (string) $row['name'],
            $rows
        );
    }

    public static function allows(string $permission, ?array $user = null): bool
    {
        $user ??= App::user();

        return $user !== null && in_array($permission, self::permissionsFor($user), true);
    }

    public static function denies(string $permission, ?array $user = null): bool
    {
        return !self::allows($permission, $user);
    }

    public static function canAny(array $permissions, ?array $user = null): bool
    {
        foreach ($permissions as $permission) {
            if (self::allows($permission, $user)) {
                return true;
            }
        }

        return false;
    }

    public static function canAll(array $permissions, ?array $user = null): bool
    {
        foreach ($permissions as $permission) {
            if (!self::allows($permission, $user)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Nom du rôle principal de l'utilisateur (cache par identifiant).
     */
    public static function roleName(?array $user): ?string
    {
        if ($user === null) {
            return null;
        }

        if (isset($user['role_name'])) {
            return (string) $user['role_name'];
        }

        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId <= 0) {
            return null;
        }

        if (!isset(self::$roleNamesCache[$roleId])) {
            $role = Role::find($roleId);
            self::$roleNamesCache[$roleId] = (string) ($role['name'] ?? '');
        }

        return self::$roleNamesCache[$roleId] !== '' ? self::$roleNamesCache[$roleId] : null;
    }

    public static function isRole(?array $user, string ...$roles): bool
    {
        $name = self::roleName($user);

        return $name !== null && in_array($name, $roles, true);
    }
}