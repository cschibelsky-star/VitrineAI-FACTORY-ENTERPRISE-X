<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Agenda Pro</title><style>body{font:16px system-ui;margin:0;background:#f6f8fb;color:#16263b}main{max-width:1000px;margin:auto;padding:24px}section,article{background:white;border:1px solid #dce4eb;border-radius:12px;padding:20px;margin:16px 0;min-width:0}label{display:block;margin:12px 0}input,select,textarea,button{font:inherit;padding:10px;max-width:100%;box-sizing:border-box}input,select,textarea{width:100%}button{background:#075f73;color:white;border:0;border-radius:8px;cursor:pointer;min-height:44px}a{color:#075f73}li{padding:12px;border-bottom:1px solid #ddd;overflow-wrap:anywhere}small{color:#536375}summary{cursor:pointer;padding:12px 0;font-weight:bold}.grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}.actions{display:flex;flex-wrap:wrap;gap:12px;align-items:center}:focus-visible{outline:3px solid #075f73;outline-offset:3px}pre{white-space:pre-wrap;overflow-wrap:anywhere}p,h1,h2,h3{overflow-wrap:anywhere}@media(max-width:600px){main{padding:12px}section,article{padding:14px}.grid{grid-template-columns:1fr}}</style></head>
<body><main><nav aria-label="Módulos" class="actions">
@if(Route::has('hml.home'))<a href="{{ route('hml.home') }}">Início</a>@endif
@can('cadastro.view')<a href="{{ route('cadastro.index') }}">Clientes e pessoas</a>@endcan
@can('agenda.view')<a href="{{ route('agenda.index') }}">Agenda</a>@endcan
@can('atendimento.view')<a href="{{ route('atendimento.index') }}">Atendimentos</a>@endcan
@if(Route::has('logout'))<form method="post" action="{{ route('logout') }}">@csrf<button>Sair</button></form>@endif
</nav>
<h1>Agenda Pro</h1>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
@if($errors->any())<section role="alert"><h2>Revise os campos</h2><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></section>@endif
@yield('content')
</main></body></html>
