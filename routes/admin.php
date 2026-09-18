<?php

declare(strict_types=1);

use App\Controllers\Admin\AppointmentController;
use App\Controllers\Admin\ArticleController;
use App\Controllers\Admin\ClientController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\CounselorController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\DocumentController;
use App\Controllers\Admin\InvoiceController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\MessageController;
use App\Controllers\Admin\PaymentController;
use App\Controllers\Admin\ProjectController;
use App\Controllers\Admin\PropertyController;
use App\Controllers\Admin\RequestController;
use App\Support\Router;

// ---- Back-office (personnel : admin, manager, conseillers) ------------------
Router::group(['middleware' => ['auth', 'staff']], function () {
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

    // Documents et médias d'un dossier
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/documents', [DocumentController::class, 'store'], 'admin.projects.documents.store', ['permission:documents.upload', 'csrf']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/documents/{documentPublicId:[A-Za-z0-9\-]+}/statut', [DocumentController::class, 'status'], 'admin.projects.documents.status', ['permission:documents.manage', 'csrf']);
    Router::get('/admin/projets/{publicId:[A-Za-z0-9\-]+}/documents/{documentPublicId:[A-Za-z0-9\-]+}/telecharger', [DocumentController::class, 'download'], 'admin.projects.documents.download', ['permission:documents.view']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/media', [MediaController::class, 'store'], 'admin.projects.media.store', ['permission:media.upload', 'csrf']);
    Router::post('/admin/projets/{publicId:[A-Za-z0-9\-]+}/media/{mediaPublicId:[A-Za-z0-9\-]+}/visibilite', [MediaController::class, 'visibility'], 'admin.projects.media.visibility', ['permission:media.manage', 'csrf']);
    Router::get('/admin/projets/{publicId:[A-Za-z0-9\-]+}/media/{mediaPublicId:[A-Za-z0-9\-]+}/telecharger', [MediaController::class, 'download'], 'admin.projects.media.download', ['permission:media.view']);

    // Factures
    Router::get('/admin/factures', [InvoiceController::class, 'index'], 'admin.invoices', ['permission:invoices.view']);
    Router::get('/admin/factures/nouvelle', [InvoiceController::class, 'create'], 'admin.invoices.create', ['permission:invoices.manage']);
    Router::post('/admin/factures', [InvoiceController::class, 'store'], 'admin.invoices.store', ['permission:invoices.manage', 'csrf']);
    Router::get('/admin/factures/{publicId:[A-Za-z0-9\-]+}', [InvoiceController::class, 'show'], 'admin.invoices.show', ['permission:invoices.view']);
    Router::post('/admin/factures/{publicId:[A-Za-z0-9\-]+}/envoyer', [InvoiceController::class, 'send'], 'admin.invoices.send', ['permission:invoices.manage', 'csrf']);
    Router::post('/admin/factures/{publicId:[A-Za-z0-9\-]+}/annuler', [InvoiceController::class, 'cancel'], 'admin.invoices.cancel', ['permission:invoices.manage', 'csrf']);

    // Paiements
    Router::get('/admin/paiements', [PaymentController::class, 'index'], 'admin.payments', ['permission:payments.view']);
    Router::get('/admin/paiements/nouveau', [PaymentController::class, 'create'], 'admin.payments.create', ['permission:payments.record']);
    Router::post('/admin/paiements', [PaymentController::class, 'store'], 'admin.payments.store', ['permission:payments.record', 'csrf']);
    Router::get('/admin/paiements/{publicId:[A-Za-z0-9\-]+}', [PaymentController::class, 'show'], 'admin.payments.show', ['permission:payments.view']);
    Router::post('/admin/paiements/{publicId:[A-Za-z0-9\-]+}/valider', [PaymentController::class, 'validate'], 'admin.payments.validate', ['permission:payments.validate', 'csrf']);
    Router::post('/admin/paiements/{publicId:[A-Za-z0-9\-]+}/rejeter', [PaymentController::class, 'reject'], 'admin.payments.reject', ['permission:payments.validate', 'csrf']);
    Router::get('/admin/paiements/{publicId:[A-Za-z0-9\-]+}/recu', [PaymentController::class, 'receipt'], 'admin.payments.receipt', ['permission:payments.view']);

    // Messagerie
    Router::get('/admin/messages', [MessageController::class, 'index'], 'admin.messages', ['permission:messages.view']);
    Router::get('/admin/messages/{publicId:[A-Za-z0-9\-]+}', [MessageController::class, 'show'], 'admin.messages.show', ['permission:messages.view']);
    Router::post('/admin/messages/{publicId:[A-Za-z0-9\-]+}', [MessageController::class, 'store'], 'admin.messages.store', ['permission:messages.send', 'csrf']);
    Router::post('/admin/messages/{publicId:[A-Za-z0-9\-]+}/fermer', [MessageController::class, 'close'], 'admin.messages.close', ['permission:messages.view', 'csrf']);

    // Rendez-vous
    Router::get('/admin/rendez-vous', [AppointmentController::class, 'index'], 'admin.appointments', ['permission:appointments.view']);
    Router::get('/admin/rendez-vous/{publicId:[A-Za-z0-9\-]+}', [AppointmentController::class, 'show'], 'admin.appointments.show', ['permission:appointments.view']);
    Router::post('/admin/rendez-vous/{publicId:[A-Za-z0-9\-]+}/confirmer', [AppointmentController::class, 'confirm'], 'admin.appointments.confirm', ['permission:appointments.manage', 'csrf']);
    Router::post('/admin/rendez-vous/{publicId:[A-Za-z0-9\-]+}/annuler', [AppointmentController::class, 'cancel'], 'admin.appointments.cancel', ['permission:appointments.manage', 'csrf']);
    Router::post('/admin/rendez-vous/{publicId:[A-Za-z0-9\-]+}/terminer', [AppointmentController::class, 'complete'], 'admin.appointments.complete', ['permission:appointments.manage', 'csrf']);

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