<?php

use App\Core\Domain\Tenant\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

Route::get('/healthz', function () {
    DB::table('modules')->count();
    return response()->json(['status' => 'ok', 'environment' => 'homologation',
        'revision' => trim(file_get_contents(__DIR__ . '/REVISION'))]);
});
Route::get('/', fn () => redirect('/agenda'))->middleware('auth');
Route::get('/login', fn () => view('modulos-hml::login'))->middleware('guest')->name('login');
Route::post('/login', function (Request $request) {
    $input = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
    if (!Auth::attempt($input + ['is_active' => true])) {
        throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
    }
    $request->session()->regenerate();
    return redirect()->intended('/agenda');
})->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');
