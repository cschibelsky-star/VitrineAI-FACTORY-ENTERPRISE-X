<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_session_started();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!ADMIN_CONFIGURED) {
        $error = 'O acesso administrativo ainda não foi configurado com credenciais locais seguras.';
    } else {
        $usuario = (string)($_POST['usuario'] ?? '');
        $senha = (string)($_POST['senha'] ?? '');
        $usuarioValido = hash_equals(ADMIN_USER, $usuario);
        $senhaValida = password_verify($senha, ADMIN_PASS_HASH);

        if ($usuarioValido && $senhaValida) {
            session_regenerate_id(true);
            $_SESSION['admin'] = true;
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - Conheça Sumaré</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body><div class="login"><form method="post"><h1>Conheça Sumaré</h1><p>Painel administrativo</p><?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?><label>Usuário</label><input name="usuario" autocomplete="username" required><label>Senha</label><input type="password" name="senha" autocomplete="current-password" required><button>Entrar</button><small>Acesso restrito à equipe autorizada.</small></form></div></body></html>
