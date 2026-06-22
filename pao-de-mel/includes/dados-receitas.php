<?php
// dados-receitas.php
//
// RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
// RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS RECUPERADOS DO BANCO E DEMONSTRADOS NA TELA
// RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS


require_once __DIR__ . '/conexao.php';   // abre $conn (mysqli)
require_once __DIR__ . '/funcoes.php';

// ===== RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS =====
// $receitas é o array central do sistema. Todas as páginas leem daqui.
$receitas = buscarReceitasDoBanco($conn);

$conn->close();
