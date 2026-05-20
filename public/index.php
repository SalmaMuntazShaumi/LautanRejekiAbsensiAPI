<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../../laravel_api/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../../laravel_api/vendor/autoload.php';

$app = require_once __DIR__.'/../../laravel_api/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);