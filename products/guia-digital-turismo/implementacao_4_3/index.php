<?php include 'includes/header.php';
$settings = app_settings();
$attractions = read_json('attractions.json');
$events = read_json('events.json');
$businesses = read_json('businesses.json');
$routes = read_json('routes.json');
$week = null; foreach($attractions as $a){ if(!empty($a['semana'])) { $week=$a; break; } } if(!$week && count($attractions)) $week=$attractions[0];
$featuredEvent = null; foreach($events as $e){ if(!empty($e['destaque'])) { $featuredEvent=$e; break; } } if(!$featuredEvent && count($events)) $featuredEvent=$events[0];
function home_event_label($e){ if(!empty($e['data_label'])) return $e['data_label']; if(!empty($e['data'])) return format_date_br($e['data']); return 'A definir'; }
?>
<section class="hero">
  <img src="assets/img/hero-real.jpg" alt="Sumaré" decoding="async" fetchpriority="high">
  <div class="hero-content">
    <span class="badge">📍 Guia Digital da Cidade</span>
    <h1>Visite Sumaré</h1>
    <p>Turismo, cultura, eventos, gastronomia, hospedagem e comércio local em um guia digital da cidade.</p>
    <div class="actions hero-actions">
      <a class="btn primary" href="atrativos.php">Explorar Cidade</a><a class="hero-category-action" href="eventos.php" aria-label="Ver eventos">
        <span>📅</span>
        <strong>Eventos</strong>
      </a>
    </div>
  </div>
</section>

<div class="search-card"><input data-search placeholder="Buscar atrações, eventos, restaurantes..."><button>Buscar</button></div>

<section class="section"><div class="category-scroll">
<a class="category" href="atrativos.php?cat=Natureza"><span>🌳</span><strong>Natureza</strong></a><a class="category" href="atrativos.php?cat=Cultura"><span>🏛️</span><strong>Cultura</strong></a><a class="category" href="guia-comercial.php?cat=Gastronomia"><span>🍴</span><strong>Gastronomia</strong></a><a class="category" href="guia-comercial.php?cat=Hospedagem"><span>🏨</span><strong>Hospedagem</strong></a><a class="category" href="eventos.php"><span>📅</span><strong>Eventos</strong></a><a class="category" href="guia-comercial.php"><span>🛍️</span><strong>Comércio</strong></a></div></section>

<?php if($featuredEvent): ?>
<section class="section"><div class="section-title"><h2>Evento em Destaque</h2><a href="eventos.php">Agenda ›</a></div>
  <a class="featured-event-home" href="eventos.php?id=<?= h($featuredEvent['id']) ?>">
    <img loading="lazy" decoding="async" src="<?= h(img_url('eventos',($featuredEvent['imagem'] ?? ''))) ?>" alt="<?= h($featuredEvent['titulo'] ?? 'Evento') ?>">
    <div class="featured-event-info">
      <span class="badge">📅 <?= h(home_event_label($featuredEvent)) ?></span>
      <h3><?= h($featuredEvent['titulo'] ?? 'Evento') ?></h3>
      <p><?= h($featuredEvent['descricao_curta'] ?? '') ?></p>
    </div>
  </a>
</section>
<?php endif; ?>

<section class="section"><div class="section-title"><h2>Atrativos</h2><a href="atrativos.php">Ver todos ›</a></div><div class="card-grid">
<?php foreach(array_slice($attractions,0,4) as $a): ?><a class="place-card" data-card href="atrativos.php?id=<?= h($a['id']) ?>"><img loading="lazy" decoding="async" src="<?= h(img_url('atrativos',($a['imagem'] ?? ''))) ?>" alt="<?= h(($a['nome'] ?? '')) ?>"><div class="body"><h3><?= h(($a['nome_card'] ?? $a['nome'] ?? '')) ?></h3><span class="pill"><?= h(strtolower($a['categoria'] ?? 'turismo')) ?></span><span class="rating"><?= h($a['rating'] ?? '4,7') ?></span></div></a><?php endforeach; ?></div></section>

<section class="section"><div class="section-title"><h2>Agenda em Destaque</h2><a href="eventos.php">Ver todos ›</a></div><div class="event-list">
<?php foreach(array_slice($events,0,4) as $e): $ts=strtotime($e['data'] ?? ''); ?><a class="event-row upgraded-event-row" href="eventos.php?id=<?= h($e['id']) ?>"><div class="date-tile <?= $ts?'':'pending' ?>"><b><?= $ts?date('d',$ts):'A' ?></b><small><?= $ts?strtoupper(month_br($e['data'] ?? '')):'agenda' ?></small></div><div><span class="event-type"><?= h($e['categoria'] ?? 'Evento') ?></span><h3><?= h(($e['titulo'] ?? $e['nome'] ?? 'Evento')) ?></h3><p><?= h(home_event_label($e)) ?> • 📍 <?= h(($e['local'] ?? 'Sumaré')) ?></p></div><span class="free-tag"><?= h($e['gratis'] ?? 'Grátis') ?></span></a><?php endforeach; ?></div></section>

<?php if($week): ?><section class="section"><div class="section-title"><h2>Atrativo da Semana</h2></div><article class="featured-card"><img loading="lazy" decoding="async" src="<?= h(img_url('atrativos',$week['imagem'])) ?>" alt="<?= h($week['nome']) ?>"><div class="featured-body"><span class="badge"><?= h($week['categoria']) ?></span><h3><?= h($week['nome']) ?></h3><p><?= h($week['descricao_curta']) ?></p><div class="actions"><a class="btn green" href="atrativos.php?id=<?= h($week['id']) ?>">Conhecer</a><a class="btn" style="background:#eef6f1;color:var(--primary)" href="<?= h(maps_link($week)) ?>" target="_blank">Como chegar</a></div></div></article></section><?php endif; ?>

<section class="section"><div class="section-title"><h2>Roteiros Recomendados</h2><a href="perfil.php">Meu guia ›</a></div><div class="route-grid"><?php foreach(array_slice($routes,0,3) as $r): ?><article class="route-card"><div class="icon"><?= h($r['icone']) ?></div><h3><?= h($r['titulo']) ?></h3><p><?= h($r['descricao']) ?></p></article><?php endforeach; ?></div></section>

<section class="section"><div class="section-title"><h2>Empresas em Destaque</h2><a href="cadastro-empresa.php">Cadastrar ›</a></div><div class="horizontal-list"><?php foreach(array_slice($businesses,0,5) as $b): ?><a class="mini-card" data-card href="guia-comercial.php"><img loading="lazy" decoding="async" src="<?= h(img_url('empresas',($b['imagem'] ?? ''))) ?>" alt="<?= h(($b['nome'] ?? '')) ?>"><div class="body"><span class="pill"><?= h(($b['categoria'] ?? '')) ?></span><h3><?= h(($b['nome'] ?? '')) ?></h3><p>Perfil básico de demonstração.</p></div></a><?php endforeach; ?></div></section>
<?php include 'includes/footer.php'; ?>
