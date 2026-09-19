<?php

declare(strict_types=1);

use App\Support\App;
use App\Support\Console;
use App\Support\Router;

require dirname(__DIR__) . '/vendor/autoload.php';

App::bootstrap();

// ----- Mode console -------------------------------------------------------
if (App::isCli()) {
    $argv = $_SERVER['argv'] ?? [];
    (new Console($argv))->run();
    exit(0);
}

// ----- Mode web -----------------------------------------------------------
App::resetRequestState();
require routes_path('web.php');
require routes_path('api.php');
require routes_path('admin.php');

$response = Router::resolve(App::request());
$response->send();