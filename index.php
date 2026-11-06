<?php

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * Root gateway for Shared Hosting (DomaiNesia / cPanel).
 * This ensures the application runs directly under the root domain without /public.
 */

define('LARAVEL_START', microtime(true));

// Maintenance mode check
if (file_exists($maintenance = __DIR__.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap the Laravel application
$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
)->send();

$kernel->terminate($request, $response);
