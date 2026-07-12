<?php
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '_layout.php';

$leads = array_reverse(read_json('leads_empresas.json'));
$q = trim($_GET['q'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$syncFiltro = trim($_GET['sync'] ?? '');

$statusDisponiveis = [];
$syncDisponiveis = [];
$syncCounts = [
    'synchronized' => 0,
    'pending' => 0,
    'failed' => 0,
    'not_configured' => 0,
];

foreach($leads as $lead){
    $status = (string)($lead['status'] ?? 'novo');
    $syncStatus = (string)($lead['sync_master_status'] ?? 'pending');
    $statusDisponiveis[$status] = true;
    $syncDisponiveis[$syncStatus] = true;
    $syncCounts[$syncStatus] = ($syncCounts[$syncStatus] ?? 0) + 1;
}
ksort($statusDisponiveis);
ksort($syncDisponiveis);

$filtrados = array_values(array_filter($leads, function($lead) use ($q, $statusFiltro, $syncFiltro){
    $status = (string)($lead['status'] ?? 'novo');
    $syncStatus = (string)($lead['sync_master_status'] ?? 'pending');
    if($statusFiltro !== '' && $status !== $statusFiltro) return false;
    if($syncFiltro !== '' && $syncStatus !== $syncFiltro) return false;
    if($q === '') return true;

    $texto = implode(' ', [
        $lead['nome'] ?? '',
        $lead['categoria'] ?? '',
        $lead['cidade'] ?? '',
        $lead['bairro'] ?? '',
        $lead['responsavel'] ?? '',
        $lead['whatsapp'] ?? '',
        $lead['email'] ?? '',
        $lead['origem'] ?? '',
        $lead['master_lead_id'] ?? '',
    ]);

    return stripos($texto, $q) !== false;
}));

$syncLabels = [
    'synchronized' => 'Sincronizado',
    'pending' => 'Pendente',
    'failed' => 'Falhou',
    'not_configured' => 'Não configurado',
];

admin_header('Leads Comerciais');
?>
<?php if(isset($_GET['sync_ok'])): ?>
<div class="notice"><strong>Sincronização concluída.</strong> Enviados: <?= (int)$_GET['sync_ok'] ?> · Falhas: <?= (int)($_GET['sync_failed'] ?? 0) ?> · Já sincronizados: <?= (int)($_GET['sync_skipped'] ?? 0) ?></div>
<?php endif; ?>
<?php if(!empty($_GET['sync_error'])): ?><div class="notice error-notice"><?= h($_GET['sync_error']) ?></div><?php endif; ?>

<div class="cards">
  <div class="stat"><strong>Total recebido</strong><h2><?= count($leads) ?></h2></div>
  <div class="stat"><strong>No Comercial Master</strong><h2><?= (int)($syncCounts['synchronized'] ?? 0) ?></h2></div>
  <div class="stat"><strong>Pendentes/Falhas</strong><h2><?= (int)($syncCounts['pending'] ?? 0) + (int)($syncCounts['failed'] ?? 0) + (int)($syncCounts['not_configured'] ?? 0) ?></h2></div>
  <div class="stat"><strong>Resultado do filtro</strong><h2><?= count($filtrados) ?></h2></div>
</div>

<div class="top-actions">
  <a class="btn" href="exportar-leads.php">Exportar CSV</a>
  <a class="btn" href="auditoria-leads.php">Auditar armazenamento</a>
  <form method="post" action="sincronizar-leads.php" style="display:inline">
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <button class="btn" type="submit"<?= master_leads_configured() ? '' : ' disabled title="Configure VITRINE_LEADS_URL e VITRINE_LEADS_TOKEN"' ?>>Sincronizar pendentes</button>
  </form>
</div>

<div class="card">
  <h2>Cadastros gratuitos recebidos</h2>
  <p>O arquivo local continua como contingência. A coluna “Master” confirma quais registros também chegaram ao Centro Operacional.</p>
  <form method="get" style="display:grid;grid-template-columns:minmax(220px,1fr) 190px 190px auto;gap:12px;align-items:end">
    <div><label>Pesquisar</label><input name="q" value="<?= h($q) ?>" placeholder="Empresa, responsável, WhatsApp ou ID"></div>
    <div><label>Status local</label><select name="status"><option value="">Todos</option><?php foreach(array_keys($statusDisponiveis) as $status): ?><option value="<?= h($status) ?>"<?= $statusFiltro === $status ? ' selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></div>
    <div><label>Master</label><select name="sync"><option value="">Todos</option><?php foreach(array_keys($syncDisponiveis) as $syncStatus): ?><option value="<?= h($syncStatus) ?>"<?= $syncFiltro === $syncStatus ? ' selected' : '' ?>><?= h($syncLabels[$syncStatus] ?? $syncStatus) ?></option><?php endforeach; ?></select></div>
    <div><button class="btn" type="submit">Filtrar</button></div>
  </form>
</div>

<table class="admin-table"><thead><tr><th>Data</th><th>Empresa</th><th>Categoria/Cidade</th><th>Responsável</th><th>WhatsApp</th><th>Status local</th><th>Master</th></tr></thead><tbody>
<?php foreach($filtrados as $lead):
    $digits = preg_replace('/\D+/', '', (string)($lead['whatsapp'] ?? ''));
    $whatsappUrl = $digits !== '' ? 'https://wa.me/' . $digits : '';
    $syncStatus = (string)($lead['sync_master_status'] ?? 'pending');
?>
<tr>
  <td><?= h($lead['data'] ?? ($lead['created_at'] ?? '')) ?></td>
  <td><strong><?= h($lead['nome'] ?? '') ?></strong><br><small><?= h($lead['bairro'] ?? '') ?></small></td>
  <td><?= h($lead['categoria'] ?? '') ?><br><small><?= h($lead['cidade'] ?? 'Sumaré') ?></small></td>
  <td><?= h($lead['responsavel'] ?? '') ?><br><small><?= h($lead['email'] ?? '') ?></small></td>
  <td><?php if($whatsappUrl): ?><a href="<?= h($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= h($lead['whatsapp'] ?? '') ?></a><?php else: ?><?= h($lead['whatsapp'] ?? '') ?><?php endif; ?></td>
  <td><span class="status-pill"><?= h($lead['status'] ?? 'novo') ?></span></td>
  <td>
    <span class="status-pill"><?= h($syncLabels[$syncStatus] ?? $syncStatus) ?></span>
    <?php if(!empty($lead['master_lead_id'])): ?><br><small>ID <?= h($lead['master_lead_id']) ?></small><?php endif; ?>
    <?php if(!empty($lead['sync_master_last_error'])): ?><br><small title="<?= h($lead['sync_master_last_error']) ?>">Erro: <?= h(mb_strimwidth($lead['sync_master_last_error'], 0, 55, '…')) ?></small><?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
<?php if(!$filtrados): ?><tr><td colspan="7">Nenhum lead encontrado para os filtros informados.</td></tr><?php endif; ?>
</tbody></table>
<?php admin_footer(); ?>
