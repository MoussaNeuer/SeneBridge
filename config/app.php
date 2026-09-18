<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'SeneBridge'),
    'env' => env('APP_ENV', 'development'),
    'debug' => (bool) env('APP_DEBUG', true),
    'url' => rtrim(env('APP_URL', 'http://localhost/SeneBridge/public'), '/'),
    'asset_url' => rtrim(env('ASSET_URL', env('APP_URL', 'http://localhost/SeneBridge/public')), '/'),
    'timezone' => env('APP_TIMEZONE', 'Africa/Dakar'),
    'locale' => env('APP_LOCALE', 'fr'),
    'key' => env('APP_KEY', ''),
    'base_path' => dirname(__DIR__),
];