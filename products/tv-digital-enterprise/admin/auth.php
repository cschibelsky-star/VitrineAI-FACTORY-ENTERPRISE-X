<?php
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

$configPath = __DIR__ . '/config.php';
if (!file_exists($configPath)) {
    $configPath = dirname(__DIR__) . '/config.php';
}
if (!file_exists($configPath)) {
    die('Arquivo config.php não encontrado. Verifique se todos os arquivos foram enviados corretamente.');
}
require_once $configPath;

function tvs_redirect($url) {
    header('Location: ' . $url);
    exit;
}

function tvs_is_logged() {
    return !empty($_SESSION['tvs_admin_logged']);
}

function require_login() {
    if (!tvs_is_logged()) {
        tvs_redirect('login.php');
    }
}

function tvs_admin_password_ok($password) {
    global $admin_pass_hash, $admin_pass;
    $password = (string) $password;
    if (!empty($admin_pass_hash) && password_verify($password, (string)$admin_pass_hash)) {
        return true;
    }
    // Fallback legado apenas para instalações antigas. Em produção, use $admin_pass_hash.
    if (!empty($admin_pass) && hash_equals((string)$admin_pass, $password)) {
        return true;
    }
    return false;
}

function tvs_login_throttled() {
    $now = time();
    $_SESSION['tvs_login_attempts'] = $_SESSION['tvs_login_attempts'] ?? [];
    $_SESSION['tvs_login_attempts'] = array_values(array_filter($_SESSION['tvs_login_attempts'], function($t) use ($now) {
        return ($now - (int)$t) < 900;
    }));
    return count($_SESSION['tvs_login_attempts']) >= 8;
}

function tvs_register_failed_login() {
    $_SESSION['tvs_login_attempts'] = $_SESSION['tvs_login_attempts'] ?? [];
    $_SESSION['tvs_login_attempts'][] = time();
}

$erro = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['user'], $_POST['pass'])) {
    if (tvs_login_throttled()) {
        $erro = 'Muitas tentativas inválidas. Aguarde alguns minutos e tente novamente.';
    } else {
        $user = trim((string) $_POST['user']);
        $pass = (string) $_POST['pass'];
        if (isset($admin_user) && hash_equals((string)$admin_user, $user) && tvs_admin_password_ok($pass)) {
            session_regenerate_id(true);
            $_SESSION['tvs_admin_logged'] = true;
            $_SESSION['tvs_admin_user'] = $user;
            $_SESSION['tvs_login_attempts'] = [];
            tvs_redirect('monitor.php');
        }
        tvs_register_failed_login();
        $erro = 'Usuário ou senha inválidos.';
        if (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'auth.php') {
            tvs_redirect('login.php?erro=1');
        }
    }
}
?>
