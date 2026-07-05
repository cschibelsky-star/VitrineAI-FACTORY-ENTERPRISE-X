<?php

use App\Http\Controllers\SiteContratarController;
use Illuminate\Support\Facades\Route;

Route::get('/contratar', [SiteContratarController::class, 'create'])
    ->name('site.contratar');

Route::post('/contratar', [SiteContratarController::class, 'store'])
    ->name('site.contratar.store');
