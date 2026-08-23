<?php
// conexao.php
// RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
//
// Conexão única do sistema, via PDO. É usada tanto pelas páginas que
// montam HTML (index, receitas, receita-detalhe) quanto pela api.php.
//
// As credenciais ficam SÓ aqui: para trocar de servidor (localhost, VM
// da faculdade, hospedagem), basta editar as quatro linhas abaixo.

$host   = "localhost";
$dbname = "padaria";
$user   = "root";
$pass   = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // resultados como array associativo
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);

} catch (PDOException $e) {
    // A mensagem de erro muda conforme quem está pedindo a conexão.
    //
    // A api.php define RESPOSTA_EM_JSON antes de incluir este arquivo,
    // porque ela é consumida pelo fetch() do TypeScript: se o erro saísse
    // como texto puro, o await resposta.json() quebraria no navegador.
    // As páginas normais não definem nada e recebem a mensagem em tela.
    if (defined('RESPOSTA_EM_JSON')) {
        http_response_code(500);
        echo json_encode(["error" => "Erro de conexão com o banco de dados"]);
    } else {
        die("Connection failed: " . $e->getMessage());
    }
    exit;
}
