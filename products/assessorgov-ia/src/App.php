<?php
declare(strict_types=1);

final class App
{
    private PDO $db;

    public function __construct(string $sqlitePath)
    {
        if (!is_dir(dirname($sqlitePath))) {
            mkdir(dirname($sqlitePath), 0775, true);
        }
        $this->db = new PDO('sqlite:' . $sqlitePath);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->db->exec('CREATE TABLE IF NOT EXISTS opportunities (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            agency TEXT NOT NULL,
            segment TEXT NOT NULL,
            kind TEXT NOT NULL,
            location TEXT NOT NULL,
            deadline TEXT NOT NULL,
            budget REAL NOT NULL DEFAULT 0,
            score INTEGER NOT NULL DEFAULT 0,
            status TEXT NOT NULL,
            summary TEXT NOT NULL,
            requirements TEXT NOT NULL,
            favorite INTEGER NOT NULL DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        )');

        if ((int) $this->db->query('SELECT COUNT(*) FROM opportunities')->fetchColumn() === 0) {
            $rows = [
                ['Aquisição de materiais de informática','Prefeitura de Campinas','Empresas','Pregão eletrônico','Campinas/SP','2026-07-28',185000,94,'Recomendada','Fornecimento de notebooks, monitores e periféricos.','CNPJ ativo; CNAE compatível; regularidade fiscal; capacidade técnica.'],
                ['Credenciamento de artistas para programação municipal','Secretaria de Cultura de Sumaré','Cultura','Credenciamento','Sumaré/SP','2026-08-05',48000,97,'Alta aderência','Seleção de artistas, grupos e coletivos culturais.','Portfólio; currículo artístico; comprovante de residência; proposta técnica.'],
                ['Chamamento para oficinas socioeducativas','Fundo Municipal da Criança e do Adolescente','Terceiro Setor','Chamamento público','Hortolândia/SP','2026-08-12',320000,91,'Recomendada','Parceria com OSC para oficinas e acompanhamento social.','CNPJ de OSC; estatuto; experiência; plano de trabalho.'],
                ['Serviços de comunicação digital','Câmara Municipal de Americana','Empresas','Dispensa eletrônica','Americana/SP','2026-07-22',58000,86,'Atenção ao prazo','Produção audiovisual, conteúdo e transmissão digital.','CNAE de comunicação; portfólio; certidões; proposta comercial.'],
                ['Edital PNAB – circulação de espetáculos','Governo do Estado de São Paulo','Cultura','Edital cultural','Estado de SP','2026-09-01',120000,89,'Recomendada','Apoio a projetos de circulação de espetáculos.','Projeto cultural; orçamento; cronograma; documentação.'],
                ['Capacitação para mulheres empreendedoras','Secretaria de Desenvolvimento Social','Terceiro Setor','Termo de colaboração','Paulínia/SP','2026-08-19',210000,84,'Compatível','Trilha de capacitação e mentoria para mulheres.','Plano de trabalho; equipe técnica; experiência; regularidade.']
            ];
            $insert = $this->db->prepare('INSERT INTO opportunities(title,agency,segment,kind,location,deadline,budget,score,status,summary,requirements) VALUES(?,?,?,?,?,?,?,?,?,?,?)');
            foreach ($rows as $row) {
                $insert->execute($row);
            }
        }
    }

    public function run(): void
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';

        if ($path === '/health') {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok', 'product' => 'AssessorGov IA']);
            return;
        }

        if ($path === '/api/opportunities') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($this->db->query('SELECT * FROM opportunities ORDER BY score DESC')->fetchAll(PDO::FETCH_ASSOC), JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($path === '/api/favorite' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $this->db->prepare('UPDATE opportunities SET favorite = CASE favorite WHEN 1 THEN 0 ELSE 1 END WHERE id = ?')->execute([$id]);
            header('Location: /oportunidades');
            return;
        }

        if ($path === '/api/analyze' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $this->db->prepare('SELECT * FROM opportunities WHERE id = ?');
            $stmt->execute([$id]);
            $op = $stmt->fetch(PDO::FETCH_ASSOC);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'ok' => (bool) $op,
                'analysis' => $op
                    ? "A oportunidade tem {$op['score']}% de aderência. Valide: {$op['requirements']} Prazo final: " . date('d/m/Y', strtotime($op['deadline'])) . '.'
                    : 'Oportunidade não encontrada.'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->render($path);
    }

    private function rows(?string $segment = null): array
    {
        if ($segment) {
            $stmt = $this->db->prepare('SELECT * FROM opportunities WHERE segment = ? ORDER BY score DESC');
            $stmt->execute([$segment]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $this->db->query('SELECT * FROM opportunities ORDER BY score DESC')->fetchAll(PDO::FETCH_ASSOC);
    }

    private function render(string $path): void
    {
        $segment = match ($path) {
            '/empresas' => 'Empresas',
            '/cultura' => 'Cultura',
            '/terceiro-setor' => 'Terceiro Setor',
            default => null,
        };
        $rows = $this->rows($segment);
        $total = (int) $this->db->query('SELECT COUNT(*) FROM opportunities')->fetchColumn();
        $high = (int) $this->db->query('SELECT COUNT(*) FROM opportunities WHERE score >= 90')->fetchColumn();
        $favorites = (int) $this->db->query('SELECT COUNT(*) FROM opportunities WHERE favorite = 1')->fetchColumn();
        $budget = (float) $this->db->query('SELECT SUM(budget) FROM opportunities')->fetchColumn();
        $title = $segment ?: ($path === '/oportunidades' ? 'Oportunidades' : 'Visão Geral');
        ?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?= htmlspecialchars($title) ?> | AssessorGov IA</title>
    <link rel="stylesheet" href="/style.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand"><div class="logo">AG</div><div><strong>AssessorGov</strong><span>IA</span></div></div>
    <nav>
        <a href="/" class="<?= $path === '/' ? 'active' : '' ?>">Visão geral</a>
        <a href="/oportunidades" class="<?= $path === '/oportunidades' ? 'active' : '' ?>">Oportunidades</a>
        <small>SEGMENTOS</small>
        <a href="/empresas" class="<?= $path === '/empresas' ? 'active' : '' ?>">Empresas</a>
        <a href="/cultura" class="<?= $path === '/cultura' ? 'active' : '' ?>">Fazedores de Cultura</a>
        <a href="/terceiro-setor" class="<?= $path === '/terceiro-setor' ? 'active' : '' ?>">Terceiro Setor</a>
    </nav>
    <div class="profile"><b>Vitrine IA Pro</b><span>Produto em homologação</span></div>
</aside>
<main>
    <header><div><small>ASSESSORGOV IA</small><h1><?= htmlspecialchars($title) ?></h1></div><button class="primary" onclick="alert('Agente IA será conectado ao provedor configurado.')">Perguntar à IA</button></header>
    <section class="content">
        <?php if ($path === '/'): ?>
            <div class="hero"><div><span>INTELIGÊNCIA PARA NEGÓCIOS PÚBLICOS</span><h2>Encontre, entenda e acompanhe oportunidades públicas.</h2><p>Para empresas, fazedores de cultura e organizações do terceiro setor.</p></div><div class="pulse"><strong>94%</strong><span>aderência média</span></div></div>
            <div class="stats">
                <article><span>Oportunidades</span><strong><?= $total ?></strong></article>
                <article><span>Alta compatibilidade</span><strong><?= $high ?></strong></article>
                <article><span>Favoritos</span><strong><?= $favorites ?></strong></article>
                <article><span>Valor mapeado</span><strong>R$ <?= number_format($budget / 1000, 0, ',', '.') ?> mil</strong></article>
            </div>
        <?php else: ?>
            <div class="toolbar"><input id="search" placeholder="Buscar por título, órgão ou cidade"><select id="segment"><option value="">Todos os segmentos</option><option>Empresas</option><option>Cultura</option><option>Terceiro Setor</option></select></div>
        <?php endif; ?>

        <div id="opList" class="op-list">
            <?php foreach ($rows as $op): ?>
                <article class="op-card" data-title="<?= htmlspecialchars(strtolower($op['title'] . ' ' . $op['agency'] . ' ' . $op['location'])) ?>" data-segment="<?= htmlspecialchars($op['segment']) ?>">
                    <div class="score"><strong><?= (int) $op['score'] ?>%</strong><span>aderência</span></div>
                    <div class="op-main"><div class="badges"><span><?= htmlspecialchars($op['segment']) ?></span><span><?= htmlspecialchars($op['kind']) ?></span></div><h3><?= htmlspecialchars($op['title']) ?></h3><p><?= htmlspecialchars($op['agency']) ?> · <?= htmlspecialchars($op['location']) ?></p><div class="summary"><?= htmlspecialchars($op['summary']) ?></div><div class="meta"><span>Prazo <?= date('d/m/Y', strtotime($op['deadline'])) ?></span><span>R$ <?= number_format((float) $op['budget'], 2, ',', '.') ?></span><span><?= htmlspecialchars($op['status']) ?></span></div></div>
                    <div class="actions"><form method="post" action="/api/favorite"><input type="hidden" name="id" value="<?= (int) $op['id'] ?>"><button><?= $op['favorite'] ? '★' : '☆' ?></button></form><button onclick="analyze(<?= (int) $op['id'] ?>)">Analisar com IA</button></div>
                </article>
            <?php endforeach; ?>
        </div>
    </section>
</main>
<script src="/app.js"></script>
</body>
</html>
<?php
    }
}
