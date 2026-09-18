<?php

declare(strict_types=1);

/**
 * Bootstrap des tests PHPUnit de SeneBridge.
 * S'exécute en CLI : aucune sortie HTTP ne doit être émise avant les buffering.
 */

require dirname(__DIR__) . '/vendor/autoload.php';

// Défauts serveur pour les classes qui lisent $_SERVER.
$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$_SERVER['HTTP_HOST'] ??= 'localhost';
$_SERVER['REQUEST_URI'] ??= '/';
$_SERVER['REQUEST_METHOD'] ??= 'GET';
$_SERVER['argv'] ??= ['phpunit'];

use App\Support\App;

App::bootstrap();

// Désactive les requêtes réseau accidentelles dans les tests unitaires.
if (!defined('PHPUNIT_TEST')) {
    define('PHPUNIT_TEST', true);
}