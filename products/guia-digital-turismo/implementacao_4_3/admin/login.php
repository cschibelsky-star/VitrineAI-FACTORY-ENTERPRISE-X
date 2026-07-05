<?php require_once __DIR__ . '/../includes/functions.php'; session_start(); $error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
  if(($_POST['usuario'] ?? '') === ADMIN_USER && ($_POST['senha'] ?? '') === ADMIN_PASS){ $_SESSION['admin']=true; header('Location: dashboard.php'); exit; }
  $error='Usuário ou senha inválidos.';
}
?>
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Admin - Conheça Sumaré</title><link rel="stylesheet" href="../assets/css/admin.css"></head><body><div class="login"><form method="post"><h1>Conheça Sumaré</h1><p>Painel administrativo</p><?php if($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?><label>Usuário</label><input name="usuario" required><label>Senha</label><input type="password" name="senha" required><button>Entrar</button><small>Acesso restrito à equipe autorizada.</small></form></div></body></html>
