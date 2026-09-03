<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth', 'tenant', 'module:atendimento'])
    ->prefix('atendimento')
    ->name('atendimento.')
    ->group(function (): void {
        Route::get('/', fn () => response()->json(['module' => 'atendimento']))
            ->middleware('permission:atendimento.view')
            ->name('index');
    });
