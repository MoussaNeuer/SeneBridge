<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Hachage et vérification des mots de passe (Argon2id privilégié).
 */
final class Hasher
{
    public static function make(string $value): string
    {
        $options = config('security.password', []);
        $options['algorithm'] = PASSWORD_ARGON2ID;

        $hash = password_hash($value, PASSWORD_ARGON2ID, $options);

        if ($hash === false) {
            throw new \RuntimeException('Le hachage du mot de passe a échoué.');
        }

        return $hash;
    }

    public static function verify(string $value, string $hash): bool
    {
        if ($hash === '' || $hash === null) {
            return false;
        }

        return (bool) password_verify($value, $hash);
    }

    /**
     * Rehache le mot de passe si l'algorithme cible a changé.
     */
    public static function needsRehash(string $hash): bool
    {
        $options = config('security.password', []);
        unset($options['algorithm']);

        return (bool) password_needs_rehash($hash, PASSWORD_ARGON2ID);
    }
}