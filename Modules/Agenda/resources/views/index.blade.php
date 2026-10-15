<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Agenda Pro</title>
<style>body{font:16px system-ui;margin:0;background:#f6f8fb;color:#16263b}main{max-width:1000px;margin:auto;padding:24px}section{background:white;border:1px solid #dce4eb;border-radius:12px;padding:20px;margin:16px 0}label{display:block;margin:12px 0}input,select,textarea,button{font:inherit;padding:10px;max-width:100%;box-sizing:border-box}input,select,textarea{width:100%}button{background:#075f73;color:white;border:0;border-radius:8px;cursor:pointer}li{padding:12px;border-bottom:1px solid #ddd}small{color:#536375}</style></head>
<body><main><h1>Agenda Pro</h1><p>Gerencie compromissos e recursos.</p>
@if(session('status'))<p role="status">{{ session('status') }}</p>@endif
<section><h2>Novo compromisso</h2><form method="post" action="{{ route('agenda.appointments.store') }}">@csrf
<label>Título<input name="title" required maxlength="180" value="{{ old('title') }}"></label>
<label>Início<input name="starts_at" type="datetime-local" required value="{{ old('starts_at') }}"></label>
<label>Fim<input name="ends_at" type="datetime-local" required value="{{ old('ends_at') }}"></label>
<label>Pessoa (opcional)<select name="person_id"><option value="">Nenhuma</option>@foreach($people as $person)<option value="{{ $person->ulid }}" @selected(old('person_id') === $person->ulid)>{{ $person->name }}</option>@endforeach</select></label>
<label>Recurso<select name="resource_id"><option value="">Nenhum</option>@foreach($resources as $resource)<option value="{{ $resource->ulid }}">{{ $resource->name }}</option>@endforeach</select></label>
<label>Descrição<textarea name="description" maxlength="2000">{{ old('description') }}</textarea></label>
<button type="submit">Criar compromisso</button></form>
@if($errors->any())<div role="alert"><strong>Revise os campos:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif</section>
<section><h2>Compromissos</h2><ul>@forelse($appointments as $appointment)
<li><strong>{{ $appointment->title }}</strong> — {{ $appointment->starts_at?->format('d/m/Y H:i') }} até {{ $appointment->ends_at?->format('d/m/Y H:i') }} <small>{{ $appointment->status }}</small>
@if($appointment->status !== 'cancelled')<form method="post" action="{{ route('agenda.appointments.cancel', $appointment->ulid) }}">@csrf<button type="submit">Cancelar</button></form>@endif</li>
@empty<li>Nenhum compromisso encontrado.</li>@endforelse</ul>{{ $appointments->links() }}</section></main></body></html>
