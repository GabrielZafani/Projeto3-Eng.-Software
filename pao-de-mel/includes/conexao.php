<?php
// conexao.php
// RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
//
// Conexão da VM com a máquina real, via mysqli.

$servername = "192.168.56.101";
$username = "Padeiro";
$password = "";
$dbname = "padaria";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Sem isso, acentos (ã, ç, é...) vêm corrompidos do banco pra tela
$conn->set_charset("utf8mb4");
