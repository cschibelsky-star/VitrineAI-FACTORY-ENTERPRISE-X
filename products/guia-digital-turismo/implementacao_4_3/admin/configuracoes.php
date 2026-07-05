<?php require 'auth.php'; require '_layout.php';
$settings = read_json('settings.json');
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    foreach(array('cidade','estado','pais','nome_app','slogan','subtitulo','cor_primaria','cor_secundaria','cor_destaque','email','telefone','whatsapp','site','instagram','endereco_secretaria','versao','modo') as $field){
        $settings[$field] = isset($_POST[$field]) ? trim($_POST[$field]) : '';
    }
    if(!empty($_FILES['logo']['name'])){
        $dir = ROOT_PATH . '/uploads/'; if(!is_dir($dir)) mkdir($dir,0775,true);
        $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
        if(in_array($ext, array('jpg','jpeg','png','gif','webp','svg'))){
            $name = 'logo-' . date('YmdHis') . '.' . $ext;
            if(move_uploaded_file($_FILES['logo']['tmp_name'], $dir . $name)) $settings['logo'] = '/uploads/' . $name;
        }
    } else {
        $settings['logo'] = isset($_POST['logo_atual']) ? $_POST['logo_atual'] : ($settings['logo'] ?? '');
    }
    save_json('settings.json', $settings);
    header('Location: configuracoes.php?ok=1'); exit;
}
admin_header('Configurações'); ?>
<?php if(isset($_GET['ok'])): ?><div class="stat">Configurações salvas com sucesso.</div><?php endif; ?>
<form class="panel" style="width:100%;max-width:none" method="post" enctype="multipart/form-data">
<input type="hidden" name="logo_atual" value="<?= h($settings['logo'] ?? '') ?>">
<div class="form-grid">
<?php foreach(array('cidade'=>'Cidade','estado'=>'Estado','pais'=>'País','nome_app'=>'Nome do app','slogan'=>'Slogan','subtitulo'=>'Subtítulo','cor_primaria'=>'Cor primária','cor_secundaria'=>'Cor secundária','cor_destaque'=>'Cor destaque','email'=>'E-mail','telefone'=>'Telefone','whatsapp'=>'WhatsApp','site'=>'Site','instagram'=>'Instagram','endereco_secretaria'=>'Endereço da Secretaria','versao'=>'Versão','modo'=>'Modo') as $key=>$label): ?>
<div><label><?= h($label) ?></label><input name="<?= h($key) ?>" value="<?= h($settings[$key] ?? '') ?>"></div>
<?php endforeach; ?>
<div><label>Logo</label><input type="file" name="logo" accept="image/*"><small>Atual: <?= h($settings['logo'] ?? '') ?></small></div>
</div>
<button>Salvar configurações</button>
</form>
<?php admin_footer(); ?>
