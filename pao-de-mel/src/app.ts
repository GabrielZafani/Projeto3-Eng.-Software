// ====================================================================
// DASHBOARD DE VENDAS - Padaria Pão de Mel
// ====================================================================
// RUBRICA - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO
// RUBRICA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
// RUBRICA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)
// RUBRICA - STORED PROCEDURES (busca, filtro e paginação no banco)


// Linhas por página na tabela. Com 21 vendas dá 3 páginas.
const TAMANHO_PAGINA = 10;

// Estado dos controles da tela. Toda mudança aqui vira uma query
// string no fetch e, do outro lado, parâmetro do CALL.
const estado: EstadoConsulta = {
    busca: '',
    categoria: 'todas',
    pagina: 1,
};


// ====================================================================
// AUXILIAR: conversão numérica à prova de NaN
// ====================================================================
// O JSON traz "8.00" (string) e, se o banco estiver sujo, pode trazer
// null ou texto inválido. Number(null) vira 0, mas Number("abc") vira
// NaN - e NaN contamina toda soma seguinte, estampando "R$ NaN" na tela.
// Esta função barra o problema na entrada, antes de qualquer cálculo.
function numeroSeguro(valor: unknown): number {
    const convertido = Number(valor);
    return Number.isFinite(convertido) ? convertido : 0;
}

function formatarMoeda(valor: number): string {
    return numeroSeguro(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}

function escreverTexto(id: string, texto: string): void {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.innerText = texto;
    }
}

// Texto vindo do banco nunca entra por innerHTML sem passar por aqui.
// Um produto cadastrado como <img onerror=...> executaria script na
// tela de quem abrisse a dashboard.
function escapar(texto: string): string {
    const area = document.createElement('div');
    area.innerText = texto;
    return area.innerHTML;
}


// ====================================================================
// PASSO 1: BUSCAR OS DADOS NA API PHP (fetch + async/await + try/catch)
// ====================================================================
// Monta a query string que a api.php repassa para o CALL.
function montarUrl(pagina: number, tamanho: number): string {
    const parametros = new URLSearchParams({
        busca: estado.busca,
        categoria: estado.categoria,
        pagina: String(pagina),
        tamanho: String(tamanho),
    });

    return 'api.php?' + parametros.toString();
}

// Uma busca só por vez. Sem esta trava, clicar rápido em duas páginas
// deixava a resposta mais lenta chegar por último e sobrescrever a
// tela com a página errada.
let carregando = false;

async function carregarDashboard(): Promise<void> {
    if (carregando) {
        return;
    }

    carregando = true;
    travarControles(true);
    mostrarCarregando(true);

    try {
        // DUAS chamadas, de propósito:
        //   1) a página atual, para a tabela
        //   2) o filtro inteiro (tamanho=0), para os cards
        // Se os cards usassem só a página, o faturamento total mudaria
        // toda vez que o usuário clicasse em "próxima".
        const [respostaPagina, respostaTudo] = await Promise.all([
            fetch(montarUrl(estado.pagina, TAMANHO_PAGINA)),
            fetch(montarUrl(1, 0)),
        ]);

        // Falha de servidor (500, 404...) não dispara catch sozinha:
        // o fetch só rejeita em erro de rede. Por isso checamos na mão.
        if (!respostaPagina.ok) {
            throw new Error(`A API respondeu com status ${respostaPagina.status}`);
        }
        if (!respostaTudo.ok) {
            throw new Error(`A API respondeu com status ${respostaTudo.status}`);
        }

        const dadosPagina: unknown = await respostaPagina.json();
        const dadosTudo: unknown = await respostaTudo.json();

        // A API pode devolver {"error": "..."} em vez do objeto esperado.
        // Sem esta checagem, o .reduce() abaixo quebraria a página.
        const pagina = validarResposta(dadosPagina);
        const tudo = validarResposta(dadosTudo);

        mostrarCarregando(false);

        // A procedure é a dona da verdade sobre a página atual: se o
        // usuário pediu a página 9 de um filtro com 2, ela devolve
        // vazio e a tela se corrige aqui em vez de travar sem explicação.
        if (pagina.vendas.length === 0 && tudo.vendas.length > 0 && estado.pagina > 1) {
            estado.pagina = 1;
            carregando = false;
            travarControles(false);
            await carregarDashboard();
            return;
        }

        desenharChips(tudo.meta);
        conferirComBanco(tudo);

        // EDGE CASE: banco limpo, ou filtro que não achou nada.
        // Em vez de dividir por zero e imprimir NaN, mostramos um aviso.
        if (tudo.vendas.length === 0) {
            mostrarVazio(true, tudo.meta);
            zerarCards();
            zerarDestaques();
            exibirTabela([]);
            desenharPaginacao(pagina.meta);
            escreverResumo(pagina.meta);
            return;
        }

        mostrarVazio(false, tudo.meta);
        atualizarCards(tudo.vendas);
        atualizarDestaques(tudo.vendas);
        exibirTabela(pagina.vendas);
        desenharPaginacao(pagina.meta);
        escreverResumo(pagina.meta);

    } catch (erro) {
        // Rede fora do ar, Apache parado, MySQL desligado, JSON inválido:
        // tudo cai aqui e a página mostra um aviso em vez de travar.
        console.error('Falha ao carregar as vendas:', erro);
        mostrarCarregando(false);
        mostrarErro(erro instanceof Error ? erro.message : 'Erro desconhecido');
        zerarCards();
        zerarDestaques();
        exibirTabela([]);
        limparPaginacao();

    } finally {
        carregando = false;
        travarControles(false);
    }
}

// Confere o formato antes de confiar. O que vem pela rede não tem tipo:
// o TypeScript só garante o que foi checado em tempo de execução.
function validarResposta(dados: unknown): RespostaApi {
    if (dados === null || typeof dados !== 'object') {
        throw new Error('A API não retornou um objeto de resposta');
    }

    const corpo = dados as { vendas?: unknown; meta?: unknown };

    if (!Array.isArray(corpo.vendas)) {
        throw new Error('A API não retornou uma lista de vendas');
    }

    if (corpo.meta === null || typeof corpo.meta !== 'object') {
        throw new Error('A API não informou os dados de paginação');
    }

    return corpo as RespostaApi;
}

// A função do banco (fn_faturamento_periodo) calcula o mesmo faturamento
// por outro caminho. Se os dois números divergirem, alguém mexeu na
// regra de um lado só - e o console avisa antes de a nota sair errada.
function conferirComBanco(resposta: RespostaApi): void {
    const doBanco = resposta.meta.faturamento_conferencia;

    if (doBanco === undefined) {
        return;
    }

    const doReduce = calcularMetricas(resposta.vendas).faturamentoTotal;
    const diferenca = Math.abs(numeroSeguro(doBanco) - doReduce);

    if (diferenca > 0.01) {
        console.warn(
            `Divergência: reduce=${doReduce.toFixed(2)} x ` +
            `fn_faturamento_periodo=${numeroSeguro(doBanco).toFixed(2)}`
        );
    } else {
        console.info(
            `Conferido: o reduce (${formatarMoeda(doReduce)}) bate com a ` +
            `função fn_faturamento_periodo do banco.`
        );
    }
}


// ====================================================================
// PASSO 2: OS GRANDES NÚMEROS - TUDO COM .reduce()
// ====================================================================
// RUBRICA: extrair as métricas globais com reduce() diretamente do
// array bruto enviado pelo PHP (quantidade × valor unitário).
function calcularMetricas(vendas: Venda[]): Metricas {
    // --- 1. FATURAMENTO TOTAL ---
    // Acumula, linha a linha, quantidade × valor unitário.
    const faturamentoTotal: number = vendas.reduce((acumulador, venda) => {
        const quantidade = numeroSeguro(venda.quantidade);
        const valorUnitario = numeroSeguro(venda.valor_unitario);
        return acumulador + (quantidade * valorUnitario);
    }, 0);

    // --- 2. UNIDADES VENDIDAS ---
    const unidadesVendidas: number = vendas.reduce((acumulador, venda) => {
        return acumulador + numeroSeguro(venda.quantidade);
    }, 0);

    // --- 3. TICKET MÉDIO POR VENDA ---
    // vendas.length nunca é 0 aqui (checado antes), mas o guarda-chuva
    // fica: divisão por zero produziria Infinity na tela.
    const ticketMedio: number = vendas.length > 0
        ? faturamentoTotal / vendas.length
        : 0;

    // --- 4. PRODUTO CAMPEÃO DE FATURAMENTO ---
    // Aqui o reduce acumula um objeto: soma o faturamento por produto.
    const porProduto: Record<string, number> = vendas.reduce((acumulador, venda) => {
        const nome = venda.produto;
        const total = numeroSeguro(venda.quantidade) * numeroSeguro(venda.valor_unitario);
        acumulador[nome] = (acumulador[nome] ?? 0) + total;
        return acumulador;
    }, {} as Record<string, number>);

    // E este reduce percorre o objeto acima achando o maior.
    const campeaoVendas: string = Object.keys(porProduto).reduce((campeao, nome) => {
        return porProduto[nome] > (porProduto[campeao] ?? 0) ? nome : campeao;
    }, Object.keys(porProduto)[0] ?? '—');

    return { faturamentoTotal, unidadesVendidas, ticketMedio, campeaoVendas };
}

function atualizarCards(vendas: Venda[]): void {
    const metricas = calcularMetricas(vendas);

    escreverTexto('card-faturamento', formatarMoeda(metricas.faturamentoTotal));
    escreverTexto('card-unidades', `${metricas.unidadesVendidas} un.`);
    escreverTexto('card-ticket', formatarMoeda(metricas.ticketMedio));
    escreverTexto('card-campeao', metricas.campeaoVendas);
}

// EDGE CASE: sem dados, os cards mostram zero de verdade - nunca "NaN".
function zerarCards(): void {
    escreverTexto('card-faturamento', formatarMoeda(0));
    escreverTexto('card-unidades', '0 un.');
    escreverTexto('card-ticket', formatarMoeda(0));
    escreverTexto('card-campeao', '—');
}


// ====================================================================
// PASSO 2B: RANKING DE DESTAQUES - objeto de contagem + .map()
// ====================================================================
// RUBRICA: descobrir o maior indicador de destaque de forma dinâmica,
// com estrutura de chave-valor.
//
// O acumulador é um objeto de contagem: a chave é o nome do produto e o
// valor guarda os totais dele. Percorrer o array UMA vez e consultar por
// chave é o que evita o laço dentro de laço - com a lista de produtos
// crescendo, comparar cada venda com cada produto ficaria lento à toa.
//
// Nada aqui tem nome de produto escrito no código: se amanhã a padaria
// cadastrar um pão novo que lidere, ele aparece sozinho.

const TOTAL_DESTAQUES = 3;

function ranquearProdutos(vendas: Venda[], limite: number): ProdutoRanqueado[] {
    const porProduto: Record<string, TotaisProduto> = vendas.reduce((acumulador, venda) => {
        const nome = venda.produto;
        const unidades = numeroSeguro(venda.quantidade);
        const faturamento = unidades * numeroSeguro(venda.valor_unitario);

        const atual = acumulador[nome] ?? { unidades: 0, faturamento: 0 };

        acumulador[nome] = {
            unidades: atual.unidades + unidades,
            faturamento: atual.faturamento + faturamento,
        };

        return acumulador;
    }, {} as Record<string, TotaisProduto>);

    const faturamentoGeral: number = Object.values(porProduto)
        .reduce((total, totais) => total + totais.faturamento, 0);

    return Object.entries(porProduto)
        // .map() nº 1: o par [chave, valor] do objeto vira o formato da tela.
        .map(([produto, totais]): ProdutoRanqueado => ({
            posicao: 0,
            produto,
            faturamento: totais.faturamento,
            // EDGE CASE: faturamento geral zerado (banco só com preço 0)
            // faria 0/0 = NaN e estamparia "NaN% do total" na tela.
            participacao: faturamentoGeral > 0
                ? (totais.faturamento / faturamentoGeral) * 100
                : 0,
        }))
        .sort((a, b) => {
            // Empate em dinheiro se desfaz por unidade vendida. Sem este
            // critério, dois produtos com o mesmo faturamento trocavam de
            // lugar a cada F5 - a ordem ficava por conta do navegador, e
            // parecia que o ranking estava calculando errado.
            if (b.faturamento !== a.faturamento) {
                return b.faturamento - a.faturamento;
            }
            return (porProduto[b.produto]?.unidades ?? 0) - (porProduto[a.produto]?.unidades ?? 0);
        })
        .slice(0, limite)
        // .map() nº 2: a posição só existe DEPOIS de ordenar e cortar.
        .map((item, indice): ProdutoRanqueado => ({ ...item, posicao: indice + 1 }));
}


// ====================================================================
// PASSO 2C: SEGMENTAÇÃO POR PERÍODO - .filter()
// ====================================================================
// RUBRICA: isolar subconjuntos de dados para criar inteligência de
// negócio, segmentando indicadores por período.
//
// A janela é contada a partir da venda MAIS RECENTE do banco, e não da
// data de hoje. Custou um painel em branco descobrir isto: os dados de
// demonstração são de agosto, e contar "últimos 7 dias" a partir de hoje
// devolvia zero venda - uma tela vazia que parecia defeito do código,
// quando o banco estava certo. Ancorado no último movimento, o recorte
// sempre mostra a semana que de fato aconteceu.

const DIAS_DA_JANELA = 7;

// O banco pode devolver data nula, vazia ou '0000-00-00'. Sem esta
// barreira o new Date() produz Invalid Date, e a janela inteira vira NaN.
function ehDataValida(texto: unknown): boolean {
    if (typeof texto !== 'string' || !/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
        return false;
    }

    return !Number.isNaN(new Date(texto + 'T00:00:00Z').getTime());
}

// UTC de ponta a ponta. Com horário local, quem abrisse a dashboard em
// fuso negativo veria a data voltar um dia na conversão de ida e volta,
// e a semana começaria no dia errado.
function somarDias(dataIso: string, dias: number): string {
    const data = new Date(dataIso + 'T00:00:00Z');
    data.setUTCDate(data.getUTCDate() + dias);
    return data.toISOString().slice(0, 10);
}

function somarFaturamento(vendas: Venda[]): number {
    return vendas.reduce(
        (total, venda) => total + numeroSeguro(venda.quantidade) * numeroSeguro(venda.valor_unitario),
        0
    );
}

function recortarUltimaSemana(vendas: Venda[]): RecortePeriodo | null {
    // .filter() nº 1: fora as linhas sem data utilizável.
    const comData: Venda[] = vendas.filter((venda) => ehDataValida(venda.data_venda));

    if (comData.length === 0) {
        return null;
    }

    // Datas em 'AAAA-MM-DD' comparam certo como texto: largura fixa e a
    // parte mais significativa primeiro. Por isso não há Date() aqui.
    const fim: string = comData.reduce(
        (maior, venda) => (venda.data_venda > maior ? venda.data_venda : maior),
        comData[0].data_venda
    );
    const inicio: string = somarDias(fim, -(DIAS_DA_JANELA - 1));

    // .filter() nº 2: a janela em si.
    const daJanela: Venda[] = comData.filter(
        (venda) => venda.data_venda >= inicio && venda.data_venda <= fim
    );

    // .filter() nº 3: a semana imediatamente anterior, só para comparar.
    const fimAnterior: string = somarDias(inicio, -1);
    const inicioAnterior: string = somarDias(fimAnterior, -(DIAS_DA_JANELA - 1));
    const daJanelaAnterior: Venda[] = comData.filter(
        (venda) => venda.data_venda >= inicioAnterior && venda.data_venda <= fimAnterior
    );

    const faturamento: number = somarFaturamento(daJanela);
    const faturamentoAnterior: number = somarFaturamento(daJanelaAnterior);

    return {
        inicio,
        fim,
        faturamento,
        unidades: daJanela.reduce((total, venda) => total + numeroSeguro(venda.quantidade), 0),
        quantidadeVendas: daJanela.length,
        // Set sobre o map dos nomes: quantas categorias distintas
        // movimentaram na semana, sem contar a mesma duas vezes.
        categorias: new Set(daJanela.map((venda) => venda.categoria)).size,
        // EDGE CASE: sem semana anterior não existe variação. Zero aqui
        // seria mentira ("não mudou nada"), e dividir por zero imprimiria
        // "Infinity%". null é o único valor honesto, e vira "—" na tela.
        variacao: faturamentoAnterior > 0
            ? ((faturamento - faturamentoAnterior) / faturamentoAnterior) * 100
            : null,
    };
}


// ====================================================================
// PASSO 2D: DESENHO DO PAINEL DE DESTAQUES
// ====================================================================
function atualizarDestaques(vendas: Venda[]): void {
    desenharRanking(ranquearProdutos(vendas, TOTAL_DESTAQUES));
    desenharPeriodo(recortarUltimaSemana(vendas));
}

function desenharRanking(ranking: ProdutoRanqueado[]): void {
    const lista = document.getElementById('lista-ranking');
    if (!lista) return;

    // EDGE CASE: sem venda nenhuma, uma lista vazia e muda parece tela
    // quebrada. A frase explica que o banco está limpo.
    if (ranking.length === 0) {
        lista.innerHTML = '<li class="ranking-vazio">Nenhum dado registrado.</li>';
        return;
    }

    // .map() nº 3: cada item ranqueado vira o HTML da sua linha, e só
    // então o join monta a lista inteira de uma vez - um innerHTML só,
    // em vez de um por produto.
    lista.innerHTML = ranking
        .map((item) => '<li class="ranking-item">'
            + '<span class="ranking-posicao">' + item.posicao + 'º</span>'
            + '<span class="ranking-nome">' + escapar(item.produto) + '</span>'
            + '<span class="ranking-valor">' + formatarMoeda(item.faturamento) + '</span>'
            + '<span class="ranking-fatia">' + item.participacao.toFixed(1) + '% do total</span>'
            + '</li>')
        .join('');
}

// '2026-08-23' -> '23/08'. Feito na mão porque toLocaleDateString em
// cima de uma data sem hora aplica o fuso do navegador e mostra o dia
// anterior para quem está a oeste de Greenwich.
function formatarDiaMes(dataIso: string): string {
    const partes = dataIso.split('-');
    return partes.length === 3 ? partes[2] + '/' + partes[1] : dataIso;
}

function desenharPeriodo(recorte: RecortePeriodo | null): void {
    if (recorte === null) {
        escreverTexto('periodo-faturamento', formatarMoeda(0));
        escreverTexto('periodo-intervalo', 'Nenhum dado registrado.');
        escreverTexto('periodo-detalhe', '—');
        escreverTexto('periodo-variacao', '—');
        return;
    }

    escreverTexto('periodo-faturamento', formatarMoeda(recorte.faturamento));
    escreverTexto(
        'periodo-intervalo',
        formatarDiaMes(recorte.inicio) + ' a ' + formatarDiaMes(recorte.fim)
    );

    const plural = recorte.quantidadeVendas === 1 ? 'venda' : 'vendas';
    const pluralCategoria = recorte.categorias === 1 ? 'categoria' : 'categorias';

    escreverTexto(
        'periodo-detalhe',
        recorte.quantidadeVendas + ' ' + plural + ' · ' + recorte.unidades + ' un. · '
        + recorte.categorias + ' ' + pluralCategoria
    );

    if (recorte.variacao === null) {
        escreverTexto('periodo-variacao', 'Sem semana anterior para comparar.');
        return;
    }

    const sinal = recorte.variacao >= 0 ? '+' : '';
    escreverTexto(
        'periodo-variacao',
        sinal + recorte.variacao.toFixed(1) + '% em relação à semana anterior'
    );
}

// EDGE CASE: erro de rede ou banco limpo apaga os destaques junto com
// os cards. Deixar o ranking antigo na tela depois de uma falha faria o
// usuário ler número velho achando que é o de agora.
function zerarDestaques(): void {
    desenharRanking([]);
    desenharPeriodo(null);
}


// ====================================================================
// PASSO 3: A TABELA DE VENDAS
// ====================================================================
// RUBRICA: .map() transforma a estrutura crua da API no formato que a
// interface exige - aqui, cada número virando texto em moeda local.
//
// A preparação é uma etapa separada do desenho de propósito: assim dá
// para conferir o subtotal de uma linha chamando esta função no console,
// sem depender de ler o HTML da tabela.
function prepararLinhas(vendas: Venda[]): LinhaTabela[] {
    return vendas.map((venda): LinhaTabela => {
        const quantidade = numeroSeguro(venda.quantidade);
        const valorUnitario = numeroSeguro(venda.valor_unitario);

        return {
            identificador: '#' + numeroSeguro(venda.venda_id),
            produto: venda.produto,
            categoria: venda.categoria,
            quantidade: String(quantidade),
            valorUnitario: formatarMoeda(valorUnitario),
            subtotal: formatarMoeda(quantidade * valorUnitario),
        };
    });
}

function exibirTabela(vendas: Venda[]): void {
    const tbody = document.getElementById('tabela-vendas-body');
    if (!tbody) return;

    // EDGE CASE: mensagem elegante no lugar de uma tabela vazia e muda.
    if (vendas.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum dado registrado.</td></tr>';
        return;
    }

    // Texto vindo do banco continua passando por escapar() na hora de
    // virar HTML: o map formata número, não neutraliza script.
    tbody.innerHTML = prepararLinhas(vendas)
        .map((linha) => '<tr>'
            + '<td>' + linha.identificador + '</td>'
            + '<td><strong>' + escapar(linha.produto) + '</strong></td>'
            + '<td><span class="badge badge-categoria">' + escapar(linha.categoria) + '</span></td>'
            + '<td class="text-end">' + linha.quantidade + '</td>'
            + '<td class="text-end">' + linha.valorUnitario + '</td>'
            + '<td class="text-end"><strong>' + linha.subtotal + '</strong></td>'
            + '</tr>')
        .join('');
}


// ====================================================================
// PASSO 4: BUSCA, FILTRO E PAGINAÇÃO (o que a procedure resolve)
// ====================================================================
// Os botões de categoria saem do CALL sp_vendas_categorias(): a tela
// não tem nenhuma categoria escrita à mão no código.
function desenharChips(meta: MetaConsulta): void {
    const area = document.getElementById('chips-categoria');
    if (!area) return;

    const categorias = Array.isArray(meta.categorias) ? meta.categorias : [];

    area.innerHTML = '';
    area.appendChild(criarChip('Todas', 'todas'));

    categorias.forEach((item) => {
        const rotulo = `${item.categoria} (${numeroSeguro(item.vendas)})`;
        area.appendChild(criarChip(rotulo, item.categoria));
    });
}

function criarChip(rotulo: string, valor: string): HTMLButtonElement {
    const botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'chip' + (estado.categoria === valor ? ' active' : '');
    botao.innerText = rotulo;

    botao.addEventListener('click', () => {
        estado.categoria = valor;
        // Trocar de filtro sempre volta para a página 1: senão o usuário
        // filtra "Bolo" estando na página 3 e recebe uma tela vazia.
        estado.pagina = 1;
        carregarDashboard();
    });

    return botao;
}

// Desenha os botões de página a partir do total que a PROCEDURE contou.
// A tela não sabe quantas páginas existem: quem sabe é o banco.
function desenharPaginacao(meta: MetaConsulta): void {
    const lista = document.querySelector('#paginacao .pagination');
    if (!lista) return;

    lista.innerHTML = '';

    const totalPaginas = Math.max(numeroSeguro(meta.total_paginas), 1);
    const paginaAtual = Math.max(numeroSeguro(meta.pagina), 1);

    if (totalPaginas <= 1) {
        return;
    }

    lista.appendChild(itemPaginacao('‹', paginaAtual - 1, paginaAtual <= 1, false));

    for (let numero = 1; numero <= totalPaginas; numero++) {
        lista.appendChild(itemPaginacao(String(numero), numero, false, numero === paginaAtual));
    }

    lista.appendChild(itemPaginacao('›', paginaAtual + 1, paginaAtual >= totalPaginas, false));
}

function itemPaginacao(rotulo: string, destino: number, desativado: boolean, ativo: boolean): HTMLLIElement {
    const item = document.createElement('li');
    item.className = 'page-item' + (desativado ? ' disabled' : '') + (ativo ? ' active' : '');

    const botao = document.createElement('button');
    botao.type = 'button';
    botao.className = 'page-link';
    botao.innerText = rotulo;
    botao.disabled = desativado;

    if (!desativado && !ativo) {
        botao.addEventListener('click', () => {
            estado.pagina = destino;
            carregarDashboard();
        });
    }

    item.appendChild(botao);
    return item;
}

function limparPaginacao(): void {
    const lista = document.querySelector('#paginacao .pagination');
    if (lista) {
        lista.innerHTML = '';
    }
    escreverTexto('resumo-resultado', '—');
}

function escreverResumo(meta: MetaConsulta): void {
    const total = numeroSeguro(meta.total_linhas);

    if (total === 0) {
        escreverTexto('resumo-resultado', 'Nenhuma venda encontrada para este filtro.');
        return;
    }

    const plural = total === 1 ? 'venda encontrada' : 'vendas encontradas';
    const paginas = Math.max(numeroSeguro(meta.total_paginas), 1);

    escreverTexto(
        'resumo-resultado',
        `${total} ${plural} · página ${numeroSeguro(meta.pagina)} de ${paginas}`
    );
}

// Enquanto o CALL não volta, os controles ficam travados. É o que evita
// duas buscas concorrentes pintarem a tabela na ordem errada.
function travarControles(travado: boolean): void {
    const campo = document.getElementById('campo-busca') as HTMLInputElement | null;
    if (campo) {
        campo.disabled = travado;
    }

    document.querySelectorAll('#chips-categoria .chip, #paginacao .page-link')
        .forEach((elemento) => {
            (elemento as HTMLButtonElement).disabled = travado;
        });
}


// ====================================================================
// ESTADOS DA TELA (carregando / vazio / erro)
// ====================================================================
function alternar(id: string, visivel: boolean): void {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.classList.toggle('d-none', !visivel);
    }
}

function mostrarCarregando(visivel: boolean): void {
    alternar('estado-carregando', visivel);
}

// O aviso de vazio muda de texto conforme o motivo: banco sem venda
// nenhuma é um problema; filtro que não achou nada é uso normal.
function mostrarVazio(visivel: boolean, meta?: MetaConsulta): void {
    if (visivel && meta) {
        const temFiltro = meta.busca !== '' || meta.categoria !== 'todas';

        escreverTexto(
            'estado-vazio',
            temFiltro
                ? 'Nenhuma venda com esse filtro. Tente outra categoria ou limpe a busca.'
                : 'Nenhum dado registrado. Assim que houver vendas no banco, os números aparecem aqui.'
        );
    }

    alternar('estado-vazio', visivel);
}

function mostrarErro(mensagem: string): void {
    escreverTexto('estado-erro-msg', mensagem);
    alternar('estado-erro', true);
}


// ====================================================================
// INÍCIO: dispara quando o HTML termina de carregar
// ====================================================================
document.addEventListener('DOMContentLoaded', () => {
    carregarDashboard();

    const formulario = document.getElementById('form-busca');
    const campo = document.getElementById('campo-busca') as HTMLInputElement | null;

    if (formulario && campo) {
        formulario.addEventListener('submit', (evento) => {
            // Sem o preventDefault o formulário recarregava a página
            // inteira e a busca "piscava" sem nunca mostrar resultado.
            evento.preventDefault();
            estado.busca = campo.value.trim();
            estado.pagina = 1;
            carregarDashboard();
        });
    }

    const botaoLimpar = document.getElementById('btn-limpar');
    if (botaoLimpar && campo) {
        botaoLimpar.addEventListener('click', () => {
            campo.value = '';
            estado.busca = '';
            estado.categoria = 'todas';
            estado.pagina = 1;
            carregarDashboard();
        });
    }

    const botaoAtualizar = document.getElementById('btn-atualizar');
    if (botaoAtualizar) {
        botaoAtualizar.addEventListener('click', () => {
            alternar('estado-erro', false);
            carregarDashboard();
        });
    }
});
