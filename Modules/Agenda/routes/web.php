<?php

use Illuminate\Support\Facades\Route;
use App\Integrations\Modules\EnsureActiveMembership;
use Modules\Agenda\Http\Controllers\AppointmentController;
use Modules\Agenda\Http\Controllers\ResourceController;

// Both web and JSON endpoints use authenticated sessions and CSRF protection.
Route::middleware(['web', 'auth', 'tenant', EnsureActiveMembership::class, 'module:agenda', 'module:cadastro'])
    ->prefix('agenda')->name('agenda.')
    ->group(function (): void {
        Route::get('/', [AppointmentController::class, 'index'])->middleware('permission:agenda.view')->name('index');
        Route::post('/compromissos', [AppointmentController::class, 'store'])->middleware('permission:agenda.create')->name('appointments.store');
        Route::put('/compromissos/{appointment}', [AppointmentController::class, 'update'])->middleware('permission:agenda.update')->name('appointments.update');
        Route::post('/compromissos/{appointment}/cancelar', [AppointmentController::class, 'cancel'])->middleware('permission:agenda.delete')->name('appointments.cancel');
        Route::get('/recursos', [ResourceController::class, 'index'])->middleware('permission:agenda.resources.manage')->name('resources.index');
        Route::post('/recursos', [ResourceController::class, 'store'])->middleware('permission:agenda.resources.manage')->name('resources.store');
        Route::put('/recursos/{resource}', [ResourceController::class, 'update'])->middleware('permission:agenda.resources.manage')->name('resources.update');
        Route::post('/disponibilidade', [ResourceController::class, 'availability'])->middleware('permission:agenda.resources.manage')->name('availability.store');
        Route::put('/disponibilidade/{availability}', [ResourceController::class, 'availability'])->middleware('permission:agenda.resources.manage')->name('availability.update');
        Route::delete('/disponibilidade/{availability}', [ResourceController::class, 'destroyAvailability'])->middleware('permission:agenda.resources.manage')->name('availability.destroy');
    });
