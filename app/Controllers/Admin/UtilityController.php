<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Support\BackofficeWidgets;
use App\Support\Response;

/**
 * Utilitaires asynchrones du back-office (polling temps réel des badges).
 */
final class UtilityController extends Controller
{
    public function counters(): Response
    {
        return Response::json(BackofficeWidgets::counters());
    }
}