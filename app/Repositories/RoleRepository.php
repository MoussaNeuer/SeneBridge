<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Database;

/**
 * Accès données liés aux rôles et permissions (RBAC exploitable via l'UI).
 */
final class RoleRepository
{
    /** Rôles système protégés contre la modification/suppression via l'UI. */
    public const SYSTEM_ROLES = [Role::ADMIN, Role::MANAGER, Role::COUNSELOR, Role::ACCOUNTING, Role::CLIENT];

    public function all(): array
    {
        return Database::select(
            'SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id AND u.deleted_at IS NULL) AS user_count
             FROM roles r
             ORDER BY r.is_system DESC, r.label ASC'
        );
    }

    public function find(int $id): ?array
    {
        $role = Role::find($id);

        if ($role === null) {
            return null;
        }

        $role['user_count'] = $this->userCount($id);

        return $role;
    }

    public function findByPublicId(string $publicId): ?array
    {
        $role = Role::findByPublicId($publicId);

        if ($role === null) {
            return null;
        }

        $role['user_count'] = $this->userCount((int) $role['id']);

        return $role;
    }

    public function findByName(string $name): ?array
    {
        return Role::findByName($name);
    }

    public function userCount(int $roleId): int
    {
        return (int) Database::scalar(
            'SELECT COUNT(*) FROM users WHERE role_id = ? AND deleted_at IS NULL',
            [$roleId]
        );
    }

    /**
     * Identifiants des permissions affectées à un rôle.
     *
     * @return array<int, int>
     */
    public function permissionIdsFor(int $roleId): array
    {
        $rows = Database::select(
            'SELECT permission_id FROM role_permissions WHERE role_id = ?',
            [$roleId]
        );

        return array_map(static fn (array $row): int => (int) $row['permission_id'], $rows);
    }

    /**
     * Toutes les permissions, groupées par module (préfixe avant le premier point).
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function permissionGroups(): array
    {
        $rows = Database::select('SELECT * FROM permissions ORDER BY name');
        $groups = [];

        foreach ($rows as $permission) {
            $module = explode('.', (string) $permission['name'], 2)[0] ?: 'autres';
            $groups[$module] ??= [];
            $groups[$module][] = $permission;
        }

        ksort($groups);

        return $groups;
    }

    public function permissionExists(int $permissionId): bool
    {
        return Permission::find($permissionId) !== null;
    }

    public function isSystemRole(array $role): bool
    {
        return (int) ($role['is_system'] ?? 0) === 1
            || in_array($role['name'], self::SYSTEM_ROLES, true);
    }

    /**
     * Crée un rôle (non système) avec ses permissions, dans une transaction.
     *
     * @param array<int, int> $permissionIds
     */
    public function create(string $name, string $label, ?string $description, array $permissionIds): array
    {
        return Database::transaction(function () use ($name, $label, $description, $permissionIds) {
            $role = Role::create([
                'name' => $name,
                'label' => $label,
                'description' => $description,
                'is_system' => 0,
            ]);

            if ($role === null) {
                throw new \RuntimeException('Impossible de créer le rôle.');
            }

            $this->syncPermissions((int) $role['id'], $permissionIds);

            return $role;
        });
    }

    /**
     * Met à jour un rôle (non système) et resynchronise ses permissions.
     *
     * @param array<int, int> $permissionIds
     */
    public function update(int $roleId, string $label, ?string $description, array $permissionIds): void
    {
        Database::transaction(function () use ($roleId, $label, $description, $permissionIds) {
            Role::update($roleId, [
                'label' => $label,
                'description' => $description,
            ]);
            $this->syncPermissions($roleId, $permissionIds);
        });
    }

    /**
     * Supprime un rôle non système sans utilisateurs affectés.
     */
    public function destroy(int $roleId): bool
    {
        return Database::transaction(function () use ($roleId): bool {
            $role = Role::find($roleId);

            if ($role === null || $this->isSystemRole($role)) {
                return false;
            }

            if ($this->userCount($roleId) > 0) {
                return false;
            }

            Database::statement('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

            return Role::delete($roleId);
        });
    }

    /**
     * Affecte un rôle (déjà validé) à un utilisateur.
     */
    public function assignRoleToUser(int $userId, int $roleId): void
    {
        Database::statement(
            'UPDATE users SET role_id = ? WHERE id = ?',
            [$roleId, $userId]
        );
    }

    public function countAdmins(): int
    {
        $admin = Role::findByName(Role::ADMIN);

        if ($admin === null) {
            return 0;
        }

        return $this->userCount((int) $admin['id']);
    }

    /**
     * Remplace les permissions d'un rôle (DELETE + INSERT dans une même transaction).
     *
     * @param array<int, int> $permissionIds
     */
    private function syncPermissions(int $roleId, array $permissionIds): void
    {
        Database::statement('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);

        foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
            if (!$this->permissionExists($permissionId)) {
                continue;
            }

            Database::insert(
                'INSERT INTO role_permissions (role_id, permission_id) VALUES (?, ?)',
                [$roleId, $permissionId]
            );
        }
    }
}