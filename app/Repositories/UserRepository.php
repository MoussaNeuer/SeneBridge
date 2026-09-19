<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;
use App\Support\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        return User::findByEmail($email);
    }

    public function findById(int $id): ?array
    {
        return User::find($id);
    }

    public function findByPublicId(string $publicId): ?array
    {
        return User::findByPublicId($publicId);
    }

    public function createClient(array $data, string $passwordHash): array
    {
        $clientRole = Role::findByName(Role::CLIENT);

        if ($clientRole === null) {
            throw new \RuntimeException('Le rôle client n\'est pas initialisé (lancez php public/index.php seed).');
        }

        $user = User::create([
            'role_id' => (int) $clientRole['id'],
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => mb_strtolower(trim($data['email'])),
            'phone' => $data['phone'] ?? null,
            'password_hash' => $passwordHash,
            'status' => 'active',
            'locality' => $data['locality'] ?? null,
            'language' => $data['language'] ?? 'fr',
            'timezone' => $data['timezone'] ?? 'Africa/Dakar',
        ]);

        if ($user === null) {
            throw new \RuntimeException('Impossible de créer le compte client.');
        }

        return $user;
    }

    public function emailExists(string $email): bool
    {
        return User::findByEmail(mb_strtolower(trim($email))) !== null;
    }

    public function touchLastLogin(int $id): void
    {
        $user = User::find($id);

        if ($user !== null) {
            User::update($id, ['last_login_at' => date('Y-m-d H:i:s')]);
        }
    }

    public function markEmailVerified(int $id): void
    {
        User::update($id, ['email_verified_at' => date('Y-m-d H:i:s')]);
    }

    public function updatePassword(int $id, string $passwordHash): void
    {
        // session_version : incrémenté pour invalider les autres sessions
        // (celle qui change le mot de passe resynchronise sa propre session).
        Database::statement(
            'UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?',
            [$passwordHash, $id]
        );
    }

    public function updateProfile(int $id, array $data): void
    {
        $values = [
            'first_name' => trim((string) $data['first_name']),
            'last_name' => trim((string) $data['last_name']),
        ];

        if (isset($data['phone'])) {
            $values['phone'] = trim((string) $data['phone']) !== '' ? trim((string) $data['phone']) : null;
        }

        if (isset($data['locality'])) {
            $values['locality'] = trim((string) $data['locality']) !== '' ? trim((string) $data['locality']) : null;
        }

        if (isset($data['language'])) {
            $values['language'] = in_array($data['language'], ['fr', 'en'], true) ? $data['language'] : 'fr';
        }

        User::update($id, $values);
    }

    /**
     * Vérifie qu'un e-mail ou numéro n'est pas déjà utilisé par un autre compte.
     */
    public static function assertUniqueContact(int $exceptUserId, ?string $email = null, ?string $phone = null): void
    {
        if ($phone !== null && trim($phone) !== '') {
            $existing = Database::select(
                'SELECT id FROM users WHERE id != ? AND phone = ? LIMIT 1',
                [$exceptUserId, trim($phone)]
            );
            if ($existing !== []) {
                throw new \RuntimeException('Ce numéro de téléphone est déjà utilisé par un autre compte.');
            }
        }

        if ($email !== null && trim($email) !== '') {
            $existing = Database::select(
                'SELECT id FROM users WHERE id != ? AND email = ? LIMIT 1',
                [$exceptUserId, mb_strtolower(trim($email))]
            );
            if ($existing !== []) {
                throw new \RuntimeException('Cette adresse e-mail est déjà utilisée par un autre compte.');
            }
        }
    }

    public function setStatus(int $id, string $status): void
    {
        User::update($id, ['status' => $status]);
    }

    /**
     * Liste paginée des utilisateurs d'un rôle donné.
     */
    public function paginateByRole(string $roleName, int $perPage = 20, int $page = 1): array
    {
        $role = Role::findByName($roleName);

        if ($role === null) {
            return ['items' => [], 'total' => 0, 'per_page' => $perPage, 'page' => 1, 'last_page' => 1];
        }

        return User::paginate(['role_id' => (int) $role['id']], $perPage, $page, [['created_at', 'DESC']]);
    }

    public function listCounselors(): array
    {
        $role = Role::findByName(Role::COUNSELOR);

        if ($role === null) {
            return [];
        }

        return User::where(
            ['role_id' => (int) $role['id'], 'status' => 'active'],
            [['last_name', 'ASC'], ['first_name', 'ASC']]
        );
    }

    /**
     * Cherche un utilisateur par nom/e-mail (recherche libre back-office).
     */
    public function searchClients(string $term, int $limit = 25): array
    {
        $role = Role::findByName(Role::CLIENT);

        if ($role === null) {
            return [];
        }

        $term = '%' . $term . '%';

        return Database::select(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.role_id = ?
               AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR CONCAT(u.first_name, \' \', u.last_name) LIKE ?)
             ORDER BY u.last_name ASC, u.first_name ASC
             LIMIT ?',
            [(int) $role['id'], $term, $term, $term, $term, $limit]
        );
    }

    public function findByEmailWithRole(string $email): ?array
    {
        $user = User::findByEmail($email);

        if ($user === null) {
            return null;
        }

        $row = User::withRole((int) $user['id']);

        return $row ?? $user;
    }
}