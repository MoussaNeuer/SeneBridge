<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\CSRF;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class CsrfMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            if (!CSRF::check($request)) {
                $message = 'Le jeton de sécurité CSRF est invalide ou expiré.';

                if ($request->wantsJson()) {
                    return Response::error($message, 419);
                }

                App::flash('error', $message);

                return Response::redirectBack();
            }
        }

        return $next($request);
    }
}