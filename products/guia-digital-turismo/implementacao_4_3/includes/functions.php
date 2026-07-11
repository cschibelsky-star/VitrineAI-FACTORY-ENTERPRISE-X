<?php
require_once __DIR__ . '/../config/config.php';

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function ensure_session_started(){
    if(session_status() !== PHP_SESSION_ACTIVE){
        session_start();
    }
}

function csrf_token(){
    ensure_session_started();
    if(empty($_SESSION['csrf_token'])){
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_is_valid($token){
    ensure_session_started();
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function read_json($file){
    $path = DATA_PATH . $file;
    if(!file_exists($path)) return array();
    $json = file_get_contents($path);
    if($json === false) return array();
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function encode_json_data($data){
    return json_encode(
        $data,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
    );
}

function save_json($file, $data){
    $path = DATA_PATH . $file;
    $json = encode_json_data($data);
    if($json === false) return false;

    $temp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $written = file_put_contents($temp, $json, LOCK_EX);
    if($written === false){
        @unlink($temp);
        return false;
    }

    if(!rename($temp, $path)){
        @unlink($temp);
        return false;
    }

    return $written;
}

function mutate_json_records($file, callable $mutator){
    $path = DATA_PATH . $file;
    $handle = fopen($path, 'c+');
    if($handle === false) return false;

    $success = false;
    try {
        if(!flock($handle, LOCK_EX)) return false;
        rewind($handle);
        $contents = stream_get_contents($handle);
        if($contents === false) return false;

        if(trim($contents) === ''){
            $items = array();
        } else {
            $items = json_decode($contents, true);
            if(json_last_error() !== JSON_ERROR_NONE || !is_array($items)){
                return false;
            }
        }

        $updated = $mutator($items);
        if(!is_array($updated)) return false;

        $json = encode_json_data($updated);
        if($json === false) return false;

        rewind($handle);
        if(!ftruncate($handle, 0)) return false;
        $written = fwrite($handle, $json);
        if($written === false) return false;
        fflush($handle);
        $success = true;
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }

    return $success;
}

function append_json_record($file, $record){
    return mutate_json_records($file, function(array $items) use ($record){
        $items[] = $record;
        return $items;
    });
}

function update_json_record($file, $id, array $changes){
    $found = false;
    $saved = mutate_json_records($file, function(array $items) use ($id, $changes, &$found){
        foreach($items as $index => $item){
            if((string)($item['id'] ?? '') === (string)$id){
                $items[$index] = array_merge($item, $changes);
                $found = true;
                break;
            }
        }
        return $items;
    });

    return $saved && $found;
}

function master_leads_configured(){
    return defined('MASTER_LEADS_CONFIGURED') && MASTER_LEADS_CONFIGURED;
}

function lead_master_payload(array $lead){
    $capturedAt = $lead['created_at'] ?? null;
    if(!$capturedAt && !empty($lead['data'])){
        $timestamp = strtotime((string)$lead['data']);
        $capturedAt = $timestamp ? date(DATE_ATOM, $timestamp) : null;
    }

    $notes = array_filter([
        !empty($lead['categoria']) ? 'Categoria: ' . $lead['categoria'] : null,
        !empty($lead['bairro']) ? 'Bairro: ' . $lead['bairro'] : null,
        !empty($lead['endereco']) ? 'Endereço informado: ' . $lead['endereco'] : null,
        !empty($lead['descricao']) ? 'Descrição: ' . $lead['descricao'] : null,
        'Solicitação de cadastro gratuito no Conheça Sumaré.',
    ]);

    return [
        'external_id' => 'conheca_sumare:' . (string)($lead['id'] ?? ''),
        'empresa' => $lead['nome'] ?? null,
        'contato' => $lead['responsavel'] ?? '',
        'telefone' => $lead['whatsapp'] ?? '',
        'email' => $lead['email'] ?? null,
        'cidade' => $lead['cidade'] ?? 'Sumaré',
        'estado' => 'SP',
        'produto_interesse' => 'Visite Cidade',
        'plano_sugerido' => 'Beta',
        'valor_estimado' => 0,
        'origem_lead' => 'Conheça Sumaré',
        'pagina_origem' => 'conhecasumare.com.br/cadastro-empresa.php',
        'campanha' => 'cadastro_gratuito_empresas',
        'consentimento_lgpd' => !empty($lead['consentimento_lgpd']),
        'capturado_em' => $capturedAt,
        'observacoes' => implode("\n", $notes),
        'metadata' => [
            'local_id' => $lead['id'] ?? null,
            'categoria' => $lead['categoria'] ?? null,
            'bairro' => $lead['bairro'] ?? null,
            'endereco' => $lead['endereco'] ?? null,
            'plano_local' => $lead['plano'] ?? null,
            'origem_local' => $lead['origem'] ?? null,
        ],
    ];
}

function post_master_lead(array $payload){
    if(!master_leads_configured()){
        return [
            'success' => false,
            'status_code' => 0,
            'error' => 'Integração com o Master não configurada.',
        ];
    }

    $json = encode_json_data($payload);
    if($json === false){
        return ['success' => false, 'status_code' => 0, 'error' => 'Falha ao codificar o lead.'];
    }

    $responseBody = false;
    $statusCode = 0;
    $transportError = '';

    if(function_exists('curl_init')){
        $curl = curl_init(MASTER_LEADS_API_URL);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json',
                'X-Vitrine-Lead-Key: ' . MASTER_LEADS_TOKEN,
            ],
            CURLOPT_POSTFIELDS => $json,
        ]);
        $responseBody = curl_exec($curl);
        $statusCode = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $transportError = curl_error($curl);
        curl_close($curl);
    } else {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 12,
                'ignore_errors' => true,
                'header' => implode("\r\n", [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'X-Vitrine-Lead-Key: ' . MASTER_LEADS_TOKEN,
                ]),
                'content' => $json,
            ],
        ]);
        $responseBody = @file_get_contents(MASTER_LEADS_API_URL, false, $context);
        $headers = $http_response_header ?? [];
        if(isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $matches)){
            $statusCode = (int)$matches[1];
        }
        if($responseBody === false){
            $transportError = 'Falha de transporte HTTP.';
        }
    }

    $decoded = is_string($responseBody) ? json_decode($responseBody, true) : null;
    $success = $statusCode >= 200 && $statusCode < 300 && is_array($decoded) && !empty($decoded['success']);

    return [
        'success' => $success,
        'status_code' => $statusCode,
        'lead_id' => is_array($decoded) ? ($decoded['lead_id'] ?? null) : null,
        'duplicate' => is_array($decoded) ? !empty($decoded['duplicate']) : false,
        'error' => $success ? null : ($transportError ?: (is_array($decoded) ? ($decoded['message'] ?? 'Falha na sincronização.') : 'Resposta inválida da API.')),
    ];
}

function sync_lead_to_master(array $lead){
    return post_master_lead(lead_master_payload($lead));
}

function app_settings(){
    static $settings = null;
    if($settings === null){
        $settings = read_json('settings.json');
    }
    return $settings;
}

function find_by_id($items, $id){
    foreach($items as $item){
        if(isset($item['id']) && (string)$item['id'] === (string)$id) return $item;
    }
    return null;
}

function img_url($folder, $filename){
    if(!$filename) return 'assets/img/hero-sumare.svg';
    if(strpos($filename, 'http://') === 0 || strpos($filename, 'https://') === 0) return $filename;
    $clean = ltrim($filename, '/');
    if(file_exists(ROOT_PATH . '/' . $clean)) return $clean;
    $upload = 'uploads/' . trim($folder, '/') . '/' . $clean;
    if(file_exists(ROOT_PATH . '/' . $upload)) return $upload;
    $asset = 'assets/img/' . trim($folder, '/') . '/' . $clean;
    if(file_exists(ROOT_PATH . '/' . $asset)) return $asset;
    return 'assets/img/hero-sumare.svg';
}

function categories_from($items){
    $cats = array();
    foreach($items as $item){
        if(!empty($item['categoria'])) $cats[$item['categoria']] = true;
    }
    return array_keys($cats);
}

function active($file){
    return basename($_SERVER['SCRIPT_NAME']) === $file ? 'active' : '';
}

function format_date_br($date){
    if(!$date) return 'A confirmar';
    $ts = strtotime($date);
    if(!$ts) return $date;
    return date('d/m/Y', $ts);
}

function maps_link($item){
    if(!empty($item['latitude']) && !empty($item['longitude'])){
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($item['latitude'] . ',' . $item['longitude']);
    }
    $q = !empty($item['maps_query']) ? $item['maps_query'] : (!empty($item['endereco']) ? $item['endereco'] : (!empty($item['nome']) ? $item['nome'] . ' Sumaré SP' : 'Sumaré SP'));
    return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($q);
}

function month_br($date){
    if(!$date) return 'definir';
    $ts = strtotime($date);
    if(!$ts) return 'definir';
    $m = array(1=>'jan.',2=>'fev.',3=>'mar.',4=>'abr.',5=>'mai.',6=>'jun.',7=>'jul.',8=>'ago.',9=>'set.',10=>'out.',11=>'nov.',12=>'dez.');
    return $m[(int)date('n',$ts)];
}
?>
