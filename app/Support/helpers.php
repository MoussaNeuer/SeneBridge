<?php

declare(strict_types=1);

use App\Support\App;
use App\Support\Config;
use App\Support\Response;
use App\Support\Router;
use App\Support\View;

/**
 * Fichier de fonctions globales.
 * Chargé systématiquement via composer (autoload.files).
 */

/**
 * Racine absolue du projet (indépendante du répertoire de travail courant).
 */
if (!defined('SENEBRIDGE_ROOT')) {
    define('SENEBRIDGE_ROOT', dirname(__DIR__, 2));
}

/**
 * Lire une variable d'environnement (système puis .env).
 */
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);

    if ($value === false) {
        $value = $_ENV[$key] ?? null;
    }

    if ($value === null || $value === false) {
        return $default;
    }

    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

/**
 * Lire une valeur de configuration pointée.
 */
function config(string $key, mixed $default = null): mixed
{
    return Config::get($key, $default);
}

/**
 * Lire un réglage vivant de la plateforme (table settings).
 */
function setting(string $key, mixed $default = null): mixed
{
    return \App\Services\SettingsService::get($key, $default);
}

/**
 * Nom affiché du site (réglage vivant, repli sur la configuration).
 */
function site_name(): string
{
    $name = (string) setting('site.name', '');

    return $name !== '' ? $name : (string) config('app.name', 'SeneBridge');
}

/**
 * Symbole monétaire actif (réglage vivant, repli « F »).
 */
function currency_symbol(): string
{
    return (string) setting('site.currency_symbol', 'F');
}

/**
 * Chemins absolus du projet.
 */
function base_path(string $path = ''): string
{
    return rtrim((string) config('app.base_path', SENEBRIDGE_ROOT), '/\\') . ($path !== '' ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
}

function config_path(string $path = ''): string
{
    return base_path('config/' . ltrim($path, '/\\'));
}

function app_path(string $path = ''): string
{
    return base_path('app/' . ltrim($path, '/\\'));
}

function routes_path(string $path = ''): string
{
    return base_path('routes/' . ltrim($path, '/\\'));
}

function database_path(string $path = ''): string
{
    return base_path('database/' . ltrim($path, '/\\'));
}

function storage_path(string $path = ''): string
{
    return base_path('storage/' . ltrim($path, '/\\'));
}

function asset_path(string $path = ''): string
{
    return base_path('public/' . ltrim($path, '/\\'));
}

function views_path(string $path = ''): string
{
    return base_path('views/' . ltrim($path, '/\\'));
}

/**
 * URL d'application (URL absolues dans vues et emails).
 */
function app_url(string $path = ''): string
{
    $base = (string) config('app.url', '');

    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/**
 * URL d'asset statique (public).
 */
function asset_url(string $path = ''): string
{
    $base = (string) config('app.asset_url', (string) config('app.url', ''));

    return $base . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/**
 * Échappement HTML systématique avant toute sortie.
 */
function e(mixed $value): string
{
    if ($value === null || $value === false) {
        return '';
    }

    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formatage de nombre décimal commun.
 */
function format_number(mixed $value, int $decimals = 0): string
{
    return number_format((float) $value, $decimals, ',', ' ');
}

/**
 * Accès session.
 */
function session(?string $key = null, mixed $default = null): mixed
{
    if ($key === null) {
        return App::session();
    }

    $data = App::session();

    return $data[$key] ?? $default;
}

/**
 * Raccourcis de réponse pour les contrôleurs.
 */
function redirect(string $url): Response
{
    return Response::redirect($url);
}

function redirect_back(): Response
{
    return Response::redirectBack();
}

function json_response(mixed $data, int $status = 200): Response
{
    return Response::json($data, $status);
}

function view(string $template, array $data = [], int $status = 200): Response
{
    return Response::view($template, $data, $status);
}

/**
 * Génère une URL vers une route nommée.
 */
function route(string $name, array $params = []): string
{
    return Router::url($name, $params);
}