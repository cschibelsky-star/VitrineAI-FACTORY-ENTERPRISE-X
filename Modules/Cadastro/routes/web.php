<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'module:cadastro'])
    ->prefix('cadastro')
    ->name('cadastro.')
    ->group(function (): void {
        Route::view('/', 'cadastro::index')->name('index');
    });
