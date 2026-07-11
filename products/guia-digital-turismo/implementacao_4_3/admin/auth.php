<?php
require_once __DIR__ . '/../includes/functions.php';
ensure_session_started();
if(empty($_SESSION['admin'])){
    header('Location: login.php');
    exit;
}
?>
