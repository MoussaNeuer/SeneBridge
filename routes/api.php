<?php

declare(strict_types=1);

use App\Controllers\Api\AppointmentController;
use App\Controllers\Api\AuthController;
use App\Controllers\Api\InvoiceController;
use App\Controllers\Api\MessageController;
use App\Controllers\Api\NotificationController;
use App\Controllers\Api\PaymentController;
use App\Controllers\Api\ProjectController;
use App\Support\Router;

/*
 * API REST /api/v1 — authentification par Bearer token.
 * Enveloppe standard : { success, data, message, errors }
 * Les routes protégées portent le middleware 'api' (alias ApiMiddleware).
 */

Router::group(['prefix' => '/api/v1'], function (): void {
    // Connexion (publique) — rien d'autre n'est accessible sans token.
    Router::post('/auth/login', [AuthController::class, 'login'], 'api.auth.login');

    Router::group(['middleware' => ['api']], function (): void {
        Router::post('/auth/logout', [AuthController::class, 'logout'], 'api.auth.logout');
        Router::get('/me', [AuthController::class, 'me'], 'api.me');
    });

    Router::group(['prefix' => '/projects', 'middleware' => ['api']], function (): void {
        Router::get('/', [ProjectController::class, 'index'], 'api.projects.index');
        Router::get('/{publicId}', [ProjectController::class, 'show'], 'api.projects.show');
        Router::get('/{publicId}/steps', [ProjectController::class, 'steps'], 'api.projects.steps');
        Router::get('/{publicId}/properties', [ProjectController::class, 'properties'], 'api.projects.properties');
        Router::get('/{publicId}/documents', [ProjectController::class, 'documents'], 'api.projects.documents');
        Router::get('/{publicId}/messages', [MessageController::class, 'index'], 'api.projects.messages');
        Router::post('/{publicId}/messages', [MessageController::class, 'store'], 'api.projects.messages.store');
    });

    Router::group(['prefix' => '/invoices', 'middleware' => ['api']], function (): void {
        Router::get('/', [InvoiceController::class, 'index'], 'api.invoices.index');
        Router::get('/{publicId}', [InvoiceController::class, 'show'], 'api.invoices.show');
    });

    Router::group(['prefix' => '/payments', 'middleware' => ['api']], function (): void {
        Router::get('/', [PaymentController::class, 'index'], 'api.payments.index');
        Router::get('/{publicId}', [PaymentController::class, 'show'], 'api.payments.show');
    });

    Router::group(['prefix' => '/appointments', 'middleware' => ['api']], function (): void {
        Router::get('/', [AppointmentController::class, 'index'], 'api.appointments.index');
        Router::get('/{publicId}', [AppointmentController::class, 'show'], 'api.appointments.show');
        Router::post('/', [AppointmentController::class, 'store'], 'api.appointments.store');
        Router::post('/{publicId}/cancel', [AppointmentController::class, 'cancel'], 'api.appointments.cancel');
    });

    Router::group(['prefix' => '/notifications', 'middleware' => ['api']], function (): void {
        Router::get('/', [NotificationController::class, 'index'], 'api.notifications.index');
        Router::get('/unread-count', [NotificationController::class, 'unreadCount'], 'api.notifications.unread-count');
        Router::post('/read-all', [NotificationController::class, 'readAll'], 'api.notifications.read-all');
        Router::post('/{publicId}/read', [NotificationController::class, 'read'], 'api.notifications.read');
    });
});