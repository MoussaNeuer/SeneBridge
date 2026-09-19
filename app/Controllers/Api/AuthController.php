<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\UserRepository;
use App\Services\ApiTokenService;
use App\Support\App;
use App\Support\Audit;
use App\Support\Hasher;
use App\Support\RateLimiter;
use App\Support\Response;

final class AuthController extends ApiController
{
    private UserRepository $users;
    private ApiTokenService $tokens;

    public function __construct()
    {
        parent::__construct();
        $this->users = new UserRepository();
        $this->tokens = new ApiTokenService();
    }

    /**
     * POST /api/v1/auth/login — échange email+mot de passe contre un token.
     */
    public function login(): Response
    {
        $email = mb_strtolower(trim((string) $this->request->input('email')));
        $password = (string) $this->request->input('password');

        if ($email === '' || $password === '') {
            return $this->fail('Adresse e-mail et mot de passe requis.', 422);
        }

        // Protection brute force : par IP et par compte.
        $limits = (array) config('security.api.login', ['max_attempts' => 5, 'decay_minutes' => 15]);
        $decay = (int) $limits['decay_minutes'] * 60;

        if (!RateLimiter::attempt('api-login:ip:' . $this->request->ip(), (int) $limits['max_attempts'], $decay)
            || !RateLimiter::attempt('api-login:email:' . $email, (int) $limits['max_attempts'], $decay)) {
            return $this->fail('Trop de tentatives. Réessayez plus tard.', 429);
        }

        $user = $this->users->findByEmail($email);

        if ($user === null || !Hasher::verify($password, (string) ($user['password_hash'] ?? ''))) {
            Audit::log('api.auth.login_failed', 'users', $user['id'] ?? null, [], ['email' => $email]);

            return $this->fail('Identifiants invalides.', 401);
        }

        if (($user['status'] ?? 'active') === 'suspended') {
            Audit::log('api.auth.login_suspended', 'users', (int) $user['id'], [], ['email' => $email]);

            return $this->fail('Ce compte est suspendu. Contactez SeneBridge.', 403);
        }

        $issued = $this->tokens->issue((int) $user['id'], 'login');

        if ($issued === null) {
            return $this->fail('Impossible de créer la session API.', 500);
        }

        $this->users->touchLastLogin((int) $user['id']);

        Audit::log('api.auth.login', 'users', (int) $user['id'], [], ['email' => $email]);

        return $this->ok([
            'token_type' => 'Bearer',
            'access_token' => $issued['token'],
            'expires_at' => $issued['expires_at'],
            'user' => $this->userPayloadFor($user),
        ], 'Connecté.');
    }

    /**
     * POST /api/v1/auth/logout — révoque le token courant.
     */
    public function logout(): Response
    {
        $tokenId = App::apiTokenId();

        if ($tokenId !== null) {
            (new ApiTokenService())->revoke($tokenId, $this->id());
        }

        return $this->ok(null, 'Déconnecté.');
    }

    /**
     * GET /api/v1/me
     */
    public function me(): Response
    {
        return $this->ok($this->userPayload());
    }

    private function userPayloadFor(array $user): array
    {
        $saved = $this->authUser;
        $this->authUser = $user;

        try {
            return $this->userPayload();
        } finally {
            $this->authUser = $saved;
        }
    }
}