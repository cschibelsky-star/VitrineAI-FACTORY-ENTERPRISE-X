<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant', 'module:agenda'])
    ->prefix('agenda')
    ->name('agenda.')
    ->group(function (): void {
        Route::get('/', fn () => response()->json(['module' => 'agenda']))
            ->middleware('permission:agenda.view')
            ->name('index');
    });
