<?php
// crud-ingredientes.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD DE INGREDIENTES =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - REGRAS DE EXCLUSÃO =====
//
// Ingrediente é catálogo: a lista do que a padaria usa, com a unidade de
// medida de cada um. Não aparece em histórico, então PODE ser apagado -
// mas só enquanto nenhuma receita estiver usando.
//
// A trava é a chave estrangeira receita_ingrediente.id_ingrediente.

require_once __DIR__ . '/funcoes.php';

const INGREDIENTE_NOME_LIMITE    = 100;
const INGREDIENTE_UNIDADE_LIMITE = 20;

/** Unidades que a padaria usa. Lista fechada para não virar bagunça: sem
 *  isso o mesmo grama entra como "g", "G", "gr" e "gramas", e a receita
 *  fica com quatro unidades para a mesma coisa. */
const INGREDIENTE_UNIDADES = ['g', 'kg', 'ml', 'l', 'un', 'colher', 'xícara', 'pitada'];


function listarIngredientes(PDO $pdo): array
{
    $sql = "SELECT i.id,
                   i.nome,
                   i.unidade_medida,
                   COUNT(ri.id_receita) AS receitas
            FROM ingredientes i
            LEFT JOIN receita_ingrediente ri ON ri.id_ingrediente = i.id
            GROUP BY i.id, i.nome, i.unidade_medida
            ORDER BY i.nome";

    return $pdo->query($sql)->fetchAll();
}

function buscarIngrediente(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, nome, unidade_medida FROM ingredientes WHERE id = ?");
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    return $linha === false ? null : $linha;
}


function salvarIngrediente(PDO $pdo, int $id, string $nome, string $unidade): array
{
    if ($nome === '') {
        return ['ok' => false, 'mensagem' => 'O nome do ingrediente não pode ficar em branco.'];
    }

    // A unidade vem de um <select>, mas um POST montado à mão manda o que
    // quiser - por isso é conferida contra a lista aqui no servidor.
    if (!in_array($unidade, INGREDIENTE_UNIDADES, true)) {
        return ['ok' => false, 'mensagem' => 'Escolha uma unidade de medida da lista.'];
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE ingredientes SET nome = ?, unidade_medida = ? WHERE id = ?");
            $stmt->execute([$nome, $unidade, $id]);

            if ($stmt->rowCount() === 0 && buscarIngrediente($pdo, $id) === null) {
                return ['ok' => false, 'mensagem' => 'Ingrediente não encontrado.'];
            }

            return ['ok' => true, 'mensagem' => "Ingrediente \"{$nome}\" salvo."];
        }

        $stmt = $pdo->prepare("INSERT INTO ingredientes (nome, unidade_medida) VALUES (?, ?)");
        $stmt->execute([$nome, $unidade]);

        return ['ok' => true, 'mensagem' => "Ingrediente \"{$nome}\" cadastrado. Já pode ser usado nas receitas."];

    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'mensagem' => "Já existe um ingrediente chamado \"{$nome}\"."];
        }

        throw $e;
    }
}


function excluirIngrediente(PDO $pdo, int $id): array
{
    $ingrediente = buscarIngrediente($pdo, $id);

    if ($ingrediente === null) {
        return ['ok' => false, 'mensagem' => 'Ingrediente não encontrado.'];
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM ingredientes WHERE id = ?");
        $stmt->execute([$id]);

        return ['ok' => true, 'mensagem' => "Ingrediente \"{$ingrediente['nome']}\" excluído do catálogo."];

    } catch (PDOException $e) {
        // 1451: alguma receita usa este ingrediente. Só depois da recusa é
        // que perguntamos QUAIS, para a mensagem dizer o motivo.
        if (($e->errorInfo[1] ?? 0) === 1451) {
            $stmt = $pdo->prepare(
                "SELECT r.nome
                 FROM receita_ingrediente ri
                 INNER JOIN receitas r ON r.id = ri.id_receita
                 WHERE ri.id_ingrediente = ?
                 ORDER BY r.nome
                 LIMIT 3"
            );
            $stmt->execute([$id]);
            $receitas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM receita_ingrediente WHERE id_ingrediente = ?");
            $stmt->execute([$id]);
            $total = (int) $stmt->fetchColumn();

            $lista = implode(', ', $receitas);
            $resto = $total > count($receitas) ? ' e mais ' . ($total - count($receitas)) : '';

            return [
                'ok' => false,
                'mensagem' => "Não dá para excluir \"{$ingrediente['nome']}\": ele está em {$lista}{$resto}. "
                            . 'Tire o ingrediente dessas receitas primeiro.',
            ];
        }

        throw $e;
    }
}
