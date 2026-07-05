<?php
require_once __DIR__ . '/includes/functions.php';
$ok = false; $erro = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if($nome === '' || $categoria === '' || $responsavel === '' || $whatsapp === ''){
        $erro = 'Preencha nome da empresa, categoria, responsável e WhatsApp.';
    } else {
        $leads = read_json('leads_empresas.json');
        $lead = array(
            'id' => 'lead_' . date('YmdHis') . '_' . substr(md5($nome.$whatsapp),0,6),
            'data' => date('Y-m-d H:i:s'),
            'status' => 'aguardando_aprovacao',
            'plano' => 'gratuito',
            'nome' => $nome,
            'categoria' => $categoria,
            'bairro' => trim($_POST['bairro'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'responsavel' => $responsavel,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'origem' => 'landing_conheca_sumare'
        );
        $leads[] = $lead;
        save_json('leads_empresas.json', $leads);
        $ok = true;
    }
}
include 'includes/header.php';
?>
<section class="page-hero"><span class="badge">Solicitação de inclusão</span><h1>Solicite inclusão no guia</h1><p>Envie os dados do estabelecimento para avaliação da equipe responsável pelo guia digital da cidade.</p></section>
<?php if($ok): ?>
<section class="success-card">
  <div class="success-icon">✅</div>
  <h2>Solicitação recebida</h2>
  <p>Sua solicitação entrou na fila de validação. A publicação dependerá da conferência das informações e das regras institucionais do guia.</p>
  <div class="actions"><a class="btn green" href="index.php">Voltar ao início</a><a class="btn soft" href="guia-comercial.php">Ver guia</a></div>
</section>
<?php else: ?>
<?php if($erro): ?><div class="notice error-notice"><?= h($erro) ?></div><?php endif; ?>
<section class="form-card">
  <form method="post">
    <label>Nome da empresa *</label><input name="nome" required placeholder="Ex.: Restaurante Central">
    <label>Categoria *</label><select name="categoria" required><option value="">Selecione</option><option>Gastronomia</option><option>Hospedagem</option><option>Comércio</option><option>Serviços</option><option>Turismo</option><option>Cultura e Eventos</option></select>
    <label>Bairro</label><input name="bairro" placeholder="Ex.: Centro">
    <label>Endereço</label><input name="endereco" placeholder="Usado internamente para validação e mapa">
    <label>Descrição curta</label><textarea name="descricao" maxlength="280" placeholder="Conte em poucas palavras o que sua empresa oferece."></textarea>
    <label>Responsável *</label><input name="responsavel" required placeholder="Nome de contato interno">
    <label>WhatsApp interno *</label><input name="whatsapp" required placeholder="Não será exibido no perfil gratuito">
    <label>E-mail</label><input type="email" name="email" placeholder="Não será exibido no perfil gratuito">
    <button class="btn green" type="submit">Enviar solicitação</button>
  </form>
</section>
<section class="info-card"><h2>Como funciona a inclusão?</h2><p>Após aprovação, o estabelecimento poderá aparecer no guia com informações básicas validadas. Dados de contato devem ser informados pelo responsável ou autorizados formalmente.</p></section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
