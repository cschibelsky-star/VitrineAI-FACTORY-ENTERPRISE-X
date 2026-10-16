@extends('agenda::layout')
@section('content')
<p>Compromissos, recursos e horários da sua empresa.</p>
@can('agenda.create')
<section><h2>Novo compromisso</h2><form method="post" action="{{ route('agenda.appointments.store') }}">@csrf
<label>Título<input name="title" required maxlength="180" value="{{ old('title') }}"></label>
<div class="grid"><label>Início<input name="starts_at" type="datetime-local" required value="{{ old('starts_at') }}"></label><label>Fim<input name="ends_at" type="datetime-local" required value="{{ old('ends_at') }}"></label></div>
<label>Pessoa (opcional)<select name="person_id"><option value="">Nenhuma</option>@foreach($people as $person)<option value="{{ $person['id'] }}" @selected(old('person_id') === $person['id'])>{{ $person['name'] }}</option>@endforeach</select></label>
<label>Recurso<select name="resource_id"><option value="">Nenhum</option>@foreach($resources->where('is_active', true) as $resource)<option value="{{ $resource->ulid }}" @selected(old('resource_id') === $resource->ulid)>{{ $resource->name }}</option>@endforeach</select></label>
<label>Descrição<textarea name="description" maxlength="2000">{{ old('description') }}</textarea></label><button>Criar compromisso</button></form></section>
@endcan
<section><h2>Compromissos</h2>
@forelse($appointments as $appointment)
<article><h3>{{ $appointment->title }}</h3><p>{{ $appointment->starts_at?->format('d/m/Y H:i') }} até {{ $appointment->ends_at?->format('d/m/Y H:i') }} · {{ $appointment->status === 'cancelled' ? 'Cancelado' : 'Agendado' }}</p><p>{{ $appointment->description }}</p>
@if($appointment->status !== 'cancelled')
@can('agenda.update')
<details><summary>Editar ou reagendar</summary><form method="post" action="{{ route('agenda.appointments.update', $appointment->ulid) }}">@csrf @method('PUT')
<label>Título<input name="title" required maxlength="180" value="{{ $appointment->title }}"></label>
<div class="grid"><label>Início<input name="starts_at" type="datetime-local" required value="{{ $appointment->starts_at->format('Y-m-d\TH:i') }}"></label><label>Fim<input name="ends_at" type="datetime-local" required value="{{ $appointment->ends_at->format('Y-m-d\TH:i') }}"></label></div>
<label>Pessoa<select name="person_id"><option value="">Nenhuma</option>@foreach($people as $person)<option value="{{ $person['id'] }}" @selected($appointment->person_id === $person['id'])>{{ $person['name'] }}</option>@endforeach</select></label>
<label>Recurso<select name="resource_id"><option value="">Nenhum</option>@foreach($resources->where('is_active', true) as $resource)<option value="{{ $resource->ulid }}" @selected($appointment->resource_id === $resource->ulid)>{{ $resource->name }}</option>@endforeach</select></label>
<label>Descrição<textarea name="description" maxlength="2000">{{ $appointment->description }}</textarea></label><button>Salvar alterações</button></form></details>
@endcan
@can('agenda.delete')<form method="post" action="{{ route('agenda.appointments.cancel', $appointment->ulid) }}">@csrf<button>Cancelar compromisso</button></form>@endcan
@endif</article>
@empty<p>Nenhum compromisso encontrado.</p>@endforelse
{{ $appointments->links() }}</section>
@can('agenda.resources.manage')
<section><h2>Recursos da agenda</h2><p>Desative recursos sem apagar seus compromissos. Sem faixas cadastradas, um recurso não tem restrição de disponibilidade.</p>
<form method="post" action="{{ route('agenda.resources.store') }}">@csrf<label>Nome<input name="name" required maxlength="180"></label><label>Tipo<input name="type" value="resource" required maxlength="60"></label><button>Criar recurso</button></form>
@foreach($resources as $resource)<details><summary>{{ $resource->name }} · {{ $resource->is_active ? 'Ativo' : 'Inativo' }}</summary><form method="post" action="{{ route('agenda.resources.update', $resource->ulid) }}">@csrf @method('PUT')<label>Nome<input name="name" value="{{ $resource->name }}" required maxlength="180"></label><label>Tipo<input name="type" value="{{ $resource->type }}" required maxlength="60"></label><label>Situação<select name="is_active"><option value="1" @selected($resource->is_active)>Ativo</option><option value="0" @selected(!$resource->is_active)>Inativo</option></select></label><button>Salvar recurso</button></form></details>@endforeach
</section>
<section><h2>Disponibilidade semanal</h2><p>Horários no fuso configurado da aplicação. Mudanças nas faixas valem para novas reservas e reagendamentos.</p>
<form method="post" action="{{ route('agenda.availability.store') }}">@csrf
<label>Recurso<select name="resource_id" required>@foreach($resources->where('is_active', true) as $resource)<option value="{{ $resource->ulid }}">{{ $resource->name }}</option>@endforeach</select></label>
<label>Dia<select name="weekday">@foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $day => $label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></label>
<div class="grid"><label>De<input type="time" name="starts_at" required></label><label>Até<input type="time" name="ends_at" required></label></div><button>Adicionar faixa</button></form>
@foreach($availability as $window)<details><summary>{{ $resources->firstWhere('ulid', $window->resource_id)?->name }} · {{ ['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'][$window->weekday] }} · {{ $window->starts_at }}–{{ $window->ends_at }}</summary>
<form method="post" action="{{ route('agenda.availability.update', $window->ulid) }}">@csrf @method('PUT')
<input type="hidden" name="resource_id" value="{{ $window->resource_id }}"><label>Dia<select name="weekday">@foreach(['Domingo','Segunda','Terça','Quarta','Quinta','Sexta','Sábado'] as $day => $label)<option value="{{ $day }}" @selected((int)$window->weekday === $day)>{{ $label }}</option>@endforeach</select></label>
<div class="grid"><label>De<input type="time" name="starts_at" required value="{{ substr($window->starts_at,0,5) }}"></label><label>Até<input type="time" name="ends_at" required value="{{ substr($window->ends_at,0,5) }}"></label></div><button>Salvar faixa</button></form>
<form method="post" action="{{ route('agenda.availability.destroy', $window->ulid) }}">@csrf @method('DELETE')<button>Remover faixa</button></form></details>@endforeach
</section>
@endcan
@endsection
