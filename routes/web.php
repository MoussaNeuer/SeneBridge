<?php

declare(strict_types=1);

use App\Controllers\Web\AuthController;
use App\Controllers\Web\DashboardController;
use App\Controllers\Web\HomeController;
use App\Support\Router;

// ---- Site public ---------------------------------------------------------
Router::get('/', [HomeController::class, 'index'], 'home');

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