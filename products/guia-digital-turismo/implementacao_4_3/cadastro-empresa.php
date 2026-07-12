<?php
require_once __DIR__ . '/includes/functions.php';
ensure_session_started();
$ok = false;
$erro = '';

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $nome = trim($_POST['nome'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $whatsapp = trim($_POST['whatsapp'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $cidade = trim($_POST['cidade'] ?? 'Sumaré');
    $consentimento = isset($_POST['consentimento_lgpd']);
    $honeypot = trim($_POST['website'] ?? '');

    if(!csrf_is_valid($_POST['csrf_token'] ?? null)){
        $erro = 'A sessão do formulário expirou. Atualize a página e tente novamente.';
    } elseif($honeypot !== ''){
        $erro = 'Não foi possível processar a solicitação.';
    } elseif($nome === '' || $categoria === '' || $responsavel === '' || $whatsapp === ''){
        $erro = 'Preencha nome da empresa, categoria, responsável e WhatsApp.';
    } elseif($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)){
        $erro = 'Informe um e-mail válido.';
    } elseif(!$consentimento){
        $erro = 'É necessário autorizar o tratamento dos dados para análise da solicitação.';
    } else {
        $lead = array(
            'id' => 'lead_' . date('YmdHis') . '_' . bin2hex(random_bytes(3)),
            'data' => date('Y-m-d H:i:s'),
            'created_at' => date(DATE_ATOM),
            'status' => 'aguardando_aprovacao',
            'plano' => 'gratuito',
            'nome' => $nome,
            'categoria' => $categoria,
            'cidade' => $cidade !== '' ? $cidade : 'Sumaré',
            'bairro' => trim($_POST['bairro'] ?? ''),
            'endereco' => trim($_POST['endereco'] ?? ''),
            'descricao' => trim($_POST['descricao'] ?? ''),
            'responsavel' => $responsavel,
            'whatsapp' => $whatsapp,
            'email' => $email,
            'consentimento_lgpd' => true,
            'origem' => 'landing_conheca_sumare',
            'sync_master_status' => master_leads_configured() ? 'pending' : 'not_configured',
            'sync_master_attempts' => 0,
            'sync_master_last_error' => null,
            'master_lead_id' => null,
            'synced_at' => null,
        );

        if(append_json_record('leads_empresas.json', $lead)){
            if(master_leads_configured()){
                $sync = sync_lead_to_master($lead);
                update_json_record('leads_empresas.json', $lead['id'], [
                    'sync_master_status' => $sync['success'] ? 'synchronized' : 'failed',
                    'sync_master_attempts' => 1,
                    'sync_master_last_error' => $sync['error'] ?? null,
                    'sync_master_http_status' => $sync['status_code'] ?? 0,
                    'sync_master_duplicate' => !empty($sync['duplicate']),
                    'master_lead_id' => $sync['lead_id'] ?? null,
                    'synced_at' => $sync['success'] ? date(DATE_ATOM) : null,
                ]);
            }

            $ok = true;
            unset($_SESSION['csrf_token']);
        } else {
            $erro = 'Não foi possível registrar a solicitação. Tente novamente ou entre em contato com a equipe.';
        }
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
  <form method="post" novalidate>
    <input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>">
    <div aria-hidden="true" style="position:absolute;left:-9999px"><label>Website</label><input name="website" tabindex="-1" autocomplete="off"></div>
    <label>Nome da empresa *</label><input name="nome" required maxlength="160" value="<?= h($_POST['nome'] ?? '') ?>" placeholder="Ex.: Restaurante Central">
    <label>Categoria *</label><select name="categoria" required><option value="">Selecione</option><?php foreach(['Gastronomia','Hospedagem','Comércio','Serviços','Turismo','Cultura e Eventos'] as $op): ?><option<?= (($_POST['categoria'] ?? '') === $op) ? ' selected' : '' ?>><?= h($op) ?></option><?php endforeach; ?></select>
    <label>Cidade *</label><input name="cidade" required maxlength="100" value="<?= h($_POST['cidade'] ?? 'Sumaré') ?>">
    <label>Bairro</label><input name="bairro" maxlength="120" value="<?= h($_POST['bairro'] ?? '') ?>" placeholder="Ex.: Centro">
    <label>Endereço</label><input name="endereco" maxlength="220" value="<?= h($_POST['endereco'] ?? '') ?>" placeholder="Usado internamente para validação e mapa">
    <label>Descrição curta</label><textarea name="descricao" maxlength="280" placeholder="Conte em poucas palavras o que sua empresa oferece."><?= h($_POST['descricao'] ?? '') ?></textarea>
    <label>Responsável *</label><input name="responsavel" required maxlength="160" value="<?= h($_POST['responsavel'] ?? '') ?>" placeholder="Nome de contato interno">
    <label>WhatsApp interno *</label><input name="whatsapp" required maxlength="30" inputmode="tel" value="<?= h($_POST['whatsapp'] ?? '') ?>" placeholder="Não será exibido no perfil gratuito">
    <label>E-mail</label><input type="email" name="email" maxlength="180" value="<?= h($_POST['email'] ?? '') ?>" placeholder="Não será exibido no perfil gratuito">
    <label style="display:flex;gap:10px;align-items:flex-start"><input type="checkbox" name="consentimento_lgpd" value="1" required style="width:auto;margin-top:4px"<?= isset($_POST['consentimento_lgpd']) ? ' checked' : '' ?>><span>Autorizo o tratamento destes dados exclusivamente para análise da solicitação, contato e eventual inclusão da empresa no Conheça Sumaré.</span></label>
    <button class="btn green" type="submit">Enviar solicitação</button>
  </form>
</section>
<section class="info-card"><h2>Como funciona a inclusão?</h2><p>Após aprovação, o estabelecimento poderá aparecer no guia com informações básicas validadas. Dados de contato devem ser informados pelo responsável ou autorizados formalmente.</p></section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
