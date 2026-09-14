// ====================================================================
// TESTE DA LÓGICA DA DASHBOARD
// ====================================================================
// Roda as funções já compiladas (dist/app.js) fora do navegador e
// confere os resultados contra valores escritos À MÃO, conferidos com
// SELECT no banco. Refazer aqui a mesma conta do código não provaria
// nada: se os dois lados errassem igual, o teste passaria feliz.
//
// Como rodar:   npm test
// Antes:        npx tsc     (o teste lê o dist, não o src)
//
// O TESTE 1 usa os dados reais da API e por isso precisa do Apache e do
// MySQL ligados. Se a API não responder, ele é PULADO e os demais
// continuam - nenhum dos outros depende de servidor.

const fs = require('fs');
const path = require('path');
const vm = require('vm');

const RAIZ = path.join(__dirname, '..');
const CAMINHO_APP = path.join(RAIZ, 'dist', 'app.js');
const URL_API = 'http://localhost/pao-de-mel/api.php?tamanho=0';

// ====================================================================
// Carga do código compilado num contexto com DOM de mentira
// ====================================================================
// O dist/app.js é script global com "use strict" no topo. Com eval, as
// funções ficariam presas no escopo do eval e sumiriam - custou uma
// hora de "is not defined" descobrir isso. Num contexto de vm elas
// viram propriedades do objeto global e ficam acessíveis aqui.
function carregarApp() {
    if (!fs.existsSync(CAMINHO_APP)) {
        console.error(`dist/app.js não existe. Rode "npx tsc" antes de "npm test".`);
        process.exit(1);
    }

    const ambiente = {
        console,
        document: {
            addEventListener: () => {},
            getElementById: () => null,
            querySelector: () => null,
            querySelectorAll: () => [],
            createElement: () => ({ innerText: '', innerHTML: '' }),
        },
    };

    vm.createContext(ambiente);
    vm.runInContext(fs.readFileSync(CAMINHO_APP, 'utf8'), ambiente);
    return ambiente;
}

const app = carregarApp();

// ====================================================================
// Placar
// ====================================================================
let falhas = 0;
let pulados = 0;

function conferir(rotulo, obtido, esperado) {
    const passou = String(obtido) === String(esperado);
    if (!passou) falhas++;
    console.log(`${passou ? 'ok  ' : 'FALHA'} | ${rotulo}: obtido=${obtido} esperado=${esperado}`);
}

// ====================================================================
// TESTE 1 - dados REAIS da API x valores conferidos no banco
// ====================================================================
async function teste1() {
    console.log('--- TESTE 1: ranking e recorte sobre os dados reais da API ---');

    let vendas;
    try {
        const resposta = await fetch(URL_API);
        if (!resposta.ok) throw new Error(`status ${resposta.status}`);
        vendas = (await resposta.json()).vendas;
    } catch (erro) {
        console.log(`PULADO | API fora do ar (${erro.message}). Ligue o Apache e o MySQL no XAMPP.`);
        pulados++;
        return;
    }

    // Esperados do SELECT ... GROUP BY produto sobre vw_vendas_detalhadas.
    const ranking = app.ranquearProdutos(vendas, 3);
    conferir('1o produto', ranking[0].produto, 'Coxinha de Frango (un.)');
    conferir('1o faturamento', ranking[0].faturamento.toFixed(2), '4600.00');
    conferir('1o participacao', ranking[0].participacao.toFixed(1), '28.8');
    conferir('1o posicao', ranking[0].posicao, 1);
    conferir('2o produto', ranking[1].produto, 'Pão de Mel Tradicional (un.)');
    conferir('2o faturamento', ranking[1].faturamento.toFixed(2), '2992.50');
    conferir('3o produto', ranking[2].produto, 'Pão de Fermentação Natural (un.)');
    conferir('3o faturamento', ranking[2].faturamento.toFixed(2), '2952.00');
    conferir('corta no limite pedido', ranking.length, 3);

    // O 2o e o 3o estão a R$ 40,50 um do outro: se o sort for instável
    // ou a conta escorregar um centavo, eles trocam de lugar e o teste
    // acusa. É de propósito que o esperado está escrito nesta ordem.

    const recorte = app.recortarUltimaSemana(vendas);
    conferir('janela fim', recorte.fim, '2026-08-23');
    conferir('janela inicio', recorte.inicio, '2026-08-17');
    conferir('faturamento da janela', recorte.faturamento.toFixed(2), '6376.00');
    conferir('unidades da janela', recorte.unidades, 587);
    conferir('vendas da janela', recorte.quantidadeVendas, 22);
    conferir('categorias na janela', recorte.categorias, 4);
    // (6376,00 - 5175,50) / 5175,50 = 23,2%
    conferir('variacao vs semana anterior', recorte.variacao.toFixed(1), '23.2');

    // A prova de que o filter corta: a janela tem que ser MENOR que o
    // total. Enquanto a base inteira cabia em sete dias, os dois números
    // eram iguais e o painel parecia um filtro que não filtra.
    const totalGeral = vendas.reduce(
        (soma, v) => soma + Number(v.quantidade) * Number(v.valor_unitario), 0
    );
    conferir('total geral da base', totalGeral.toFixed(2), '15953.00');
    conferir('a janela e um recorte, nao o todo', recorte.faturamento < totalGeral, true);
}

// ====================================================================
// TESTE 2 - o filter isola mesmo a janela? Dados montados à mão
// ====================================================================
function teste2() {
    console.log('\n--- TESTE 2: filter isolando a janela de 7 dias ---');

    const vendas = [
        // Semana recente (10/03 a 16/03): 10x5,00 + 4x25,00 = 150,00
        { venda_id: 1, produto: 'Pão A',  categoria: 'Pão',  quantidade: 10, valor_unitario: '5.00',  data_venda: '2026-03-16' },
        { venda_id: 2, produto: 'Bolo B', categoria: 'Bolo', quantidade: 4,  valor_unitario: '25.00', data_venda: '2026-03-12' },
        // Semana anterior (03/03 a 09/03): 2x50,00 = 100,00
        { venda_id: 3, produto: 'Bolo B', categoria: 'Bolo', quantidade: 2,  valor_unitario: '50.00', data_venda: '2026-03-05' },
        // Velha demais: tem que ficar de fora das DUAS janelas.
        { venda_id: 4, produto: 'Antigo', categoria: 'Doce', quantidade: 99, valor_unitario: '99.00', data_venda: '2026-01-01' },
        // Data podre, como o banco devolve quando o campo é nulo.
        { venda_id: 5, produto: 'Podre',  categoria: 'Doce', quantidade: 7,  valor_unitario: '10.00', data_venda: '0000-00-00' },
    ];

    const recorte = app.recortarUltimaSemana(vendas);
    conferir('fim = venda mais recente', recorte.fim, '2026-03-16');
    conferir('inicio = fim - 6 dias', recorte.inicio, '2026-03-10');
    conferir('faturamento da janela', recorte.faturamento.toFixed(2), '150.00');
    conferir('unidades da janela', recorte.unidades, 14);
    conferir('vendas da janela', recorte.quantidadeVendas, 2);
    conferir('categorias distintas', recorte.categorias, 2);
    // (150,00 - 100,00) / 100,00 = +50%
    conferir('variacao vs anterior', recorte.variacao.toFixed(1), '50.0');
    // Se a venda de janeiro entrasse, o faturamento seria 9.951,00.
    conferir('venda velha ficou de fora', recorte.faturamento < 9951, true);
}

// ====================================================================
// TESTE 3 - cenários de exceção
// ====================================================================
function teste3() {
    console.log('\n--- TESTE 3: cenarios de excecao ---');

    conferir('base vazia nao quebra o recorte', app.recortarUltimaSemana([]), null);
    conferir('base vazia nao quebra o ranking', app.ranquearProdutos([], 3).length, 0);

    const soDataPodre = [
        { venda_id: 1, produto: 'X', categoria: 'Y', quantidade: 1, valor_unitario: '1.00', data_venda: '0000-00-00' },
    ];
    conferir('so data invalida vira null', app.recortarUltimaSemana(soDataPodre), null);

    // Banco sujo: texto onde devia haver número, e quantidade nula.
    const sujo = [
        { venda_id: 9, produto: 'Sujo', categoria: 'Doce', quantidade: null, valor_unitario: 'abc', data_venda: '2026-03-16' },
    ];
    conferir('valor invalido nao vira NaN', app.ranquearProdutos(sujo, 3)[0].faturamento, 0);
    conferir('participacao 0/0 nao vira NaN', app.ranquearProdutos(sujo, 3)[0].participacao, 0);
    conferir('recorte de base suja soma 0', app.recortarUltimaSemana(sujo).faturamento, 0);
    conferir('sem anterior, variacao e null', app.recortarUltimaSemana(sujo).variacao, null);
}

// ====================================================================
// TESTE 4 - o map entrega texto pronto para a tela
// ====================================================================
function teste4() {
    console.log('\n--- TESTE 4: map preparando as linhas da tabela ---');

    const vendas = [
        { venda_id: 1, produto: 'Pão A',  categoria: 'Pão',  quantidade: 10, valor_unitario: '5.00',  data_venda: '2026-03-16' },
        { venda_id: 2, produto: 'Bolo B', categoria: 'Bolo', quantidade: 4,  valor_unitario: '25.00', data_venda: '2026-03-12' },
    ];

    const linhas = app.prepararLinhas(vendas);

    // O pt-BR separa o R$ do número com espaço NÃO separável (U+00A0),
    // e não com espaço comum. Comparar sem normalizar falha por um
    // caractere invisível - erro chato de enxergar no terminal.
    const normalizar = (texto) => texto.replace(/ /g, ' ');

    conferir('quantidade de linhas', linhas.length, 2);
    conferir('identificador', linhas[0].identificador, '#1');
    conferir('quantidade virou texto', linhas[0].quantidade, '10');
    conferir('subtotal em moeda local', normalizar(linhas[0].subtotal), 'R$ 50,00');
    conferir('valor unitario em moeda local', normalizar(linhas[1].valorUnitario), 'R$ 25,00');
    conferir('subtotal da 2a linha', normalizar(linhas[1].subtotal), 'R$ 100,00');
}

// ====================================================================
async function main() {
    await teste1();
    teste2();
    teste3();
    teste4();

    console.log('');
    if (falhas > 0) {
        console.log(`=== ${falhas} TESTE(S) FALHARAM ===`);
        process.exit(1);
    }

    console.log(pulados > 0
        ? `=== TUDO PASSOU (${pulados} bloco pulado por falta de servidor) ===`
        : '=== TUDO PASSOU ===');
}

main();
