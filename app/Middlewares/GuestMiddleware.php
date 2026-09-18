<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\App;
use App\Support\Request;
use App\Support\Response;
use Closure;

final class GuestMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next): mixed
    {
        if (App::user() !== null) {
            return Response::redirect(app_url('dashboard'));
        }

        return $next($request);
    }
}