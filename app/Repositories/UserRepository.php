<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Role;
use App\Models\User;

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
        User::update($id, ['password_hash' => $passwordHash]);
    }

    public function setStatus(int $id, string $status): void
    {
        User::update($id, ['status' => $status]);
    }
}