<?php
// dados-receitas.php
//
// RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
// RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS RECUPERADOS DO BANCO E DEMONSTRADOS NA TELA
// RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS


require_once __DIR__ . '/conexao.php';   // abre $pdo (PDO)
require_once __DIR__ . '/funcoes.php';

// ===== RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS =====
// $receitas é o array central do sistema. Todas as páginas leem daqui.
$receitas = buscarReceitasDoBanco($pdo);

// A lista de categorias tem que sair daqui, antes de a conexão fechar.
// Ela vem da TABELA, e não das receitas carregadas: é o que faz uma
// categoria recém-cadastrada aparecer no filtro mesmo sem receita ainda.
$categoriasCadastradas = buscarCategoriasDoBanco($pdo);

// Em PDO não existe close(): a conexão é encerrada ao anular a variável
// (e o PHP encerraria sozinho no fim do script, de qualquer forma).
$pdo = null;
