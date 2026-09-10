<?php
// crud-produtos.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD DE PRODUTOS =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - REGRAS DE EXCLUSÃO =====
//
// Produto NÃO é apagado: é tirado de linha (ativo = FALSE).
//
// O motivo é que produto aparece em venda, e venda é histórico. Apagar o
// produto exigiria apagar as vendas dele junto - e aí o faturamento de um
// mês já fechado mudaria porque alguém aposentou um item hoje.
//
// Desde a decisão de manter o histórico, tirar de linha NÃO altera mais o
// faturamento: a view soma a venda do produto fora de linha do mesmo jeito.
// O produto some do cardápio e dos cadastros novos, e só.

require_once __DIR__ . '/funcoes.php';

const PRODUTO_NOME_LIMITE = 100;
const PRODUTO_PRECO_MINIMO = 0.01;
const PRODUTO_PRECO_MAXIMO = 99999999.99;   // o DECIMAL(10,2) do banco


function listarProdutos(PDO $pdo): array
{
    $sql = "SELECT p.id,
                   p.nome,
                   p.valor_unitario,
                   p.ativo,
                   r.nome AS receita,
                   COUNT(v.id) AS vendas
            FROM produtos p
            INNER JOIN receitas r ON r.id = p.id_receita
            LEFT  JOIN vendas   v ON v.id_produto = p.id AND v.cancelada = FALSE
            GROUP BY p.id, p.nome, p.valor_unitario, p.ativo, r.nome
            ORDER BY p.ativo DESC, p.nome";

    return $pdo->query($sql)->fetchAll();
}

function buscarProduto(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, nome, valor_unitario, id_receita, ativo FROM produtos WHERE id = ?");
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    return $linha === false ? null : $linha;
}

/** Receitas disponíveis para o campo de seleção do formulário. */
function listarReceitasParaProduto(PDO $pdo): array
{
    return $pdo->query("SELECT id, nome FROM receitas ORDER BY nome")->fetchAll();
}


function salvarProduto(PDO $pdo, int $id, string $nome, float $valor, int $idReceita): array
{
    if ($nome === '') {
        return ['ok' => false, 'mensagem' => 'O nome do produto não pode ficar em branco.'];
    }

    // A validação de preço é repetida no banco pela trigger de valor positivo.
    // Aqui ela existe para o usuário ver a frase certa; lá, para valer mesmo.
    if (!is_finite($valor) || $valor < PRODUTO_PRECO_MINIMO) {
        return ['ok' => false, 'mensagem' => 'O preço precisa ser maior que zero. Use vírgula para os centavos, como 12,50.'];
    }

    if ($valor > PRODUTO_PRECO_MAXIMO) {
        return ['ok' => false, 'mensagem' => 'Esse preço passa do limite da coluna do banco. Confira se não sobrou um zero.'];
    }

    if ($idReceita <= 0) {
        return ['ok' => false, 'mensagem' => 'Escolha a receita de origem do produto.'];
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE produtos SET nome = ?, valor_unitario = ?, id_receita = ? WHERE id = ?");
            $stmt->execute([$nome, $valor, $idReceita, $id]);

            if ($stmt->rowCount() === 0 && buscarProduto($pdo, $id) === null) {
                return ['ok' => false, 'mensagem' => 'Produto não encontrado. Ele pode ter sido excluído em outra aba.'];
            }

            return ['ok' => true, 'mensagem' => "Produto \"{$nome}\" salvo."];
        }

        $stmt = $pdo->prepare("INSERT INTO produtos (nome, valor_unitario, id_receita, ativo) VALUES (?, ?, ?, TRUE)");
        $stmt->execute([$nome, $valor, $idReceita]);

        return ['ok' => true, 'mensagem' => "Produto \"{$nome}\" cadastrado e já disponível no caixa."];

    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'mensagem' => "Já existe um produto chamado \"{$nome}\"."];
        }

        // 1452 = receita informada não existe (chave estrangeira).
        if (($e->errorInfo[1] ?? 0) === 1452) {
            return ['ok' => false, 'mensagem' => 'A receita escolhida não existe mais. Recarregue a página e escolha outra.'];
        }

        throw $e;
    }
}


/**
 * O "excluir" do produto: tira de linha em vez de apagar.
 */
function tirarProdutoDeLinha(PDO $pdo, int $id): array
{
    $produto = buscarProduto($pdo, $id);

    if ($produto === null) {
        return ['ok' => false, 'mensagem' => 'Produto não encontrado.'];
    }

    if (!$produto['ativo']) {
        return ['ok' => false, 'mensagem' => "\"{$produto['nome']}\" já estava fora de linha."];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vendas WHERE id_produto = ? AND cancelada = FALSE");
    $stmt->execute([$id]);
    $vendas = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare("UPDATE produtos SET ativo = FALSE WHERE id = ?");
    $stmt->execute([$id]);

    $detalhe = $vendas === 0
        ? 'Ele nunca foi vendido, então nada muda no faturamento.'
        : ($vendas === 1
            ? 'A venda que ele já teve continua contando no faturamento.'
            : "As {$vendas} vendas que ele já teve continuam contando no faturamento.");

    return [
        'ok' => true,
        'mensagem' => "\"{$produto['nome']}\" saiu de linha e não aparece mais para novas vendas. {$detalhe}",
    ];
}

/** Desfaz o "excluir": o produto volta ao cardápio. */
function voltarProdutoParaLinha(PDO $pdo, int $id): array
{
    $produto = buscarProduto($pdo, $id);

    if ($produto === null) {
        return ['ok' => false, 'mensagem' => 'Produto não encontrado.'];
    }

    $stmt = $pdo->prepare("UPDATE produtos SET ativo = TRUE WHERE id = ?");
    $stmt->execute([$id]);

    return ['ok' => true, 'mensagem' => "\"{$produto['nome']}\" voltou para o cardápio."];
}
