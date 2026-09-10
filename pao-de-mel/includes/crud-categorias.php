<?php
// crud-categorias.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD DE CATEGORIAS =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - REGRAS DE EXCLUSÃO =====
//
// Categoria é rótulo de lista: não aparece em histórico, então PODE ser
// apagada de verdade - mas só enquanto ninguém estiver usando.
//
// A regra de quem "está usando" não mora aqui. Mora na chave estrangeira
// receitas.id_categoria, que o InnoDB faz cumprir mesmo para quem apagar
// pelo DBeaver, sem passar por esta tela. O papel deste arquivo é traduzir
// a recusa do banco para uma frase em português.

require_once __DIR__ . '/funcoes.php';

const CATEGORIA_NOME_LIMITE = 100;


/** Lista com a contagem de receitas, para a tela avisar antes de tentar apagar. */
function listarCategorias(PDO $pdo): array
{
    $sql = "SELECT c.id,
                   c.nome,
                   COUNT(r.id) AS receitas
            FROM categorias c
            LEFT JOIN receitas r ON r.id_categoria = c.id
            GROUP BY c.id, c.nome
            ORDER BY c.nome";

    return $pdo->query($sql)->fetchAll();
}

function buscarCategoria(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, nome FROM categorias WHERE id = ?");
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    return $linha === false ? null : $linha;
}


/**
 * Inclui (id = 0) ou edita (id > 0).
 * Retorno: ['ok' => bool, 'mensagem' => string].
 */
function salvarCategoria(PDO $pdo, int $id, string $nome): array
{
    if ($nome === '') {
        return ['ok' => false, 'mensagem' => 'O nome da categoria não pode ficar em branco.'];
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
            $stmt->execute([$nome, $id]);

            if ($stmt->rowCount() === 0 && buscarCategoria($pdo, $id) === null) {
                return ['ok' => false, 'mensagem' => 'Categoria não encontrada. Ela pode ter sido excluída em outra aba.'];
            }

            return ['ok' => true, 'mensagem' => "Categoria \"{$nome}\" salva."];
        }

        $stmt = $pdo->prepare("INSERT INTO categorias (nome) VALUES (?)");
        $stmt->execute([$nome]);

        return ['ok' => true, 'mensagem' => "Categoria \"{$nome}\" cadastrada."];

    } catch (PDOException $e) {
        // 1062 = índice único violado. Quem barra o nome repetido é o banco
        // (uk_categorias_nome), não um SELECT prévio daqui: entre o SELECT e
        // o INSERT cabe outra gravação, e aí entrariam duas iguais.
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'mensagem' => "Já existe uma categoria chamada \"{$nome}\"."];
        }

        throw $e;
    }
}


/**
 * Exclusão de verdade - mas o banco tem a última palavra.
 */
function excluirCategoria(PDO $pdo, int $id): array
{
    $categoria = buscarCategoria($pdo, $id);

    if ($categoria === null) {
        return ['ok' => false, 'mensagem' => 'Categoria não encontrada.'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = ?");
        $stmt->execute([$id]);

        return ['ok' => true, 'mensagem' => "Categoria \"{$categoria['nome']}\" excluída."];

    } catch (PDOException $e) {
        // 1451 = "Cannot delete or update a parent row": existe receita
        // apontando para esta categoria. Só DEPOIS da recusa é que contamos
        // quantas são, para a mensagem dizer o motivo em vez de "erro".
        if (($e->errorInfo[1] ?? 0) === 1451) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM receitas WHERE id_categoria = ?");
            $stmt->execute([$id]);
            $emUso = (int) $stmt->fetchColumn();

            $plural = $emUso === 1 ? 'receita usa' : 'receitas usam';

            return [
                'ok' => false,
                'mensagem' => "Não dá para excluir \"{$categoria['nome']}\": {$emUso} {$plural} esta categoria. "
                            . "Mude a categoria dessas receitas primeiro.",
            ];
        }

        throw $e;
    }
}
