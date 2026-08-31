<?php

use Illuminate\Support\Facades\Route;
use Modules\Cadastro\Http\Controllers\PrintPersonController;
use Modules\Cadastro\Http\Controllers\PublicRegistrationController;

Route::middleware(['web', 'module:cadastro'])
    ->prefix('cadastro')
    ->name('cadastro.')
    ->group(function (): void {
        Route::view('/', 'cadastro::index')->name('index');

        Route::get('/publico', [PublicRegistrationController::class, 'create'])
            ->name('public.create');
        Route::post('/publico', [PublicRegistrationController::class, 'store'])
            ->name('public.store');

        Route::get('/pessoas/{person}/imprimir', PrintPersonController::class)
            ->name('people.print');
    });
