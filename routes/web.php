<?php

declare(strict_types=1);

use App\Controllers\Web\AuthController;
use App\Controllers\Web\ClientAppointmentController;
use App\Controllers\Web\ClientInvoiceController;
use App\Controllers\Web\ClientMessageController;
use App\Controllers\Web\ClientProjectController;
use App\Controllers\Web\ContactController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\HomeController;
use App\Controllers\Web\NotificationController;
use App\Controllers\Web\PageController;
use App\Controllers\Web\ProfileController;
use App\Controllers\Web\ProjectRequestController;
use App\Support\Router;

// ---- Site public ---------------------------------------------------------
Router::get('/', [HomeController::class, 'index'], 'home');
Router::get('/services', [PageController::class, 'services'], 'pages.services');
Router::get('/apropos', [PageController::class, 'about'], 'pages.about');
Router::get('/actualites', [PageController::class, 'news'], 'pages.news');
Router::get('/actualites/{slug}', [PageController::class, 'newsShow'], 'pages.news.show');
Router::get('/contact', [ContactController::class, 'show'], 'pages.contact');
Router::post('/contact', [ContactController::class, 'submit'], 'pages.contact.submit', ['csrf']);
Router::get('/demarrer-un-projet', [ProjectRequestController::class, 'show'], 'pages.start-project');
Router::post('/demarrer-un-projet', [ProjectRequestController::class, 'submit'], 'pages.start-project.submit', ['csrf']);

// ---- Authentification ----------------------------------------------------
Router::get('/register', [AuthController::class, 'showRegister'], 'auth.register', ['guest']);
Router::post('/register', [AuthController::class, 'register'], 'auth.register.submit', ['guest', 'csrf']);
Router::get('/login', [AuthController::class, 'showLogin'], 'auth.login', ['guest']);
Router::post('/login', [AuthController::class, 'login'], 'auth.login.submit', ['guest', 'csrf']);
Router::post('/logout', [AuthController::class, 'logout'], 'auth.logout', ['auth', 'csrf']);
Router::get('/verify-email/{token:[a-f0-9]{64}}', [AuthController::class, 'verifyEmail'], 'auth.verify-email', ['guest']);
Router::get('/forgot-password', [AuthController::class, 'showForgotPassword'], 'auth.password.forgot', ['guest']);
Router::post('/forgot-password', [AuthController::class, 'forgotPassword'], 'auth.password.forgot.submit', ['guest', 'csrf']);
Router::get('/reset-password/{token:[a-f0-9]{64}}', [AuthController::class, 'showResetPassword'], 'auth.password.reset', ['guest']);
Router::post('/reset-password', [AuthController::class, 'resetPassword'], 'auth.password.reset.submit', ['guest', 'csrf']);

// ---- Espace client -------------------------------------------------------
Router::get('/dashboard', [DashboardController::class, 'index'], 'dashboard', ['auth']);
Router::get('/mes-projets', [ClientProjectController::class, 'index'], 'client.projects', ['auth']);
Router::get('/projets/{publicId:[A-Za-z0-9\-]+}', [ClientProjectController::class, 'show'], 'client.projects.show', ['auth']);
Router::get('/projets/{publicId:[A-Za-z0-9\-]+}/documents/{documentPublicId:[A-Za-z0-9\-]+}/telecharger', [ClientProjectController::class, 'downloadDocument'], 'client.projects.documents.download', ['auth']);
Router::get('/projets/{publicId:[A-Za-z0-9\-]+}/media/{mediaPublicId:[A-Za-z0-9\-]+}/telecharger', [ClientProjectController::class, 'downloadMedia'], 'client.projects.media.download', ['auth']);
Router::get('/profil', [ProfileController::class, 'show'], 'client.profile', ['auth']);
Router::post('/profil', [ProfileController::class, 'update'], 'client.profile.update', ['auth', 'csrf']);
Router::post('/profil/mot-de-passe', [ProfileController::class, 'password'], 'client.profile.password', ['auth', 'csrf']);
Router::get('/notifications', [NotificationController::class, 'index'], 'client.notifications', ['auth']);
Router::post('/notifications/lu-tout', [NotificationController::class, 'readAll'], 'client.notifications.read_all', ['auth', 'csrf']);
Router::post('/notifications/{publicId:[A-Za-z0-9\-]+}/lu', [NotificationController::class, 'read'], 'client.notifications.read', ['auth', 'csrf']);

// Factures côté client
Router::get('/factures', [ClientInvoiceController::class, 'index'], 'client.invoices', ['auth']);
Router::get('/factures/{publicId:[A-Za-z0-9\-]+}', [ClientInvoiceController::class, 'show'], 'client.invoices.show', ['auth']);

// Messagerie côté client
Router::get('/mes-conversations', [ClientMessageController::class, 'index'], 'client.messages', ['auth']);
Router::get('/conversations/{publicId:[A-Za-z0-9\-]+}', [ClientMessageController::class, 'show'], 'client.messages.show', ['auth']);
Router::post('/conversations/{publicId:[A-Za-z0-9\-]+}', [ClientMessageController::class, 'store'], 'client.messages.store', ['auth', 'csrf']);

// Rendez-vous côté client
Router::get('/rendez-vous', [ClientAppointmentController::class, 'index'], 'client.appointments', ['auth']);
Router::get('/rendez-vous/nouveau', [ClientAppointmentController::class, 'create'], 'client.appointments.create', ['auth']);
Router::post('/rendez-vous', [ClientAppointmentController::class, 'store'], 'client.appointments.store', ['auth', 'csrf']);
Router::post('/rendez-vous/{publicId:[A-Za-z0-9\-]+}/annuler', [ClientAppointmentController::class, 'cancel'], 'client.appointments.cancel', ['auth', 'csrf']);