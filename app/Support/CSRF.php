<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Protection CSRF : jeton généré par session, vérifié côté serveur.
 */
final class CSRF
{
    public const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        $name = config('security.csrf.token_name', '_token');
        $token = App::getSession($name);

        if (!is_string($token) || $token === '') {
            $token = Str::token(config('security.csrf.token_length_bytes', 32));
            App::setSession($name, $token);
        }

        return $token;
    }

    public static function field(): string
    {
        $name = config('security.csrf.token_name', '_token');

        return sprintf('<input type="hidden" name="%s" value="%s">', $name, e(self::token()));
    }

    /**
     * Vérifie le jeton fourni (champ formulaire ou en-tête).
     */
    public static function verify(?string $token): bool
    {
        $expected = self::token();

        return is_string($token) && $token !== '' && hash_equals($expected, $token);
    }

    /**
     * Vérifie le jeton à partir de la requête courante.
     */
    public static function check(Request $request): bool
    {
        $name = config('security.csrf.token_name', '_token');
        $header = config('security.csrf.header_name', 'X-CSRF-Token');

        $token = $request->post($name);

        if (!is_string($token) || $token === '') {
            $token = $request->header($header);
        }

        return self::verify(is_string($token) ? $token : null);
    }
}