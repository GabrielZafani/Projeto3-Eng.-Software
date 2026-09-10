"use strict";
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
const estado = {
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
function numeroSeguro(valor) {
    const convertido = Number(valor);
    return Number.isFinite(convertido) ? convertido : 0;
}
function formatarMoeda(valor) {
    return numeroSeguro(valor).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    });
}
function escreverTexto(id, texto) {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.innerText = texto;
    }
}
// Texto vindo do banco nunca entra por innerHTML sem passar por aqui.
// Um produto cadastrado como <img onerror=...> executaria script na
// tela de quem abrisse a dashboard.
function escapar(texto) {
    const area = document.createElement('div');
    area.innerText = texto;
    return area.innerHTML;
}
// ====================================================================
// PASSO 1: BUSCAR OS DADOS NA API PHP (fetch + async/await + try/catch)
// ====================================================================
// Monta a query string que a api.php repassa para o CALL.
function montarUrl(pagina, tamanho) {
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
async function carregarDashboard() {
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
        const dadosPagina = await respostaPagina.json();
        const dadosTudo = await respostaTudo.json();
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
            exibirTabela([]);
            desenharPaginacao(pagina.meta);
            escreverResumo(pagina.meta);
            return;
        }
        mostrarVazio(false, tudo.meta);
        atualizarCards(tudo.vendas);
        exibirTabela(pagina.vendas);
        desenharPaginacao(pagina.meta);
        escreverResumo(pagina.meta);
    }
    catch (erro) {
        // Rede fora do ar, Apache parado, MySQL desligado, JSON inválido:
        // tudo cai aqui e a página mostra um aviso em vez de travar.
        console.error('Falha ao carregar as vendas:', erro);
        mostrarCarregando(false);
        mostrarErro(erro instanceof Error ? erro.message : 'Erro desconhecido');
        zerarCards();
        exibirTabela([]);
        limparPaginacao();
    }
    finally {
        carregando = false;
        travarControles(false);
    }
}
// Confere o formato antes de confiar. O que vem pela rede não tem tipo:
// o TypeScript só garante o que foi checado em tempo de execução.
function validarResposta(dados) {
    if (dados === null || typeof dados !== 'object') {
        throw new Error('A API não retornou um objeto de resposta');
    }
    const corpo = dados;
    if (!Array.isArray(corpo.vendas)) {
        throw new Error('A API não retornou uma lista de vendas');
    }
    if (corpo.meta === null || typeof corpo.meta !== 'object') {
        throw new Error('A API não informou os dados de paginação');
    }
    return corpo;
}
// A função do banco (fn_faturamento_periodo) calcula o mesmo faturamento
// por outro caminho. Se os dois números divergirem, alguém mexeu na
// regra de um lado só - e o console avisa antes de a nota sair errada.
function conferirComBanco(resposta) {
    const doBanco = resposta.meta.faturamento_conferencia;
    if (doBanco === undefined) {
        return;
    }
    const doReduce = calcularMetricas(resposta.vendas).faturamentoTotal;
    const diferenca = Math.abs(numeroSeguro(doBanco) - doReduce);
    if (diferenca > 0.01) {
        console.warn(`Divergência: reduce=${doReduce.toFixed(2)} x ` +
            `fn_faturamento_periodo=${numeroSeguro(doBanco).toFixed(2)}`);
    }
    else {
        console.info(`Conferido: o reduce (${formatarMoeda(doReduce)}) bate com a ` +
            `função fn_faturamento_periodo do banco.`);
    }
}
// ====================================================================
// PASSO 2: OS GRANDES NÚMEROS - TUDO COM .reduce()
// ====================================================================
// RUBRICA: extrair as métricas globais com reduce() diretamente do
// array bruto enviado pelo PHP (quantidade × valor unitário).
function calcularMetricas(vendas) {
    // --- 1. FATURAMENTO TOTAL ---
    // Acumula, linha a linha, quantidade × valor unitário.
    const faturamentoTotal = vendas.reduce((acumulador, venda) => {
        const quantidade = numeroSeguro(venda.quantidade);
        const valorUnitario = numeroSeguro(venda.valor_unitario);
        return acumulador + (quantidade * valorUnitario);
    }, 0);
    // --- 2. UNIDADES VENDIDAS ---
    const unidadesVendidas = vendas.reduce((acumulador, venda) => {
        return acumulador + numeroSeguro(venda.quantidade);
    }, 0);
    // --- 3. TICKET MÉDIO POR VENDA ---
    // vendas.length nunca é 0 aqui (checado antes), mas o guarda-chuva
    // fica: divisão por zero produziria Infinity na tela.
    const ticketMedio = vendas.length > 0
        ? faturamentoTotal / vendas.length
        : 0;
    // --- 4. PRODUTO CAMPEÃO DE FATURAMENTO ---
    // Aqui o reduce acumula um objeto: soma o faturamento por produto.
    const porProduto = vendas.reduce((acumulador, venda) => {
        const nome = venda.produto;
        const total = numeroSeguro(venda.quantidade) * numeroSeguro(venda.valor_unitario);
        acumulador[nome] = (acumulador[nome] ?? 0) + total;
        return acumulador;
    }, {});
    // E este reduce percorre o objeto acima achando o maior.
    const campeaoVendas = Object.keys(porProduto).reduce((campeao, nome) => {
        return porProduto[nome] > (porProduto[campeao] ?? 0) ? nome : campeao;
    }, Object.keys(porProduto)[0] ?? '—');
    return { faturamentoTotal, unidadesVendidas, ticketMedio, campeaoVendas };
}
function atualizarCards(vendas) {
    const metricas = calcularMetricas(vendas);
    escreverTexto('card-faturamento', formatarMoeda(metricas.faturamentoTotal));
    escreverTexto('card-unidades', `${metricas.unidadesVendidas} un.`);
    escreverTexto('card-ticket', formatarMoeda(metricas.ticketMedio));
    escreverTexto('card-campeao', metricas.campeaoVendas);
}
// EDGE CASE: sem dados, os cards mostram zero de verdade - nunca "NaN".
function zerarCards() {
    escreverTexto('card-faturamento', formatarMoeda(0));
    escreverTexto('card-unidades', '0 un.');
    escreverTexto('card-ticket', formatarMoeda(0));
    escreverTexto('card-campeao', '—');
}
// ====================================================================
// PASSO 3: A TABELA DE VENDAS
// ====================================================================
function exibirTabela(vendas) {
    const tbody = document.getElementById('tabela-vendas-body');
    if (!tbody)
        return;
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
            <td>#${numeroSeguro(venda.venda_id)}</td>
            <td><strong>${escapar(venda.produto)}</strong></td>
            <td><span class="badge badge-categoria">${escapar(venda.categoria)}</span></td>
            <td class="text-end">${quantidade}</td>
            <td class="text-end">${formatarMoeda(valorUnitario)}</td>
            <td class="text-end"><strong>${formatarMoeda(subtotal)}</strong></td>
        `;
        tbody.appendChild(tr);
    });
}
// ====================================================================
// PASSO 4: BUSCA, FILTRO E PAGINAÇÃO (o que a procedure resolve)
// ====================================================================
// Os botões de categoria saem do CALL sp_vendas_categorias(): a tela
// não tem nenhuma categoria escrita à mão no código.
function desenharChips(meta) {
    const area = document.getElementById('chips-categoria');
    if (!area)
        return;
    const categorias = Array.isArray(meta.categorias) ? meta.categorias : [];
    area.innerHTML = '';
    area.appendChild(criarChip('Todas', 'todas'));
    categorias.forEach((item) => {
        const rotulo = `${item.categoria} (${numeroSeguro(item.vendas)})`;
        area.appendChild(criarChip(rotulo, item.categoria));
    });
}
function criarChip(rotulo, valor) {
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
function desenharPaginacao(meta) {
    const lista = document.querySelector('#paginacao .pagination');
    if (!lista)
        return;
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
function itemPaginacao(rotulo, destino, desativado, ativo) {
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
function limparPaginacao() {
    const lista = document.querySelector('#paginacao .pagination');
    if (lista) {
        lista.innerHTML = '';
    }
    escreverTexto('resumo-resultado', '—');
}
function escreverResumo(meta) {
    const total = numeroSeguro(meta.total_linhas);
    if (total === 0) {
        escreverTexto('resumo-resultado', 'Nenhuma venda encontrada para este filtro.');
        return;
    }
    const plural = total === 1 ? 'venda encontrada' : 'vendas encontradas';
    const paginas = Math.max(numeroSeguro(meta.total_paginas), 1);
    escreverTexto('resumo-resultado', `${total} ${plural} · página ${numeroSeguro(meta.pagina)} de ${paginas}`);
}
// Enquanto o CALL não volta, os controles ficam travados. É o que evita
// duas buscas concorrentes pintarem a tabela na ordem errada.
function travarControles(travado) {
    const campo = document.getElementById('campo-busca');
    if (campo) {
        campo.disabled = travado;
    }
    document.querySelectorAll('#chips-categoria .chip, #paginacao .page-link')
        .forEach((elemento) => {
        elemento.disabled = travado;
    });
}
// ====================================================================
// ESTADOS DA TELA (carregando / vazio / erro)
// ====================================================================
function alternar(id, visivel) {
    const elemento = document.getElementById(id);
    if (elemento) {
        elemento.classList.toggle('d-none', !visivel);
    }
}
function mostrarCarregando(visivel) {
    alternar('estado-carregando', visivel);
}
// O aviso de vazio muda de texto conforme o motivo: banco sem venda
// nenhuma é um problema; filtro que não achou nada é uso normal.
function mostrarVazio(visivel, meta) {
    if (visivel && meta) {
        const temFiltro = meta.busca !== '' || meta.categoria !== 'todas';
        escreverTexto('estado-vazio', temFiltro
            ? 'Nenhuma venda com esse filtro. Tente outra categoria ou limpe a busca.'
            : 'Nenhum dado registrado. Assim que houver vendas no banco, os números aparecem aqui.');
    }
    alternar('estado-vazio', visivel);
}
function mostrarErro(mensagem) {
    escreverTexto('estado-erro-msg', mensagem);
    alternar('estado-erro', true);
}
// ====================================================================
// INÍCIO: dispara quando o HTML termina de carregar
// ====================================================================
document.addEventListener('DOMContentLoaded', () => {
    carregarDashboard();
    const formulario = document.getElementById('form-busca');
    const campo = document.getElementById('campo-busca');
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
