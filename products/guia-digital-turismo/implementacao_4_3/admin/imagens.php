<?php
require 'auth.php';
require '_layout.php';

$files = array(
    'attractions.json' => 'Atrativo',
    'events.json' => 'Evento',
    'businesses.json' => 'Empresa'
);

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $file = $_POST['file'] ?? '';
    $id = $_POST['id'] ?? '';
    if(isset($files[$file]) && $id !== ''){
        $items = read_json($file);
        foreach($items as $k => $item){
            if((string)($item['id'] ?? '') === (string)$id){
                $items[$k]['fonte_imagem'] = trim($_POST['fonte_imagem'] ?? '');
                $items[$k]['imagem_status'] = trim($_POST['imagem_status'] ?? 'provisoria_referencia');
                $items[$k]['imagem_origem_tipo'] = trim($_POST['imagem_origem_tipo'] ?? 'referencia_visual');
                $items[$k]['imagem_fonte_url'] = trim($_POST['imagem_fonte_url'] ?? '');
                $items[$k]['imagem_credito'] = trim($_POST['imagem_credito'] ?? '');
                $items[$k]['imagem_autorizada'] = trim($_POST['imagem_autorizada'] ?? 'nao');
                $items[$k]['imagem_observacao'] = trim($_POST['imagem_observacao'] ?? '');
                break;
            }
        }
        save_json($file, $items);
    }
    header('Location: imagens.php?ok=1');
    exit;
}

admin_header('Banco de Imagens');
?>
<div class="panel" style="width:100%;max-width:none">
    <h2>Curadoria visual do protótipo</h2>
    <p>Use esta tela para registrar a origem das imagens usadas no app piloto. Imagens de internet podem ser utilizadas nesta fase como referência visual, mas devem ser substituídas por fotos oficiais ou autorizadas na versão final.</p>
    <p><strong>Status recomendado para o piloto:</strong> <code>provisoria_referencia</code>. Quando houver autorização, altere para <code>autorizada</code> ou <code>oficial</code>.</p>
</div>
<?php foreach($files as $file => $label): $items = read_json($file); ?>
<h2><?= h($label) ?>s</h2>
<div class="table">
<table>
<thead><tr><th>Item</th><th>Imagem</th><th>Status</th><th>Origem / Crédito</th><th>Ações</th></tr></thead>
<tbody>
<?php foreach($items as $item): ?>
<tr>
<td><strong><?= h($item['nome'] ?? ($item['titulo'] ?? '')) ?></strong><br><small><?= h($item['categoria'] ?? '') ?></small></td>
<td><small><?= h($item['imagem'] ?? '') ?></small></td>
<td><?= h($item['imagem_status'] ?? 'provisoria_referencia') ?><br><small>Autorizada: <?= h($item['imagem_autorizada'] ?? 'nao') ?></small></td>
<td><small><?= h($item['fonte_imagem'] ?? '') ?></small><br><small><?= h($item['imagem_fonte_url'] ?? '') ?></small><br><small><?= h($item['imagem_credito'] ?? '') ?></small></td>
<td><details><summary class="btn muted">Editar origem</summary>
<form method="post" style="margin-top:12px;min-width:320px">
<input type="hidden" name="file" value="<?= h($file) ?>">
<input type="hidden" name="id" value="<?= h($item['id'] ?? '') ?>">
<label>Status</label>
<input name="imagem_status" value="<?= h($item['imagem_status'] ?? 'provisoria_referencia') ?>">
<label>Tipo de origem</label>
<input name="imagem_origem_tipo" value="<?= h($item['imagem_origem_tipo'] ?? 'referencia_visual') ?>">
<label>Link de origem</label>
<input name="imagem_fonte_url" value="<?= h($item['imagem_fonte_url'] ?? '') ?>">
<label>Crédito</label>
<input name="imagem_credito" value="<?= h($item['imagem_credito'] ?? '') ?>">
<label>Autorizada?</label>
<input name="imagem_autorizada" value="<?= h($item['imagem_autorizada'] ?? 'nao') ?>">
<label>Fonte/observação</label>
<textarea name="fonte_imagem"><?= h($item['fonte_imagem'] ?? '') ?></textarea>
<label>Observação interna</label>
<textarea name="imagem_observacao"><?= h($item['imagem_observacao'] ?? '') ?></textarea>
<button type="submit">Salvar origem</button>
</form></details></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php endforeach; ?>
<?php admin_footer(); ?>
