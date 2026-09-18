<?php

declare(strict_types=1);

namespace App\Controllers\Web;

use App\Controllers\Controller;
use App\Repositories\ArticleRepository;
use App\Support\Response;

final class PageController extends Controller
{
    private ArticleRepository $articles;

    public function __construct()
    {
        parent::__construct();
        $this->articles = new ArticleRepository();
    }

    public function services(): Response
    {
        $services = [
            ['slug' => 'immobilier', 'title' => 'Immobilier', 'description' => 'Achat, vente, location et gestion de biens. Votre conseiller suit le dossier à chaque étape, de la recherche à la remise des clés.', 'icon' => '🏠'],
            ['slug' => 'gestion-projets', 'title' => 'Gestion de projets', 'description' => 'Pilotage de vos dossiers de A à Z : planification, suivi des étapes, livrables et reporting transparent.', 'icon' => '📋'],
            ['slug' => 'import-export', 'title' => 'Import / Export', 'description' => 'Facilitation de vos échanges commerciaux : formalités, transit et logistique avec des partenaires fiables.', 'icon' => '🚢'],
            ['slug' => 'auto', 'title' => 'SeneBridge Auto', 'description' => 'Achat et importation de véhicules en toute confiance : sourcing, inspection et livraison.', 'icon' => '🚗'],
            ['slug' => 'conciergerie', 'title' => 'Conciergerie', 'description' => 'Services dédiés aux résidents et à la diaspora : assistance, entretien et démarches locales.', 'icon' => '🛎️'],
            ['slug' => 'investissement', 'title' => 'Investissement', 'description' => 'Accompagnement de vos investissements au Sénégal : étude, structuration et suivi.', 'icon' => '📈'],
        ];

        return Response::view('public/services', ['services' => $services]);
    }

    public function about(): Response
    {
        $values = [
            ['title' => 'Transparence', 'description' => 'Chaque dossier est suivi en temps réel : vous savez exactement où en est votre projet.'],
            ['title' => 'Sécurité', 'description' => 'Vos documents et informations sont protégés et accessibles uniquement par vous et vos conseillers.'],
            ['title' => 'Proximité', 'description' => 'Une équipe locale au Sénégal et une interface pensée pour la diaspora.'],
            ['title' => 'Accompagnement', 'description' => 'Un conseiller dédié sur toute la durée de votre dossier.'],
        ];

        return Response::view('public/about', ['values' => $values]);
    }

    public function news(): Response
    {
        return Response::view('public/news', [
            'articles' => $this->articles->publishedPublic(),
        ]);
    }

    public function newsShow(string $slug): Response
    {
        $article = $this->articles->findPublishedBySlug($slug);

        if ($article === null) {
            return Response::notFound('Cette actualité n\'existe pas ou n\'est pas publiée.');
        }

        return Response::view('public/news-show', [
            'article' => $article,
            'others' => $this->articles->publishedPublic(3, (int) $article['id']),
        ]);
    }
}