<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Services\ApiTokenService;
use App\Support\App;
use App\Support\RateLimiter;
use App\Support\Request;
use App\Support\Response;
use Closure;

/**
 * Authentification de l'API REST par Bearer Token + rate limiting.
 */
final class ApiMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next, mixed $parameter = null): mixed
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return $this->unauthorized('Jeton d\'accès manquant.');
        }

        $result = (new ApiTokenService())->authenticate($token);

        if ($result === null) {
            return $this->unauthorized('Jeton d\'accès invalide ou expiré.');
        }

        // Rate limiting global par token.
        $limits = (array) config('security.api.general', ['max_attempts' => 300, 'decay_minutes' => 15]);

        if (!RateLimiter::attempt('api:' . (int) $result['token']['id'], (int) $limits['max_attempts'], (int) $limits['decay_minutes'] * 60)) {
            return Response::json([
                'success' => false,
                'data' => null,
                'message' => 'Trop de requêtes, réessayez plus tard.',
                'errors' => [],
            ], 429, ['Retry-After' => (string) $limits['decay_minutes'] * 60]);
        }

        App::setApiTokenId((int) $result['token']['id']);
        App::withApiUser($result['user']);

        return $next($request);
    }

    private function unauthorized(string $message): Response
    {
        return Response::json([
            'success' => false,
            'data' => null,
            'message' => $message,
            'errors' => [],
        ], 401);
    }
}