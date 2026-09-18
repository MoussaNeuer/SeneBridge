<?php

declare(strict_types=1);

use App\Controllers\Admin\ArticleController;
use App\Controllers\Admin\ClientController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\CounselorController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProjectController;
use App\Controllers\Admin\PropertyController;
use App\Controllers\Admin\RequestController;
use App\Support\Router;

// ---- Back-office (personnel : admin, manager, conseillers) ------------------
Router::group(['middleware' => ['auth']], function () {
    // Tableau de bord
    Router::get('/admin', [DashboardController::class, 'index'], 'admin.dashboard', ['permission:admin.access']);

    // Clients (comptes)
    Router::get('/admin/clients', [ClientController::class, 'index'], 'admin.clients', ['permission:users.view']);
    Router::get('/admin/clients/{publicId:[A-Za-z0-9\-]+}', [ClientController::class, 'show'], 'admin.clients.show', ['permission:users.view']);
    Router::get('/admin/clients/{publicId:[A-Za-z0-9\-]+}/projets', [ProjectController::class, 'create'], 'admin.clients.projects.create', ['permission:projects.create']);

    // Conseillers
    Router::get('/admin/conseillers', [CounselorController::class, 'index'], 'admin.counselors', ['permission:users.view']);
    Router::post('/admin/conseillers', [CounselorController::class, 'store'], 'admin.counselors.store', ['permission:users.assign_counselor', 'csrf']);
    Router::post('/admin/conseillers/{publicId:[A-Za-z0-9\-]+}/statut', [CounselorController::class, 'status'], 'admin.counselors.status', ['permission:users.assign_counselor', 'csrf']);

    // Projets (dossiers)
    Router::get('/admin/projets', [ProjectController::class, 'index'], 'admin.projects', ['permission:projects.view']);
    Router::get('/admin/projets/nouveau', [ProjectController::class, 'create'], 'admin.projects.create', ['permission:projects.create']);
    Router::post('/admin/projets', [ProjectController::class, 'store'], 'admin.projects.store', ['permission:projects.create', 'csrf']);
    Router::get('/admin/projets/{publicId:[A-Za-z0-9\-]+}', [ProjectController::class, 'show'], 'admin.projects.show', ['permission:projects.view']);
    Router::get('/admin/projets/{publicId:[A-Za-z0-9\-]+}/modifier', [ProjectController::class, 'edit'], 'admin.projects.edit', ['permission:projects.update']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/modifier', [ProjectController::class, 'update'], 'admin.projects.update', ['permission:projects.update', 'csrf']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/statut', [ProjectController::class, 'status'], 'admin.projects.status', ['permission:projects.close', 'csrf']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/conseiller', [ProjectController::class, 'counselor'], 'admin.projects.counselor', ['permission:users.assign_counselor', 'csrf']);

    // Workflow : étapes d'un projet
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/etapes/mettre-a-jour', [ProjectController::class, 'stepTransition'], 'admin.projects.steps.transition', ['permission:steps.update', 'csrf']);

    // Biens immobiliers
    Router::get('/admin/biens', [PropertyController::class, 'index'], 'admin.properties', ['permission:properties.view']);
    Router::get('/admin/biens/nouveau', [PropertyController::class, 'create'], 'admin.properties.create', ['permission:properties.manage']);
    Router::post('/admin/biens', [PropertyController::class, 'store'], 'admin.properties.store', ['permission:properties.manage', 'csrf']);
    Router::get('/admin/biens/{publicId:[A-Za-z0-9\-]+}/modifier', [PropertyController::class, 'edit'], 'admin.properties.edit', ['permission:properties.manage']);
    Router::post('/admin/biens/{publicId:[A-Za-z0-9\-]+}/modifier', [PropertyController::class, 'update'], 'admin.properties.update', ['permission:properties.manage', 'csrf']);
    Router::post('/admin/biens/{publicId:[A-Za-z0-9\-]+}/supprimer', [PropertyController::class, 'destroy'], 'admin.properties.destroy', ['permission:properties.manage', 'csrf']);

    // Demandes de projets reçues
    Router::get('/admin/demandes', [RequestController::class, 'index'], 'admin.requests', ['permission:requests.view']);
    Router::get('/admin/demandes/{publicId:[A-Za-z0-9\-]+}', [RequestController::class, 'show'], 'admin.requests.show', ['permission:requests.view']);
    Router::post('/admin/demandes/{publicId:[A-Za-z0-9\-]+}/statut', [RequestController::class, 'status'], 'admin.requests.status', ['permission:requests.manage', 'csrf']);

    // Messages de contact reçus
    Router::get('/admin/contacts', [ContactController::class, 'index'], 'admin.contacts', ['permission:contacts.view']);
    Router::get('/admin/contacts/{publicId:[A-Za-z0-9\-]+}', [ContactController::class, 'show'], 'admin.contacts.show', ['permission:contacts.view']);

    // Actualités
    Router::get('/admin/articles', [ArticleController::class, 'index'], 'admin.articles', ['permission:articles.manage']);
    Router::get('/admin/articles/nouveau', [ArticleController::class, 'create'], 'admin.articles.create', ['permission:articles.manage']);
    Router::post('/admin/articles', [ArticleController::class, 'store'], 'admin.articles.store', ['permission:articles.manage', 'csrf']);
    Router::get('/admin/articles/{publicId:[A-Za-z0-9\-]+}/modifier', [ArticleController::class, 'edit'], 'admin.articles.edit', ['permission:articles.manage']);
    Router::post('/admin/articles/{publicId:[A-Za-z0-9\-]+}/modifier', [ArticleController::class, 'update'], 'admin.articles.update', ['permission:articles.manage', 'csrf']);
    Router::post('/admin/articles/{publicId:[A-Za-z0-9\-]+}/statut', [ArticleController::class, 'toggle'], 'admin.articles.toggle', ['permission:articles.manage', 'csrf']);
});