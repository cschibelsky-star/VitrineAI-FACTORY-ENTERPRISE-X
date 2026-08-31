<?php

use Illuminate\Support\Facades\Route;
use Modules\Cadastro\Http\Controllers\PersonPrintController;
use Modules\Cadastro\Http\Controllers\PublicRegistrationController;

Route::middleware(['web', 'module:cadastro'])
    ->prefix('cadastro')
    ->name('cadastro.')
    ->group(function (): void {
        Route::view('/', 'cadastro::index')
            ->middleware('permission:cadastro.view')
            ->name('index');

        Route::get('/pessoas/{person}/imprimir', PersonPrintController::class)
            ->middleware('permission:cadastro.print')
            ->name('people.print');

        Route::get('/publico', [PublicRegistrationController::class, 'create'])
            ->name('public.create');
        Route::post('/publico', [PublicRegistrationController::class, 'store'])
            ->name('public.store');
    });
