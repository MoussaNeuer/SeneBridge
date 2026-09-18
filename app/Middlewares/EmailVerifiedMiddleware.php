<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\Request;
use Closure;

final class EmailVerifiedMiddleware implements MiddlewareContract
{
    public function handle(Request $request, Closure $next): mixed
    {
        // Implémenté en Phase 2 — passe directement pour le MVP.
        return $next($request);
    }
}