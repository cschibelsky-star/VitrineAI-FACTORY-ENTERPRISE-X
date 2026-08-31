<?php

use Illuminate\Support\Facades\Route;
use Modules\Cadastro\Http\Controllers\PersonController;
use Modules\Cadastro\Http\Controllers\PersonPrintController;
use Modules\Cadastro\Http\Controllers\PublicRegistrationController;

Route::middleware(['web', 'auth', 'tenant', 'module:cadastro'])
    ->prefix('cadastro')
    ->name('cadastro.')
    ->group(function (): void {
        Route::get('/', [PersonController::class, 'index'])
            ->middleware('permission:cadastro.view')
            ->name('index');

        Route::post('/pessoas', [PersonController::class, 'store'])
            ->middleware('permission:cadastro.create')
            ->name('people.store');

        Route::put('/pessoas/{person}', [PersonController::class, 'update'])
            ->middleware('permission:cadastro.update')
            ->name('people.update');

        Route::delete('/pessoas/{person}', [PersonController::class, 'destroy'])
            ->middleware('permission:cadastro.delete')
            ->name('people.destroy');

        Route::get('/pessoas/{person}/imprimir', PersonPrintController::class)
            ->middleware('permission:cadastro.print')
            ->name('people.print');
    });

Route::middleware(['web'])
    ->prefix('cadastro-publico/{tenant:slug}')
    ->name('cadastro.public.')
    ->group(function (): void {
        Route::get('/', [PublicRegistrationController::class, 'create'])->name('create');
        Route::post('/', [PublicRegistrationController::class, 'store'])->name('store');
    });
