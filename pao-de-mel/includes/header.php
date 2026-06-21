<?php
// header.php — parte de cima do template, incluída em toda página com require
require_once __DIR__ . '/funcoes.php'; // RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO
$paginaAtual = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pão de Mel — Receitas de Padaria</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Work+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    <!-- Estilo próprio -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-pdm sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">Pão <span class="brand-accent">de Mel</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPdm" aria-label="Abrir menu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPdm">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link<?php echo classeNavAtiva($paginaAtual, ['index.php']); ?>" href="index.php">Início</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo classeNavAtiva($paginaAtual, ['receitas.php', 'receita-detalhe.php']); ?>" href="receitas.php">Receitas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo classeNavAtiva($paginaAtual, ['contato.php']); ?>" href="contato.php">Contato</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main>
