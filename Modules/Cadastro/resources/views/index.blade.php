<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gestão de Cadastros e Atendimentos</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; color: #111; }
        .toolbar, .card { border: 1px solid #ddd; padding: 16px; margin-bottom: 16px; }
        .grid { display: grid; grid-template-columns: repeat(3, minmax(0,1fr)); gap: 10px; }
        input, button { padding: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 1px solid #ddd; padding: 8px; text-align: left; vertical-align: top; }
        form.inline { display: inline; }
        small { color: #666; }
    </style>
</head>
<body>
<main>
    <h1>Gestão de Cadastros e Atendimentos</h1>

    @if (session('status'))
        <p>{{ session('status') }}</p>
    @endif

    <section class="toolbar">
        <form method="get" action="{{ route('cadastro.index') }}">
            <label>Localizar cadastro
                <input type="search" name="q" value="{{ $search }}" placeholder="Nome, documento, e-mail ou telefone">
            </label>
            <button type="submit">Buscar</button>
        </form>
    </section>

    <section class="card">
        <h2>Novo cadastro</h2>
        <form method="post" action="{{ route('cadastro.people.store') }}">
            @csrf
            <div class="grid">
                <input name="full_name" required placeholder="Nome completo">
                <input name="preferred_name" placeholder="Nome preferido">
                <input name="document" placeholder="Documento">
                <input type="date" name="birth_date">
                <input type="email" name="email" placeholder="E-mail">
                <input name="phone" placeholder="Telefone">
            </div>
            <p><button type="submit">Salvar cadastro</button></p>
        </form>
    </section>

    <section class="card">
        <h2>Cadastros</h2>
        <table>
            <thead><tr><th>Nome</th><th>Documento</th><th>Contato</th><th>Status</th><th>Ações</th></tr></thead>
            <tbody>
            @forelse ($people as $person)
                <tr>
                    <td>{{ $person->full_name }}<br><small>{{ $person->preferred_name }}</small></td>
                    <td>{{ $person->document ?: '—' }}</td>
                    <td>{{ $person->email ?: '—' }}<br>{{ $person->phone ?: '—' }}</td>
                    <td>{{ $person->is_active ? 'Ativo' : 'Inativo' }}</td>
                    <td>
                        <a href="{{ route('cadastro.people.print', $person) }}">Imprimir</a>
                        <details>
                            <summary>Editar</summary>
                            <form method="post" action="{{ route('cadastro.people.update', $person) }}">
                                @csrf @method('PUT')
                                <input type="hidden" name="q" value="{{ $search }}">
                                <input name="full_name" value="{{ $person->full_name }}" required>
                                <input name="preferred_name" value="{{ $person->preferred_name }}">
                                <input name="document" value="{{ $person->document }}">
                                <input type="date" name="birth_date" value="{{ $person->birth_date?->format('Y-m-d') }}">
                                <input type="email" name="email" value="{{ $person->email }}">
                                <input name="phone" value="{{ $person->phone }}">
                                <button type="submit">Atualizar</button>
                            </form>
                        </details>
                        <form class="inline" method="post" action="{{ route('cadastro.people.destroy', $person) }}" onsubmit="return confirm('Excluir este cadastro?')">
                            @csrf @method('DELETE')
                            <button type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5">Nenhum cadastro encontrado.</td></tr>
            @endforelse
            </tbody>
        </table>
        {{ $people->links() }}
    </section>
</main>
</body>
</html>
