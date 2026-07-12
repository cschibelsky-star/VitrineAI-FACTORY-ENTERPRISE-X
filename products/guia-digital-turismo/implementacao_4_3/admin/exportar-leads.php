<?php
require_once 'auth.php';

$leads = read_json('leads_empresas.json');
$filename = 'conheca-sumare-leads-' . date('Y-m-d-His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');

$output = fopen('php://output', 'w');
fwrite($output, "\xEF\xBB\xBF");

$headers = [
    'ID local', 'Data', 'Status local', 'Plano', 'Empresa', 'Categoria', 'Cidade', 'Bairro',
    'Endereço', 'Descrição', 'Responsável', 'WhatsApp', 'E-mail',
    'Consentimento LGPD', 'Origem', 'Status Master', 'ID Master',
    'Tentativas de sincronização', 'Último erro', 'Sincronizado em'
];
fputcsv($output, $headers, ';', '"', '\\');

foreach($leads as $lead){
    fputcsv($output, [
        $lead['id'] ?? '',
        $lead['data'] ?? ($lead['created_at'] ?? ''),
        $lead['status'] ?? '',
        $lead['plano'] ?? '',
        $lead['nome'] ?? '',
        $lead['categoria'] ?? '',
        $lead['cidade'] ?? 'Sumaré',
        $lead['bairro'] ?? '',
        $lead['endereco'] ?? '',
        $lead['descricao'] ?? '',
        $lead['responsavel'] ?? '',
        $lead['whatsapp'] ?? '',
        $lead['email'] ?? '',
        !empty($lead['consentimento_lgpd']) ? 'Sim' : 'Não informado',
        $lead['origem'] ?? 'não informada',
        $lead['sync_master_status'] ?? 'pendente',
        $lead['master_lead_id'] ?? '',
        $lead['sync_master_attempts'] ?? 0,
        $lead['sync_master_last_error'] ?? '',
        $lead['synced_at'] ?? '',
    ], ';', '"', '\\');
}

fclose($output);
exit;
