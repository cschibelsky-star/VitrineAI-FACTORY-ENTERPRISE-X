<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth', 'tenant', 'module:atendimento'])
    ->prefix('api/atendimento')
    ->name('api.atendimento.')
    ->group(function (): void {
        Route::get('/health', fn () => response()->json(['module' => 'atendimento', 'status' => 'ok']))->name('health');
    });
