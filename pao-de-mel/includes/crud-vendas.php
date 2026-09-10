<?php
// crud-vendas.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD DE VENDAS =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - REGRAS DE EXCLUSÃO =====
//
// Venda NÃO é apagada: é cancelada (cancelada = TRUE).
//
// Venda é o próprio histórico do caixa. Se o registro sumisse do banco,
// ninguém conseguiria explicar depois por que o faturamento de um dia
// mudou. Cancelando, a linha fica, a CTE da view deixa de contá-la, e o
// número se corrige com o registro do que aconteceu preservado.
//
// É esta regra que faz a View devolver 22 de 23 linhas.

require_once __DIR__ . '/funcoes.php';

const VENDA_QUANTIDADE_MAXIMA = 100000;


function listarVendas(PDO $pdo): array
{
    $sql = "SELECT v.id,
                   v.quantidade,
                   v.data_venda,
                   v.cancelada,
                   p.nome           AS produto,
                   p.ativo          AS produto_ativo,
                   p.valor_unitario,
                   (v.quantidade * p.valor_unitario) AS subtotal
            FROM vendas v
            INNER JOIN produtos p ON p.id = v.id_produto
            ORDER BY v.data_venda DESC, v.id DESC";

    return $pdo->query($sql)->fetchAll();
}

function buscarVenda(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, id_produto, quantidade, data_venda, cancelada FROM vendas WHERE id = ?");
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    return $linha === false ? null : $linha;
}

/**
 * Só produtos em linha entram no campo de seleção.
 * Um produto fora de linha mantém as vendas antigas, mas não recebe novas.
 */
function listarProdutosParaVenda(PDO $pdo): array
{
    return $pdo->query(
        "SELECT id, nome, valor_unitario FROM produtos WHERE ativo = TRUE ORDER BY nome"
    )->fetchAll();
}

/** O faturamento atual, para a mensagem mostrar o antes e o depois. */
function faturamentoAtual(PDO $pdo): float
{
    return (float) $pdo->query("SELECT fn_faturamento_periodo(NULL, NULL)")->fetchColumn();
}


function salvarVenda(PDO $pdo, int $id, int $idProduto, int $quantidade, string $data): array
{
    if ($idProduto <= 0) {
        return ['ok' => false, 'mensagem' => 'Escolha o produto vendido.'];
    }

    if ($quantidade < 1) {
        return ['ok' => false, 'mensagem' => 'A quantidade precisa ser pelo menos 1.'];
    }

    if ($quantidade > VENDA_QUANTIDADE_MAXIMA) {
        return ['ok' => false, 'mensagem' => 'Essa quantidade é grande demais para uma venda só. Confira o número.'];
    }

    // A data vem de um <input type="date">, mas ninguém garante que veio de lá:
    // um POST montado à mão manda o que quiser. Por isso é conferida aqui.
    $partes = date_parse_from_format('Y-m-d', $data);

    if ($partes['error_count'] > 0 || !checkdate((int) $partes['month'], (int) $partes['day'], (int) $partes['year'])) {
        return ['ok' => false, 'mensagem' => 'Data inválida. Use o seletor de data do formulário.'];
    }

    if ($data > date('Y-m-d')) {
        return ['ok' => false, 'mensagem' => 'Não dá para registrar uma venda com data futura.'];
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE vendas SET id_produto = ?, quantidade = ?, data_venda = ? WHERE id = ?");
            $stmt->execute([$idProduto, $quantidade, $data, $id]);

            if ($stmt->rowCount() === 0 && buscarVenda($pdo, $id) === null) {
                return ['ok' => false, 'mensagem' => 'Venda não encontrada. Ela pode ter sido alterada em outra aba.'];
            }

            return ['ok' => true, 'mensagem' => "Venda #{$id} salva."];
        }

        $stmt = $pdo->prepare("INSERT INTO vendas (id_produto, quantidade, data_venda, cancelada) VALUES (?, ?, ?, FALSE)");
        $stmt->execute([$idProduto, $quantidade, $data]);
        $novoId = (int) $pdo->lastInsertId();

        // Relê a quantidade gravada: se o valor tiver sido corrigido pela
        // trigger do banco, a mensagem precisa dizer o que REALMENTE entrou,
        // e não o que o formulário mandou.
        $gravada = buscarVenda($pdo, $novoId);
        $qtdReal = (int) ($gravada['quantidade'] ?? $quantidade);

        $aviso = $qtdReal !== $quantidade
            ? " O banco ajustou a quantidade de {$quantidade} para {$qtdReal}."
            : '';

        return [
            'ok' => true,
            'mensagem' => "Venda #{$novoId} registrada. O faturamento agora é "
                        . formatarDinheiro(faturamentoAtual($pdo)) . '.' . $aviso,
        ];

    } catch (PDOException $e) {
        // 1452 = produto informado não existe (chave estrangeira).
        if (($e->errorInfo[1] ?? 0) === 1452) {
            return ['ok' => false, 'mensagem' => 'O produto escolhido não existe mais. Recarregue a página.'];
        }

        throw $e;
    }
}


/**
 * O "excluir" da venda: cancela, preservando o registro.
 * A mensagem mostra o faturamento antes e depois, que é a prova de que a
 * exclusão mexeu no sistema inteiro, e não só numa linha de tabela.
 */
function cancelarVenda(PDO $pdo, int $id): array
{
    $venda = buscarVenda($pdo, $id);

    if ($venda === null) {
        return ['ok' => false, 'mensagem' => 'Venda não encontrada.'];
    }

    if ($venda['cancelada']) {
        return ['ok' => false, 'mensagem' => "A venda #{$id} já estava cancelada."];
    }

    $antes = faturamentoAtual($pdo);

    $stmt = $pdo->prepare("UPDATE vendas SET cancelada = TRUE WHERE id = ?");
    $stmt->execute([$id]);

    $depois = faturamentoAtual($pdo);

    return [
        'ok' => true,
        'mensagem' => "Venda #{$id} cancelada. O registro continua no banco para consulta, "
                    . 'mas saiu do faturamento: de ' . formatarDinheiro($antes)
                    . ' para ' . formatarDinheiro($depois) . '.',
    ];
}

/** Desfaz o cancelamento. */
function reativarVenda(PDO $pdo, int $id): array
{
    $venda = buscarVenda($pdo, $id);

    if ($venda === null) {
        return ['ok' => false, 'mensagem' => 'Venda não encontrada.'];
    }

    if (!$venda['cancelada']) {
        return ['ok' => false, 'mensagem' => "A venda #{$id} não estava cancelada."];
    }

    $stmt = $pdo->prepare("UPDATE vendas SET cancelada = FALSE WHERE id = ?");
    $stmt->execute([$id]);

    return [
        'ok' => true,
        'mensagem' => "Venda #{$id} reativada. O faturamento voltou para "
                    . formatarDinheiro(faturamentoAtual($pdo)) . '.',
    ];
}
