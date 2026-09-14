<?php
// funcoes.php
//
// ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
// ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====


/**
 * Prepara um texto do banco para ser impresso em HTML.
 *
 * Enquanto o site era só leitura, imprimir direto era inofensivo: os nomes
 * vinham de um script SQL que só nós editávamos. Com as telas de cadastro,
 * qualquer pessoa passa a escolher esses textos - e uma categoria chamada
 * "<script>alert(1)</script>" executaria na página de receitas de quem
 * abrisse o site. Esta função corta esse caminho na saída.
 *
 * ENT_QUOTES cobre aspas simples também, porque o valor às vezes cai dentro
 * de um atributo (alt="...", value='...').
 */
function escapar(?string $texto): string
{
    return htmlspecialchars($texto ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formata um valor em reais para as mensagens das telas de cadastro.
 *
 * Não usa a extensão intl de propósito: ela não vem ligada no XAMPP padrão,
 * e a tela quebraria na máquina da faculdade sem aviso nenhum.
 */
function formatarDinheiro(float $valor): string
{
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

/**
 * ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * Converte minutos em um texto legível.
 * Exemplos: 240 -> "4h", 90 -> "1h30", 45 -> "45 min".
 * Parâmetro de entrada: $minutos. Retorno: string formatada.
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
 * ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * Decide se um link de menu deve receber a classe "active".
 * Parâmetros: página atual e lista de páginas que ativam o link.
 * Retorno: string " active" ou vazio.
 */
function classeNavAtiva(string $paginaAtual, array $paginasQueAtivam): string
{
    if (in_array($paginaAtual, $paginasQueAtivam, true)) {
        return ' active';
    }

    return '';
}

/**
 * ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * Seleciona as N primeiras receitas do array pra exibir como destaque.
 * Parâmetros: array $receitas, int $quantidade. Retorno: array fatiado.
 */
function obterDestaques(array $receitas, int $quantidade): array
{
    return array_slice($receitas, 0, $quantidade);
}

/**
 * ===== RUBRICA TECH FORGE - VALIDAÇÃO DE REGRAS DE NEGÓCIO COM CONDICIONAIS =====
 * Confere se uma receita tem dados consistentes antes de ser exibida:
 * tempo de preparo deve ser maior que zero, e precisa ter pelo menos
 * um ingrediente e um passo no modo de preparo.
 */
function receitaValida(array $receita): bool
{
    if (!isset($receita['tempo_preparo']) || $receita['tempo_preparo'] <= 0) {
        return false;
    }

    if (empty($receita['ingredientes']) || empty($receita['modo_preparo'])) {
        return false;
    }

    return true;
}

/**
 * Percorre o array de receitas e devolve só as que passam na validação
 * de receitaValida(). Receitas com dado inconsistente (ex: tempo de
 * preparo zerado ou negativo) simplesmente não aparecem na tela -
 * sem gerar erro na página.
 */
function filtrarReceitasValidas(array $receitas): array
{
    $receitasValidas = [];

    foreach ($receitas as $receita) {
        if (receitaValida($receita)) {
            $receitasValidas[] = $receita;
        }
    }

    return $receitasValidas;
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
 * ===== RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS DO BANCO NA TELA =====
 * Lista TODAS as categorias cadastradas, para os botões de filtro.
 *
 * Antes os botões eram montados a partir das receitas já carregadas. O
 * efeito era que uma categoria recém-cadastrada na administração não
 * aparecia aqui enquanto não tivesse receita - e parecia que o cadastro
 * não tinha funcionado. Agora a lista vem da tabela.
 *
 * Clicar numa categoria vazia cai no "Nenhuma receita encontrada nessa
 * categoria", que já existia e explica o que houve.
 */
function buscarCategoriasDoBanco(PDO $pdo): array
{
    $categorias = [];

    $resultado = $pdo->query("SELECT nome FROM categorias ORDER BY nome");

    while ($linha = $resultado->fetch()) {
        $categorias[] = $linha['nome'];
    }

    return $categorias;
}

/**
 * ===== RUBRICA TECH FORGE - LÓGICA DE PESQUISA OU FILTRO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * Procura dentro do array de receitas a que tem o id informado.
 * Parâmetros: array $receitas, int $id. Retorno: array da receita ou null.
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

/**
 * ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * Monta uma linha de ingrediente legível a partir dos dados normalizados
 * do banco (quantidade + unidade de medida + nome do ingrediente).
 * Exemplo: formatarIngrediente(500, 'g', 'Farinha de trigo') -> "500g de Farinha de trigo"
 */
function formatarIngrediente(float $quantidade, string $unidade, string $nome): string
{
    // remove ".00" de quantidades inteiras, mantém casas decimais quando existirem
    $quantidadeTexto = (floor($quantidade) === $quantidade)
        ? (string) (int) $quantidade
        : rtrim(rtrim(number_format($quantidade, 2, ',', ''), '0'), ',');

    return $quantidadeTexto . $unidade . ' de ' . $nome;
}

/**
 * ===== RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO =====
 * ===== RUBRICA TECH FORGE - FLUXO DE DADOS (PARÂMETROS E RETORNO) =====
 * O banco guarda o modo de preparo como um texto único (TEXT), com cada
 * passo em uma linha separada. Essa função transforma esse texto em um
 * array de passos, igual ao formato que as páginas já esperam.
 */
function modoPreparoParaPassos(string $textoModoPreparo): array
{
    $linhas = explode("\n", $textoModoPreparo);
    $passos = [];

    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if ($linha !== '') {
            $passos[] = $linha;
        }
    }

    return $passos;
}

/**
 * Caminho da foto de uma receita, seguindo a convenção de nome de arquivo
 * "receita-{id}.jpg" dentro de assets/img. Se o arquivo não existir no
 * servidor, devolve string vazia - quem exibe decide usar o gradiente
 * de cor como alternativa, sem gerar erro de imagem quebrada.
 */
function caminhoImagemReceita(int $id): string
{
    $caminhoRelativo = 'assets/img/receita-' . $id . '.jpg';
    $caminhoNoServidor = __DIR__ . '/../' . $caminhoRelativo;

    if (file_exists($caminhoNoServidor)) {
        return $caminhoRelativo;
    }

    return '';
}

/**
 * Escolhe uma das 3 classes de gradiente de cor com base no id da receita,
 * só pra variar visualmente os cards que ainda não têm foto.
 */
function thumbClassPorId(int $id): string
{
    $opcoes = ['thumb-1', 'thumb-2', 'thumb-3'];
    $indice = ($id - 1) % count($opcoes);

    return $opcoes[$indice];
}

/**
 * ===== RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS =====
 * ===== RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS RECUPERADOS DO BANCO E DEMONSTRADOS NA TELA =====
 * ===== RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP (WHILE) =====
 *
 * Busca todas as receitas no banco (PDO) e monta o MESMO formato de
 * array que usávamos no mock de teste: um único array $receitas, cada
 * item com id, nome, categoria, tempo_preparo, ingredientes (array) e
 * modo_preparo (array de passos). É por isso que nenhuma outra função
 * (filtro, validação, busca por id) precisou mudar uma linha sequer.
 */
function buscarReceitasDoBanco(PDO $pdo): array
{
    $receitas = [];

    // 1ª consulta: dados principais da receita + nome da categoria (JOIN)
    $sqlReceitas = "SELECT r.id, r.nome, r.modo_preparo, r.tempo_preparo, c.nome AS categoria
                    FROM receitas r
                    INNER JOIN categorias c ON r.id_categoria = c.id
                    ORDER BY r.id";
    $resultadoReceitas = $pdo->query($sqlReceitas);

    while ($linha = $resultadoReceitas->fetch()) {
        $id = (int) $linha['id'];
        $receitas[$id] = [
            'id' => $id,
            'nome' => $linha['nome'],
            'categoria' => $linha['categoria'],
            'tempo_preparo' => (int) $linha['tempo_preparo'],
            'thumb' => thumbClassPorId($id),
            'imagem' => caminhoImagemReceita($id),
            'modo_preparo' => modoPreparoParaPassos($linha['modo_preparo']),
            'ingredientes' => [],
        ];
    }

    // 2ª consulta: ingredientes de todas as receitas, já com nome e unidade
    // (tabela intermediária receita_ingrediente + tabela ingredientes)
    $sqlIngredientes = "SELECT ri.id_receita, ri.quantidade, i.nome, i.unidade_medida
                        FROM receita_ingrediente ri
                        INNER JOIN ingredientes i ON ri.id_ingrediente = i.id
                        ORDER BY ri.id_receita";
    $resultadoIngredientes = $pdo->query($sqlIngredientes);

    while ($linha = $resultadoIngredientes->fetch()) {
        $idReceita = (int) $linha['id_receita'];

        if (isset($receitas[$idReceita])) {
            $receitas[$idReceita]['ingredientes'][] = formatarIngrediente(
                (float) $linha['quantidade'],
                $linha['unidade_medida'],
                $linha['nome']
            );
        }
    }

    // array_values reindexa de 0,1,2... (estava indexado pelo id da receita,
    // útil só durante a montagem acima, pra ligar ingrediente à receita certa)
    return array_values($receitas);
}
