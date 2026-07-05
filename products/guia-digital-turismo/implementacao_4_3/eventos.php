<?php
include 'includes/header.php';
$items = read_json('events.json');
$id = isset($_GET['id']) ? $_GET['id'] : null;

function event_label($e){
    if(!empty($e['data_label'])) return $e['data_label'];
    if(!empty($e['data'])) return format_date_br($e['data']);
    return 'A definir';
}

function event_day($e){
    if(!empty($e['data']) && strtotime($e['data'])) return date('d', strtotime($e['data']));
    $label = event_label($e);
    return strtoupper(substr($label, 0, 1));
}

function event_month($e){
    if(!empty($e['data']) && strtotime($e['data'])) return strtoupper(month_br($e['data']));
    $label = event_label($e);
    if(strlen($label) > 10) return 'agenda';
    return strtolower($label);
}

if($id){
    $item = find_by_id($items, $id);
    if(!$item){
        echo '<div class="empty">Evento não encontrado.</div>';
        include 'includes/footer.php';
        exit;
    }
    $programacao = isset($item['programacao']) && is_array($item['programacao']) ? $item['programacao'] : array();
?>
<section class="detail-hero event-detail-hero">
  <img decoding="async" fetchpriority="high" src="<?= h(img_url('eventos',($item['imagem'] ?? ''))) ?>" alt="<?= h($item['titulo'] ?? 'Evento') ?>">
  <div class="overlay">
    <span class="badge">📅 <?= h(event_label($item)) ?></span>
    <h1><?= h($item['titulo'] ?? 'Evento') ?></h1>
    <p><?= h($item['descricao_curta'] ?? '') ?></p>
  </div>
</section>

<section class="info-card event-info-card">
  <div class="event-detail-meta">
    <div><strong>Quando</strong><span><?= h(event_label($item)) ?></span></div>
    <div><strong>Horário</strong><span><?= h($item['horario'] ?? 'A confirmar') ?></span></div>
    <div><strong>Local</strong><span><?= h($item['local'] ?? 'Sumaré') ?></span></div>
  </div>
</section>

<section class="info-card">
  <h2>Sobre o evento</h2>
  <p><?= h($item['descricao'] ?? '') ?></p>
  <div class="info-list">
    <div class="info-row">🏷️ <span><?= h($item['categoria'] ?? 'Evento') ?></span></div>
    <div class="info-row">🎟️ <span><?= h($item['gratis'] ?? 'Consultar') ?></span></div>
    <div class="info-row">📌 <span>Evento cadastrado no guia</span></div>
    <?php if(!empty($item['url_fonte'])): ?><div class="info-row">🔗 <span>Fonte oficial registrada: <a target="_blank" rel="noopener" href="<?= h($item['url_fonte']) ?>">abrir fonte</a></span></div><?php endif; ?>
  </div>
</section>

<?php if(count($programacao)): ?>
<section class="info-card">
  <h2>Destaques da programação</h2>
  <div class="program-grid">
    <?php foreach($programacao as $p): ?>
      <div class="program-item">✓ <?= h($p) ?></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="info-card">
  <h2>Orientações ao visitante</h2>
  <p>Confira as informações do evento e use os botões abaixo para rota, compartilhamento e favoritos. A programação pode ser atualizada a qualquer momento pelos organizadores.</p>
  <div class="event-icon-actions" aria-label="Ações do evento">
    <a class="event-icon-btn" href="<?= h(maps_link(array('nome'=>$item['titulo'] ?? 'Evento','endereco'=>$item['endereco'] ?? ($item['local'] ?? 'Sumaré')))) ?>" target="_blank" rel="noopener" aria-label="Como chegar">
      <span class="event-icon-circle">📍</span>
      <small>Rota</small>
    </a>
    <button class="event-icon-btn" type="button" onclick="shareItem('<?= h($item['titulo'] ?? 'Evento') ?>')" aria-label="Compartilhar evento">
      <span class="event-icon-circle">↗</span>
      <small>Compartilhar</small>
    </button>
    <button class="event-icon-btn" type="button" onclick="favoriteItem('evento_<?= h($item['id'] ?? '') ?>')" aria-label="Salvar evento">
      <span class="event-icon-circle">♡</span>
      <small>Salvar</small>
    </button>
  </div>
</section>
<?php
    include 'includes/footer.php';
    exit;
}

$cat = isset($_GET['cat']) ? $_GET['cat'] : '';
$cats = categories_from($items);
$featured = array();
foreach($items as $e){ if(!empty($e['destaque']) && !empty($e['ativo'])) $featured[] = $e; }
if(!count($featured)) $featured = array_slice($items, 0, 3);
?>

<section class="page-hero events-hero">
  <span class="badge">📅 Agenda de eventos</span>
  <h1>Eventos em Sumaré</h1>
  <p>Programação cultural, turística, religiosa, gastronômica e institucional em um só lugar.</p>
</section>

<section class="section event-feature-section">
  <div class="section-title">
    <h2>Em destaque</h2>
    <a href="#agenda-completa">Agenda completa ›</a>
  </div>
  <div class="horizontal-list event-feature-list">
    <?php foreach(array_slice($featured,0,4) as $e): ?>
      <a class="event-feature-card" href="eventos.php?id=<?= h($e['id']) ?>">
        <img loading="lazy" decoding="async" src="<?= h(img_url('eventos',($e['imagem'] ?? ''))) ?>" alt="<?= h($e['titulo'] ?? 'Evento') ?>">
        <div class="event-feature-body">
          <span class="pill"><?= h($e['categoria'] ?? 'Evento') ?></span>
          <h3><?= h($e['titulo'] ?? 'Evento') ?></h3>
          <p><?= h(event_label($e)) ?> • <?= h($e['local'] ?? 'Sumaré') ?></p>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="section event-dashboard">
  <div class="event-stat"><strong><?= count($items) ?></strong><span>eventos cadastrados</span></div>
  <div class="event-stat"><strong><?= count($cats) ?></strong><span>categorias</span></div>
  <div class="event-stat"><strong>RC</strong><span>agenda em atualização</span></div>
</section>

<div class="filters event-filters">
  <a class="filter <?= $cat==''?'active':'' ?>" href="eventos.php">Todos</a>
  <?php foreach($cats as $c): ?>
    <a class="filter <?= $cat==$c?'active':'' ?>" href="eventos.php?cat=<?= urlencode($c) ?>"><?= h($c) ?></a>
  <?php endforeach; ?>
</div>

<section class="section" id="agenda-completa">
  <div class="section-title">
    <h2>Agenda completa</h2>
  </div>
  <div class="event-list upgraded-event-list">
    <?php $shown = 0; foreach($items as $e): if($cat && ($e['categoria'] ?? '') !== $cat) continue; $shown++; ?>
      <a class="event-row upgraded-event-row" href="eventos.php?id=<?= h($e['id']) ?>">
        <div class="date-tile <?= (!empty($e['data']) && strtotime($e['data']))?'':'pending' ?>">
          <b><?= h(event_day($e)) ?></b>
          <small><?= h(event_month($e)) ?></small>
        </div>
        <div class="event-row-content">
          <span class="event-type"><?= h($e['tipo'] ?? ($e['categoria'] ?? 'Evento')) ?></span>
          <h3><?= h($e['titulo'] ?? ($e['nome'] ?? 'Evento')) ?></h3>
          <p><?= h(event_label($e)) ?> • 📍 <?= h($e['local'] ?? 'Sumaré') ?></p>
        </div>
        <span class="free-tag"><?= h($e['gratis'] ?? 'Consultar') ?></span>
      </a>
    <?php endforeach; ?>
    <?php if(!$shown): ?>
      <div class="empty">Nenhum evento encontrado nesta categoria.</div>
    <?php endif; ?>
  </div>
</section>


<?php include 'includes/footer.php'; ?>
