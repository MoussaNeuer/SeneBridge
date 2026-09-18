<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Support\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        return Response::view('public/home', [
            'services' => [
                ['slug' => 'immobilier', 'title' => 'Immobilier', 'description' => 'Achat, vente et accompagnement de vos projets immobiliers au Sénégal.'],
                ['slug' => 'gestion-projets', 'title' => 'Gestion de projets', 'description' => 'Pilotage de vos dossiers de A à Z, avec un suivi transparent.'],
                ['slug' => 'import-export', 'title' => 'Import / Export', 'description' => 'Facilitation de vos échanges commerciaux internationaux.'],
                ['slug' => 'auto', 'title' => 'SeneBridge Auto', 'description' => 'Achat et importation de véhicules en toute confiance.'],
                ['slug' => 'conciergerie', 'title' => 'Conciergerie', 'description' => 'Services dédiés pour les résidents et la diaspora.'],
                ['slug' => 'investissement', 'title' => 'Investissement', 'description' => 'Accompagnement de vos investissements au Sénégal.'],
            ],
        ]);
    }
}