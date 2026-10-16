<?php

use Illuminate\Support\Facades\Route;
use App\Integrations\Modules\EnsureActiveMembership;
use Modules\Atendimento\Http\Controllers\ServiceCaseController;

// Both web and JSON endpoints use authenticated sessions and CSRF protection.
Route::middleware(['web', 'auth', 'tenant', EnsureActiveMembership::class, 'module:atendimento', 'module:cadastro'])
    ->prefix('atendimento')->name('atendimento.')
    ->group(function (): void {
        Route::get('/', [ServiceCaseController::class, 'index'])->middleware('permission:atendimento.view')->name('index');
        Route::get('/casos/{case}', [ServiceCaseController::class, 'show'])->middleware('permission:atendimento.view')->name('cases.show');
        Route::post('/casos', [ServiceCaseController::class, 'store'])->middleware('permission:atendimento.create')->name('cases.store');
        Route::put('/casos/{case}', [ServiceCaseController::class, 'update'])->middleware('permission:atendimento.update')->name('cases.update');
        Route::post('/casos/{case}/encerrar', [ServiceCaseController::class, 'close'])->middleware('permission:atendimento.close')->name('cases.close');
        Route::post('/casos/{case}/notas', [ServiceCaseController::class, 'note'])->middleware('permission:atendimento.notes.manage')->name('cases.notes.store');
    });
