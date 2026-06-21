<?php
// funcoes.php
//
// ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
// ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
//
// Toda função deste arquivo recebe os dados de que precisa via parâmetro
// e devolve o resultado com return. Nenhuma delas lê variável global —
// quem chama a função é quem decide qual array/valor entregar.

/**
 * Converte minutos em um texto legível.
 * Exemplos: 240 -> "4h", 90 -> "1h30", 45 -> "45 min".
 */
function formatarTempoPreparo(int $minutos): string
{
    $horas = intdiv($minutos, 60);
    $minutosRestantes = $minutos % 60;

    if ($horas === 0) {
        return $minutosRestantes . ' min';
    }

    if ($minutosRestantes === 0) {
        return $horas . 'h';
    }

    return $horas . 'h' . $minutosRestantes;
}

/**
 * Decide se um link de menu deve receber a classe "active".
 * Recebe a página atual e a lista de páginas que acendem aquele link.
 */
function classeNavAtiva(string $paginaAtual, array $paginasQueAtivam): string
{
    if (in_array($paginaAtual, $paginasQueAtivam, true)) {
        return ' active';
    }

    return '';
}

/**
 * Seleciona as N primeiras receitas do array pra exibir como destaque.
 */
function obterDestaques(array $receitas, int $quantidade): array
{
    return array_slice($receitas, 0, $quantidade);
}

/**
 * ===== RUBRICA TECH FORGE - LÓGICA DE PESQUISA OU FILTRO =====
 * Filtra o array de receitas por categoria. Se a categoria for "todas"
 * (ou vazia), devolve o array completo, sem filtrar nada.
 */
function filtrarPorCategoria(array $receitas, string $categoria): array
{
    if ($categoria === '' || $categoria === 'todas') {
        return $receitas;
    }

    $receitasFiltradas = [];

    foreach ($receitas as $receita) {
        if ($receita['categoria'] === $categoria) {
            $receitasFiltradas[] = $receita;
        }
    }

    return $receitasFiltradas;
}

/**
 * Extrai a lista de categorias únicas existentes no array de receitas,
 * pra montar os botões de filtro dinamicamente (sem precisar digitar
 * "Pão, Doce, Salgado..." fixo em algum lugar).
 */
function obterCategorias(array $receitas): array
{
    $categorias = [];

    foreach ($receitas as $receita) {
        if (!in_array($receita['categoria'], $categorias, true)) {
            $categorias[] = $receita['categoria'];
        }
    }

    sort($categorias);

    return $categorias;
}

/**
 * Procura dentro do array de receitas a que tem o id informado.
 * Devolve a receita (array associativo) encontrada, ou null se não existir.
 */
function buscarReceitaPorId(array $receitas, int $id): ?array
{
    foreach ($receitas as $receita) {
        if ($receita['id'] === $id) {
            return $receita;
        }
    }

    return null;
}
