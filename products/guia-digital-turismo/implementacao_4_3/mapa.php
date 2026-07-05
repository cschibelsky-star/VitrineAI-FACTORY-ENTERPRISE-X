<?php include 'includes/header.php'; $attractions=read_json('attractions.json'); $businesses=read_json('businesses.json'); ?>
<section class="page-hero"><h1>Mapa</h1><p>Encontre pontos turísticos, eventos e serviços no guia digital da cidade.</p></section>
<section class="info-card map-card">
  <div class="mock-map" aria-label="Mapa ilustrativo de Sumaré">
    <span class="map-pin nature" style="left:22%;top:34%">🌳</span>
    <span class="map-pin culture" style="left:58%;top:28%">🏛️</span>
    <span class="map-pin food" style="left:42%;top:58%">🍴</span>
    <span class="map-pin service" style="left:72%;top:65%">🏨</span>
    <div class="mock-map-label"><strong>Mapa turístico de Sumaré</strong><small>Rotas abertas no Google Maps/Waze</small></div>
  </div>
</section>
<section class="info-card"><h2>Atrativos</h2><?php foreach(array_slice($attractions,0,10) as $a): ?><div class="info-row map-list-row"><span>📍 <?= h(($a['nome'] ?? '')) ?><br><small><?= h(($a['categoria'] ?? 'Turismo')) ?></small></span><a class="filter active" href="<?= h(maps_link($a)) ?>" target="_blank" rel="noopener">Rota</a></div><?php endforeach; ?></section>
<section class="info-card"><h2>Guia Comercial</h2><?php foreach($businesses as $b): ?><div class="info-row map-list-row"><span>🏢 <?= h(($b['nome'] ?? '')) ?><br><small><?= h(($b['categoria'] ?? '')) ?></small></span><a class="filter active" href="<?= h(maps_link($b)) ?>" target="_blank" rel="noopener">Rota</a></div><?php endforeach; ?></section>
<?php include 'includes/footer.php'; ?>
