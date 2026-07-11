<?php
date_default_timezone_set('America/Sao_Paulo');

define('APP_VERSION', '4.3 Fontes Oficiais');
define('ROOT_PATH', dirname(__DIR__));
define('DATA_PATH', ROOT_PATH . '/data/');
define('UPLOAD_PATH', ROOT_PATH . '/uploads/');

$credentialsFile = __DIR__ . '/credentials.local.php';
$credentials = file_exists($credentialsFile) ? require $credentialsFile : [];
if (!is_array($credentials)) {
    $credentials = [];
}

$envUser = getenv('CONHECA_ADMIN_USER');
$envHash = getenv('CONHECA_ADMIN_PASS_HASH');

$adminUser = is_string($envUser) && $envUser !== ''
    ? $envUser
    : (string)($credentials['user'] ?? '');

$adminPassHash = is_string($envHash) && $envHash !== ''
    ? $envHash
    : (string)($credentials['password_hash'] ?? '');

define('ADMIN_USER', $adminUser);
define('ADMIN_PASS_HASH', $adminPassHash);
define('ADMIN_CONFIGURED', ADMIN_USER !== '' && ADMIN_PASS_HASH !== '');

$integrationFile = __DIR__ . '/integrations.local.php';
$integration = file_exists($integrationFile) ? require $integrationFile : [];
if (!is_array($integration)) {
    $integration = [];
}

$masterUrl = getenv('VITRINE_LEADS_URL');
$masterToken = getenv('VITRINE_LEADS_TOKEN');

$resolvedMasterUrl = is_string($masterUrl) && $masterUrl !== ''
    ? $masterUrl
    : (string)($integration['leads_url'] ?? '');

$resolvedMasterToken = is_string($masterToken) && $masterToken !== ''
    ? $masterToken
    : (string)($integration['leads_token'] ?? '');

define('MASTER_LEADS_API_URL', rtrim($resolvedMasterUrl, '/'));
define('MASTER_LEADS_TOKEN', $resolvedMasterToken);
define('MASTER_LEADS_CONFIGURED', MASTER_LEADS_API_URL !== '' && MASTER_LEADS_TOKEN !== '');
