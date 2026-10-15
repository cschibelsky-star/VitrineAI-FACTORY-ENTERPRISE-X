<?php

use Illuminate\Support\Facades\Route;
use Modules\Atendimento\Http\Controllers\ServiceCaseController;

Route::middleware(['web', 'auth', 'tenant', 'module:atendimento'])
    ->prefix('atendimento')->name('atendimento.')
    ->group(function (): void {
        Route::get('/', [ServiceCaseController::class, 'index'])->middleware('permission:atendimento.view')->name('index');
        Route::post('/casos', [ServiceCaseController::class, 'store'])->middleware('permission:atendimento.create')->name('cases.store');
        Route::post('/casos/{case}/encerrar', [ServiceCaseController::class, 'close'])->middleware('permission:atendimento.close')->name('cases.close');
    });
