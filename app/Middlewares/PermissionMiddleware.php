<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Gate;
use App\Support\Request;
use App\Support\Response;
use Closure;

/**
 * Vérifie une permission précise sur la route : « permission:<name> ».
 * Ex. routes/admin.php : 'projects.create', 'steps.update'…
 */
final class PermissionMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next, mixed $permission = null): mixed
    {
        if (App::user() === null) {
            return Response::redirect(app_url('login'));
        }

        $required = is_string($permission) ? $permission : '';

        if ($required === '' || Gate::denies($required)) {
            $message = 'Vous n\'avez pas les droits nécessaires pour effectuer cette action.';

            if ($request->wantsJson()) {
                return Response::error($message, 403);
            }

            App::flash('error', $message);

            return Response::redirect(app_url('dashboard'));
        }

        return $next($request);
    }
}