<?php

use Illuminate\Support\Facades\Route;
use Modules\Agenda\Http\Controllers\AppointmentController;

Route::middleware(['web', 'auth', 'tenant', 'module:agenda'])
    ->prefix('agenda')->name('agenda.')
    ->group(function (): void {
        Route::get('/', [AppointmentController::class, 'index'])->middleware('permission:agenda.view')->name('index');
        Route::post('/compromissos', [AppointmentController::class, 'store'])->middleware('permission:agenda.create')->name('appointments.store');
        Route::put('/compromissos/{appointment}', [AppointmentController::class, 'update'])->middleware('permission:agenda.update')->name('appointments.update');
        Route::post('/compromissos/{appointment}/cancelar', [AppointmentController::class, 'cancel'])->middleware('permission:agenda.delete')->name('appointments.cancel');
    });
