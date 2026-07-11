<?php
require_once 'auth.php';
require_once '_layout.php';

$file = DATA_PATH . 'leads_empresas.json';
$leads = read_json('leads_empresas.json');
$ids = [];
$duplicados = [];
$invalidos = [];
$porStatus = [];
$porOrigem = [];

foreach($leads as $index => $lead){
    $id = (string)($lead['id'] ?? '');
    if($id !== ''){
        if(isset($ids[$id])) $duplicados[] = $id;
        $ids[$id] = true;
    }

    if(empty($lead['nome']) || empty($lead['responsavel']) || empty($lead['whatsapp'])){
        $invalidos[] = $index + 1;
    }

    $status = (string)($lead['status'] ?? 'não informado');
    $origem = (string)($lead['origem'] ?? 'não informada');
    $porStatus[$status] = ($porStatus[$status] ?? 0) + 1;
    $porOrigem[$origem] = ($porOrigem[$origem] ?? 0) + 1;
}

arsort($porStatus);
arsort($porOrigem);
$recentes = array_slice(array_reverse($leads), 0, 10);

admin_header('Auditoria de Leads');
?>
<div class="cards">
  <div class="stat"><strong>Total de registros</strong><h2><?= count($leads) ?></h2></div>
  <div class="stat"><strong>Arquivo gravável</strong><h2><?= is_writable($file) ? 'Sim' : 'Não' ?></h2></div>
  <div class="stat"><strong>IDs duplicados</strong><h2><?= count(array_unique($duplicados)) ?></h2></div>
  <div class="stat"><strong>Registros incompletos</strong><h2><?= count($invalidos) ?></h2></div>
</div>

<div class="top-actions">
  <a class="btn" href="leads.php">Voltar aos leads</a>
  <a class="btn" href="exportar-leads.php">Exportar CSV</a>
</div>

<div class="panel" style="width:100%;max-width:none">
  <h2>Integridade do armazenamento</h2>
  <table class="admin-table"><tbody>
    <tr><th>Arquivo</th><td>data/leads_empresas.json</td></tr>
    <tr><th>Existe</th><td><?= file_exists($file) ? 'Sim' : 'Não' ?></td></tr>
    <tr><th>Leitura permitida</th><td><?= is_readable($file) ? 'Sim' : 'Não' ?></td></tr>
    <tr><th>Gravação permitida</th><td><?= is_writable($file) ? 'Sim' : 'Não' ?></td></tr>
    <tr><th>Tamanho</th><td><?= file_exists($file) ? number_format((int)filesize($file), 0, ',', '.') . ' bytes' : '—' ?></td></tr>
    <tr><th>Última alteração</th><td><?= file_exists($file) ? date('d/m/Y H:i:s', (int)filemtime($file)) : '—' ?></td></tr>
  </tbody></table>
</div>

<div class="panel" style="width:100%;max-width:none">
  <h2>Distribuição por status</h2>
  <table class="admin-table"><thead><tr><th>Status</th><th>Quantidade</th></tr></thead><tbody>
  <?php foreach($porStatus as $status => $total): ?><tr><td><?= h($status) ?></td><td><?= (int)$total ?></td></tr><?php endforeach; ?>
  <?php if(!$porStatus): ?><tr><td colspan="2">Nenhum registro encontrado.</td></tr><?php endif; ?>
  </tbody></table>
</div>

<div class="panel" style="width:100%;max-width:none">
  <h2>Distribuição por origem</h2>
  <table class="admin-table"><thead><tr><th>Origem</th><th>Quantidade</th></tr></thead><tbody>
  <?php foreach($porOrigem as $origem => $total): ?><tr><td><?= h($origem) ?></td><td><?= (int)$total ?></td></tr><?php endforeach; ?>
  <?php if(!$porOrigem): ?><tr><td colspan="2">Nenhum registro encontrado.</td></tr><?php endif; ?>
  </tbody></table>
</div>

<div class="panel" style="width:100%;max-width:none">
  <h2>Dez registros mais recentes</h2>
  <table class="admin-table"><thead><tr><th>Data</th><th>Empresa</th><th>Responsável</th><th>WhatsApp</th><th>Origem</th></tr></thead><tbody>
  <?php foreach($recentes as $lead): ?><tr><td><?= h($lead['data'] ?? ($lead['created_at'] ?? '')) ?></td><td><?= h($lead['nome'] ?? '') ?></td><td><?= h($lead['responsavel'] ?? '') ?></td><td><?= h($lead['whatsapp'] ?? '') ?></td><td><?= h($lead['origem'] ?? 'não informada') ?></td></tr><?php endforeach; ?>
  <?php if(!$recentes): ?><tr><td colspan="5">Nenhum lead recebido neste arquivo.</td></tr><?php endif; ?>
  </tbody></table>
</div>
<?php admin_footer(); ?>
