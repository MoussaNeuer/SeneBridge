<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Gate;
use App\Support\Request;
use App\Support\Response;
use Closure;

/**
 * Contrôle le rôle requis sur la route : « role:<nom> ».
 * Ex. routes/admin.php : 'role:admin'.
 */
final class RoleMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next, mixed $role = null): mixed
    {
        $user = App::user();

        if ($user === null) {
            return Response::redirect(app_url('login'));
        }

        $required = is_string($role) ? $role : null;

        if ($required !== null && !Gate::isRole($user, $required)) {
            $message = 'Vous n\'avez pas les droits nécessaires pour accéder à cette ressource.';

            if ($request->wantsJson()) {
                return Response::error($message, 403);
            }

            App::flash('error', $message);

            return Response::redirect(app_url('dashboard'));
        }

        return $next($request);
    }
}