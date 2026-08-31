<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cadastro</title>
</head>
<body>
    <main>
        <h1>Cadastro</h1>
        <p>Preencha seus dados para envio ao responsável pelo atendimento.</p>

        @if (session('status'))
            <p>{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="post" action="{{ route('cadastro.public.store') }}">
            @csrf
            <label>Nome completo <input name="name" value="{{ old('name') }}" required></label><br>
            <label>Nome social/preferido <input name="preferred_name" value="{{ old('preferred_name') }}"></label><br>
            <label>Documento <input name="document" value="{{ old('document') }}"></label><br>
            <label>Data de nascimento <input type="date" name="birth_date" value="{{ old('birth_date') }}"></label><br>
            <label>E-mail <input type="email" name="email" value="{{ old('email') }}"></label><br>
            <label>Telefone <input name="phone" value="{{ old('phone') }}"></label><br>
            <button type="submit">Enviar cadastro</button>
        </form>
    </main>
</body>
</html>
