<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class AuthMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (App::user() === null) {
            $message = 'Vous devez être connecté pour accéder à cette page.';

            if ($request->wantsJson()) {
                return Response::error($message, 401);
            }

            return Response::redirect(app_url('login'));
        }

        return $next($request);
    }
}