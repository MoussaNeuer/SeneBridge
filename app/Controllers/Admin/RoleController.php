<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;
use App\Support\App;
use App\Support\Audit;
use App\Support\Gate;
use App\Support\Response;
use App\Support\Validator;
use App\Models\Role;

/**
 * Gestion des rôles et de l'affectation des permissions (RBAC via l'UI).
 * Les rôles système (admin, manager, conseiller, comptabilité, client) sont
 * protégés : modification/suppression interdites.
 */
final class RoleController extends Controller
{
    private RoleRepository $roles;
    private UserRepository $users;

    public function __construct()
    {
        parent::__construct();
        $this->roles = new RoleRepository();
        $this->users = new UserRepository();
    }

    public function index(): Response
    {
        return Response::view('admin/roles/index', [
            'user' => App::user(),
            'roles' => $this->roles->all(),
        ]);
    }

    public function create(): Response
    {
        return Response::view('admin/roles/create', [
            'user' => App::user(),
            'groups' => $this->roles->permissionGroups(),
        ]);
    }

    public function store(): Response
    {
        $data = $this->request->only(['name', 'label', 'description', 'permissions']);
        $validation = Validator::make($data, [
            'name' => 'required|string|max:60|regex:/^[a-z][a-z0-9_]*$/',
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $name = trim((string) $data['name']);

        if (in_array($name, RoleRepository::SYSTEM_ROLES, true)) {
            App::flash('error', 'Ce nom correspond à un rôle système, il est réservé.');

            return Response::redirectBack();
        }

        if ($this->roles->findByName($name) !== null) {
            App::flash('error', 'Un rôle avec ce nom existe déjà.');
            App::remember($data);

            return Response::redirectBack();
        }

        $permissionIds = $this->permissionIdsFromRequest($data['permissions'] ?? []);
        $role = $this->roles->create($name, trim((string) $data['label']), $this->nullableString($data['description'] ?? ''), $permissionIds);

        Audit::log('roles.created', 'roles', (int) $role['id'], [], [
            'name' => $name,
            'permissions' => count($permissionIds),
        ]);
        App::flash('success', sprintf('Le rôle « %s » a été créé.', $role['label']));

        return Response::redirect(route('admin.roles'));
    }

    public function edit(string $publicId): Response
    {
        $role = $this->roles->findByPublicId($publicId);

        if ($role === null) {
            return Response::notFound('Rôle introuvable.');
        }

        if ($this->roles->isSystemRole($role)) {
            App::flash('error', 'Les rôles système ne peuvent pas être modifiés.');

            return Response::redirect(route('admin.roles'));
        }

        return Response::view('admin/roles/edit', [
            'user' => App::user(),
            'role' => $role,
            'groups' => $this->roles->permissionGroups(),
            'rolePermissionIds' => $this->roles->permissionIdsFor((int) $role['id']),
        ]);
    }

    public function update(string $publicId): Response
    {
        $role = $this->roles->findByPublicId($publicId);

        if ($role === null) {
            return Response::notFound('Rôle introuvable.');
        }

        if ($this->roles->isSystemRole($role)) {
            App::flash('error', 'Les rôles système ne peuvent pas être modifiés.');

            return Response::redirect(route('admin.roles'));
        }

        $data = $this->request->only(['label', 'description', 'permissions']);
        $validation = Validator::make($data, [
            'label' => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        if (!$validation->passes()) {
            return $this->backWithErrors($validation->errors(), $data);
        }

        $this->roles->update(
            (int) $role['id'],
            trim((string) $data['label']),
            $this->nullableString($data['description'] ?? ''),
            $this->permissionIdsFromRequest($data['permissions'] ?? [])
        );

        Audit::log('roles.updated', 'roles', (int) $role['id'], [], ['name' => (string) $role['name']]);
        App::flash('success', sprintf('Le rôle « %s » a été mis à jour.', trim((string) $data['label'])));

        return Response::redirect(route('admin.roles'));
    }

    public function destroy(string $publicId): Response
    {
        $role = $this->roles->findByPublicId($publicId);

        if ($role === null) {
            return Response::notFound('Rôle introuvable.');
        }

        if ($this->roles->isSystemRole($role)) {
            App::flash('error', 'Les rôles système ne peuvent pas être supprimés.');

            return Response::redirect(route('admin.roles'));
        }

        if ((int) ($role['user_count'] ?? 0) > 0) {
            App::flash('error', 'Impossible de supprimer un rôle encore affecté à des utilisateurs.');

            return Response::redirect(route('admin.roles'));
        }

        $this->roles->destroy((int) $role['id']);
        Audit::log('roles.deleted', 'roles', (int) $role['id'], [], ['name' => (string) $role['name']]);
        App::flash('success', 'Le rôle a été supprimé.');

        return Response::redirect(route('admin.roles'));
    }

    /**
     * POST /admin/utilisateurs/{publicId}/role — change le rôle d'un utilisateur.
     */
    public function assignRole(string $publicId): Response
    {
        $target = $this->users->findByPublicId($publicId);

        if ($target === null) {
            return Response::notFound('Utilisateur introuvable.');
        }

        if ((int) $target['id'] === (int) ($this->authUser['id'] ?? 0)) {
            App::flash('error', 'Vous ne pouvez pas modifier votre propre rôle.');

            return Response::redirectBack();
        }

        $roleId = (int) $this->request->post('role_id', 0);
        $role = $roleId > 0 ? $this->roles->find($roleId) : null;

        if ($role === null) {
            App::flash('error', 'Rôle invalide.');

            return Response::redirectBack();
        }

        // Protège le dernier administrateur : impossible de le rétrograder.
        if (Gate::isRole($target, Role::ADMIN)) {
            if ((string) $role['name'] !== Role::ADMIN && $this->roles->countAdmins() <= 1) {
                App::flash('error', 'Impossible de rétrograder le dernier administrateur.');

                return Response::redirectBack();
            }
        }

        $this->roles->assignRoleToUser((int) $target['id'], (int) $role['id']);
        Audit::log('roles.assigned', 'users', (int) $target['id'], [], [
            'role' => (string) $role['name'],
            'by' => (int) ($this->authUser['id'] ?? 0),
        ]);
        App::flash('success', sprintf(
            'Le rôle de %s %s est maintenant « %s ».',
            trim((string) ($target['first_name'] ?? '')),
            trim((string) ($target['last_name'] ?? '')),
            (string) $role['label']
        ));

        return Response::redirectBack();
    }

    /**
     * @return array<int, int> Identifiants de permissions valides cochés dans le formulaire.
     */
    private function permissionIdsFromRequest(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $ids = [];

        foreach ($raw as $value) {
            $id = (int) $value;
            if ($id > 0 && $this->roles->permissionExists($id)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    private function nullableString(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' && $value !== 'null' ? $value : null;
    }
}