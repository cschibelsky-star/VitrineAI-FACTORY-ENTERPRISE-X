<?php
require_once 'auth.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_is_valid($_POST['csrf_token'] ?? null)){
    header('Location: leads.php?sync_error=' . rawurlencode('Solicitação inválida ou sessão expirada.'));
    exit;
}

if(!master_leads_configured()){
    header('Location: leads.php?sync_error=' . rawurlencode('Integração com o Comercial Master ainda não configurada.'));
    exit;
}

$file = DATA_PATH . 'leads_empresas.json';
$raw = file_exists($file) ? file_get_contents($file) : false;
$leads = is_string($raw) ? json_decode($raw, true) : null;

if(!is_array($leads) || json_last_error() !== JSON_ERROR_NONE){
    header('Location: leads.php?sync_error=' . rawurlencode('Arquivo de leads inválido. Execute a auditoria antes de sincronizar.'));
    exit;
}

$success = 0;
$failed = 0;
$skipped = 0;
$processed = 0;
$limit = 100;

foreach($leads as $index => $lead){
    $status = (string)($lead['sync_master_status'] ?? 'pending');
    if($status === 'synchronized'){
        $skipped++;
        continue;
    }

    if($processed >= $limit){
        break;
    }

    $result = sync_lead_to_master($lead);
    $attempts = (int)($lead['sync_master_attempts'] ?? 0) + 1;

    $leads[$index]['sync_master_status'] = $result['success'] ? 'synchronized' : 'failed';
    $leads[$index]['sync_master_attempts'] = $attempts;
    $leads[$index]['sync_master_last_error'] = $result['error'] ?? null;
    $leads[$index]['sync_master_http_status'] = $result['status_code'] ?? 0;
    $leads[$index]['sync_master_duplicate'] = !empty($result['duplicate']);
    $leads[$index]['master_lead_id'] = $result['lead_id'] ?? ($lead['master_lead_id'] ?? null);
    $leads[$index]['synced_at'] = $result['success'] ? date(DATE_ATOM) : ($lead['synced_at'] ?? null);

    $processed++;
    if($result['success']){
        $success++;
    } else {
        $failed++;
    }
}

if(!save_json('leads_empresas.json', $leads)){
    header('Location: leads.php?sync_error=' . rawurlencode('Não foi possível salvar o resultado da sincronização.'));
    exit;
}

header('Location: leads.php?sync_ok=' . $success . '&sync_failed=' . $failed . '&sync_skipped=' . $skipped);
exit;
