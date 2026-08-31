<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['api', 'auth', 'tenant', 'module:cadastro'])
    ->prefix('api/cadastro')
    ->name('api.cadastro.')
    ->group(function (): void {
        Route::get('/health', fn () => response()->json([
            'module' => 'cadastro',
            'status' => 'ok',
        ]))->name('health');
    });
