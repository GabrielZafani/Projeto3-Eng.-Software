// ====================================================================
// TESTE DE TELA - a dashboard clicada de verdade
// ====================================================================
// Abre a dashboard num navegador sem janela e CLICA: vira página, filtra
// por categoria, digita na busca. Confere o que ficou escrito na tela.
//
// Como rodar:   npm run test:tela
// Precisa de:   Apache e MySQL ligados, e o Microsoft Edge instalado.
//
// Por que este teste existe, além do npm test: o outro roda as funções
// isoladas e nunca toca no DOM. Este pegou um alarme falso que só
// aparecia DEPOIS de clicar num filtro - o console acusava "Divergência"
// porque a conferência comparava o total do banco com um subconjunto
// filtrado. Nenhuma das 39 conferências do outro teste via isso.
//
// O navegador é dirigido pelo protocolo de depuração (CDP), falando
// WebSocket direto. Sem biblioteca: o Node já traz WebSocket embutido.

const { spawn } = require('child_process');
const fs = require('fs');

const URL_BASE = 'http://localhost/pao-de-mel/dashboard.php';
const PORTA = 9222;

const CAMINHOS_EDGE = [
    'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Microsoft/Edge/Application/msedge.exe',
    'C:/Program Files/Google/Chrome/Application/chrome.exe',
    'C:/Program Files (x86)/Google/Chrome/Application/chrome.exe',
];

let proximoId = 0;
const pendentes = new Map();
const mensagensConsole = [];
const errosJs = [];
let navegador = null;

const esperar = (ms) => new Promise((r) => setTimeout(r, ms));

// --------------------------------------------------------------------
// Navegador: sobe se ainda não estiver de pé
// --------------------------------------------------------------------
async function portaResponde() {
    try {
        const r = await fetch(`http://127.0.0.1:${PORTA}/json/version`);
        return r.ok;
    } catch {
        return false;
    }
}

async function abrirNavegador() {
    if (await portaResponde()) {
        console.log(`(navegador já estava aberto na porta ${PORTA})`);
        return false;
    }

    const executavel = CAMINHOS_EDGE.find((c) => fs.existsSync(c));
    if (!executavel) {
        console.error('Não encontrei o Edge nem o Chrome. Caminhos procurados:');
        CAMINHOS_EDGE.forEach((c) => console.error('  ' + c));
        process.exit(1);
    }

    // Perfil próprio e descartável: sem ele o navegador reaproveita a
    // sessão do usuário e pode devolver JavaScript velho do cache -
    // custou um diagnóstico errado achar que uma correção não funcionou,
    // quando o arquivo novo estava certo e a página é que era antiga.
    const perfil = require('path').join(require('os').tmpdir(), 'pdm-teste-tela');
    fs.rmSync(perfil, { recursive: true, force: true });

    navegador = spawn(executavel, [
        '--headless=new',
        '--disable-gpu',
        '--hide-scrollbars',
        `--remote-debugging-port=${PORTA}`,
        `--user-data-dir=${perfil}`,
        '--window-size=1280,1200',
        'about:blank',
    ], { stdio: 'ignore', detached: false });

    for (let tentativa = 0; tentativa < 40; tentativa++) {
        await esperar(250);
        if (await portaResponde()) return true;
    }

    console.error('O navegador subiu mas não abriu a porta de depuração.');
    process.exit(1);
}

function fecharNavegador() {
    if (navegador) {
        try { process.kill(navegador.pid); } catch { /* já morreu */ }
    }
}

// --------------------------------------------------------------------
// Conversa com o navegador
// --------------------------------------------------------------------
function enviar(ws, method, params = {}) {
    const id = ++proximoId;
    ws.send(JSON.stringify({ id, method, params }));
    return new Promise((resolve, reject) => {
        pendentes.set(id, { resolve, reject });
        setTimeout(() => {
            if (pendentes.has(id)) {
                pendentes.delete(id);
                reject(new Error(`timeout em ${method}`));
            }
        }, 20000);
    });
}

async function avaliar(ws, expressao) {
    const r = await enviar(ws, 'Runtime.evaluate', {
        expression: expressao,
        returnByValue: true,
        awaitPromise: true,
    });
    if (r.exceptionDetails) {
        throw new Error('erro na página: '
            + (r.exceptionDetails.exception?.description || r.exceptionDetails.text));
    }
    return r.result.value;
}

// Texto visível, com o espaço não separável do pt-BR virando espaço
// comum. Sem isso "R$ 50,00" nunca bate com "R$ 50,00" - a diferença é
// um caractere invisível (U+00A0) que o toLocaleString insere.
function textoDe(ws, seletor) {
    return avaliar(ws, `document.querySelector(${JSON.stringify(seletor)}).innerText.replace(/\\u00a0/g, ' ')`);
}

// A dashboard busca os dados por fetch: o HTML chega antes dos números.
// Esperar tempo fixo daria teste que passa numa máquina e falha na
// outra; aqui esperamos o sinal de que ela terminou.
async function esperarCarregar(ws) {
    for (let tentativa = 0; tentativa < 60; tentativa++) {
        const pronto = await avaliar(ws, `
            (() => {
                const card = document.getElementById('card-faturamento');
                const linhas = document.querySelectorAll('#tabela-vendas-body tr').length;
                const carregando = document.getElementById('estado-carregando');
                const visivel = carregando && !carregando.classList.contains('d-none');
                return !!card && card.innerText.trim() !== '—' && linhas > 0 && !visivel;
            })()
        `);
        if (pronto) return true;
        await esperar(250);
    }
    return false;
}

let falhas = 0;

function conferir(rotulo, obtido, esperado) {
    const passou = String(obtido) === String(esperado);
    if (!passou) falhas++;
    console.log(`${passou ? 'ok  ' : 'FALHA'} | ${rotulo}: obtido=${obtido} esperado=${esperado}`);
}

// --------------------------------------------------------------------
async function main() {
    const subiAqui = await abrirNavegador();

    const alvos = await (await fetch(`http://127.0.0.1:${PORTA}/json/list`)).json();
    const pagina = alvos.find((a) => a.type === 'page');
    const ws = new WebSocket(pagina.webSocketDebuggerUrl);

    await new Promise((resolve, reject) => {
        ws.onopen = resolve;
        ws.onerror = () => reject(new Error('não consegui falar com o navegador'));
    });

    ws.onmessage = (evento) => {
        const msg = JSON.parse(evento.data);
        if (msg.id && pendentes.has(msg.id)) {
            const { resolve, reject } = pendentes.get(msg.id);
            pendentes.delete(msg.id);
            msg.error ? reject(new Error(msg.error.message)) : resolve(msg.result);
            return;
        }
        if (msg.method === 'Runtime.consoleAPICalled') {
            const texto = (msg.params.args || [])
                .map((a) => a.value ?? a.description ?? '').join(' ');
            mensagensConsole.push(`[${msg.params.type}] ${texto}`);
        }
        if (msg.method === 'Runtime.exceptionThrown') {
            errosJs.push(msg.params.exceptionDetails.exception?.description
                || msg.params.exceptionDetails.text);
        }
    };

    await enviar(ws, 'Runtime.enable');
    await enviar(ws, 'Page.enable');

    // ---------------------------------------------------------------
    console.log('--- 1. CARGA INICIAL ---');
    await enviar(ws, 'Page.navigate', { url: URL_BASE });
    conferir('a dashboard terminou de carregar', await esperarCarregar(ws), true);

    conferir('card faturamento', await textoDe(ws, '#card-faturamento'), 'R$ 15.953,00');
    conferir('card unidades', await textoDe(ws, '#card-unidades'), '1462 un.');
    conferir('linhas na tabela',
        await avaliar(ws, `document.querySelectorAll('#tabela-vendas-body tr').length`), 10);
    conferir('resumo', await textoDe(ws, '#resumo-resultado'),
        '66 vendas encontradas · página 1 de 7');

    console.log('\n--- 2. PAINEL DE DESTAQUES ---');
    conferir('1o do podio', await textoDe(ws, '#lista-ranking .ranking-item .ranking-nome'),
        'Coxinha de Frango (un.)');
    conferir('valor do 1o', await textoDe(ws, '#lista-ranking .ranking-item .ranking-valor'),
        'R$ 4.600,00');
    conferir('o podio tem 3 linhas',
        await avaliar(ws, `document.querySelectorAll('#lista-ranking .ranking-item').length`), 3);
    conferir('faturamento da semana', await textoDe(ws, '#periodo-faturamento'), 'R$ 6.376,00');
    conferir('intervalo da semana', await textoDe(ws, '#periodo-intervalo'), '17/08 a 23/08');
    conferir('variacao', await textoDe(ws, '#periodo-variacao'),
        '+23.2% em relação à semana anterior');

    // ---------------------------------------------------------------
    console.log('\n--- 3. CLICANDO NA PAGINA 2 (os cards NAO podem mudar) ---');
    await avaliar(ws, `
        [...document.querySelectorAll('#paginacao .page-link')]
            .find(b => b.innerText.trim() === '2').click()
    `);
    await esperar(1200);
    await esperarCarregar(ws);

    // Esta é a prova do reduce: ele soma o filtro inteiro, não a página.
    conferir('cards seguem iguais na pagina 2',
        await textoDe(ws, '#card-faturamento'), 'R$ 15.953,00');
    conferir('o resumo mudou de pagina', await textoDe(ws, '#resumo-resultado'),
        '66 vendas encontradas · página 2 de 7');

    // ---------------------------------------------------------------
    console.log('\n--- 4. CLICANDO NO CHIP "Bolo" ---');
    // textContent, e não innerText: o CSS põe o chip em maiúsculas, e o
    // innerText devolve "BOLO (12)" - o texto renderizado, não o escrito.
    await avaliar(ws, `
        [...document.querySelectorAll('#chips-categoria .chip')]
            .find(c => c.textContent.trim().startsWith('Bolo')).click()
    `);
    await esperar(1200);
    await esperarCarregar(ws);

    conferir('faturamento filtrado por Bolo',
        await textoDe(ws, '#card-faturamento'), 'R$ 1.850,00');
    conferir('o filtro voltou para a pagina 1', await textoDe(ws, '#resumo-resultado'),
        '12 vendas encontradas · página 1 de 2');
    conferir('o podio so tem os produtos de Bolo',
        await avaliar(ws, `document.querySelectorAll('#lista-ranking .ranking-item').length`), 2);

    // ---------------------------------------------------------------
    console.log('\n--- 5. BUSCANDO "coxinha" ---');
    await avaliar(ws, `
        (() => {
            [...document.querySelectorAll('#chips-categoria .chip')]
                .find(c => c.textContent.trim() === 'Todas').click();
            return true;
        })()
    `);
    await esperar(1000);
    await esperarCarregar(ws);

    await avaliar(ws, `
        (() => {
            document.getElementById('campo-busca').value = 'coxinha';
            document.getElementById('form-busca')
                .dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            return true;
        })()
    `);
    await esperar(1200);
    await esperarCarregar(ws);

    conferir('faturamento da busca', await textoDe(ws, '#card-faturamento'), 'R$ 4.600,00');
    conferir('resultado da busca', await textoDe(ws, '#resumo-resultado'),
        '12 vendas encontradas · página 1 de 2');

    // ---------------------------------------------------------------
    console.log('\n--- 6. BUSCA SEM RESULTADO (edge case) ---');
    await avaliar(ws, `
        (() => {
            document.getElementById('campo-busca').value = 'zzzz';
            document.getElementById('form-busca')
                .dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
            return true;
        })()
    `);
    await esperar(1500);

    conferir('aviso de vazio aparece',
        await avaliar(ws, `!document.getElementById('estado-vazio').classList.contains('d-none')`), true);
    conferir('texto do aviso', await textoDe(ws, '#estado-vazio'),
        'Nenhuma venda com esse filtro. Tente outra categoria ou limpe a busca.');
    conferir('cards zerados', await textoDe(ws, '#card-faturamento'), 'R$ 0,00');
    conferir('tabela avisa em vez de ficar muda',
        await textoDe(ws, '#tabela-vendas-body tr td'), 'Nenhum dado registrado.');
    conferir('podio tambem zera',
        await textoDe(ws, '#lista-ranking .ranking-vazio'), 'Nenhum dado registrado.');
    conferir('nenhum NaN na tela inteira',
        await avaliar(ws, `document.body.innerText.includes('NaN')`), false);

    // ---------------------------------------------------------------
    console.log('\n--- 7. CONSOLE DO NAVEGADOR ---');
    conferir('nenhum erro de JavaScript', errosJs.length, 0);
    const ruins = mensagensConsole.filter((m) => m.startsWith('[error]') || m.startsWith('[warning]'));
    conferir('nenhum error/warning no console', ruins.length, 0);
    if (ruins.length > 0) {
        ruins.forEach((m) => console.log('   ' + m.slice(0, 140)));
    }
    console.log('a página escreveu:');
    [...new Set(mensagensConsole)].slice(0, 4).forEach((m) => console.log('   ' + m.slice(0, 120)));

    ws.close();
    if (subiAqui) fecharNavegador();

    console.log('');
    console.log(falhas === 0 ? '=== TUDO PASSOU NA TELA ===' : `=== ${falhas} FALHA(S) NA TELA ===`);
    process.exit(falhas === 0 ? 0 : 1);
}

main().catch((erro) => {
    console.error('ERRO:', erro.message);
    console.error('Confira se o Apache e o MySQL estão ligados no XAMPP.');
    fecharNavegador();
    process.exit(1);
});
