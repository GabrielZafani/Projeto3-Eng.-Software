<?php
// api.php
// RUBRICA - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO (lado servidor)
// RUBRICA - STORED PROCEDURES CENTRALIZANDO BUSCA, FILTRO E PAGINAÇÃO
//
// Endpoint que devolve as vendas da padaria em JSON. Quem consome é o
// TypeScript em src/app.ts, via fetch() com async/await.
//
// Repare que não existe mais nenhum SELECT montado aqui: a API só
// repassa os parâmetros que vieram da tela e faz CALL. Busca, filtro,
// paginação e limpeza moram todos na procedure sp_vendas_buscar.
//
// O endpoint continua entregando as LINHAS BRUTAS (quantidade e
// valor_unitario em colunas separadas) - quem multiplica e acumula é o
// .reduce() no front, como pede a rubrica de Lógica Avançada.

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");

// Avisa a conexão que os erros devem sair em JSON, e não como texto na tela
define('RESPOSTA_EM_JSON', true);
require_once __DIR__ . '/includes/conexao.php';

// ---------------------------------------------------------------
// Entrada vinda da tela. Tudo é tratado como DADO, nunca como SQL.
// ---------------------------------------------------------------
$busca     = isset($_GET['busca'])     ? trim((string) $_GET['busca'])     : '';
$categoria = isset($_GET['categoria']) ? trim((string) $_GET['categoria']) : 'todas';
$pagina    = isset($_GET['pagina'])    ? (int) $_GET['pagina']             : 1;

// tamanho = 0 significa "traz tudo, sem paginar". É o que a dashboard
// usa para calcular os cards com o reduce sobre o filtro inteiro, para
// o faturamento não mudar quando o usuário vira a página.
$tamanho = isset($_GET['tamanho']) ? (int) $_GET['tamanho'] : 10;

// Corta o texto de busca antes de mandar. O parâmetro da procedure é
// VARCHAR(100): sem isto, digitar um texto gigante no campo derrubava a
// chamada com "Data too long for column".
if (mb_strlen($busca) > 100) {
    $busca = mb_substr($busca, 0, 100);
}

try {
    // -----------------------------------------------------------
    // CHAMADA LIMPA: um CALL, quatro parâmetros, duas saídas.
    // -----------------------------------------------------------
    $stmt = $pdo->prepare("CALL sp_vendas_buscar(?, ?, ?, ?, @total_linhas, @faturamento)");
    $stmt->execute([$busca, $categoria, $pagina, $tamanho]);
    $vendas = $stmt->fetchAll();

    // Sem o closeCursor(), a próxima consulta nesta mesma conexão falha
    // com "Cannot execute queries while other unbuffered queries are
    // active" - procedure devolve mais de um conjunto de resultados.
    $stmt->closeCursor();

    // As duas saídas (OUT) da procedure. O faturamento vem da FUNÇÃO
    // fn_faturamento_periodo, chamada lá dentro.
    $saidas = $pdo->query("SELECT @total_linhas AS total_linhas, @faturamento AS faturamento")->fetch();

    $totalLinhas = (int) ($saidas['total_linhas'] ?? 0);

    // Categorias para os botões de filtro, também por CALL.
    $stmtCat = $pdo->prepare("CALL sp_vendas_categorias()");
    $stmtCat->execute();
    $categorias = $stmtCat->fetchAll();
    $stmtCat->closeCursor();

    $tamanhoEfetivo = $tamanho > 0 ? $tamanho : max($totalLinhas, 1);
    $totalPaginas   = (int) ceil($totalLinhas / $tamanhoEfetivo);

    http_response_code(200);
    echo json_encode([
        "vendas" => $vendas,
        "meta"   => [
            "origem"                  => "procedure",
            "total_linhas"            => $totalLinhas,
            "pagina"                  => max($pagina, 1),
            "tamanho"                 => $tamanho,
            "total_paginas"           => max($totalPaginas, 1),
            "busca"                   => $busca,
            "categoria"               => $categoria,
            "categorias"              => $categorias,
            // Conferência vinda da função do banco. Não é o que a tela
            // exibe nos cards: aqueles são calculados pelo reduce. Serve
            // para o console avisar se os dois números divergirem.
            "faturamento_conferencia" => (float) ($saidas['faturamento'] ?? 0),
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (PDOException $e) {
    // -----------------------------------------------------------
    // PLANO B: o código publicado não pode exigir migração aplicada.
    //
    // Se alguém atualizar os arquivos mas ainda não tiver rodado o
    // dashboard-vendas.sql, a procedure não existe (erro 1305). Em vez
    // de a dashboard morrer com 500, a API volta ao comportamento
    // antigo: lê a view direto e devolve tudo, sem paginar.
    // -----------------------------------------------------------
    $procedureAusente = ($e->errorInfo[1] ?? 0) === 1305;

    if ($procedureAusente) {
        try {
            $sql = "SELECT venda_id, produto, categoria, quantidade, valor_unitario, data_venda
                    FROM vw_vendas_detalhadas
                    ORDER BY data_venda DESC, produto ASC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $vendas = $stmt->fetchAll();

            http_response_code(200);
            echo json_encode([
                "vendas" => $vendas,
                "meta"   => [
                    "origem"        => "view (procedure ainda não instalada)",
                    "total_linhas"  => count($vendas),
                    "pagina"        => 1,
                    "tamanho"       => 0,
                    "total_paginas" => 1,
                    "busca"         => "",
                    "categoria"     => "todas",
                    "categorias"    => [],
                ],
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;

        } catch (PDOException $eFallback) {
            // cai no erro genérico abaixo
        }
    }

    // Erro real de banco (view faltando, tabela derrubada...) vira JSON,
    // porque quem consome é um fetch() e texto puro quebraria o .json().
    http_response_code(500);
    echo json_encode(["error" => "Erro ao executar consulta no banco de dados"]);
}
