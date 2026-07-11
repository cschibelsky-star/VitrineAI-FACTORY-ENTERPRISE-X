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

function append_json_record($file, $record){
    $path = DATA_PATH . $file;
    $handle = fopen($path, 'c+');
    if($handle === false) return false;

    $success = false;
    try {
        if(!flock($handle, LOCK_EX)) return false;
        rewind($handle);
        $contents = stream_get_contents($handle);

        if($contents === false){
            return false;
        }

        if(trim($contents) === ''){
            $items = array();
        } else {
            $items = json_decode($contents, true);
            if(json_last_error() !== JSON_ERROR_NONE || !is_array($items)){
                return false;
            }
        }

        $items[] = $record;
        $json = encode_json_data($items);
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
