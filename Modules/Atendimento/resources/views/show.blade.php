@extends('atendimento::layout')
@section('content')
<p><a href="{{ route('atendimento.index') }}">Voltar à lista</a></p>
<section><h2>{{ $case->subject }}</h2><p>Situação: {{ ['open'=>'Aberto','in_progress'=>'Em atendimento','waiting'=>'Aguardando','closed'=>'Encerrado'][$case->status] ?? $case->status }}</p><p>Aberto em {{ $case->opened_at?->format('d/m/Y H:i') }} @if($case->closed_at) · Encerrado em {{ $case->closed_at->format('d/m/Y H:i') }} @endif</p>
<p>Responsável: {{ $responsibles->firstWhere('id', $case->responsible_user_id)['name'] ?? 'Não atribuído ou inativo' }}</p>
@if($case->status !== 'closed')
@can('atendimento.update')<form method="post" action="{{ route('atendimento.cases.update', $case->ulid) }}">@csrf @method('PUT')
<label>Assunto<input name="subject" required maxlength="180" value="{{ old('subject', $case->subject) }}"></label>
<label>Situação<select name="status">@foreach(['open'=>'Aberto','in_progress'=>'Em atendimento','waiting'=>'Aguardando'] as $value=>$label)<option value="{{ $value }}" @selected(old('status', $case->status) === $value)>{{ $label }}</option>@endforeach</select></label>
<label>Responsável<select name="responsible_user_id"><option value="">Não atribuído</option>@foreach($responsibles as $member)<option value="{{ $member['id'] }}" @selected(old('responsible_user_id', $case->responsible_user_id) === $member['id'])>{{ $member['name'] }}</option>@endforeach</select></label><button>Salvar acompanhamento</button></form>@endcan
@can('atendimento.close')<form method="post" action="{{ route('atendimento.cases.close', $case->ulid) }}">@csrf<button>Encerrar atendimento</button></form>@endcan
@endif</section>
<section><h2>Notas</h2>@forelse($notes as $note)<article><p>{{ $note->body }}</p><small>{{ $note->created_at->format('d/m/Y H:i') }} · {{ $responsibles->firstWhere('id', $note->author_user_id)['name'] ?? 'Integrante anterior' }}</small></article>@empty<p>Nenhuma nota.</p>@endforelse
@if($case->status !== 'closed')
@can('atendimento.notes.manage')<form method="post" action="{{ route('atendimento.cases.notes.store', $case->ulid) }}">@csrf<label>Nova nota<textarea name="body" required maxlength="5000">{{ old('body') }}</textarea></label><button>Adicionar nota</button></form>@endcan
@endif
</section>
<section><h2>Histórico</h2><p>Registros preservados de criação, alterações, notas e encerramento.</p><ol>@foreach($history as $entry)<li>{{ $entry->created_at->format('d/m/Y H:i') }} · {{ ['created'=>'Criação','updated'=>'Atualização','note_added'=>'Nota adicionada','closed'=>'Encerramento'][$entry->action] ?? $entry->action }}<pre>{{ json_encode(['antes'=>$entry->before,'depois'=>$entry->after], JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) }}</pre></li>@endforeach</ol></section>
@endsection
