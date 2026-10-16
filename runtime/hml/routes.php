<?php

use App\Core\Application\Module\ModuleGate;
use App\Integrations\Modules\EnsureActiveMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

Route::get('/healthz', function () {
    DB::table('modules')->count();
    return response()->json(['status' => 'ok', 'environment' => 'homologation',
        'revision' => trim(file_get_contents(__DIR__ . '/REVISION'))]);
});
Route::get('/', function (ModuleGate $modules) {
    $available = [];
    foreach (['cadastro' => ['Clientes e pessoas', 'cadastro.index', 'Organize os cadastros usados pelos demais módulos.'],
        'agenda' => ['Agenda', 'agenda.index', 'Marque horários, ajuste disponibilidade e reagende compromissos.'],
        'atendimento' => ['Atendimentos', 'atendimento.index', 'Acompanhe responsáveis, notas, etapas e encerramento.']] as $key => $card) {
        if ($modules->isEnabled($key) && Gate::allows($key . '.view')) {
            $available[] = ['title' => $card[0], 'route' => $card[1], 'description' => $card[2]];
        }
    }
    return view('modulos-hml::home', ['modules' => $available]);
})->middleware(['auth', 'tenant', EnsureActiveMembership::class])->name('hml.home');
Route::get('/login', fn () => view('modulos-hml::login'))->middleware('guest')->name('login');
Route::post('/login', function (Request $request) {
    $input = $request->validate(['email' => ['required', 'email'], 'password' => ['required', 'string']]);
    if (!Auth::attempt($input + ['is_active' => true])) {
        throw ValidationException::withMessages(['email' => 'Credenciais inválidas.']);
    }
    $request->session()->regenerate();
    return redirect()->intended('/');
})->middleware(['guest', 'throttle:5,1']);
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect('/login');
})->middleware('auth')->name('logout');
