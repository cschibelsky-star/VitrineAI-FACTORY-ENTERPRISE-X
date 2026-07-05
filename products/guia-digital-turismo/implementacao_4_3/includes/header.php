<?php require_once __DIR__ . '/functions.php'; $settings = app_settings(); ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
  <title><?= h($settings['nome_app'] ?? 'Visite Sumaré') ?></title>
  <meta name="description" content="<?= h($settings['subtitulo'] ?? 'Guia turístico digital de Sumaré') ?>">
  <meta name="theme-color" content="<?= h($settings['cor_primaria'] ?? '#0F6B3A') ?>">
  <link rel="manifest" href="manifest.json">
  <link rel="preload" href="assets/css/style.css?v=4.2.0" as="style">
  <link rel="preload" href="assets/img/hero-real.jpg" as="image" fetchpriority="high">
  <link rel="stylesheet" href="assets/css/style.css?v=4.2.0">
  <style>:root{--primary:<?= h($settings['cor_primaria'] ?? '#0F6B3A') ?>;--secondary:<?= h($settings['cor_secundaria'] ?? '#F5C000') ?>;--accent:<?= h($settings['cor_destaque'] ?? '#FF7A1A') ?>;}</style>
</head>
<body>
<div class="app-shell">
<header class="app-header">
  <a href="app.php" class="brand">
    <img src="<?= h($settings['logo'] ?? 'assets/icons/icon.svg') ?>" alt="Logo" onerror="this.src='assets/icons/icon.svg'">
    <span><strong><?= h($settings['nome_app'] ?? 'Sumaré Turismo') ?></strong><small><?= h($settings['slogan'] ?? 'Guia turístico, cultural e comercial digital') ?></small></span>
  </a>
</header>
<main class="content">
