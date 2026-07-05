<?php
require_once __DIR__ . '/../config/config.php';

function h($value){
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function read_json($file){
    $path = DATA_PATH . $file;
    if(!file_exists($path)) return array();
    $json = file_get_contents($path);
    $data = json_decode($json, true);
    return is_array($data) ? $data : array();
}

function save_json($file, $data){
    $path = DATA_PATH . $file;
    return file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
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
