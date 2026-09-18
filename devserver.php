<?php

declare(strict_types=1);

/**
 * Router pour le serveur de développement :
 *   php -S 127.0.0.1:8088 -t public devserver.php
 *
 * Sert les fichiers statiques existants et renvoie tout le reste sur index.php.
 */

$uri = urldecode((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
$file = __DIR__ . '/public' . $uri;

if ($uri !== '/' && is_file($file)) {
    return false;
}

require __DIR__ . '/public/index.php';