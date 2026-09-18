<?php

declare(strict_types=1);

namespace App\Middlewares;

use App\Support\Request;
use Closure;

interface MiddlewareContract
{
    /**
     * @return Response|null  null = continue, Response = abort.
     */
    public function handle(Request $request, Closure $next): mixed;
}