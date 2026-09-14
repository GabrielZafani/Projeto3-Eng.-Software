/**
 * ====================================================================
 * DEFINIÇÃO DE TIPOS (TypeScript)
 * ====================================================================
 * Descreve o "contrato" dos dados que a API PHP (api.php) devolve.
 * Com isso o editor autocompleta os campos e o compilador avisa se
 * algum nome for digitado errado.
 */

// Uma linha de venda, exatamente como vem da procedure sp_vendas_buscar.
//
// Atenção: quantidade e valor_unitario chegam do MySQL como string
// (colunas INT/DECIMAL viram texto no JSON do PDO). Por isso o tipo
// aceita os dois formatos e o código sempre converte com Number()
// antes de calcular - é isso que evita NaN na tela.
type Venda = {
    venda_id: number;
    produto: string;
    categoria: string;
    quantidade: number | string;
    valor_unitario: number | string;
    data_venda: string;
};

// Uma categoria com a contagem de vendas, vinda do CALL
// sp_vendas_categorias(). Vira um botão de filtro na tela.
type CategoriaFiltro = {
    categoria: string;
    vendas: number | string;
};

// O que a procedure devolveu sobre a consulta em si: quantas linhas o
// filtro achou, em que página estamos e quantas páginas existem.
type MetaConsulta = {
    origem: string;
    total_linhas: number;
    pagina: number;
    tamanho: number;
    total_paginas: number;
    busca: string;
    categoria: string;
    categorias: CategoriaFiltro[];
    // Faturamento calculado pela FUNÇÃO fn_faturamento_periodo, dentro
    // do banco. Não alimenta os cards (quem faz isso é o reduce): serve
    // só de conferência no console.
    faturamento_conferencia?: number | string;
};

// A resposta completa do endpoint.
type RespostaApi = {
    vendas: Venda[];
    meta: MetaConsulta;
};

// O estado dos controles da tela. É ele que vira a query string do
// fetch, e daí os parâmetros do CALL.
type EstadoConsulta = {
    busca: string;
    categoria: string;
    pagina: number;
};

// Os "grandes números" da dashboard, calculados com reduce().
type Metricas = {
    faturamentoTotal: number;
    unidadesVendidas: number;
    ticketMedio: number;
    campeaoVendas: string;
};

// Uma linha da tabela já pronta para a tela: todo número virou texto
// formatado. Existe para separar duas responsabilidades que antes
// estavam grudadas: o map() calcula e formata, o desenho só escreve.
// Com isso dá para conferir o valor de uma linha sem abrir o DOM.
type LinhaTabela = {
    identificador: string;
    produto: string;
    categoria: string;
    quantidade: string;
    valorUnitario: string;
    subtotal: string;
};

// O acumulado de um produto dentro do objeto de contagem do ranking.
// A chave do objeto é o nome do produto; isto aqui é o valor.
type TotaisProduto = {
    unidades: number;
    faturamento: number;
};

// Um produto já ranqueado, pronto para virar linha da lista de
// destaques. A posição não nasce com o objeto: só é atribuída depois
// de ordenar, porque antes disso ela seria um palpite.
type ProdutoRanqueado = {
    posicao: number;
    produto: string;
    faturamento: number;
    participacao: number;
};

// O recorte de um intervalo de datas, produzido pelo filter().
// variacao é null - e não zero - quando não existe semana anterior
// para comparar: zero significaria "não mudou nada", que é mentira.
type RecortePeriodo = {
    inicio: string;
    fim: string;
    faturamento: number;
    unidades: number;
    quantidadeVendas: number;
    categorias: number;
    variacao: number | null;
};
