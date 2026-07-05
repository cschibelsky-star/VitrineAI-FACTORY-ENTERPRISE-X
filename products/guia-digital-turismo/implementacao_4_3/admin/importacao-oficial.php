<?php
require 'auth.php';
require '_layout.php';

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

function short_text_oficial($text, $limit = 180){
    $text = trim(preg_replace('/\s+/', ' ', (string)$text));
    if(strlen($text) <= $limit) return $text;
    return substr($text, 0, $limit - 3) . '...';
}

function slugify_oficial($text){
    $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', (string)$text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text ?: 'registro-oficial';
}

function fetch_url_oficial($url){
    $url = trim($url);
    if(!preg_match('#^https://(www\.)?(cultura|turismo)\.sumare\.sp\.gov\.br(/|$)#i', $url)){
        return array('ok'=>false, 'error'=>'Use apenas URLs oficiais dos portais cultura.sumare.sp.gov.br ou turismo.sumare.sp.gov.br.');
    }
    $context = stream_context_create(array(
        'http' => array(
            'timeout' => 12,
            'user_agent' => 'Visite Sumare Guia Digital - Importador Oficial'
        ),
        'ssl' => array(
            'verify_peer' => true,
            'verify_peer_name' => true
        )
    ));
    $html = @file_get_contents($url, false, $context);
    if(!$html){
        return array('ok'=>false, 'error'=>'Não foi possível ler a URL. Verifique se o servidor permite acesso externo via PHP.');
    }
    return array('ok'=>true, 'html'=>$html);
}

function attr_meta_oficial($html, $name){
    $patterns = array(
        '#<meta[^>]+property=["\']'.preg_quote($name,'#').'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>#i',
        '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']'.preg_quote($name,'#').'["\'][^>]*>#i',
        '#<meta[^>]+name=["\']'.preg_quote($name,'#').'["\'][^>]+content=["\']([^"\']+)["\'][^>]*>#i',
        '#<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']'.preg_quote($name,'#').'["\'][^>]*>#i'
    );
    foreach($patterns as $p){
        if(preg_match($p, $html, $m)) return html_entity_decode(trim($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    return '';
}

function resolve_url_oficial($base, $path){
    $path = trim($path);
    if($path === '') return '';
    if(preg_match('#^https?://#i', $path)) return $path;
    $parts = parse_url($base);
    if(!$parts || empty($parts['scheme']) || empty($parts['host'])) return $path;
    $root = $parts['scheme'] . '://' . $parts['host'];
    if(substr($path,0,1) === '/') return $root . $path;
    $dir = isset($parts['path']) ? preg_replace('#/[^/]*$#', '/', $parts['path']) : '/';
    return $root . $dir . $path;
}

function extract_page_oficial($url, $html){
    $title = attr_meta_oficial($html, 'og:title');
    if(!$title && preg_match('#<title[^>]*>(.*?)</title>#is', $html, $m)) $title = html_entity_decode(trim(strip_tags($m[1])), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $description = attr_meta_oficial($html, 'og:description');
    if(!$description) $description = attr_meta_oficial($html, 'description');
    $image = attr_meta_oficial($html, 'og:image');
    if(!$image && preg_match('#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $html, $m)) $image = $m[1];
    $image = resolve_url_oficial($url, $image);
    $title = preg_replace('/\s+/', ' ', str_replace(array('Home |','Portal da Secretaria de Cultura e Turismo de Sumaré'), '', $title));
    $title = trim($title, " \t\n\r\0\x0B-|›");
    return array(
        'title' => $title ?: 'Registro oficial',
        'description' => $description ?: 'Conteúdo importado de fonte oficial da Secretaria Municipal de Cultura e Turismo de Sumaré.',
        'image' => $image,
        'source' => $url
    );
}

$message = '';
$preview = null;
$url = isset($_POST['url_oficial']) ? trim($_POST['url_oficial']) : '';
$tipo = isset($_POST['tipo']) ? $_POST['tipo'] : 'evento';

if(isset($_POST['preview']) && $url){
    $fetch = fetch_url_oficial($url);
    if(!$fetch['ok']) $message = '<div class="notice error">'.h($fetch['error']).'</div>';
    else $preview = extract_page_oficial($url, $fetch['html']);
}

if(isset($_POST['salvar_registro'])){
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $imagem = trim($_POST['imagem'] ?? '');
    $fonte = trim($_POST['fonte'] ?? '');
    $categoria = trim($_POST['categoria'] ?? 'Institucional');
    $tipo = $_POST['tipo'] ?? 'evento';
    if($titulo === '' || $fonte === ''){
        $message = '<div class="notice error">Informe ao menos título e URL da fonte oficial.</div>';
    } else {
        if($tipo === 'atrativo'){
            $file = 'attractions.json';
            $items = read_json($file);
            $id = next_id_admin($items, 'atr');
            $record = array(
                'id' => $id,
                'nome' => $titulo,
                'slug' => slugify_oficial($titulo),
                'categoria' => $categoria,
                'descricao_curta' => short_text_oficial($descricao, 180),
                'descricao' => $descricao,
                'endereco' => 'Sumaré - SP',
                'horario' => 'Consultar informação oficial',
                'maps_query' => $titulo . ' Sumaré SP',
                'imagem' => $imagem,
                'fonte_oficial' => 'Portal Turismo/Cultura Sumaré',
                'url_fonte' => $fonte,
                'imagem_fonte_url' => $imagem,
                'imagem_credito' => 'Fonte oficial: Secretaria Municipal de Cultura e Turismo de Sumaré',
                'imagem_autorizada' => 'Pendente de validação formal para uso definitivo',
                'imagem_status' => 'fonte_oficial_registrada',
                'ativo' => true,
                'destaque' => false
            );
        } else {
            $file = 'events.json';
            $items = read_json($file);
            $id = next_id_admin($items, 'evt');
            $record = array(
                'id' => $id,
                'titulo' => $titulo,
                'nome' => $titulo,
                'slug' => slugify_oficial($titulo),
                'categoria' => $categoria,
                'tipo' => 'Agenda oficial',
                'data' => trim($_POST['data'] ?? ''),
                'data_label' => trim($_POST['data_label'] ?? 'Agenda oficial'),
                'horario' => trim($_POST['horario'] ?? 'Consultar programação oficial'),
                'local' => trim($_POST['local'] ?? 'Sumaré - SP'),
                'endereco' => trim($_POST['local'] ?? 'Sumaré - SP'),
                'descricao_curta' => short_text_oficial($descricao, 180),
                'descricao' => $descricao,
                'programacao' => array('Programação publicada/validada em fonte oficial', 'Informações sujeitas a atualização pela Secretaria'),
                'imagem' => $imagem,
                'fonte_oficial' => 'Portal Cultura/Turismo Sumaré',
                'url_fonte' => $fonte,
                'imagem_fonte_url' => $imagem,
                'imagem_credito' => 'Fonte oficial: Secretaria Municipal de Cultura e Turismo de Sumaré',
                'imagem_autorizada' => 'Pendente de validação formal para uso definitivo',
                'imagem_status' => 'fonte_oficial_registrada',
                'status' => 'importado_fonte_oficial',
                'gratis' => 'Consultar',
                'ativo' => true,
                'destaque' => false
            );
        }
        $items[] = $record;
        save_json($file, $items);
        $message = '<div class="notice success">Registro criado a partir de fonte oficial. Revise o conteúdo antes de publicar.</div>';
        $preview = null;
        $url = '';
    }
}

admin_header('Importação oficial');
?>
<style>
.notice{padding:12px 14px;border-radius:12px;margin:12px 0;font-weight:700}.notice.success{background:#e9f7ef;color:#075b30}.notice.error{background:#fff0f0;color:#9b1c1c}.source-card{background:#fff;border:1px solid #e3e9e6;border-radius:18px;padding:18px;margin:14px 0}.source-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.source-card input,.source-card textarea,.source-card select{width:100%;padding:11px;border:1px solid #d8e2dd;border-radius:10px}.source-card textarea{min-height:110px}.official-badge{display:inline-block;padding:6px 10px;background:#e8f5ee;color:#007a3d;border-radius:999px;font-weight:800;margin-bottom:8px}.help-list li{margin:7px 0}.preview-img{width:100%;max-width:360px;border-radius:14px;border:1px solid #e3e9e6;object-fit:cover}.muted-note{color:#5c6c66;font-size:14px}
@media(max-width:800px){.source-grid{grid-template-columns:1fr}}
</style>

<div class="source-card">
  <span class="official-badge">Fontes oficiais autorizáveis</span>
  <h2>Usar Cultura e Turismo para alimentar agenda, atrativos e imagens</h2>
  <p>Este módulo permite criar um registro a partir de URLs dos portais oficiais <strong>cultura.sumare.sp.gov.br</strong> e <strong>turismo.sumare.sp.gov.br</strong>. Ele registra a fonte da informação e o crédito da imagem para revisão da Secretaria.</p>
  <p class="muted-note"><strong>Importante:</strong> mesmo sendo fonte oficial, mantenha validação formal da Secretaria/Comunicação para uso definitivo das fotos no app oficial.</p>
</div>

<?= $message ?>

<form method="post" class="source-card">
  <h2>1. Ler uma URL oficial</h2>
  <label>Tipo de cadastro</label>
  <select name="tipo">
    <option value="evento" <?= $tipo==='evento'?'selected':'' ?>>Evento / Agenda</option>
    <option value="atrativo" <?= $tipo==='atrativo'?'selected':'' ?>>Atrativo turístico/cultural</option>
  </select>
  <label>URL oficial</label>
  <input name="url_oficial" value="<?= h($url) ?>" placeholder="https://cultura.sumare.sp.gov.br/... ou https://turismo.sumare.sp.gov.br/...">
  <button class="btn" name="preview" value="1" type="submit">Buscar dados da página</button>
</form>

<?php if($preview): ?>
<form method="post" class="source-card">
  <h2>2. Revisar e salvar no app</h2>
  <input type="hidden" name="tipo" value="<?= h($tipo) ?>">
  <input type="hidden" name="fonte" value="<?= h($preview['source']) ?>">
  <div class="source-grid">
    <div>
      <label>Título/Nome</label>
      <input name="titulo" value="<?= h($preview['title']) ?>">
      <label>Categoria</label>
      <input name="categoria" value="<?= h($tipo==='evento' ? 'Agenda Cultural' : 'Atrativo Oficial') ?>">
      <?php if($tipo==='evento'): ?>
        <label>Data YYYY-MM-DD</label>
        <input name="data" placeholder="2026-06-20">
        <label>Rótulo de data</label>
        <input name="data_label" value="Agenda oficial">
        <label>Horário</label>
        <input name="horario" value="Consultar programação oficial">
        <label>Local</label>
        <input name="local" value="Sumaré - SP">
      <?php endif; ?>
      <label>Imagem / URL da imagem</label>
      <input name="imagem" value="<?= h($preview['image']) ?>">
      <p class="muted-note">Fonte: <?= h($preview['source']) ?></p>
    </div>
    <div>
      <?php if($preview['image']): ?><img class="preview-img" src="<?= h($preview['image']) ?>" alt="Prévia da imagem"><?php endif; ?>
      <label>Descrição</label>
      <textarea name="descricao"><?= h($preview['description']) ?></textarea>
    </div>
  </div>
  <button class="btn" name="salvar_registro" value="1" type="submit">Salvar registro para revisão</button>
</form>
<?php endif; ?>

<div class="source-card">
  <h2>Fluxo recomendado</h2>
  <ol class="help-list">
    <li>Copiar a URL do evento no Portal da Cultura ou do atrativo no Portal do Turismo.</li>
    <li>Buscar os dados nesta tela.</li>
    <li>Revisar título, descrição, data, local e imagem.</li>
    <li>Salvar como registro em revisão.</li>
    <li>Entrar em Eventos ou Atrativos, ajustar o texto final e publicar.</li>
  </ol>
</div>
<?php admin_footer(); ?>
