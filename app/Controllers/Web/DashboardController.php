<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Support\App;
use App\Support\Response;

final class DashboardController extends \App\Controllers\Controller
{
    public function index(): Response
    {
        $user = App::user();

        if ($user === null) {
            return Response::redirect(app_url('login'));
        }

        // Bloc 1 : l'espace client complet (projets, timeline, médias) arrive en Phase 5-7.
        // Le dashboard expose ici l'état authentifié et les prochaines étapes.
        return Response::view('client/dashboard', [
            'user' => $user,
        ]);
    }
}