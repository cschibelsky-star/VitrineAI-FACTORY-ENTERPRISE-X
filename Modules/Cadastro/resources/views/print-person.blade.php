<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Ficha de Cadastro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 28px; color: #111; }
        header { display: flex; align-items: center; gap: 20px; border-bottom: 2px solid #222; padding-bottom: 12px; margin-bottom: 20px; }
        header img { max-height: 70px; max-width: 180px; }
        h1 { margin: 0; font-size: 24px; }
        h2 { font-size: 17px; margin-top: 22px; border-bottom: 1px solid #bbb; padding-bottom: 4px; }
        dl { display: grid; grid-template-columns: 180px 1fr; gap: 6px 12px; }
        dt { font-weight: bold; }
        dd { margin: 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #bbb; padding: 7px; text-align: left; }
        .signature { margin-top: 70px; display: grid; grid-template-columns: 1fr 1fr; gap: 50px; }
        .signature div { border-top: 1px solid #222; text-align: center; padding-top: 6px; }
        footer { margin-top: 35px; font-size: 11px; }
        @media print { .no-print { display: none; } body { margin: 12mm; } }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Imprimir</button>

    <header>
        @if (!empty($branding?->logo_path))
            <img src="{{ asset($branding->logo_path) }}" alt="Logo">
        @endif
        <div>
            <h1>{{ $branding?->system_name ?: 'Gestão de Cadastros e Atendimentos' }}</h1>
            <strong>Ficha de Cadastro</strong>
        </div>
    </header>

    <h2>Dados do cadastro</h2>
    <dl>
        <dt>Nome</dt><dd>{{ $person->name }}</dd>
        <dt>Nome preferido</dt><dd>{{ $person->preferred_name ?: '—' }}</dd>
        <dt>Documento</dt><dd>{{ $person->document ?: '—' }}</dd>
        <dt>Data de nascimento</dt><dd>{{ $person->birth_date?->format('d/m/Y') ?: '—' }}</dd>
        <dt>Status</dt><dd>{{ $person->is_active ? 'Ativo' : 'Inativo' }}</dd>
    </dl>

    <h2>Contatos</h2>
    <table>
        <thead><tr><th>Tipo</th><th>Contato</th><th>Principal</th></tr></thead>
        <tbody>
        @forelse ($person->contacts as $contact)
            <tr><td>{{ $contact->type }}</td><td>{{ $contact->value }}</td><td>{{ $contact->is_primary ? 'Sim' : 'Não' }}</td></tr>
        @empty
            <tr><td colspan="3">Nenhum contato registrado.</td></tr>
        @endforelse
        </tbody>
    </table>

    <h2>Responsáveis</h2>
    <table>
        <thead><tr><th>Nome</th><th>Relação</th><th>Contato</th></tr></thead>
        <tbody>
        @forelse ($person->guardians as $guardian)
            <tr>
                <td>{{ $guardian->name }}</td>
                <td>{{ $guardian->pivot->relationship ?? 'Responsável' }}</td>
                <td>{{ optional($guardian->contacts->firstWhere('is_primary', true))->value ?? optional($guardian->contacts->first())->value ?? '—' }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Nenhum responsável vinculado.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="signature">
        <div>Responsável pelo cadastro</div>
        <div>Responsável/cliente</div>
    </div>

    @if (!empty($branding?->document_footer))
        <footer>{{ $branding->document_footer }}</footer>
    @endif
</body>
</html>
