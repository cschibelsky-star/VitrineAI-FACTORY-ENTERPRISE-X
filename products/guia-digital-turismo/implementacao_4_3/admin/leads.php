<?php require_once 'auth.php'; require_once '../includes/functions.php'; include '_layout.php'; admin_header('Leads Comerciais');
$leads = read_json('leads_empresas.json');
$leads = array_reverse($leads);
?>
<div class="card"><h2>Cadastros gratuitos recebidos</h2><p>Empresas captadas pela landing page e pelo app Conheça Sumaré.</p></div>
<table class="admin-table"><thead><tr><th>Data</th><th>Empresa</th><th>Categoria</th><th>Responsável</th><th>WhatsApp</th><th>Status</th></tr></thead><tbody>
<?php foreach($leads as $l): ?><tr><td><?= h($l['data'] ?? '') ?></td><td><strong><?= h($l['nome'] ?? '') ?></strong><br><small><?= h($l['bairro'] ?? '') ?></small></td><td><?= h($l['categoria'] ?? '') ?></td><td><?= h($l['responsavel'] ?? '') ?><br><small><?= h($l['email'] ?? '') ?></small></td><td><?= h($l['whatsapp'] ?? '') ?></td><td><span class="status-pill"><?= h($l['status'] ?? 'novo') ?></span></td></tr><?php endforeach; ?>
<?php if(!$leads): ?><tr><td colspan="6">Nenhum lead recebido ainda.</td></tr><?php endif; ?>
</tbody></table>
<?php admin_footer(); ?>
