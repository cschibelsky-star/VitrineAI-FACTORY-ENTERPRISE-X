<?php
require 'auth.php';
require '_layout.php';

if(!isset($FILE, $TITLE, $FOLDER, $FIELDS, $PRIMARY_LABEL)){
    die('Configuração do CRUD ausente.');
}

$items = read_json($FILE);
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? $_GET['id'] : '';

function next_id_admin($items, $prefix = 'item'){
    $max = 0;
    foreach($items as $item){
        if(isset($item['id']) && preg_match('/(\d+)$/', (string)$item['id'], $m)){
            $n = (int)$m[1];
            if($n > $max) $max = $n;
        }
    }
    return $prefix . '_' . str_pad($max + 1, 3, '0', STR_PAD_LEFT);
}

function upload_image_admin($folder){
    if(empty($_FILES['imagem']['name'])) return '';
    $dir = ROOT_PATH . '/uploads/' . trim($folder, '/') . '/';
    if(!is_dir($dir)) mkdir($dir, 0775, true);
    $ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));
    $allowed = array('jpg','jpeg','png','gif','webp');
    if(!in_array($ext, $allowed)) return '';
    $safe = preg_replace('/[^a-zA-Z0-9._-]+/', '-', $_FILES['imagem']['name']);
    $name = date('YmdHis') . '-' . $safe;
    if(move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $name)) return $name;
    return '';
}

if($action === 'delete' && $id !== ''){
    $new = array();
    foreach($items as $item){
        if((string)($item['id'] ?? '') !== (string)$id) $new[] = $item;
    }
    save_json($FILE, $new);
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $postId = isset($_POST['id']) ? $_POST['id'] : '';
    $prefix = isset($ID_PREFIX) ? $ID_PREFIX : strtolower(substr($FILE,0,3));
    $record = array('id' => $postId !== '' ? $postId : next_id_admin($items, $prefix));

    // Preserve campos estruturais existentes que não aparecem no formulário,
    // como programação, destaque, status interno e metadados de imagem.
    if($postId !== ''){
        $existing = find_by_id($items, $postId);
        if(is_array($existing)) $record = $existing;
        $record['id'] = $postId;
    }

    foreach($FIELDS as $key => $label){
        $record[$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
    }
    $img = upload_image_admin($FOLDER);
    $record['imagem'] = $img ? $img : (isset($_POST['imagem_atual']) ? $_POST['imagem_atual'] : ($record['imagem'] ?? ''));

    if($postId !== ''){
        $found = false;
        foreach($items as $k => $item){
            if((string)($item['id'] ?? '') === (string)$postId){
                $items[$k] = $record;
                $found = true;
                break;
            }
        }
        if(!$found) $items[] = $record;
    } else {
        $items[] = $record;
    }
    save_json($FILE, $items);
    header('Location: ' . basename($_SERVER['PHP_SELF']));
    exit;
}

$editing = null;
if($id !== '') $editing = find_by_id($items, $id);

if($action === 'new' || $editing){
    admin_header(($editing ? 'Editar ' : 'Novo ') . $TITLE);
?>
<form class="panel" style="width:100%;max-width:none" method="post" enctype="multipart/form-data">
    <input type="hidden" name="id" value="<?= h($editing['id'] ?? '') ?>">
    <input type="hidden" name="imagem_atual" value="<?= h($editing['imagem'] ?? '') ?>">
    <div class="form-grid">
        <?php foreach($FIELDS as $key => $label): ?>
            <div>
                <label><?= h($label) ?></label>
                <?php if(in_array($key, array('descricao', 'descricao_curta'))): ?>
                    <textarea name="<?= h($key) ?>"><?= h($editing[$key] ?? '') ?></textarea>
                <?php else: ?>
                    <input name="<?= h($key) ?>" value="<?= h($editing[$key] ?? '') ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div>
            <label>Imagem</label>
            <input type="file" name="imagem" accept="image/*">
            <small>Atual: <?= h($editing['imagem'] ?? '') ?></small>
        </div>
    </div>
    <button type="submit">Salvar</button>
    <a class="btn muted" href="<?= h(basename($_SERVER['PHP_SELF'])) ?>">Cancelar</a>
</form>
<?php
    admin_footer();
    exit;
}

admin_header($TITLE);
?>
<div class="top-actions">
    <a class="btn" href="?action=new">+ Novo</a>
</div>
<div class="table">
<table>
<thead>
<tr><th>ID</th><th><?= h($PRIMARY_LABEL) ?></th><th>Categoria</th><th>Imagem</th><th>Ações</th></tr>
</thead>
<tbody>
<?php foreach($items as $item): ?>
<tr>
    <td><?= h($item['id'] ?? '') ?></td>
    <td><?= h($item['nome'] ?? ($item['titulo'] ?? '')) ?></td>
    <td><?= h($item['categoria'] ?? '') ?></td>
    <td><?= h($item['imagem'] ?? '') ?></td>
    <td>
        <a class="btn muted" href="?action=edit&id=<?= h($item['id'] ?? '') ?>">Editar</a>
        <a class="btn danger" href="?action=delete&id=<?= h($item['id'] ?? '') ?>" onclick="return confirm('Excluir este registro?')">Excluir</a>
    </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<?php admin_footer(); ?>
