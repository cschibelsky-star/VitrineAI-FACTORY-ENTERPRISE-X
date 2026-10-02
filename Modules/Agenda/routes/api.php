<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth', 'tenant', 'module:agenda'])
    ->prefix('api/agenda')
    ->name('api.agenda.')
    ->group(function (): void {
        Route::get('/health', fn () => response()->json(['module' => 'agenda', 'status' => 'ok']))->name('health');
    });
