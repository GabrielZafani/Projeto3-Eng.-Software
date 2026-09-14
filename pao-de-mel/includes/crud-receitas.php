<?php
// crud-receitas.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD DE RECEITAS =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - REGRAS DE EXCLUSÃO =====
//
// Receita é a ficha técnica: o que a padaria sabe fazer. É dela que o
// produto herda a categoria, e é por isso que produto exige uma.
//
// A parte difícil é o relacionamento N:N com ingredientes: uma receita tem
// vários ingredientes, cada um com sua quantidade, na tabela
// receita_ingrediente. Cada "Adicionar" grava uma linha lá de verdade -
// não fica nada guardado na tela esperando um salvar geral.

require_once __DIR__ . '/funcoes.php';

const RECEITA_NOME_LIMITE  = 100;
const RECEITA_TEMPO_MAXIMO = 10080;   // uma semana em minutos


function listarReceitas(PDO $pdo): array
{
    // As contagens vêm de subconsultas, e não de LEFT JOINs somados: com
    // dois JOINs na mesma consulta, cada ingrediente multiplicava a
    // contagem de produtos e vice-versa.
    $sql = "SELECT r.id,
                   r.nome,
                   r.tempo_preparo,
                   c.nome AS categoria,
                   (SELECT COUNT(*) FROM receita_ingrediente ri WHERE ri.id_receita = r.id) AS ingredientes,
                   (SELECT COUNT(*) FROM produtos p           WHERE p.id_receita  = r.id) AS produtos
            FROM receitas r
            INNER JOIN categorias c ON c.id = r.id_categoria
            ORDER BY r.nome";

    return $pdo->query($sql)->fetchAll();
}

function buscarReceita(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare(
        "SELECT id, nome, modo_preparo, tempo_preparo, id_categoria FROM receitas WHERE id = ?"
    );
    $stmt->execute([$id]);
    $linha = $stmt->fetch();

    return $linha === false ? null : $linha;
}


function salvarReceita(PDO $pdo, int $id, string $nome, int $idCategoria, int $tempo, string $modoPreparo): array
{
    if ($nome === '') {
        return ['ok' => false, 'mensagem' => 'O nome da receita não pode ficar em branco.'];
    }

    if ($idCategoria <= 0) {
        return ['ok' => false, 'mensagem' => 'Escolha a categoria da receita.'];
    }

    // Tempo zerado não é detalhe: filtrarReceitasValidas() esconde do site
    // qualquer receita com tempo <= 0, então ela sumiria sem explicação.
    if ($tempo < 1) {
        return ['ok' => false, 'mensagem' => 'O tempo de preparo precisa ser de pelo menos 1 minuto.'];
    }

    if ($tempo > RECEITA_TEMPO_MAXIMO) {
        return ['ok' => false, 'mensagem' => 'Esse tempo passa de uma semana. Confira se o número está em minutos.'];
    }

    if (trim($modoPreparo) === '') {
        return ['ok' => false, 'mensagem' => 'Escreva o modo de preparo: um passo por linha.'];
    }

    try {
        if ($id > 0) {
            $stmt = $pdo->prepare(
                "UPDATE receitas SET nome = ?, id_categoria = ?, tempo_preparo = ?, modo_preparo = ? WHERE id = ?"
            );
            $stmt->execute([$nome, $idCategoria, $tempo, $modoPreparo, $id]);

            if ($stmt->rowCount() === 0 && buscarReceita($pdo, $id) === null) {
                return ['ok' => false, 'mensagem' => 'Receita não encontrada.'];
            }

            return ['ok' => true, 'mensagem' => "Receita \"{$nome}\" salva."];
        }

        $stmt = $pdo->prepare(
            "INSERT INTO receitas (nome, id_categoria, tempo_preparo, modo_preparo) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$nome, $idCategoria, $tempo, $modoPreparo]);
        $novoId = (int) $pdo->lastInsertId();

        return [
            'ok'       => true,
            'novo_id'  => $novoId,
            'mensagem' => "Receita \"{$nome}\" cadastrada. Agora acrescente os ingredientes dela — "
                        . 'sem pelo menos um, a receita não aparece no site.',
        ];

    } catch (PDOException $e) {
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'mensagem' => "Já existe uma receita chamada \"{$nome}\"."];
        }

        if (($e->errorInfo[1] ?? 0) === 1452) {
            return ['ok' => false, 'mensagem' => 'A categoria escolhida não existe mais. Recarregue a página.'];
        }

        throw $e;
    }
}


/**
 * Exclusão da receita.
 *
 * Duas situações diferentes:
 *  - existe PRODUTO apontando para ela -> recusa. Apagar a receita
 *    obrigaria a apagar o produto, e o produto tem histórico de venda.
 *  - só existem ingredientes ligados   -> apaga os vínculos e a receita,
 *    numa transação. O vínculo não tem valor sozinho.
 */
function excluirReceita(PDO $pdo, int $id): array
{
    $receita = buscarReceita($pdo, $id);

    if ($receita === null) {
        return ['ok' => false, 'mensagem' => 'Receita não encontrada.'];
    }

    $stmt = $pdo->prepare("SELECT nome FROM produtos WHERE id_receita = ? ORDER BY nome LIMIT 3");
    $stmt->execute([$id]);
    $produtos = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($produtos) {
        $lista = implode(', ', $produtos);

        return [
            'ok' => false,
            'mensagem' => "Não dá para excluir \"{$receita['nome']}\": o produto {$lista} nasce dela, "
                        . 'e produto tem histórico de venda. Exclua o produto primeiro.',
        ];
    }

    // A transação existe porque são dois comandos: se o segundo falhasse,
    // a receita ficaria sem ingredientes e ninguém saberia por quê.
    $pdo->beginTransaction();

    try {
        $stmt = $pdo->prepare("DELETE FROM receita_ingrediente WHERE id_receita = ?");
        $stmt->execute([$id]);
        $vinculos = $stmt->rowCount();

        $stmt = $pdo->prepare("DELETE FROM receitas WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        $detalhe = $vinculos > 0
            ? " Os {$vinculos} ingredientes dela foram desvinculados, mas continuam no catálogo."
            : '';

        return ['ok' => true, 'mensagem' => "Receita \"{$receita['nome']}\" excluída.{$detalhe}"];

    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }
}


// ====================================================================
// OS INGREDIENTES DA RECEITA (relacionamento N:N)
// ====================================================================

function listarIngredientesDaReceita(PDO $pdo, int $idReceita): array
{
    $sql = "SELECT ri.id_ingrediente,
                   ri.quantidade,
                   i.nome,
                   i.unidade_medida
            FROM receita_ingrediente ri
            INNER JOIN ingredientes i ON i.id = ri.id_ingrediente
            WHERE ri.id_receita = ?
            ORDER BY i.nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idReceita]);

    return $stmt->fetchAll();
}

/** Só os que ainda NÃO estão na receita: assim o seletor não oferece
 *  algo que geraria erro de chave duplicada ao ser adicionado. */
function ingredientesDisponiveis(PDO $pdo, int $idReceita): array
{
    $sql = "SELECT id, nome, unidade_medida
            FROM ingredientes
            WHERE id NOT IN (SELECT id_ingrediente FROM receita_ingrediente WHERE id_receita = ?)
            ORDER BY nome";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idReceita]);

    return $stmt->fetchAll();
}


function adicionarIngredienteNaReceita(PDO $pdo, int $idReceita, int $idIngrediente, float $quantidade): array
{
    if ($idIngrediente <= 0) {
        return ['ok' => false, 'mensagem' => 'Escolha o ingrediente.'];
    }

    if (!is_finite($quantidade) || $quantidade <= 0) {
        return ['ok' => false, 'mensagem' => 'A quantidade precisa ser maior que zero. Use vírgula para os decimais.'];
    }

    if ($quantidade > 99999999.99) {
        return ['ok' => false, 'mensagem' => 'Essa quantidade passa do limite da coluna do banco.'];
    }

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES (?, ?, ?)"
        );
        $stmt->execute([$idReceita, $idIngrediente, $quantidade]);

        $ingrediente = $pdo->prepare("SELECT nome, unidade_medida FROM ingredientes WHERE id = ?");
        $ingrediente->execute([$idIngrediente]);
        $dados = $ingrediente->fetch();

        $linha = formatarIngrediente($quantidade, $dados['unidade_medida'] ?? '', $dados['nome'] ?? '');

        return ['ok' => true, 'mensagem' => "Acrescentado: {$linha}."];

    } catch (PDOException $e) {
        // 1062: a chave primária é (id_receita, id_ingrediente), então o
        // banco recusa o mesmo ingrediente duas vezes na mesma receita.
        if (($e->errorInfo[1] ?? 0) === 1062) {
            return ['ok' => false, 'mensagem' => 'Esse ingrediente já está na receita. Remova antes de acrescentar com outra quantidade.'];
        }

        if (($e->errorInfo[1] ?? 0) === 1452) {
            return ['ok' => false, 'mensagem' => 'O ingrediente escolhido não existe mais. Recarregue a página.'];
        }

        throw $e;
    }
}


function removerIngredienteDaReceita(PDO $pdo, int $idReceita, int $idIngrediente): array
{
    $stmt = $pdo->prepare("SELECT nome FROM ingredientes WHERE id = ?");
    $stmt->execute([$idIngrediente]);
    $nome = (string) $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM receita_ingrediente WHERE id_receita = ? AND id_ingrediente = ?");
    $stmt->execute([$idReceita, $idIngrediente]);

    if ($stmt->rowCount() === 0) {
        return ['ok' => false, 'mensagem' => 'Esse ingrediente não estava na receita.'];
    }

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM receita_ingrediente WHERE id_receita = ?");
    $stmt->execute([$idReceita]);
    $restantes = (int) $stmt->fetchColumn();

    // Avisa quando a receita fica sem nenhum: nesse estado ela some do
    // site, e sem o aviso pareceria que o site quebrou.
    $aviso = $restantes === 0
        ? ' A receita ficou sem ingredientes, então deixou de aparecer no site.'
        : '';

    return [
        'ok' => true,
        'mensagem' => "\"{$nome}\" removido da receita. Ele continua no catálogo.{$aviso}",
    ];
}
