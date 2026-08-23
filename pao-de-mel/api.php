<?php
// api.php
// RUBRICA - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO (lado servidor)
//
// Endpoint que devolve as vendas da padaria em JSON. Quem consome é o
// TypeScript em src/app.ts, via fetch() com async/await.
//
// Importante: a consulta lê a VIEW vw_vendas_detalhadas, ou seja, os dados
// já chegam limpos e consolidados pelas CTEs. Mas o endpoint entrega as
// LINHAS BRUTAS (quantidade e valor_unitario separados) — quem calcula o
// faturamento é o .reduce() no front, como pede a rubrica.

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Avisa a conexão que os erros devem sair em JSON, e não como texto na tela
define('RESPOSTA_EM_JSON', true);
require_once __DIR__ . '/includes/conexao.php';

try {
    $sql = "SELECT
                venda_id,
                produto,
                categoria,
                quantidade,
                valor_unitario,
                data_venda
            FROM vw_vendas_detalhadas
            ORDER BY data_venda DESC, produto ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $dados = $stmt->fetchAll();

    http_response_code(200);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // Erro na consulta (view faltando, tabela derrubada...) também vira JSON
    http_response_code(500);
    echo json_encode(["error" => "Erro ao executar consulta no banco de dados"]);
}
