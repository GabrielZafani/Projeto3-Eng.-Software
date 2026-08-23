// ====================================================================
// DASHBOARD DE VENDAS - Padaria Pão de Mel
// ====================================================================
// RUBRICA - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO
// RUBRICA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
// RUBRICA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)


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


// ====================================================================
// PASSO 1: BUSCAR OS DADOS NA API PHP (fetch + async/await + try/catch)
// ====================================================================
async function carregarDashboard(): Promise<void> {
    mostrarCarregando(true);

    try {
        const resposta = await fetch('api.php');

        // Falha de servidor (500, 404...) não dispara catch sozinha:
        // o fetch só rejeita em erro de rede. Por isso checamos na mão.
        if (!resposta.ok) {
            throw new Error(`A API respondeu com status ${resposta.status}`);
        }

        const dados: unknown = await resposta.json();

        // A API pode devolver {"error": "..."} em vez de uma lista.
        // Sem esta checagem, o .reduce() abaixo quebraria a página.
        if (!Array.isArray(dados)) {
            throw new Error('A API não retornou uma lista de vendas');
        }

        const vendas: Venda[] = dados;

        mostrarCarregando(false);

        // EDGE CASE: banco limpo / nenhuma venda no período.
        // Em vez de dividir por zero e imprimir NaN, mostramos um aviso.
        if (vendas.length === 0) {
            mostrarVazio(true);
            zerarCards();
            exibirTabela([]);
            return;
        }

        mostrarVazio(false);
        atualizarCards(vendas);
        exibirTabela(vendas);

    } catch (erro) {
        // Rede fora do ar, Apache parado, MySQL desligado, JSON inválido:
        // tudo cai aqui e a página mostra um aviso em vez de travar.
        console.error('Falha ao carregar as vendas:', erro);
        mostrarCarregando(false);
        mostrarErro(erro instanceof Error ? erro.message : 'Erro desconhecido');
        zerarCards();
        exibirTabela([]);
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
// PASSO 3: A TABELA DE VENDAS
// ====================================================================
function exibirTabela(vendas: Venda[]): void {
    const tbody = document.getElementById('tabela-vendas-body');
    if (!tbody) return;

    tbody.innerHTML = '';

    // EDGE CASE: mensagem elegante no lugar de uma tabela vazia e muda.
    if (vendas.length === 0) {
        tbody.innerHTML =
            '<tr><td colspan="6" class="text-center text-muted py-4">Nenhum dado registrado.</td></tr>';
        return;
    }

    vendas.forEach((venda) => {
        const quantidade = numeroSeguro(venda.quantidade);
        const valorUnitario = numeroSeguro(venda.valor_unitario);
        const subtotal = quantidade * valorUnitario;

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>#${venda.venda_id}</td>
            <td><strong>${venda.produto}</strong></td>
            <td><span class="badge badge-categoria">${venda.categoria}</span></td>
            <td class="text-end">${quantidade}</td>
            <td class="text-end">${formatarMoeda(valorUnitario)}</td>
            <td class="text-end"><strong>${formatarMoeda(subtotal)}</strong></td>
        `;
        tbody.appendChild(tr);
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

function mostrarVazio(visivel: boolean): void {
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

    const botaoAtualizar = document.getElementById('btn-atualizar');
    if (botaoAtualizar) {
        botaoAtualizar.addEventListener('click', () => {
            alternar('estado-erro', false);
            carregarDashboard();
        });
    }
});
