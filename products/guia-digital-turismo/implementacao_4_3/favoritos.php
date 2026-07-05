<?php include 'includes/header.php'; ?>
<section class="page-hero">
  <h1>Favoritos</h1>
  <p>Locais e eventos salvos neste dispositivo para consulta rápida.</p>
</section>
<section class="section">
  <div class="section-title"><h2>Itens salvos</h2><a href="#" onclick="clearFavorites();return false;">Limpar ›</a></div>
  <div class="event-list" data-favorites-list>
    <div class="empty">Carregando favoritos...</div>
  </div>
</section>
<section class="info-card">
  <h2>Como funciona</h2>
  <p>Os favoritos ficam salvos localmente no aparelho. Nesta fase piloto não há login obrigatório nem coleta de dados pessoais para esse recurso.</p>
</section>
<?php include 'includes/footer.php'; ?>
