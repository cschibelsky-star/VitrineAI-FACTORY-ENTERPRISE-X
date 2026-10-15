@extends('atendimento::layout')
@section('content')
<p>Registro e acompanhamento dos atendimentos da sua empresa.</p>
<form method="get"><label>Filtrar por situação<select name="status"><option value="">Todas</option>@foreach(['open'=>'Aberto','in_progress'=>'Em atendimento','waiting'=>'Aguardando','closed'=>'Encerrado'] as $value=>$label)<option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>@endforeach</select></label><button>Filtrar</button></form>
@can('atendimento.create')
<section><h2>Novo atendimento</h2><form method="post" action="{{ route('atendimento.cases.store') }}">@csrf
<label>Pessoa<select name="person_id" required><option value="">Selecione</option>@foreach($people as $person)<option value="{{ $person['id'] }}" @selected(old('person_id') === $person['id'])>{{ $person['name'] }}</option>@endforeach</select></label>
<label>Assunto<input name="subject" required maxlength="180" value="{{ old('subject') }}"></label>
<label>Responsável<select name="responsible_user_id"><option value="">Não atribuído</option>@foreach($responsibles as $member)<option value="{{ $member['id'] }}" @selected(old('responsible_user_id') === $member['id'])>{{ $member['name'] }}</option>@endforeach</select></label>
<button>Registrar atendimento</button></form></section>
@endcan
<section><h2>Atendimentos</h2>@forelse($cases as $case)<article><h3><a href="{{ route('atendimento.cases.show', $case->ulid) }}">{{ $case->subject }}</a></h3><p>{{ ['open'=>'Aberto','in_progress'=>'Em atendimento','waiting'=>'Aguardando','closed'=>'Encerrado'][$case->status] ?? $case->status }} · {{ $case->opened_at?->format('d/m/Y H:i') }}</p></article>@empty<p>Nenhum atendimento encontrado.</p>@endforelse{{ $cases->links() }}</section>
@endsection
