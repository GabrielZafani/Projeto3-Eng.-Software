/**
 * ====================================================================
 * DEFINIÇÃO DE TIPOS (TypeScript)
 * ====================================================================
 * Descreve o "contrato" dos dados que a API PHP (api.php) devolve.
 * Com isso o editor autocompleta os campos e o compilador avisa se
 * algum nome for digitado errado.
 */

// Uma linha de venda, exatamente como vem da view vw_vendas_detalhadas.
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

// Os "grandes números" da dashboard, calculados com reduce().
type Metricas = {
    faturamentoTotal: number;
    unidadesVendidas: number;
    ticketMedio: number;
    campeaoVendas: string;
};
