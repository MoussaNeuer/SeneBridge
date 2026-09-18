<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Request;
use App\Support\Response;
use Closure;

/**
 * Contrôle le rôle requis au niveau route (paramètre dynamique `{role}` ou valeur fixe).
 */
final class RoleMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next): mixed
    {
        $user = App::user();

        if ($user === null) {
            return Response::redirect(app_url('login'));
        }

        // Rôle requis passé par le Router via un argument de route nommé
        // ou extrait du path en dernier paramètre segmenté (ex: /admin/{role}).
        $requiredRole = $request->input('__role')
            ?? $request->query('role')
            ?? null;

        if ($requiredRole !== null && is_string($requiredRole)) {
            $userRoles = $user['roles'] ?? [];

            if (!in_array($requiredRole, $userRoles, true)) {
                $message = 'Vous n\'avez pas les droits nécessaires pour accéder à cette ressource.';

                if ($request->wantsJson()) {
                    return Response::error($message, 403);
                }

                App::flash('error', $message);

                return Response::redirect(app_url('dashboard'));
            }
        }

        return $next($request);
    }
}