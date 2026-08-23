# PROJETO 3 — ENGENHARIA DE SOFTWARE

Dashboard de vendas: banco analítico, API PHP e TypeScript compilado.

| Disciplina | Rubricas |
|---|---|
| **Banco de Dados Avançado** | CTEs e Views analíticas · Triggers (BEFORE UPDATE) |
| **Desenvolvimento Web Avançada** | Aparência do sistema · Bootstrap (3+ componentes) |
| **Lógica Avançada** | Agregações com Reduce · Cenários de exceção |
| **Tech Forge** | Consumo de API e fluxo assíncrono · Integração de ambientes |

---
---

## BANCO DE DADOS AVANÇADO

### Criação de CTEs e Views analíticas no MariaDB
```
RUBRICA BANCO DE DADOS AVANÇADO - CRIAÇÃO DE CTEs E VIEWS ANALÍTICAS NO MARIADB QUE LIMPEM E
CONSOLIDEM OS DADOS BRUTOS DO SISTEMA, ENTREGANDO-OS PERFEITAMENTE ESTRUTURADOS
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

**Entregando estruturado:** a view consolida 4 tabelas (`vendas`, `produtos`,
`receitas` e `categorias`) em uma linha por venda, já com o nome do produto e a
categoria resolvidos. A API não faz nenhum `JOIN`: ela só faz `SELECT` da view.

**Prova:** o banco tem 22 vendas, mas a view devolve 21. A que some é a do
produto inativo, descartada pela CTE. Rode o teste 1 de
[db/testes-demonstracao.sql](db/testes-demonstracao.sql) para ver os dois números
lado a lado.

---

### Implementação de Triggers (BEFORE UPDATE)
```
RUBRICA BANCO DE DADOS AVANÇADO - IMPLEMENTAÇÃO DE TRIGGERS (BEFORE UPDATE) PARA PADRONIZAR
A INSERÇÃO DE VALORES POSITIVOS
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
---

## DESENVOLVIMENTO WEB AVANÇADA

### Aparência do sistema (interface amigável e usável)
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - O SISTEMA POSSUI INTERFACE AMIGÁVEL, POSSUINDO USABILIDADE
PARA FACILITAR PARA QUE O USUÁRIO NÃO TENHA DE FICAR PROCURANDO AS TAREFAS
```
- [includes/header.php:39](includes/header.php#L39) — menu fixo no topo, em todas as páginas
- [includes/funcoes.php:38](includes/funcoes.php#L38) — o item do menu da página atual fica destacado
- [receitas.php:24](receitas.php#L24) — filtro por categoria em chips, um clique
- [dashboard.php:44](dashboard.php#L44) — os 4 números mais importantes no topo, antes da tabela
- [assets/css/style.css](assets/css/style.css) — paleta da padaria (mel, massa, crosta)

**O usuário não precisa procurar:** toda tarefa do sistema está a **um clique**
do menu, que é o mesmo em todas as páginas e acompanha a rolagem. Não existe
função escondida em submenu ou alcançável só por URL digitada.

**Outras decisões de usabilidade:** o menu mostra onde você está; o card inteiro
da receita é clicável (não só o título); toda tela sem resultado explica o motivo
em vez de ficar em branco; e a dashboard exibe o estado de carregamento em vez de
parecer travada.

---

### Framework Bootstrap no layout (pelo menos 3 componentes)
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - USOU O FRAMEWORK BOOTSTRAP NO DESENVOLVIMENTO DO LAYOUT,
PELO MENOS 3 COMPONENTES
```
- [includes/header.php:20](includes/header.php#L20) — o CSS do Bootstrap 5.3 carregado via CDN
- [includes/header.php:33](includes/header.php#L33) — **Navbar** (com **Collapse** no botão de menu do celular)
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
no mínimo 3. Só na dashboard são 4 deles: Spinner, Alert, Card e Table.

---
---

## LÓGICA AVANÇADA

### Agregações e Cálculos Financeiros (uso de Reduce)
```
RUBRICA LÓGICA AVANÇADA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
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
RUBRICA LÓGICA AVANÇADA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)
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
---

## TECH FORGE

### Consumo de API e Resolução de Fluxo Assíncrono
```
RUBRICA TECH FORGE - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO
```
**No servidor (PHP):**
- [api.php:13](api.php#L13) — headers de JSON e CORS
- [api.php:21](api.php#L21) — `try` com `prepare`/`execute` via PDO
- [api.php:39](api.php#L39) — `catch (PDOException)` devolvendo erro em JSON
- [includes/conexao.php:23](includes/conexao.php#L23) — `catch` da falha de conexão
- [includes/conexao.php:30](includes/conexao.php#L30) — erro sai em JSON quando quem chama é a API

**No navegador (TypeScript):**
- [src/app.ts:39](src/app.ts#L39) — `async function` com `await fetch` na api.php
- [src/app.ts:47](src/app.ts#L47) — checa `resposta.ok`
- [src/app.ts:76](src/app.ts#L76) — `catch` que trata erro de rede e de banco

**A sutileza do `resposta.ok`:** o `fetch` só rejeita a promise em falha de rede.
Se o servidor responder 500, ele considera sucesso — a resposta chegou. Por isso
o status é checado na mão e um `Error` é lançado de propósito, para cair no mesmo
`catch`.

**Os 3 cenários cobertos:** MySQL desligado (API responde 500), Apache fora
(erro de rede) e API devolvendo um objeto de erro em vez de lista. Nos três a
tela mostra o alerta vermelho com o botão "Tentar de novo", em vez de travar.

---

