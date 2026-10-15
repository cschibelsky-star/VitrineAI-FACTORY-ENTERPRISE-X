<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Atendimento Pro</title>
<style>body{font:16px system-ui;margin:0;background:#f6f8fb;color:#16263b}main{max-width:1000px;margin:auto;padding:24px}section{background:white;border:1px solid #dce4eb;border-radius:12px;padding:20px;margin:16px 0}label{display:block;margin:12px 0}input,button{font:inherit;padding:10px;max-width:100%;box-sizing:border-box}input{width:100%}button{background:#075f73;color:white;border:0;border-radius:8px;cursor:pointer}li{padding:12px;border-bottom:1px solid #ddd}small{color:#536375}</style></head>
<body><main><h1>Atendimento Pro</h1><p>Registre e acompanhe solicitações.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<section><h2>Novo atendimento</h2><form method="post" action="{{ route('atendimento.cases.store') }}">@csrf
<label>Identificador da pessoa cadastrada<input name="person_id" required maxlength="26" value="{{ old('person_id') }}" placeholder="ULID do Cadastro Pro"></label>
<label>Assunto<input name="subject" required maxlength="180" value="{{ old('subject') }}"></label>
<button type="submit">Registrar atendimento</button></form>
@if($errors->any())<div role="alert"><strong>Revise os campos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif</section>
<section><h2>Atendimentos</h2><ul>@forelse($cases as $case)
<li><strong>{{ $case->subject }}</strong> <small>{{ $case->status }} — {{ $case->opened_at?->format('d/m/Y H:i') }}</small>
@if($case->status !== 'closed')<form method="post" action="{{ route('atendimento.cases.close', $case->ulid) }}">@csrf<button type="submit">Encerrar</button></form>@endif</li>
@empty<li>Nenhum atendimento encontrado.</li>@endforelse</ul>{{ $cases->links() }}</section></main></body></html>
