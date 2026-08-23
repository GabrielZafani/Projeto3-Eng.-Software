# Guia de Rubricas — Pão de Mel

> **Como usar:** os links abaixo abrem o arquivo direto na linha certa.
> No VS Code, clique com `Ctrl` pressionado (ou abra o preview do markdown
> com `Ctrl+Shift+V` e clique normalmente).

---

## DESENVOLVIMENTO WEB MODERNA

### Layout dinâmico com PHP
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CRIAR UM LAYOUT MINIMAMENTE AGRADÁVEL E DINÂMICO COM PHP
```
- [includes/header.php:33](includes/header.php#L33) — menu com o item ativo calculado via PHP
- [includes/footer.php:24](includes/footer.php#L24) — ano do rodapé com `date('Y')`

**O que é dinâmico aqui:** nada no layout é digitado à mão duas vezes. O menu
descobre sozinho em qual página você está e destaca o link correspondente, e o
ano do rodapé se atualiza sozinho na virada do ano.

---

### Template com PHP
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP
```
- [includes/header.php](includes/header.php) — topo do template (cabeçalho, CSS e navbar)
- [includes/footer.php](includes/footer.php) — rodapé e scripts
- [index.php:2](index.php#L2), [receitas.php:2](receitas.php#L2), [receita-detalhe.php:2](receita-detalhe.php#L2), [contato.php:1](contato.php#L1), [dashboard.php:2](dashboard.php#L2) — `require` do header

**Como funciona:** as 5 páginas do site incluem o mesmo header e o mesmo footer.
O HTML que se repete existe uma vez só: mudar o menu em um lugar muda em todas
as páginas de uma vez.

---

### Bootstrap (3+ componentes)
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE BOOTSTRAP E PELO MENOS 3 COMPONENTES
```
- [includes/header.php:33](includes/header.php#L33) — **Navbar** (com Collapse no botão de menu do celular)
- [index.php:46](index.php#L46) — **Card** e **Badge** nos destaques
- [receitas.php:41](receitas.php#L41) — **Card** e **Badge** na listagem
- [receita-detalhe.php:26](receita-detalhe.php#L26) — **Badge** da categoria
- [contato.php:20](contato.php#L20) — **Form** (`form-control`, `form-label`) e **Button**
- [dashboard.php:23](dashboard.php#L23) — **Spinner** (carregando)
- [dashboard.php:30](dashboard.php#L30) — **Alert** (erro de conexão)
- [dashboard.php:52](dashboard.php#L52) — **Card** das métricas
- [dashboard.php:90](dashboard.php#L90) — **Table** responsiva das vendas

**Contagem:** são 8 componentes distintos (Navbar, Card, Badge, Form, Button,
Spinner, Alert e Table), além do Grid usado em todas as páginas. A rubrica pede
no mínimo 3.

---

### Conexão com Banco de Dados
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
```
- [includes/conexao.php:17](includes/conexao.php#L17) — conexão PDO única do sistema
- [includes/dados-receitas.php:9](includes/dados-receitas.php#L9) — `require` da conexão
- [includes/funcoes.php:235](includes/funcoes.php#L235) — `buscarReceitasDoBanco(PDO $pdo)`
- [api.php:19](api.php#L19) — a API usa a mesma conexão

**Por que uma conexão só:** o site e a API usam o mesmo `conexao.php`, então as
credenciais (host, usuário, senha) ficam num lugar único. Para trocar de servidor
— localhost, VM da faculdade, hospedagem — basta editar as 4 primeiras linhas
daquele arquivo.

---

### Dados recuperados do banco e demonstrados na tela
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS RECUPERADOS DO BANCO E DEMONSTRADOS NA TELA
```
- [includes/funcoes.php:240](includes/funcoes.php#L240) — `SELECT` das receitas com `INNER JOIN` nas categorias
- [includes/funcoes.php:262](includes/funcoes.php#L262) — `SELECT` dos ingredientes (tabela N:N)
- [includes/dados-receitas.php:14](includes/dados-receitas.php#L14) — o array `$receitas` sendo montado
- [receitas.php:39](receitas.php#L39) — os dados aparecendo na tela

**O caminho do dado:** banco → `buscarReceitasDoBanco()` → array `$receitas` →
`foreach` na página → HTML. Nenhuma receita está escrita no código: apagar uma
linha no DBeaver a faz sumir do site no F5 seguinte.

---

### Comandos PHP (IF, WHILE, FOREACH)
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP
```
- [index.php:34](index.php#L34) — `IF` (array vazio) e [index.php:41](index.php#L41) — `FOREACH` (destaques)
- [receitas.php:36](receitas.php#L36) — `IF` (sem resultados) e [receitas.php:39](receitas.php#L39) — `FOREACH` (cards filtrados)
- [receita-detalhe.php:24](receita-detalhe.php#L24) — `IF` (receita existe) e [receita-detalhe.php:41](receita-detalhe.php#L41) — `FOREACH` (ingredientes)
- [includes/funcoes.php:246](includes/funcoes.php#L246) e [includes/funcoes.php:268](includes/funcoes.php#L268) — `WHILE` percorrendo o resultado das queries

**Onde cada um faz sentido:** o `WHILE` lê linha a linha o que o banco devolve
(não se sabe quantas virão); o `FOREACH` percorre um array já montado; e o `IF`
protege a tela quando não há nada para mostrar.

---
---

## TECH FORGE

### Armazenamento Estruturado com Arrays
```
RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS
```
- [includes/dados-receitas.php:14](includes/dados-receitas.php#L14) — onde `$receitas` nasce
- [includes/funcoes.php:248](includes/funcoes.php#L248) — a estrutura de cada receita sendo montada
- [index.php:3](index.php#L3), [receitas.php:3](receitas.php#L3), [receita-detalhe.php:3](receita-detalhe.php#L3) — páginas que leem o array

**A estrutura:** `$receitas` é um array de arrays. Cada receita carrega `id`,
`nome`, `categoria`, `tempo_preparo` e mais dois arrays dentro dela —
`ingredientes` e `modo_preparo` (a lista de passos).

---

### Modularização com Funções de Processamento
```
RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO
```
- [includes/funcoes.php:15](includes/funcoes.php#L15) — `formatarTempoPreparo()` (240 → "4h")
- [includes/funcoes.php:38](includes/funcoes.php#L38) — `classeNavAtiva()` (destaque do menu)
- [includes/funcoes.php:53](includes/funcoes.php#L53) — `obterDestaques()`
- [includes/funcoes.php:235](includes/funcoes.php#L235) — `buscarReceitasDoBanco()`

**Por que separar:** as páginas só exibem; quem processa é o `funcoes.php`. Foi
isso que permitiu trocar o mock de teste pelo banco de dados real sem reescrever
nenhuma página.

---

### Fluxo de Dados (Parâmetros e Retorno)
```
RUBRICA TECH FORGE - FLUXO DE DADOS
```
- [includes/funcoes.php](includes/funcoes.php) — toda função recebe por parâmetro e devolve com `return`
- [receitas.php:11](receitas.php#L11) — o `$_GET` é lido **na página** e passado como parâmetro
- [receita-detalhe.php:16](receita-detalhe.php#L16) — `buscarReceitaPorId($receitas, $id)`

**A regra seguida:** nenhuma função enxerga variável global nem lê `$_GET`
sozinha. Tudo entra por parâmetro e sai por `return` — por isso cada função pode
ser testada isoladamente.

---

### Lógica de Pesquisa ou Filtro
```
RUBRICA TECH FORGE - LÓGICA DE PESQUISA OU FILTRO
```
- [includes/funcoes.php:101](includes/funcoes.php#L101) — `filtrarPorCategoria()`
- [includes/funcoes.php:144](includes/funcoes.php#L144) — `buscarReceitaPorId()`
- [receitas.php:14](receitas.php#L14) — o filtro em uso, com a categoria vinda da URL

**Como testar:** clique nos chips de categoria em `receitas.php`. A categoria vai
para a URL (`?categoria=Doce`), a página lê, passa para a função e só as receitas
daquela categoria continuam no array.

---

### Validação de Regras de Negócio com Condicionais
```
RUBRICA TECH FORGE - VALIDAÇÃO DE REGRAS DE NEGÓCIO COM CONDICIONAIS
```
- [includes/funcoes.php:64](includes/funcoes.php#L64) — `receitaValida()`
- [includes/funcoes.php:83](includes/funcoes.php#L83) — `filtrarReceitasValidas()`
- [index.php:9](index.php#L9), [receitas.php:7](receitas.php#L7) — chamada antes de qualquer exibição

**As regras:** receita com tempo de preparo ≤ 0, sem ingredientes ou sem modo de
preparo não chega à tela. A validação roda antes de tudo, então as páginas nunca
lidam com dado inconsistente.

---
---

## DASHBOARD — API, TYPESCRIPT E BANCO ANALÍTICO

### CTEs e Views analíticas
```
RUBRICA - CRIAÇÃO DE CTEs E VIEWS ANALÍTICAS NO MARIADB QUE LIMPEM E CONSOLIDEM OS DADOS BRUTOS
```
- [db/dashboard-vendas.sql:114](db/dashboard-vendas.sql#L114) — `vw_vendas_detalhadas`, a view que a API consome
- [db/dashboard-vendas.sql:115](db/dashboard-vendas.sql#L115) — CTE `vendas_limpas` (descarta quantidade ≤ 0)
- [db/dashboard-vendas.sql:120](db/dashboard-vendas.sql#L120) — CTE `produtos_ativos` (descarta produto inativo e preço ≤ 0)
- [db/dashboard-vendas.sql:146](db/dashboard-vendas.sql#L146) — `vw_faturamento_categoria`, com CTEs `por_categoria` e `total_geral`
- [db/dashboard-vendas.sql:172](db/dashboard-vendas.sql#L172) — `vw_ranking_produtos`, com CTE + `ROW_NUMBER()`
- [api.php:29](api.php#L29) — a API lendo da view (já recebe tudo limpo)

**Limpeza e consolidação:** a CTE (cláusula `WITH`) funciona como uma tabela
temporária com nome. Aqui ela isola a etapa de limpar o lixo da etapa de juntar
as tabelas — em vez de um `JOIN` gigante com dez condições no `WHERE`.

**Prova:** o banco tem 22 vendas, mas a view devolve 21. A que some é a do
produto inativo, descartada pela CTE. Rode o teste 1 de
[db/testes-demonstracao.sql](db/testes-demonstracao.sql) para ver os dois números
lado a lado.

---

### Triggers BEFORE UPDATE
```
RUBRICA - IMPLEMENTAÇÃO DE TRIGGERS (BEFORE UPDATE) PARA PADRONIZAR A INSERÇÃO DE VALORES POSITIVOS
```
**Protegendo a edição (BEFORE UPDATE):**
- [db/dashboard-vendas.sql:69](db/dashboard-vendas.sql#L69) — `trg_vendas_valor_positivo` (protege `vendas.quantidade`)
- [db/dashboard-vendas.sql:74](db/dashboard-vendas.sql#L74) — `trg_produtos_valor_positivo` (protege `produtos.valor_unitario`)

**Protegendo o cadastro (BEFORE INSERT):**
- [db/dashboard-vendas.sql:82](db/dashboard-vendas.sql#L82) — `trg_vendas_valor_positivo_ins`
- [db/dashboard-vendas.sql:87](db/dashboard-vendas.sql#L87) — `trg_produtos_valor_positivo_ins`

**Como padroniza:** `GREATEST(ABS(x), 1)` faz duas coisas de uma vez. O `ABS`
transforma qualquer negativo em positivo, e o `GREATEST` garante o mínimo — venda
de zero unidade não existe. O preço usa `0.01` como piso.

**Por que são 4 e não 2:** no MySQL/MariaDB cada trigger atende a um evento só.
A dupla `BEFORE UPDATE` cobre quem edita uma linha existente; a dupla
`BEFORE INSERT` cobre quem cadastra uma linha nova. Com as quatro, não existe
caminho pelo qual um valor negativo entre nessas colunas.

**Prova:** teste 4 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql)
para a edição (grava `-99`, o banco guarda `99`) e teste 5B para o cadastro
(insere `-77`, o banco guarda `77`).

---

### Bootstrap no layout
```
RUBRICA - USO DO FRAMEWORK BOOTSTRAP NO DESENVOLVIMENTO DO LAYOUT
```
Ver a seção [Bootstrap (3+ componentes)](#bootstrap-3-componentes) acima — são 8
componentes no total, sendo 4 deles na dashboard (Spinner, Alert, Card e Table).

---

### Aparência e usabilidade do sistema
```
RUBRICA - APARÊNCIA DO SISTEMA / INTERFACE AMIGÁVEL
```
- [includes/header.php:39](includes/header.php#L39) — menu fixo no topo, em todas as páginas
- [includes/funcoes.php:38](includes/funcoes.php#L38) — o item do menu da página atual fica destacado
- [receitas.php:24](receitas.php#L24) — filtro por categoria em chips, um clique
- [assets/css/style.css](assets/css/style.css) — paleta da padaria (mel, massa, crosta)

**Decisões de usabilidade:** o menu acompanha a rolagem e mostra onde você está;
o card inteiro da receita é clicável (não só o título); toda tela sem resultado
explica o motivo em vez de ficar em branco; e a dashboard tem os quatro números
mais importantes no topo, antes da tabela.

---

### Agregações e Cálculos Financeiros (uso de Reduce)
```
RUBRICA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
```
- [src/app.ts:96](src/app.ts#L96) — **faturamento total**: `reduce` acumulando `quantidade × valor_unitario`
- [src/app.ts:103](src/app.ts#L103) — **unidades vendidas**: `reduce` somando as quantidades
- [src/app.ts:116](src/app.ts#L116) — **por produto**: `reduce` que acumula um objeto
- [src/app.ts:124](src/app.ts#L124) — **campeão de vendas**: `reduce` achando o maior
- [api.php:23](api.php#L23) — o array bruto que o PHP envia (quantidade e valor separados)

**O ponto da rubrica:** o PHP **não** manda o total pronto. Ele manda as linhas
cruas, com `quantidade` e `valor_unitario` em colunas separadas, e é o `reduce`
no TypeScript que multiplica e acumula. O acumulador começa em `0` e cresce a
cada linha.

**Prova:** rode o teste 6 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql)
e compare com os cards na tela. Os dois têm que dar **R$ 6.281,00** e **577
unidades**.

---

### Tratamento de Cenários de Exceção (Edge Cases)
```
RUBRICA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)
```
- [src/app.ts:16](src/app.ts#L16) — `numeroSeguro()`, a barreira contra `NaN`
- [src/app.ts:55](src/app.ts#L55) — valida se a resposta é mesmo um array antes do `reduce`
- [src/app.ts:65](src/app.ts#L65) — banco vazio: mostra aviso em vez de dividir por zero
- [src/app.ts:141](src/app.ts#L141) — `zerarCards()` imprime `R$ 0,00`, nunca `R$ NaN`
- [src/app.ts:159](src/app.ts#L159) — tabela vazia exibe **"Nenhum dado registrado"**
- [dashboard.php:39](dashboard.php#L39) — o bloco de aviso de banco vazio

**Por que `numeroSeguro()` existe:** o MySQL manda `DECIMAL` como texto
(`"8.00"`). Se vier `null` ou lixo, `Number()` devolve `NaN` — e basta um `NaN`
para toda a soma virar `NaN` e estampar "R$ NaN" na tela. A função converte e,
se o resultado não for um número finito, devolve `0`.

**Prova:** rode o teste 7 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql),
que esvazia a view, e recarregue a dashboard. Aparece "Nenhum dado registrado" e
os cards ficam em R$ 0,00.

---

### Consumo de API e Resolução de Fluxo Assíncrono
```
RUBRICA - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO
```
**No servidor (PHP):**
- [api.php:13](api.php#L13) — headers de JSON e CORS
- [api.php:21](api.php#L21) — `try` com `prepare`/`execute` via PDO
- [api.php:39](api.php#L39) — `catch (PDOException)` devolvendo erro em JSON
- [includes/conexao.php:23](includes/conexao.php#L23) — `catch` da falha de conexão
- [includes/conexao.php:30](includes/conexao.php#L30) — erro sai em JSON quando quem chama é a API

**No navegador (TypeScript):**
- [src/app.ts:39](src/app.ts#L39) — `async function` com `await fetch('api.php')`
- [src/app.ts:47](src/app.ts#L47) — checa `resposta.ok`
- [src/app.ts:76](src/app.ts#L76) — `catch` que trata erro de rede e de banco

**A sutileza do `resposta.ok`:** o `fetch` só rejeita a promise em falha de rede.
Se o servidor responder 500, ele considera sucesso — a resposta chegou. Por isso
o status é checado na mão e um `Error` é lançado de propósito, para cair no mesmo
`catch`.

**Os 3 cenários cobertos:** MySQL desligado (API responde 500), Apache fora
(erro de rede) e API devolvendo `{"error": ...}` em vez de lista. Nos três a tela
mostra o alerta vermelho com o botão "Tentar de novo", em vez de travar.

---

### Integração de Ambientes (XAMPP + Compilação)
```
RUBRICA - INTEGRAÇÃO DE AMBIENTES (XAMPP + COMPILAÇÃO TYPESCRIPT)
```
- [tsconfig.json:8](tsconfig.json#L8) — `rootDir: ./src` e `outDir: ./dist`
- [tsconfig.json:5](tsconfig.json#L5) — modo `strict` ligado
- [package.json:7](package.json#L7) — `npm run build` (compila) e `npm run watch` (recompila ao salvar)
- [src/app.ts](src/app.ts) — o código-fonte que você edita
- [dist/app.js](dist/app.js) — o resultado da compilação, que o navegador carrega
- [dashboard.php:113](dashboard.php#L113) — a página carregando o `.js` compilado

**O fluxo completo:** `src/app.ts` → `npx tsc` → `dist/app.js` → `<script>` na
página → Apache serve → navegador executa.

**Backend no XAMPP:** Apache e MySQL ligados no XAMPP Control Panel, projeto em
`http://localhost/pao-de-mel/dashboard.php`.

**Como demonstrar ao vivo:** mude o texto "Nenhum dado registrado" em
[src/app.ts:161](src/app.ts#L161), rode `npx tsc`, dê F5 e o texto novo aparece.
Isso prova que a compilação é real, e não um `.js` escrito à mão.

---
---

## ARQUIVOS DE APOIO

- [db/padaria.sql](db/padaria.sql) — cria as 4 tabelas originais (receitas, categorias, ingredientes, N:N)
- [db/dados-teste-9-receitas.sql](db/dados-teste-9-receitas.sql) — popula com as 9 receitas
- [db/dashboard-vendas.sql](db/dashboard-vendas.sql) — tabelas de venda, triggers e views analíticas
- [db/testes-demonstracao.sql](db/testes-demonstracao.sql) — roteiro de testes para a apresentação
