<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Gate;
use App\Support\Request;
use App\Support\Response;
use Closure;

/**
 * Réserve le back-office (/admin/*) aux rôles personnel.
 * Un client connecté ne peut pas accéder à l'aire admin, quelles que
 * soient les permissions de renseignées en base.
 */
final class AdminAreaMiddleware implements MiddlewareContract
{
    private const STAFF_ROLES = ['admin', 'manager', 'counselor', 'accounting'];

    public function handle(Request $request, Closure $next, mixed $role = null): mixed
    {
        $user = App::user();

        if ($user === null || !Gate::isRole($user, ...self::STAFF_ROLES)) {
            $message = 'Accès réservé au personnel SeneBridge.';

            if ($request->wantsJson()) {
                return Response::error($message, 403);
            }

            App::flash('error', $message);

            return Response::redirect(app_url('dashboard'));
        }

        return $next($request);
    }
}