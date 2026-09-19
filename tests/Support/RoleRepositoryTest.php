<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Role;
use App\Repositories\RoleRepository;
use App\Support\Database;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * Tests du dépôt de rôles (RBAC) en base réelle.
 * Nettoyage complet via tearDownAfterClass ; aucun impact sur les données dev.
 */
final class RoleRepositoryTest extends TestCase
{
    private const TEST_ROLE = 'role_test_tmp';

    private static ?int $roleId = null;
    private static ?int $tableRoleId = null;
    private static ?int $userId = null;
    private static ?int $tableUserId = null;

    public static function setUpBeforeClass(): void
    {
        $repo = new RoleRepository();

        // Rôle métier temporaire + ses permissions.
        $admin = Role::findByName(Role::ADMIN);
        $client = Role::findByName(Role::CLIENT);
        $tableRolePerm = Database::select('SELECT id FROM permissions ORDER BY id LIMIT 2');

        self::$roleId = (int) $repo->create(
            self::TEST_ROLE,
            'Rôle test',
            'Créé par les tests',
            array_column($tableRolePerm, 'id')
        )['id'];

        // Rôle « table » déjà présent pour tester update/destroy de l'existant.
        $role = $repo->create('role_table_tmp', 'Rôle table', null, []);
        self::$tableRoleId = (int) $role['id'];

        // Utilisateurs éphémères (rôle client) pour tester assignRoleToUser.
        self::$userId = (int) Database::insert(
            'INSERT INTO users (public_id, role_id, first_name, last_name, email, password_hash, status) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (new Ulid())->toBase32(),
                (int) $client['id'],
                'TmpRoleUser',
                'TmpRoleUser',
                'roles-tmp-' . bin2hex(random_bytes(4)) . '@tests.senebridge.local',
                'not-a-real-hash',
                'active',
            ]
        );
        self::$tableUserId = (int) Database::insert(
            'INSERT INTO users (public_id, role_id, first_name, last_name, email, password_hash, status) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                (new Ulid())->toBase32(),
                (int) $client['id'],
                'TmpTableUser',
                'TmpTableUser',
                'roles-table-tmp-' . bin2hex(random_bytes(4)) . '@tests.senebridge.local',
                'not-a-real-hash',
                'active',
            ]
        );

        unset($admin, $client);
    }

    public static function tearDownAfterClass(): void
    {
        Database::statement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ([self::$userId, self::$tableUserId] as $id) {
            if ($id !== null) {
                Database::statement('DELETE FROM users WHERE id = ?', [$id]);
            }
        }
        foreach ([self::$roleId, self::$tableRoleId] as $id) {
            if ($id !== null) {
                Database::statement('DELETE FROM role_permissions WHERE role_id = ?', [$id]);
                Database::statement('DELETE FROM roles WHERE id = ?', [$id]);
            }
        }

        Database::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testCreateFindsAndAssignsPermissions(): void
    {
        $repo = new RoleRepository();
        $role = $repo->find((int) self::$roleId);

        $this->assertNotNull($role);
        $this->assertSame(self::TEST_ROLE, $role['name']);
        $this->assertCount(2, $repo->permissionIdsFor((int) self::$roleId));
    }

    public function testIsSystemRoleProtectsDefaults(): void
    {
        $repo = new RoleRepository();

        foreach ($repo->all() as $role) {
            if ($role['name'] === Role::ADMIN) {
                $this->assertTrue($repo->isSystemRole($role));
                break;
            }
        }

        $this->assertFalse($repo->isSystemRole(
            $repo->find((int) self::$roleId) ?? []
        ));
    }

    public function testUpdateResyncsPermissions(): void
    {
        $repo = new RoleRepository();
        $one = array_column(Database::select('SELECT id FROM permissions ORDER BY id LIMIT 1'), 'id');

        $repo->update((int) self::$roleId, 'Rôle test MAJ', 'Description MAJ', $one);

        $role = $repo->find((int) self::$roleId);
        $this->assertSame('Rôle test MAJ', $role['label']);
        $this->assertSame([(int) $one[0]], $repo->permissionIdsFor((int) self::$roleId));
    }

    public function testPermissionGroupsTopLevelModules(): void
    {
        $groups = (new RoleRepository())->permissionGroups();

        $this->assertNotEmpty($groups);
        $this->assertArrayHasKey('roles', $groups);
    }

    public function testAssignRoleToUserAndCount(): void
    {
        $repo = new RoleRepository();
        $admin = Role::findByName(Role::ADMIN);

        $repo->assignRoleToUser((int) self::$userId, (int) $admin['id']);

        $row = Database::first('SELECT role_id FROM users WHERE id = ?', [self::$userId]);
        $this->assertSame((int) $admin['id'], (int) $row['role_id']);
        $this->assertGreaterThan(0, $repo->countAdmins());
    }

    public function testDestroyBlockedForRoleStillAssigned(): void
    {
        $repo = new RoleRepository();

        // Le rôle « table » n'a aucun utilisateur : suppression autorisée.
        $this->assertTrue($repo->destroy((int) self::$tableRoleId));
        $this->assertNull($repo->find((int) self::$tableRoleId));

        // Le rôle métier est affecté à un utilisateur : suppression refusée.
        $repo->assignRoleToUser((int) self::$tableUserId, (int) self::$roleId);
        $this->assertFalse($repo->destroy((int) self::$roleId));
    }
}