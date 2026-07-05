<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <title>Contratar — Vitrine AI Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        *{box-sizing:border-box}
        body{margin:0;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;background:#020617;color:#e5e7eb}
        .wrap{min-height:100vh;padding:48px 20px;background:radial-gradient(circle at top right,rgba(14,165,233,.28),transparent 32%),radial-gradient(circle at bottom left,rgba(59,130,246,.22),transparent 34%),linear-gradient(135deg,#020617 0%,#0f172a 55%,#111827 100%)}
        .container{max-width:1120px;margin:0 auto}
        .hero{display:grid;grid-template-columns:1.1fr .9fr;gap:32px;align-items:start}
        .badge{display:inline-flex;padding:8px 14px;border:1px solid rgba(255,255,255,.12);border-radius:999px;color:#bae6fd;background:rgba(255,255,255,.06);font-size:12px;letter-spacing:.18em;text-transform:uppercase;font-weight:700}
        h1{font-size:clamp(36px,5vw,64px);line-height:1;margin:24px 0;letter-spacing:-.04em}
        p{color:#cbd5e1;line-height:1.7}
        .card{background:rgba(15,23,42,.82);border:1px solid rgba(148,163,184,.2);border-radius:28px;padding:28px;box-shadow:0 24px 80px rgba(0,0,0,.35)}
        label{display:block;font-size:13px;font-weight:800;margin-bottom:8px;color:#cbd5e1}
        input,select,textarea{width:100%;border:1px solid rgba(148,163,184,.25);border-radius:16px;padding:14px 16px;background:rgba(2,6,23,.7);color:#fff;outline:none}
        textarea{min-height:96px;resize:vertical}
        .grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
        .field{margin-bottom:16px}
        button{width:100%;border:0;border-radius:18px;padding:16px 20px;background:#0ea5e9;color:white;font-weight:900;cursor:pointer;box-shadow:0 18px 48px rgba(14,165,233,.28)}
        button:hover{background:#38bdf8}
        .product{border:1px solid rgba(148,163,184,.18);background:rgba(15,23,42,.58);border-radius:20px;padding:18px;margin-top:14px}
        .product h3{margin:0 0 8px}
        .alert{margin-bottom:18px;border-radius:18px;padding:16px;font-weight:700}
        .success{background:rgba(16,185,129,.14);color:#bbf7d0;border:1px solid rgba(16,185,129,.25)}
        .error{background:rgba(239,68,68,.14);color:#fecaca;border:1px solid rgba(239,68,68,.25)}
        pre{white-space:pre-wrap;background:rgba(2,6,23,.72);border:1px solid rgba(148,163,184,.2);border-radius:16px;padding:14px;overflow:auto;font-size:12px}
        @media(max-width:860px){.hero,.grid{grid-template-columns:1fr}}
    </style>
</head>
<body>
<div class="wrap">
    <div class="container">
        <div class="hero">
            <div>
                <span class="badge">Vitrine AI Pro Enterprise</span>
                <h1>Contrate uma plataforma de IA pronta para operar.</h1>
                <p>
                    Escolha o produto, informe seus dados e o pedido será enviado automaticamente
                    para o Centro Operacional e para a Factory da Vitrine AI Pro.
                </p>

                @foreach ($products as $name => $product)
                    <div class="product">
                        <h3>{{ $name }}</h3>
                        <p>{{ $product['description'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="card">
                @if ($success)
                    <div class="alert success">
                        Pedido recebido com sucesso. Projeto enviado para homologação.
                    </div>
                    @if ($responseData)
                        <pre>{{ json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                @endif

                @if ($error)
                    <div class="alert error">{{ $error }}</div>
                    @if ($responseData)
                        <pre>{{ json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                    @endif
                @endif

                @if ($errors->any())
                    <div class="alert error">
                        Revise os campos obrigatórios.
                    </div>
                @endif

                <form method="post" action="{{ route('site.contratar.store') }}">
                    @csrf

                    <div class="field">
                        <label>Produto</label>
                        <select name="product">
                            @foreach ($products as $name => $product)
                                <option value="{{ $name }}" @selected(old('product', 'TV Digital Enterprise') === $name)>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field">
                        <label>Plano</label>
                        <select name="plan">
                            <option value="start" @selected(old('plan') === 'start')>Start</option>
                            <option value="enterprise" @selected(old('plan', 'enterprise') === 'enterprise')>Enterprise</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Nome da empresa / cliente</label>
                        <input name="client" required value="{{ old('client') }}" placeholder="Ex: TV Cidade Digital">
                    </div>

                    <div class="grid">
                        <div class="field">
                            <label>E-mail</label>
                            <input type="email" name="email" value="{{ old('email') }}" placeholder="cliente@email.com">
                        </div>
                        <div class="field">
                            <label>Telefone / WhatsApp</label>
                            <input name="phone" value="{{ old('phone') }}" placeholder="(19) 99999-9999">
                        </div>
                    </div>

                    <div class="field">
                        <label>Domínio desejado</label>
                        <input name="domain" value="{{ old('domain') }}" placeholder="cliente.com.br">
                    </div>

                    <div class="field">
                        <label>Observações</label>
                        <textarea name="notes" placeholder="Conte rapidamente o que você precisa">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit">Enviar para análise e implantação</button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
