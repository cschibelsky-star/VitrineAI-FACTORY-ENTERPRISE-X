<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Support\Facades\Route;

require dirname(__DIR__, 2) . '/vendor/autoload.php';
if (getenv('MODULOS_HML_ENABLED') !== '1') {
    throw new RuntimeException('HML runtime is disabled.');
}
$data = getenv('HML_DATA_DIR') ?: '/data';
if (!is_file($data . '/app-key')) {
    throw new RuntimeException('HML runtime must be initialized first.');
}
$_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = 'homologation';
$_ENV['APP_KEY'] = $_SERVER['APP_KEY'] = trim(file_get_contents($data . '/app-key'));
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = 'sqlite';
$_ENV['DB_DATABASE'] = $_SERVER['DB_DATABASE'] = $data . '/database.sqlite';
$_ENV['APP_DEBUG'] = $_SERVER['APP_DEBUG'] = 'false';
$app = Application::configure(basePath: dirname(__DIR__, 2))
    ->withProviders([\App\Integrations\Modules\HmlServiceProvider::class])
    ->withRouting(using: function () {
        Route::middleware('web')->group(__DIR__ . '/routes.php');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo('/login');
    })
    ->withExceptions(function (Exceptions $exceptions) {})
    ->create();
$app->useStoragePath($data . '/storage');
return $app;
