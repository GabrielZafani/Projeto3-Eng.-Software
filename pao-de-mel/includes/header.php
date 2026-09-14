<?php
// header.php - parte de cima do template, incluída em toda página com require/require_once
//
// RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP
// header.php e footer.php são incluídos em toda página (index.php, receitas.php,
// receita-detalhe.php, contato.php) via require/require_once - o HTML repetido


require_once __DIR__ . '/funcoes.php'; // RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO
$paginaAtual = basename($_SERVER['PHP_SELF']);

// As telas de administração ficam em admin/, uma pasta abaixo da raiz.
// Elas definem $raiz = '../' antes de incluir este arquivo; as páginas da
// raiz não definem nada e continuam com o caminho relativo de sempre.
// Sem isto, o menu do admin apontaria para admin/receitas.php, que não existe.
$raiz = $raiz ?? '';

// Páginas que fazem o item "Administração" ficar destacado.
$paginasAdmin = ['index.php', 'categorias.php', 'ingredientes.php', 'receitas.php', 'vendas.php'];
$estaNoAdmin  = str_contains(str_replace('\\', '/', $_SERVER['PHP_SELF']), '/admin/');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pão de Mel — Receitas de Padaria</title>

    <!--
      RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE BOOTSTRAP E PELO MENOS 3 COMPONENTES
    -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,300..700&family=Work+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    <!--
      Estilo próprio.

      O "?v=" com a data de modificação do arquivo não é firula: sem ele o
      navegador guarda o CSS antigo e continua desenhando a tela velha depois
      de você editar. Já aconteceu de a barra de busca aparecer esticada na
      largura toda porque o Chrome reaproveitou a folha de estilo em cache.
      Como o número muda sozinho a cada gravação, o F5 comum já basta.
    -->
    <link href="<?php echo $raiz; ?>assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-pdm sticky-top"> <!-- Bootstrap: componente Navbar -->
  <div class="container">
    <a class="navbar-brand" href="<?php echo $raiz; ?>index.php">Pão <span class="brand-accent">de Mel</span></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navPdm" aria-label="Abrir menu" aria-expanded="false">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navPdm">
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link<?php echo $estaNoAdmin ? '' : classeNavAtiva($paginaAtual, ['index.php']); ?>" href="<?php echo $raiz; ?>index.php">Início</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo classeNavAtiva($paginaAtual, ['receitas.php', 'receita-detalhe.php']); ?>" href="<?php echo $raiz; ?>receitas.php">Receitas</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $estaNoAdmin ? '' : classeNavAtiva($paginaAtual, ['dashboard.php']); ?>" href="<?php echo $raiz; ?>dashboard.php">Dashboard</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo classeNavAtiva($paginaAtual, ['contato.php']); ?>" href="<?php echo $raiz; ?>contato.php">Contato</a>
        </li>
        <li class="nav-item">
          <a class="nav-link<?php echo $estaNoAdmin ? ' active' : ''; ?>" href="<?php echo $raiz; ?>admin/index.php">Administração</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<main>
