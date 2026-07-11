<?php
require_once 'auth.php';
require_once '../includes/functions.php';
require_once '_layout.php';

$leads = array_reverse(read_json('leads_empresas.json'));
$q = trim($_GET['q'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');

$statusDisponiveis = [];
foreach($leads as $lead){
    $status = (string)($lead['status'] ?? 'novo');
    $statusDisponiveis[$status] = true;
}
ksort($statusDisponiveis);

$filtrados = array_values(array_filter($leads, function($lead) use ($q, $statusFiltro){
    $status = (string)($lead['status'] ?? 'novo');
    if($statusFiltro !== '' && $status !== $statusFiltro) return false;
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
    ]);

    return stripos($texto, $q) !== false;
}));

admin_header('Leads Comerciais');
?>
<div class="cards">
  <div class="stat"><strong>Total recebido</strong><h2><?= count($leads) ?></h2></div>
  <div class="stat"><strong>Resultado do filtro</strong><h2><?= count($filtrados) ?></h2></div>
</div>

<div class="top-actions">
  <a class="btn" href="exportar-leads.php">Exportar CSV</a>
  <a class="btn" href="auditoria-leads.php">Auditar armazenamento</a>
</div>

<div class="card">
  <h2>Cadastros gratuitos recebidos</h2>
  <p>Empresas captadas pela landing page e pelo app Conheça Sumaré.</p>
  <form method="get" style="display:grid;grid-template-columns:minmax(220px,1fr) 220px auto;gap:12px;align-items:end">
    <div><label>Pesquisar</label><input name="q" value="<?= h($q) ?>" placeholder="Empresa, responsável, WhatsApp ou origem"></div>
    <div><label>Status</label><select name="status"><option value="">Todos</option><?php foreach(array_keys($statusDisponiveis) as $status): ?><option value="<?= h($status) ?>"<?= $statusFiltro === $status ? ' selected' : '' ?>><?= h($status) ?></option><?php endforeach; ?></select></div>
    <div><button class="btn" type="submit">Filtrar</button></div>
  </form>
</div>

<table class="admin-table"><thead><tr><th>Data</th><th>Empresa</th><th>Categoria/Cidade</th><th>Responsável</th><th>WhatsApp</th><th>Origem</th><th>Status</th></tr></thead><tbody>
<?php foreach($filtrados as $lead):
    $digits = preg_replace('/\D+/', '', (string)($lead['whatsapp'] ?? ''));
    $whatsappUrl = $digits !== '' ? 'https://wa.me/' . $digits : '';
?>
<tr>
  <td><?= h($lead['data'] ?? ($lead['created_at'] ?? '')) ?></td>
  <td><strong><?= h($lead['nome'] ?? '') ?></strong><br><small><?= h($lead['bairro'] ?? '') ?></small></td>
  <td><?= h($lead['categoria'] ?? '') ?><br><small><?= h($lead['cidade'] ?? 'Sumaré') ?></small></td>
  <td><?= h($lead['responsavel'] ?? '') ?><br><small><?= h($lead['email'] ?? '') ?></small></td>
  <td><?php if($whatsappUrl): ?><a href="<?= h($whatsappUrl) ?>" target="_blank" rel="noopener noreferrer"><?= h($lead['whatsapp'] ?? '') ?></a><?php else: ?><?= h($lead['whatsapp'] ?? '') ?><?php endif; ?></td>
  <td><?= h($lead['origem'] ?? 'não informada') ?></td>
  <td><span class="status-pill"><?= h($lead['status'] ?? 'novo') ?></span></td>
</tr>
<?php endforeach; ?>
<?php if(!$filtrados): ?><tr><td colspan="7">Nenhum lead encontrado para os filtros informados.</td></tr><?php endif; ?>
</tbody></table>
<?php admin_footer(); ?>
