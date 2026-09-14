# PROJETO 3 - ENGENHARIA DE SOFTWARE

Dashboard de vendas: banco analítico, API PHP e TypeScript compilado.

| Disciplina | Rubricas |
|---|---|
| **Tech Forge** | Consumo de API e fluxo assíncrono, Integração de ambientes, Manipulação segura do DOM, Organização e modularidade, Comunicação técnico-visual, Domínio conceitual (apresentação) |
| **Lógica Avançada** | Contratos de interface (TypeScript), Agregações com Reduce, Segmentação com Filter, Ranking de destaques, Formatação com Map, Cenários de exceção |
| **Banco de Dados Avançado** | CTEs e Views analíticas, Stored Procedures (busca/filtro/paginação), Triggers (BEFORE UPDATE), Função no banco, View centralizadora |
| **Desenvolvimento Web Avançada** | Aparência do sistema, Bootstrap (3+ componentes), Template, Estrutura do projeto, 4 CRUDs completos, Regras de exclusão |

Os links abaixo abrem o arquivo direto na linha certa. No VS Code, clique com
`Ctrl` pressionado, ou abra o preview do markdown com `Ctrl+Shift+V` e clique
normalmente.

> **Antes de apresentar — nesta ordem:**
>
> 1. `db/dashboard-vendas.sql` — cria as tabelas de venda, as triggers, as views,
>    a função e as duas procedures.
> 2. `db/dados-historico-vendas.sql` — acrescenta as duas semanas anteriores, sem
>    as quais o painel "Última semana de movimento" recorta a base inteira e
>    mostra o mesmo número do faturamento total.
> 3. `npx tsc` — recompila o TypeScript para `dist/app.js`.
> 4. `npm test` — 39 conferências da lógica da dashboard. Tem que terminar em
>    **TUDO PASSOU**.
>
> O passo 1 **apaga e recria** as tabelas `vendas` e `produtos`. Rodá-lo de novo
> depois, por via das dúvidas, joga fora todo o histórico e as vendas que você
> cadastrou pela administração — e o passo 2 precisa ser refeito. Rode uma vez só.
>
> Prefira o **DBeaver** ou o **phpMyAdmin** para os dois `.sql`. Se for pelo
> terminal, use
> `mysql --default-character-set=utf8mb4 -u root padaria < db/dashboard-vendas.sql`
> — sem essa opção o cliente do Windows grava "Pão" como "P├úo" no banco.

---

## TECH FORGE

### Consumo de API e Resolução de Fluxo Assíncrono
```
RUBRICA TECH FORGE - CONSUMO DE API E RESOLUÇÃO DE FLUXO ASSÍNCRONO
```
**No servidor (PHP):**
- [api.php:17](api.php#L17) - headers de JSON e CORS
- [api.php:48](api.php#L48) - o `CALL` da procedure, com os valores como parâmetro
- [api.php:55](api.php#L55) - `closeCursor()`, obrigatório depois de um `CALL`
- [api.php:91](api.php#L91) - `catch (PDOException)` devolvendo erro em JSON
- [api.php:100](api.php#L100) - plano B para quem ainda não rodou o SQL novo
- [includes/conexao.php:23](includes/conexao.php#L23) - `catch` da falha de conexão
- [includes/conexao.php:30](includes/conexao.php#L30) - erro sai em JSON quando quem chama é a API

**No navegador (TypeScript):**
- [src/app.ts:78](src/app.ts#L78) - `async function` com `await fetch` na api.php
- [src/app.ts:93](src/app.ts#L93) - `Promise.all`: as duas chamadas saem juntas, não uma depois da outra
- [src/app.ts:100](src/app.ts#L100) - checa `resposta.ok`
- [src/app.ts:150](src/app.ts#L150) - `catch` que trata erro de rede e de banco

**A sutileza do `resposta.ok`:** o `fetch` só rejeita a promise em falha de rede.
Se o servidor responder 500, ele considera sucesso, porque a resposta chegou. Por
isso o status é checado na mão e um `Error` é lançado de propósito, para cair no
mesmo `catch`.

**O `closeCursor()` custou um defeito:** uma procedure devolve mais de um conjunto
de resultados. Sem fechar o cursor, a consulta seguinte na mesma conexão morria
com "Cannot execute queries while other unbuffered queries are active", e a
dashboard só mostrava erro.

**Publicar sem quebrar quem não migrou:** se alguém atualizar os arquivos mas
ainda não tiver rodado o `dashboard-vendas.sql`, a procedure não existe (erro
1305). Em vez de a dashboard morrer com 500, a API volta ao comportamento antigo
e lê a view direto — [api.php:100](api.php#L100).

**Os 3 cenários cobertos:** MySQL desligado (a API responde 500), Apache fora
(erro de rede) e API devolvendo um objeto de erro em vez de lista. Nos três a
tela mostra o alerta vermelho com o botão "Tentar de novo", em vez de travar.

---

### Integração de Ambientes (XAMPP + Compilação)
```
RUBRICA TECH FORGE - INTEGRAÇÃO DE AMBIENTES (XAMPP + COMPILAÇÃO TYPESCRIPT)
```
- [tsconfig.json:8](tsconfig.json#L8) - `rootDir: ./src` e `outDir: ./dist`
- [tsconfig.json:5](tsconfig.json#L5) - modo `strict` ligado
- [package.json:7](package.json#L7) - `npm run build` compila e `npm run watch` recompila ao salvar
- [src/app.ts](src/app.ts) - o código-fonte que você edita
- [src/types.ts](src/types.ts) - o contrato dos dados que a API devolve
- [dist/app.js](dist/app.js) - o resultado da compilação, que o navegador carrega
- [dashboard.php:194](dashboard.php#L194) - a página carregando o `.js` compilado

**O fluxo completo:** `src/app.ts` passa pelo `npx tsc`, vira `dist/app.js`, é
chamado pela tag `<script>` da página, o Apache serve e o navegador executa.

**Backend no XAMPP:** Apache e MySQL ligados no XAMPP Control Panel, projeto em
`http://localhost/pao-de-mel/dashboard.php`.

**Como demonstrar ao vivo:** mude o texto "Nenhum dado registrado" em
[src/app.ts:722](src/app.ts#L722), rode `npx tsc`, dê F5 e o texto novo aparece.
Isso prova que a compilação é real, e não um `.js` escrito à mão.

---

### Manipulação Segura do DOM no Front-End
```
RUBRICA TECH FORGE - MANIPULAÇÃO SEGURA DO DOM NO FRONT-END
```
- [src/app.ts:41](src/app.ts#L41) - `escreverTexto()`, o único caminho para escrever em card
- [src/app.ts:43](src/app.ts#L43) - o `if (elemento)` que protege TODA escrita de texto
- [src/app.ts:450](src/app.ts#L450) - `if (!lista) return` antes de desenhar o pódio
- [src/app.ts:552](src/app.ts#L552) - `if (!tbody) return` antes de montar a tabela
- [src/app.ts:583](src/app.ts#L583) - `if (!area) return` antes dos chips
- [src/app.ts:617](src/app.ts#L617) - `if (!lista) return` antes da paginação
- [src/app.ts:686](src/app.ts#L686) - `as HTMLInputElement | null`, o tipo admitindo o nulo
- [src/app.ts:51](src/app.ts#L51) - `escapar()`, antes de qualquer texto do banco virar HTML
- [tsconfig.json:5](tsconfig.json#L5) - `"strict": true`, quem obriga tudo isso

**O ponto da rubrica — e o número que vale a nota:**

```
grep -cE "\)!|\]!|[a-zA-Z]!\." src/app.ts     # zero
```

**Zero** uso do operador de asserção não-nula (`!`) nas 773 linhas. E dos **13
acessos ao DOM** do projeto, 13 estão protegidos: cada `getElementById` é seguido
de um `if`, sem exceção.

**Por que o `!` é tentador e por que ele foi evitado:** com `strict` ligado,
`document.getElementById('card-faturamento')` tem tipo `HTMLElement | null` — o
compilador recusa usar direto. Escrever `getElementById('x')!.innerText` cala o
compilador numa tecla. Mas o `!` não verifica nada: ele só promete ao compilador
que ali nunca haverá nulo. No dia em que alguém renomear um `id` no
`dashboard.php`, a promessa vira mentira e a página quebra com *"Cannot read
properties of null"* — no navegador do professor, não na sua máquina. Com o `if`,
o pior caso é o card não ser preenchido; o resto da tela continua de pé.

**A única linha sem `if` é a certa:**
[src/app.ts:691](src/app.ts#L691) usa `querySelectorAll`, que devolve uma lista
vazia quando não acha nada — nunca `null`. Guarda ali seria código morto.

**Segurança não é só nulo:** [escapar()](src/app.ts#L51) existe porque um produto
cadastrado como `<img onerror=...>` executaria script na tela de quem abrisse a
dashboard. Todo texto vindo do banco passa por ele antes de virar HTML — inclusive
no `map` que monta as linhas da tabela e o pódio.

**Prova:** `npm run test:tela`. Ele confere que, com a busca `zzzz`, a tela mostra
avisos em vez de quebrar, e que **não existe a palavra "NaN" em lugar nenhum** da
página. O teste também lê o console do navegador e falha se houver um só erro de
JavaScript.

---

### Organização do Código e Modularidade
```
RUBRICA TECH FORGE - ORGANIZAÇÃO DO CÓDIGO E MODULARIDADE
```
**A separação que a rubrica pede — buscar, processar e renderizar são funções diferentes:**

| Papel | Funções |
|---|---|
| **Busca** (fala com a API) | [carregarDashboard()](src/app.ts#L78), [montarUrl()](src/app.ts#L62), [validarResposta()](src/app.ts#L169) |
| **Processamento** (calcula) | [calcularMetricas()](src/app.ts#L230), [ranquearProdutos()](src/app.ts#L302), [recortarUltimaSemana()](src/app.ts#L390), [prepararLinhas()](src/app.ts#L534) |
| **Renderização** (escreve na tela) | [atualizarCards()](src/app.ts#L268), [exibirTabela()](src/app.ts#L550), [desenharRanking()](src/app.ts#L448), [desenharPeriodo()](src/app.ts#L480), [desenharChips()](src/app.ts#L581), [desenharPaginacao()](src/app.ts#L615) |
| **Auxiliares** (converte e formata) | [numeroSeguro()](src/app.ts#L29), [formatarMoeda()](src/app.ts#L34), [escapar()](src/app.ts#L51), [formatarDiaMes()](src/app.ts#L475) |

**Nenhuma função de processamento toca o DOM, e nenhuma de renderização
calcula.** Por isso dá para conferir um valor chamando a função no console, sem
inspecionar elemento — e é isso que torna o `npm test` possível: ele roda as
funções de cálculo sem navegador nenhum.

**O caso mais claro é a tabela:** [prepararLinhas()](src/app.ts#L534) transforma a
venda crua em texto formatado e devolve um array; [exibirTabela()](src/app.ts#L550)
só escreve o que recebeu. Antes as duas coisas estavam na mesma função, e conferir
um subtotal exigia abrir o inspetor do navegador.

**No PHP, a mesma ideia:** 11 arquivos em [includes/](includes/), cada um com um
assunto — um CRUD por entidade, a conexão isolada, o cabeçalho e o rodapé
compartilhados por todas as 10 páginas. Nenhuma página repete SQL: quem fala com
o banco é o `crud-*.php` correspondente.

**Nomenclatura:** tudo em português, verbo no infinitivo para ação
(`calcularMetricas`, `desenharRanking`, `excluirCategoria`) e substantivo para
dado (`vendas`, `porProduto`, `faturamentoTotal`). Sem abreviação inventada.

**Prova:** os dois testes. Se as responsabilidades estivessem misturadas, o
[testes/teste-logica.js](testes/teste-logica.js) não conseguiria rodar
`ranquearProdutos()` fora do navegador — ele só funciona porque essa função não
sabe que existe tela.

---

### Comunicação Técnico-Visual da Dashboard
```
RUBRICA TECH FORGE - COMUNICAÇÃO TÉCNICO-VISUAL DA DASHBOARD
```
- [dashboard.php:49](dashboard.php#L49) - os 4 grandes números, primeira coisa da página
- [dashboard.php:102](dashboard.php#L102) - o painel "Destaques": pódio e semana
- [dashboard.php:137](dashboard.php#L137) - busca e filtro, depois dos números
- [dashboard.php:158](dashboard.php#L158) - a tabela, por último
- [assets/css/style.css](assets/css/style.css) - CSS próprio, sem framework de utilitário

**A ordem da página é a ordem da leitura:** quanto mais agregado o dado, mais
alto ele está. Primeiro o total (R$ 15.953,00), depois os destaques (quem lidera,
como foi a semana), depois os controles, e só então a lista linha a linha. Quem
olha por três segundos já saiu com o número que importa.

**Uma cor de marca, usada com parcimônia:** o mel (`--honey`) marca só o que é
interativo ou de destaque — chip ativo, botão, posição no pódio. O resto é massa
e crosta. Não há segunda cor competindo por atenção.

**Hierarquia por tipografia, não por caixa colorida:** número grande em Fraunces,
rótulo pequeno em maiúsculas espaçadas, dado técnico em fonte monoespaçada. Nada
de borda grossa ou fundo forte para gritar importância.

**O que foi deliberadamente deixado de fora:** nenhum degradê, nenhum ícone
decorativo, nenhum emoji, nenhuma animação. Poluição visual é justamente o que a
rubrica desconta.

**Responsivo de verdade:** o grid do Bootstrap reorganiza os 4 cards e as 2
colunas dos destaques no celular, e a tabela rola dentro do próprio quadro
(`table-responsive`) em vez de esticar a página inteira.

**Como demonstrar:** aperte `Ctrl -` no navegador para estreitar, ou abra o F12 e
ligue o modo celular. Os cards empilham, o painel vira uma coluna, e a tabela
ganha rolagem própria — nada vaza para fora da tela.

---

### Comunicação Técnica e Domínio Conceitual (Apresentação)
```
RUBRICA TECH FORGE - COMUNICAÇÃO TÉCNICA E DOMÍNIO CONCEITUAL
```
**Esta rubrica não se resolve no código: ela se resolve na sua boca.** O que ela
cobra é você explicar o caminho completo do dado com a terminologia certa. Abaixo
está esse caminho, em seis paradas — decore as paradas, não o texto.

**O fluxo completo de um número até a tela**

| # | Onde | O que acontece | O nome certo |
|---|---|---|---|
| 1 | **MariaDB** | `vw_vendas_detalhadas` junta venda, produto, receita e categoria, e descarta o que está sujo | *View com CTE* |
| 2 | **MariaDB** | `sp_vendas_buscar` recebe busca, categoria e página, e devolve as linhas cruas | *Stored Procedure* |
| 3 | **PHP** | [api.php](api.php) faz `CALL` com os valores como parâmetro e lê o resultado | *PDO com consulta parametrizada* |
| 4 | **PHP** | O resultado vira texto com `json_encode` e sai pelo `Content-Type: application/json` | *Serialização em JSON* |
| 5 | **TypeScript** | [carregarDashboard()](src/app.ts#L78) faz `await fetch`, valida o formato e tipa como `RespostaApi` | *Requisição assíncrona e contrato de interface* |
| 6 | **DOM** | O `reduce` soma, o `map` formata, e [escreverTexto()](src/app.ts#L41) escreve no elemento | *Manipulação do DOM* |

**A frase de uma linha, se pedirem resumo:** *"O banco entrega linha crua, o PHP
serializa em JSON, o TypeScript tipa e calcula, e só então o DOM recebe texto já
formatado."*

**Cinco perguntas prováveis, com a resposta curta:**

1. *"Por que o faturamento não é somado no banco?"* — É, nos dois lugares, de
   propósito. A procedure chama `fn_faturamento_periodo` e manda o total junto; o
   `reduce` calcula pelo caminho independente. O console compara os dois e avisa
   se divergirem. É conferência cruzada, não repetição.

2. *"Por que `quantidade` é `number | string` no TypeScript?"* — Porque o PDO
   entrega `INT` e `DECIMAL` como texto no JSON. Mentir no tipo faria o
   compilador aceitar uma conta que quebra no navegador.

3. *"Se o PHP já valida a quantidade, para que serve a trigger?"* — A tela não é a
   única porta. Quem escrever `UPDATE` no DBeaver não passa pelo PHP, e a trigger
   é a única barreira que ele encontra.

4. *"Por que a venda é cancelada em vez de apagada?"* — Porque ela é histórico. O
   registro fica no banco para consulta e sai do faturamento pela view — apagar
   destruiria a prova de que a venda existiu.

5. *"O que acontece se o MySQL cair no meio da apresentação?"* — A API responde
   500, o `catch` do TypeScript pega, e a tela mostra o alerta vermelho com
   "Tentar de novo" em vez de travar. Dá para demonstrar: pare o MySQL no XAMPP e
   dê F5.

**Ensaie apontando para a tela, não para o código.** Cada parada do fluxo acima
tem algo visível: os chips saem da procedure, o subtotal sai do `map`, o painel
de destaques sai do `filter`. A única que não tem tela é a trigger — essa é no
DBeaver.

---

## LÓGICA AVANÇADA

### Modelagem de Dados e Contratos de Interface (TypeScript)
```
RUBRICA LÓGICA AVANÇADA - MODELAGEM DE DADOS E CONTRATOS DE INTERFACE (TYPESCRIPT)
```
- [src/types.ts:16](src/types.ts#L16) - `Venda`, uma linha como a procedure devolve
- [src/types.ts:27](src/types.ts#L27) - `CategoriaFiltro`, o que vira botão de filtro
- [src/types.ts:34](src/types.ts#L34) - `MetaConsulta`, o que a procedure informou sobre a consulta
- [src/types.ts:50](src/types.ts#L50) - `RespostaApi`, o envelope inteiro do endpoint
- [src/types.ts:57](src/types.ts#L57) - `EstadoConsulta`, os controles da tela
- [src/types.ts:64](src/types.ts#L64) - `Metricas`, os quatro grandes números
- [src/types.ts:75](src/types.ts#L75) - `LinhaTabela`, a linha já formatada para a tela
- [src/types.ts:86](src/types.ts#L86) - `TotaisProduto`, o valor do objeto de contagem do ranking
- [src/types.ts:94](src/types.ts#L94) - `ProdutoRanqueado`, um produto no Top 3
- [src/types.ts:104](src/types.ts#L104) - `RecortePeriodo`, o resultado da segmentação por período
- [tsconfig.json:5](tsconfig.json#L5) - `"strict": true`, o compilador cobrando tudo

**O ponto da rubrica:** são **10 types** cobrindo 100% do JSON que o PHP envia, e
**zero uso de `any`** em todo o projeto. Confira com um comando:

```
grep -c "\bany\b" src/*.ts      # tem que dar 0 nos dois arquivos
npx tsc --noEmit                # tem que sair sem mensagem nenhuma
```

**Por que `quantidade` é `number | string`:** o PDO entrega coluna `INT` e
`DECIMAL` como **texto** no JSON — `"8.00"`, e não `8`. Mentir no tipo, jurando
que é `number`, faria o compilador aceitar `quantidade * valor` e o navegador
produzir lixo em tempo de execução. O tipo diz a verdade sobre o que chega, e
[numeroSeguro()](src/app.ts#L29) faz a conversão em um lugar só.

**Por que `variacao` é `number | null`:** quando não existe semana anterior, não
existe variação. Zero ali seria mentira — significaria "não mudou nada". O `null`
obriga quem usa o valor a tratar o caso, e o compilador recusa esquecer disso.

**Todas as funções têm retorno explícito**, inclusive as que devolvem `void`.
Não é preciosismo: retorno declarado faz o compilador reclamar quando um caminho
do código esquece de devolver algo.

**Prova:** abra [src/app.ts](src/app.ts) no VS Code e passe o mouse sobre
`venda.produto` dentro de qualquer `reduce`. O editor mostra o tipo sem nenhuma
anotação no local — ele vem do contrato. Agora digite `venda.prodto` (errado de
propósito): o erro aparece **antes** de compilar.

---

### Agregações e Cálculos Financeiros (uso de Reduce)
```
RUBRICA LÓGICA AVANÇADA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
```
- [src/app.ts:233](src/app.ts#L233) - **faturamento total**, `reduce` acumulando `quantidade * valor_unitario`
- [src/app.ts:240](src/app.ts#L240) - **unidades vendidas**, `reduce` somando as quantidades
- [src/app.ts:253](src/app.ts#L253) - **por produto**, `reduce` que acumula um objeto
- [src/app.ts:261](src/app.ts#L261) - **campeão de vendas**, `reduce` achando o maior
- [db/dashboard-vendas.sql:434](db/dashboard-vendas.sql#L434) - as colunas cruas que a procedure devolve

**O ponto da rubrica:** o banco **não** manda o total pronto. A procedure devolve
as linhas cruas, com `quantidade` e `valor_unitario` em colunas separadas, e é o
`reduce` no TypeScript que multiplica e acumula. O acumulador começa em `0` e
cresce a cada linha.

**Onde isso fica visível:** clique da página 1 para a 2. A tabela troca as 10
linhas, mas os cards continuam em R$ 15.953,00 — porque o `reduce` roda sobre o
filtro inteiro, e não sobre a página. Agora clique no filtro "Bolo": aí sim os
cards mudam para R$ 1.850,00.

**Prova:** rode o teste 6 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql)
e compare com os cards na tela. Os dois têm que dar **R$ 15.953,00** e
**1462 unidades**.

---

### Segmentação e Filtros de Negócio (uso de Filter)
```
RUBRICA LÓGICA AVANÇADA - SEGMENTAÇÃO E FILTROS DE NEGÓCIO (USO DE FILTER)
```
- [src/app.ts:390](src/app.ts#L390) - `recortarUltimaSemana()`, a segmentação por período
- [src/app.ts:392](src/app.ts#L392) - **filter 1**: descarta linha sem data utilizável
- [src/app.ts:407](src/app.ts#L407) - **filter 2**: a janela de 7 dias
- [src/app.ts:414](src/app.ts#L414) - **filter 3**: a semana anterior, para comparar
- [src/app.ts:366](src/app.ts#L366) - `ehDataValida()`, a barreira contra `Invalid Date`
- [src/app.ts:377](src/app.ts#L377) - `somarDias()`, aritmética de data em UTC
- [src/app.ts:480](src/app.ts#L480) - `desenharPeriodo()`, o painel na tela
- [dashboard.php:121](dashboard.php#L121) - o cartão "Última semana de movimento"

**O ponto da rubrica:** isolar um subconjunto para criar inteligência de negócio.
O recorte responde a uma pergunta que nenhum card respondia: *a padaria está
vendendo mais ou menos que na semana passada?*

**Por que a janela é contada da última venda, e não de hoje:** as vendas do banco
são de agosto. Contar "últimos 7 dias" a partir da data de hoje devolvia **zero
venda**, e o painel aparecia vazio — parecia defeito no código, quando o banco
estava certo. Ancorado no último movimento, o recorte sempre mostra a semana que
de fato aconteceu.

**Por que a comparação de data é feita como texto:** `'AAAA-MM-DD'` tem largura
fixa e a parte mais significativa primeiro, então `>=` e `<=` de string dão o
mesmo resultado que comparar datas — sem criar um objeto `Date` por linha.

**Por que UTC:** `new Date('2026-08-23')` é meia-noite **UTC**. Somando dias no
horário local, quem estivesse em fuso negativo veria a data voltar um dia na
conversão de ida e volta, e a semana começaria no dia errado.

**Onde isso fica visível:** o card "Faturamento total" mostra **R$ 15.953,00**,
que é tudo. O painel "Última semana de movimento" mostra **R$ 6.376,00** —
o recorte de 17/08 a 23/08 — e embaixo **+23,2% em relação à semana anterior**.
Dois números diferentes na mesma tela: um é o todo, o outro é a fatia.

**Prova:** `npm test`, bloco "TESTE 2". Ele monta cinco vendas à mão — duas na
semana recente, uma na anterior, uma de janeiro e uma com data `0000-00-00` — e
confere que só as duas certas entraram, que a de janeiro ficou de fora e que a
variação deu exatamente **+50,0%**.

---

### Algoritmos de Ranking e Frequência (Destaques)
```
RUBRICA LÓGICA AVANÇADA - ALGORITMOS DE RANKING E FREQUÊNCIA (DESTAQUES)
```
- [src/app.ts:302](src/app.ts#L302) - `ranquearProdutos()`, o ranking inteiro
- [src/app.ts:303](src/app.ts#L303) - o **objeto de contagem**: chave = nome do produto
- [src/app.ts:318](src/app.ts#L318) - o total geral, para calcular a participação
- [src/app.ts:333](src/app.ts#L333) - o `sort`, com desempate por unidade vendida
- [src/app.ts:300](src/app.ts#L300) - `TOTAL_DESTAQUES`, quantos entram no pódio
- [src/app.ts:261](src/app.ts#L261) - o campeão único, que já existia nos cards
- [dashboard.php:110](dashboard.php#L110) - o cartão "Top 3 produtos por faturamento"

**O ponto da rubrica:** achar o maior indicador **de forma dinâmica**, com
estrutura de chave-valor. Não há nome de produto escrito no código: se a padaria
cadastrar um pão novo que lidere, ele sobe ao pódio sozinho.

**Por que um objeto de contagem, e não um laço dentro de outro:** percorrendo o
array uma vez e consultando por chave, o custo cresce junto com o número de
vendas. Comparando cada venda com cada produto, cresceria com o produto dos dois
— lento à toa quando a padaria tiver catálogo grande.

**Por que existe desempate:** dois produtos com o mesmo faturamento trocavam de
lugar a cada F5, porque a ordem entre iguais ficava por conta do navegador.
Parecia que o ranking estava calculando errado. O desempate por unidade vendida
deixa a ordem estável.

**Por que a posição é atribuída só no fim:** ela é um `map` **depois** do `sort` e
do `slice`. Numerar antes de ordenar seria numerar um palpite.

**Onde isso fica visível:** o pódio mostra Coxinha (**R$ 4.600,00**, 28,8% do
total), Pão de Mel (**R$ 2.992,50**) e Pão de Fermentação Natural
(**R$ 2.952,00**). Repare que o 2º e o 3º estão a R$ 40,50 um do outro — é o
caso que mais exige precisão no cálculo.

**Prova:** `npm test`, bloco "TESTE 1". Os três nomes e os três valores estão
escritos à mão no teste, conferidos com `GROUP BY produto` no banco. Se o cálculo
escorregar um centavo, o 2º e o 3º trocam de lugar e o teste acusa.

---

### Transformação e Formatação de Estruturas (uso de Map)
```
RUBRICA LÓGICA AVANÇADA - TRANSFORMAÇÃO E FORMATAÇÃO DE ESTRUTURAS (USO DE MAP)
```
- [src/app.ts:534](src/app.ts#L534) - `prepararLinhas()`, a API crua virando texto de tela
- [src/app.ts:564](src/app.ts#L564) - o `map` que monta as linhas da tabela
- [src/app.ts:323](src/app.ts#L323) - o `map` que transforma `[chave, valor]` do objeto
- [src/app.ts:345](src/app.ts#L345) - o `map` que numera o pódio
- [src/app.ts:463](src/app.ts#L463) - o `map` que desenha cada linha do ranking
- [src/app.ts:429](src/app.ts#L429) - `map` + `Set` para contar categorias distintas
- [src/types.ts:75](src/types.ts#L75) - `LinhaTabela`, o formato que a interface exige

**O ponto da rubrica:** transformar a estrutura original da API no formato que a
tela precisa — aqui, cada número virando **string em moeda local**. `18` vira
`"R$ 18,00"`, e é a linha pronta que chega no HTML.

**Por que preparar e desenhar são etapas separadas:** dá para chamar
`prepararLinhas(vendas)` no console do navegador e conferir o subtotal de
qualquer linha sem ler o HTML da tabela. Enquanto o cálculo estava grudado no
desenho, conferir um valor exigia inspecionar elemento.

**Por que `map().join('')` e não `forEach` com `appendChild`:** o `map` devolve o
array de pedaços e o `join` monta tudo de uma vez — um `innerHTML` só, em vez de
um por linha.

**O `map` não substitui o `escapar()`:** ele formata número; quem impede um nome
de produto de virar HTML executável continua sendo
[escapar()](src/app.ts#L51), aplicado na hora de montar a linha.

**Onde isso fica visível:** toda a coluna "Subtotal" da tabela, e os valores do
pódio. Nenhum número aparece na tela sem passar por um `map` ou pelo
`formatarMoeda`.

**Prova:** `npm test`, bloco "TESTE 4". Ele confere que `10 × R$ 5,00` sai como a
string `"R$ 50,00"`. O teste normaliza o espaço antes de comparar, porque o
pt-BR separa o `R$` do número com **espaço não separável** (U+00A0) — comparar
sem isso falha por um caractere invisível, e custou um tempo enxergar.

---

### Tratamento de Cenários de Exceção (Edge Cases)
```
RUBRICA LÓGICA AVANÇADA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)
```
- [src/app.ts:29](src/app.ts#L29) - `numeroSeguro()`, a barreira contra `NaN`
- [src/app.ts:169](src/app.ts#L169) - `validarResposta()`, confere o formato antes de confiar
- [src/app.ts:176](src/app.ts#L176) - valida se veio mesmo um array antes do `reduce`
- [src/app.ts:120](src/app.ts#L120) - pediu uma página que não existe mais: volta para a 1 sozinho
- [src/app.ts:133](src/app.ts#L133) - filtro sem resultado: mostra aviso em vez de dividir por zero
- [src/app.ts:278](src/app.ts#L278) - `zerarCards()` imprime `R$ 0,00`, nunca `R$ NaN`
- [src/app.ts:557](src/app.ts#L557) - tabela vazia exibe **"Nenhum dado registrado"**
- [src/app.ts:51](src/app.ts#L51) - `escapar()`, para nome de produto não virar HTML
- [src/app.ts:76](src/app.ts#L76) - trava de uma busca por vez
- [db/dashboard-vendas.sql:410](db/dashboard-vendas.sql#L410) - página 0 ou negativa tratada dentro do banco

**Por que `numeroSeguro()` existe:** o MySQL manda `DECIMAL` como texto
(`"8.00"`). Se vier `null` ou lixo, `Number()` devolve `NaN`, e basta um `NaN`
para toda a soma virar `NaN` e estampar "R$ NaN" na tela. A função converte e,
se o resultado não for um número finito, devolve `0`.

**Por que a trava de "uma busca por vez":** clicando rápido na página 2 e depois
na 3, a resposta mais lenta chegava por último e pintava a tabela com a página
errada. Enquanto o `CALL` não volta, os controles ficam desabilitados.

**Por que a página inválida é tratada nos dois lados:** o banco corrige página 0
ou negativa (viraria `OFFSET` negativo, que é erro de sintaxe e derrubaria a API
com 500), e a tela se corrige sozinha quando o filtro encolheu e a página atual
deixou de existir.

**Prova:** rode o teste 7 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql),
que esvazia a view, e recarregue a dashboard. Aparece "Nenhum dado registrado" e
os cards ficam em R$ 0,00. Depois digite `zzzz` no campo de busca: aparece o aviso
de filtro sem resultado, e nenhum "NaN".

---

## BANCO DE DADOS AVANÇADO

### Criação de CTEs e Views analíticas no MariaDB
```
RUBRICA BANCO DE DADOS AVANÇADO - CRIAÇÃO DE CTEs E VIEWS ANALÍTICAS NO MARIADB QUE LIMPEM E
CONSOLIDEM OS DADOS BRUTOS DO SISTEMA, ENTREGANDO-OS PERFEITAMENTE ESTRUTURADOS
```
- [db/dashboard-vendas.sql:174](db/dashboard-vendas.sql#L174) - `vw_vendas_detalhadas`, a view que a API consome
- [db/dashboard-vendas.sql:175](db/dashboard-vendas.sql#L175) - CTE `vendas_limpas`, descarta venda cancelada e quantidade zerada
- [db/dashboard-vendas.sql:181](db/dashboard-vendas.sql#L181) - CTE `produtos_validos`, descarta preço zerado
- [db/dashboard-vendas.sql:206](db/dashboard-vendas.sql#L206) - `vw_faturamento_categoria`, com as CTEs `por_categoria` e `total_geral`
- [db/dashboard-vendas.sql:232](db/dashboard-vendas.sql#L232) - `vw_ranking_produtos`, com CTE e `ROW_NUMBER()`
- [db/dashboard-vendas.sql:441](db/dashboard-vendas.sql#L441) - a procedure lendo da view, já recebe tudo limpo

**Limpeza e consolidação:** a CTE (cláusula `WITH`) funciona como uma tabela
temporária com nome. Aqui ela isola a etapa de limpar o lixo da etapa de juntar
as tabelas, em vez de um `JOIN` gigante com dez condições no `WHERE`.

**Entregando estruturado:** a view consolida 4 tabelas (`vendas`, `produtos`,
`receitas` e `categorias`) em uma linha por venda, já com o nome do produto e a
categoria resolvidos. Nem a procedure nem a API fazem `JOIN`: as duas só leem
da view.

**O que ela NÃO descarta, de propósito:** a venda de produto fora de linha
continua contando. Faturamento de mês fechado não pode mudar porque alguém
aposentou um produto hoje — descobrir isso custou R$ 95,00 de diferença entre a
tela e o relatório.

**Prova:** o banco tem 23 vendas, mas a view devolve 22. A que some é a venda
**cancelada**, descartada pela CTE `vendas_limpas` — o registro continua no
banco, some só do faturamento. Rode o teste 1 de
[db/testes-demonstracao.sql](db/testes-demonstracao.sql) para ver os dois números
lado a lado e qual é a linha descartada.

---

### Stored Procedures com busca, filtro e paginação
```
RUBRICA BANCO DE DADOS AVANÇADO - DESENVOLVIMENTO DE STORED PROCEDURES OTIMIZADAS PARA
CENTRALIZAR A BUSCA, FILTROS E PAGINAÇÃO DOS INDICADORES DA DASHBOARD, PERMITINDO QUE A API
EM PHP FAÇA CHAMADAS LIMPAS (CALL) E ASSÍNCRONAS
```
**No banco:**
- [db/dashboard-vendas.sql:390](db/dashboard-vendas.sql#L390) - `sp_vendas_buscar`, a procedure principal
- [db/dashboard-vendas.sql:425](db/dashboard-vendas.sql#L425) - a **busca**, com `LIKE CONCAT('%', p_busca, '%')`
- [db/dashboard-vendas.sql:442](db/dashboard-vendas.sql#L442) - o **filtro** de categoria
- [db/dashboard-vendas.sql:445](db/dashboard-vendas.sql#L445) - a **paginação**, `LIMIT v_tamanho OFFSET v_deslocamento`
- [db/dashboard-vendas.sql:464](db/dashboard-vendas.sql#L464) - `sp_vendas_categorias`, que alimenta os botões de filtro

**A chamada limpa no PHP:**
- [api.php:48](api.php#L48) - o `CALL sp_vendas_buscar(?, ?, ?, ?, @total_linhas, @faturamento)`
- [api.php:59](api.php#L59) - a leitura dos dois parâmetros de saída (`OUT`)
- [api.php:64](api.php#L64) - o `CALL sp_vendas_categorias()`

**No navegador (assíncrono):**
- [src/app.ts:93](src/app.ts#L93) - `Promise.all` com as duas chamadas em paralelo
- [src/app.ts:596](src/app.ts#L596) - os botões de categoria desenhados a partir do `CALL`
- [src/app.ts:615](src/app.ts#L615) - a paginação desenhada a partir do total que a procedure contou
- [dashboard.php:137](dashboard.php#L137) - a barra de busca e filtro na tela
- [dashboard.php:184](dashboard.php#L184) - a navegação de páginas

**O que "chamada limpa" quer dizer aqui:** a `api.php` não monta mais nenhum
`SELECT`. Ela lê quatro valores do `$_GET`, repassa como parâmetro e faz `CALL`.
Busca, filtro, recorte de página e limpeza dos dados moram todos dentro da
procedure, onde o banco pode otimizar.

**Por que a dashboard faz duas chamadas:** a tabela mostra a página atual, mas os
cards precisam somar o filtro inteiro. Por isso o parâmetro `p_tamanho = 0`
significa "traz tudo, sem paginar". Sem isso, o card "Faturamento total" cairia
de R$ 15.953,00 para o total de 10 linhas e mudaria toda vez que o professor
clicasse em "próxima página" — pareceria defeito no meio da apresentação.

**Segurança:** nada aqui é SQL montado como texto. O que o usuário digita entra
como **parâmetro** dentro do `CONCAT` do `LIKE`, e o `LIMIT`/`OFFSET` recebe
variável direto (o MariaDB aceita). Digitar `' OR 1=1 --` no campo de busca
devolve **0 linhas**, porque a frase é procurada como nome de produto, e não
executada.

**Prova:** teste 10 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql).
O 10A traz 10 linhas de 22, o 10B mostra que na página 3 sobram 2 linhas, o 10C e o
10D filtram e buscam, e o **10F é o teste de invasão**: devolve zero e mostra a
tabela `vendas` intacta logo em seguida.

---

### Implementação de Triggers (BEFORE UPDATE)
```
RUBRICA BANCO DE DADOS AVANÇADO - IMPLEMENTAÇÃO DE TRIGGERS (BEFORE UPDATE) PARA PADRONIZAR
A INSERÇÃO DE VALORES POSITIVOS
```
**Protegendo a edição (BEFORE UPDATE):**
- [db/dashboard-vendas.sql:94](db/dashboard-vendas.sql#L94) - `trg_vendas_valor_positivo`, protege `vendas.quantidade`
- [db/dashboard-vendas.sql:99](db/dashboard-vendas.sql#L99) - `trg_produtos_valor_positivo`, protege `produtos.valor_unitario`

**Protegendo o cadastro (BEFORE INSERT):**
- [db/dashboard-vendas.sql:107](db/dashboard-vendas.sql#L107) - `trg_vendas_valor_positivo_ins`
- [db/dashboard-vendas.sql:112](db/dashboard-vendas.sql#L112) - `trg_produtos_valor_positivo_ins`

**Como padroniza:** `GREATEST(ABS(x), 1)` faz duas coisas de uma vez. O `ABS`
transforma qualquer negativo em positivo, e o `GREATEST` garante o mínimo, já que
venda de zero unidade não existe. O preço usa `0.01` como piso.

**Por que são 4 e não 2:** no MySQL/MariaDB cada trigger atende a um evento só.
A dupla `BEFORE UPDATE` cobre quem edita uma linha existente; a dupla
`BEFORE INSERT` cobre quem cadastra uma linha nova. Com as quatro, não existe
caminho pelo qual um valor negativo entre nessas colunas.

**Prova:** teste 4 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql)
para a edição (grava `-99`, o banco guarda `99`) e teste 5B para o cadastro
(insere `-77`, o banco guarda `77`).

---

### Função no banco para reutilizar script complexo
```
RUBRICA BANCO DE DADOS AVANÇADO - CRIAÇÃO DE UMA FUNÇÃO NO BANCO DE DADOS PARA REUTILIZAÇÃO
DE SCRIPTS MASSIVOS OU COMPLEXOS
```
- [db/dashboard-vendas.sql:273](db/dashboard-vendas.sql#L273) - `fn_faturamento_periodo`, a função
- [db/dashboard-vendas.sql:429](db/dashboard-vendas.sql#L429) - a procedure **reutilizando** a função
- [db/testes-demonstracao.sql](db/testes-demonstracao.sql) - teste 9, chamando a função direto

**O script que ela encapsula:** para saber o faturamento de um intervalo era
preciso juntar 4 tabelas, descartar produto inativo, descartar quantidade menor
ou igual a zero, multiplicar quantidade por valor e somar. Esse bloco já aparecia
copiado em três lugares do projeto. Cada cópia é uma chance de alguém esquecer um
dos filtros e publicar um faturamento errado. A função guarda a regra em um lugar
só: `fn_faturamento_periodo(NULL, NULL)`.

**O detalhe que custou um defeito:** `SUM()` sobre zero linhas devolve `NULL`, e
não zero. Sem o `COALESCE`, um intervalo sem venda fazia a função devolver `NULL`
e a tela imprimia "R$ NaN". Por isso a linha é
`COALESCE(SUM(quantidade * valor_unitario), 0)`.

**Ela não aparece na tela — como mostrar ao professor:**

1. Abra o arquivo `db/testes-demonstracao.sql` no DBeaver, com o banco `padaria`
   selecionado, e vá até o **TESTE 9**.
2. Rode com `Ctrl+Enter`:
   ```sql
   SELECT fn_faturamento_periodo(NULL, NULL)                 AS periodo_inteiro,
          fn_faturamento_periodo('2026-08-23', '2026-08-23') AS so_dia_23,
          fn_faturamento_periodo('2030-01-01', '2030-12-31') AS periodo_sem_venda;
   ```
   Tem que sair **6376.00**, **1562.50** e **0.00**.
3. Para mostrar o código dela: `SHOW CREATE FUNCTION fn_faturamento_periodo;`
   — mas o MariaDB **apaga os comentários** nesse resultado. Os comentários que
   explicam o porquê estão em
   [db/dashboard-vendas.sql:273](db/dashboard-vendas.sql#L273); deixe esse arquivo
   aberto ao lado.
4. Para provar a reutilização, mostre a linha
   [db/dashboard-vendas.sql:429](db/dashboard-vendas.sql#L429): é a procedure
   chamando a função em vez de repetir o `SUM`.

**Prova cruzada:** o `6376.00` da função é o mesmo número que o `reduce` do
TypeScript calcula por outro caminho. A dashboard confere os dois sozinha e
escreve o resultado no console do navegador (`F12` → aba Console):
[src/app.ts:190](src/app.ts#L190).

---

### View que centraliza informações de várias tabelas
```
RUBRICA BANCO DE DADOS AVANÇADO - CRIAÇÃO DE VIEW QUE CENTRALIZE INFORMAÇÕES IMPORTANTES NO
SISTEMA E QUE ESTÃO EM DIVERSAS TABELAS DISTINTAS
```
- [db/dashboard-vendas.sql:316](db/dashboard-vendas.sql#L316) - `vw_painel_produtos`, a view
- [db/dashboard-vendas.sql:317](db/dashboard-vendas.sql#L317) - CTE `totais_venda`, agrega as vendas antes do join
- [db/dashboard-vendas.sql:328](db/dashboard-vendas.sql#L328) - CTE `ingredientes_da_receita`, junta ingrediente e unidade

**As seis tabelas que ela centraliza:** `produtos`, `receitas`, `categorias`,
`vendas`, `receita_ingrediente` e `ingredientes`. Cada linha é um produto, e traz
de uma vez o que estava espalhado: categoria, receita de origem, tempo de preparo,
quantidade de ingredientes, preço, unidades vendidas, faturamento, data da última
venda e situação.

**Por que ela não é a mesma coisa que a `vw_vendas_detalhadas`:** aquela é uma
linha por **venda**, e mostra só o que passou na limpeza. Esta é uma linha por
**produto**, e mostra inclusive o que a outra esconde de propósito — o produto
fora de linha e o que nunca vendeu.

**O defeito que a CTE evita:** juntando `vendas` e `ingredientes` na mesma
consulta, cada venda era contada uma vez por ingrediente da receita, e um pão com
8 ingredientes aparecia com 8x o faturamento real. Por isso os totais de venda
são agregados numa CTE **antes** do join, e não com um `SUM` no join final.

**Prova:** teste 11 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql).
São 9 produtos no banco e **9 linhas** na view, mesmo com receitas de 4 a 7
ingredientes — é isso que mostra que não houve multiplicação. A Coxinha aparece
com R$ 1.888,00 (o mesmo do ranking) e a Empada aparece como "Fora de linha".

---

## DESENVOLVIMENTO WEB AVANÇADA

### Aparência do sistema (interface amigável e usável)
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - O SISTEMA POSSUI INTERFACE AMIGÁVEL, POSSUINDO USABILIDADE
PARA FACILITAR PARA QUE O USUÁRIO NÃO TENHA DE FICAR PROCURANDO AS TAREFAS
```
- [includes/header.php:57](includes/header.php#L57) - menu fixo no topo, em todas as páginas
- [includes/funcoes.php:66](includes/funcoes.php#L66) - o item do menu da página atual fica destacado
- [receitas.php:24](receitas.php#L24) - filtro por categoria em chips, um clique
- [includes/funcoes.php:158](includes/funcoes.php#L158) - os chips saem da TABELA de categorias, não das receitas carregadas
- [dashboard.php:49](dashboard.php#L49) - os 4 números mais importantes no topo, antes da tabela
- [dashboard.php:102](dashboard.php#L102) - o painel "Destaques": o pódio e a semana, sem precisar filtrar nada
- [dashboard.php:137](dashboard.php#L137) - busca e filtro na dashboard, no mesmo formato de chip do resto do site
- [dashboard.php:160](dashboard.php#L160) - a linha "66 vendas encontradas · página 1 de 7"
- [assets/css/style.css](assets/css/style.css) - paleta da padaria (mel, massa, crosta)

**O usuário não precisa procurar:** toda tarefa do sistema está a **um clique**
do menu, que é o mesmo em todas as páginas e acompanha a rolagem. Não existe
função escondida em submenu ou alcançável só por URL digitada.

**Outras decisões de usabilidade:** o menu mostra onde você está; o card inteiro
da receita é clicável, não só o título; toda tela sem resultado explica o motivo
em vez de ficar em branco; a dashboard exibe o estado de carregamento em vez de
parecer travada; e os controles ficam desabilitados enquanto a busca não volta,
para ninguém disparar duas consultas em cima da outra.

**A categoria nova aparece na hora, nos dois filtros:** tanto os chips da página
de receitas quanto os da dashboard saem agora da tabela `categorias`, e não do
que já tem receita ou venda. Antes, quem cadastrava uma categoria não a via em
lugar nenhum e achava que o cadastro tinha falhado. Clicar numa categoria ainda
vazia cai no aviso de "nenhum resultado", que já existia.

**Detalhe que só aparece usando:** o aviso de tela vazia muda de texto conforme o
motivo. Banco sem venda nenhuma é problema ("Nenhum dado registrado"); filtro que
não achou nada é uso normal ("Nenhuma venda com esse filtro. Tente outra
categoria ou limpe a busca") — [src/app.ts:721](src/app.ts#L721).

---

### Framework Bootstrap no layout (pelo menos 3 componentes)
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - USOU O FRAMEWORK BOOTSTRAP NO DESENVOLVIMENTO DO LAYOUT,
PELO MENOS 3 COMPONENTES
```
- [includes/header.php:32](includes/header.php#L32) - o CSS do Bootstrap 5.3 carregado via CDN
- [includes/header.php:51](includes/header.php#L51) - **Navbar**, com **Collapse** no botão de menu do celular
- [index.php:45](index.php#L45) - **Card** e **Badge** nos destaques
- [receitas.php:42](receitas.php#L42) - **Card** e **Badge** na listagem
- [receita-detalhe.php:26](receita-detalhe.php#L26) - **Badge** da categoria
- [contato.php:20](contato.php#L20) - **Form** (`form-control`, `form-label`) e **Button**
- [dashboard.php:22](dashboard.php#L22) - **Spinner** do carregamento
- [dashboard.php:30](dashboard.php#L30) - **Alert** do erro de conexão
- [dashboard.php:52](dashboard.php#L52) - **Card** das métricas
- [dashboard.php:138](dashboard.php#L138) - **Input group** da busca
- [dashboard.php:162](dashboard.php#L162) - **Table** responsiva das vendas
- [dashboard.php:184](dashboard.php#L184) - **Pagination** das páginas de venda
- [dashboard.php:105](dashboard.php#L105) - **Grid** (`row` + `col-lg`) dividindo o painel de destaques

**Contagem:** são 10 componentes distintos (Navbar, Collapse, Card, Badge, Form,
Button, Spinner, Alert, Table, Input group e Pagination), além do Grid usado em
todas as páginas. A rubrica pede no mínimo 3. Só na dashboard são 7 deles.

---

### Template para facilitar a manutenção e diminuir o número de arquivos
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - FEZ UM TEMPLATE PARA FACILITAR A MANUTENÇÃO
E DIMINUIR O NÚMERO DE ARQUIVOS?
```
- [includes/header.php](includes/header.php) - abertura do HTML, `<head>`, Bootstrap, fontes e a navbar (79 linhas)
- [includes/footer.php](includes/footer.php) - rodapé, script do Bootstrap e fechamento (30 linhas)
- [includes/admin.php](includes/admin.php) - mensagem, token e leitura de formulário das telas de cadastro (121 linhas)
- [includes/aviso.php](includes/aviso.php) - a faixa de mensagem verde ou vermelha
- [index.php](index.php) - qualquer página: dois `require` e o miolo no meio

**O número que vale a nota:** o template é reusado por **10 páginas**. Sem ele,
essas 109 linhas de `header` + `footer` estariam copiadas 10 vezes — **1.090
linhas**, e mudar um item do menu significaria editar 10 arquivos, esperando não
esquecer nenhum.

**Como uma página fica, na prática:**

```php
<?php require 'includes/header.php'; ?>
   ... só o conteúdo desta página ...
<?php require 'includes/footer.php'; ?>
```

**Três problemas que o template resolve sozinho, e que ninguém precisa lembrar:**

1. **O caminho certo em qualquer pasta** — [header.php:16](includes/header.php#L16).
   As telas de `admin/` estão uma pasta abaixo, então definem `$raiz = '../'`
   antes do `require`; as páginas da raiz não definem nada. O `$raiz ?? ''`
   resolve as duas situações sem `if` espalhado. Sem isso, o CSS e o logo
   quebravam só na administração — e só quando alguém abrisse.

2. **O menu sabendo onde você está** — [header.php:10](includes/header.php#L10)
   descobre a página atual e [funcoes.php:66](includes/funcoes.php#L66) marca o
   item correspondente. Cada página não precisa dizer quem é.

3. **O CSS nunca vindo velho do cache** — [header.php:47](includes/header.php#L47)
   acrescenta `?v=` com a data de modificação do arquivo. Custou um defeito
   descobrir isto: a barra de busca aparecia esticada na largura toda porque o
   Chrome reaproveitava a folha de estilo antiga depois de editada. Como o número
   muda sozinho a cada gravação, um F5 comum já basta.

**Um segundo nível de template, só para a administração:**
[admin.php](includes/admin.php) é incluído pelas **5 telas** de cadastro e
carrega o que todas precisam: guardar e recuperar mensagem entre páginas, gerar e
exigir o token do formulário, ler campo de POST. [aviso.php](includes/aviso.php)
desenha a faixa de mensagem nas **6 telas** que mostram retorno de operação.

**Por que isso não é só economia de linha:** o token de formulário está em
[admin.php:57](includes/admin.php#L57). Estando num arquivo só, é impossível uma
tela nova nascer sem proteção — ela herda ao incluir. Copiado em cinco lugares,
bastaria esquecer um para abrir o buraco, e ninguém perceberia até alguém
explorar.

**Como demonstrar em 10 segundos:** abra [includes/header.php](includes/header.php),
acrescente um item no menu, salve e dê F5 em qualquer página do site. O item
aparece nas 10 de uma vez.

---

### Estrutura do projeto e separação de arquivos
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - A ESTRUTURA DO PROJETO ESTÁ BEM DEFINIDA? COM OS
ARQUIVOS SEPARADOS PARA MELHORAR A MANUTENÇÃO?
```
| Pasta | O que mora ali | Por que separado |
|---|---|---|
| raiz | as 5 páginas do site (`index`, `receitas`, `receita-detalhe`, `contato`, `dashboard`) | é o que o visitante acessa |
| `admin/` | as 5 telas de administração | administração não se mistura com o site público |
| `includes/` | conexão, template, funções, regras dos CRUDs | tudo que é reaproveitado por mais de uma página |
| `db/` | scripts SQL e roteiro de testes | o banco versionado junto com o código |
| `src/` | TypeScript que você edita | fonte |
| `dist/` | JavaScript compilado | resultado do `tsc`, nunca editado à mão |
| `assets/` | CSS próprio e imagens | estilo separado do HTML |

**A regra que orienta a separação:** cada arquivo tem **um** motivo para mudar.
- [includes/conexao.php:11](includes/conexao.php#L11) - as credenciais existem em **um** lugar. Trocar de servidor é editar 4 linhas, não 12 arquivos.
- [includes/header.php](includes/header.php) e [includes/footer.php](includes/footer.php) - o HTML repetido de todas as páginas. Mudar o menu é mudar um arquivo.
- [includes/funcoes.php](includes/funcoes.php) - funções de processamento, sem nenhuma consulta a banco misturada.
- [includes/admin.php:23](includes/admin.php#L23) - mensagem, token e leitura de formulário. Sem ele, isso estaria copiado nas três telas de cadastro — e bastaria esquecer um para abrir um buraco.

**A separação que mais importa: tela não conhece regra.**
- [includes/crud-categorias.php](includes/crud-categorias.php), [includes/crud-ingredientes.php](includes/crud-ingredientes.php), [includes/crud-receitas.php](includes/crud-receitas.php) e [includes/crud-vendas.php](includes/crud-vendas.php) têm as regras.
- [admin/categorias.php:28](admin/categorias.php#L28) só chama a função e mostra a frase que voltou.

**Como demonstrar em 10 segundos:** abra [includes/crud-vendas.php:139](includes/crud-vendas.php#L139) e
[admin/vendas.php:31](admin/vendas.php#L31) lado a lado. A tela tem uma linha; a regra
de cancelamento inteira está no outro arquivo. É isso que a rubrica chama de
manutenção.

---

### Quatro CRUDs completos
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - 3 CRUDS: O ALUNO FINALIZOU OS 3 CRUDS COMPLETOS?
COM A INCLUSÃO, EXCLUSÃO, EDIÇÃO E CONSULTA?
```
A rubrica pede 3. **São 4**, e eles cobrem a cadeia de cadastro da padaria —
cada um depende do anterior:

| # | CRUD | Tela | Regras |
|---|---|---|---|
| 1 | **Categorias** | [admin/categorias.php](admin/categorias.php) | [includes/crud-categorias.php](includes/crud-categorias.php) |
| 2 | **Ingredientes** | [admin/ingredientes.php](admin/ingredientes.php) | [includes/crud-ingredientes.php](includes/crud-ingredientes.php) |
| 3 | **Receitas** | [admin/receitas.php](admin/receitas.php) | [includes/crud-receitas.php](includes/crud-receitas.php) |
| 4 | **Vendas** | [admin/vendas.php](admin/vendas.php) | [includes/crud-vendas.php](includes/crud-vendas.php) |

**As quatro operações, uma por uma:**

| Operação | Categorias | Ingredientes | Receitas | Vendas |
|---|---|---|---|---|
| **Inclusão** | [:48](includes/crud-categorias.php#L48) | [:48](includes/crud-ingredientes.php#L48) | [:51](includes/crud-receitas.php#L51) | [:65](includes/crud-vendas.php#L65) |
| **Consulta** | [:21](includes/crud-categorias.php#L21) | [:24](includes/crud-ingredientes.php#L24) | [:21](includes/crud-receitas.php#L21) | [:21](includes/crud-vendas.php#L21) |
| **Edição** | a mesma função da inclusão, com `id > 0` | idem | idem | idem |
| **Exclusão** | [:87](includes/crud-categorias.php#L87) | [:87](includes/crud-ingredientes.php#L87) | [:125](includes/crud-receitas.php#L125) | [:139](includes/crud-vendas.php#L139) |

**O relacionamento N:N, que é a parte difícil:** uma receita tem vários
ingredientes, cada um com sua quantidade, na tabela `receita_ingrediente` —
chave primária composta por `(id_receita, id_ingrediente)`.

- [includes/crud-receitas.php:211](includes/crud-receitas.php#L211) - acrescentar ingrediente na receita
- [includes/crud-receitas.php:242](includes/crud-receitas.php#L242) - o `catch` do erro 1062: **o banco** recusa o mesmo ingrediente duas vezes
- [includes/crud-receitas.php:255](includes/crud-receitas.php#L255) - remover, avisando se a receita ficou sem nenhum
- [includes/crud-receitas.php:197](includes/crud-receitas.php#L197) - o seletor só oferece o que ainda não está na receita
- [admin/receitas.php:49](admin/receitas.php#L49) - a tela repassando a ação

**Por que os ingredientes só aparecem ao editar, e não ao criar:** a linha em
`receita_ingrediente` precisa do id da receita, que só existe depois de gravada.
Por isso o botão diz "Cadastrar e escolher ingredientes" e a tela já abre a
receita nova em edição — [admin/receitas.php:42](admin/receitas.php#L42).

**A consequência visível:** receita sem ingrediente **não aparece no site**,
porque `filtrarReceitasValidas()` a descarta. A tela avisa isso em três lugares
em vez de deixar o usuário achar que o site quebrou: na mensagem ao cadastrar, no
quadro de ingredientes vazio e na lista de receitas.

**Por que inclusão e edição são a mesma função:** o formulário é idêntico nos dois
casos; muda só se o `id` vem zerado ou não. Duas funções separadas seriam duas
listas de validação para manter em sincronia — e uma delas ficaria para trás.

**Ponto que o professor costuma cobrar — o F5 depois de gravar:** toda gravação
termina em redirecionamento, e a mensagem viaja pela sessão
([includes/admin.php:41](includes/admin.php#L41)). Sem isso, apertar F5 depois de
cadastrar reenviava o formulário e criava a linha de novo.

**Segurança:** todo formulário leva um token
([includes/admin.php:57](includes/admin.php#L57)) conferido antes de tocar no banco
([includes/admin.php:78](includes/admin.php#L78)). Sem ele, bastava alguém abrir uma
página com `<img src="admin/vendas.php?acao=cancelar&id=5">` para cancelar venda
sem clique nenhum. Todas as consultas são preparadas, com o valor como parâmetro.

**Prova:** teste 14 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql) traz
o roteiro do que clicar em cada tela e a mensagem exata que tem que aparecer.

---

### Regras de exclusão com mensagem clara
```
RUBRICA DESENVOLVIMENTO WEB AVANÇADA - AS REGRAS DE EXCLUSÃO FORAM FEITAS, DANDO AO
USUÁRIO UMA MENSAGEM CLARA DO QUE OCORREU?
```
As três exclusões seguem regras **diferentes**, porque as três coisas têm relação
diferente com o histórico:

| Excluir | O que acontece | Mensagem que aparece |
|---|---|---|
| **Categoria** | apaga de verdade — mas o **banco recusa** se houver receita usando | *"Não dá para excluir "Salgado": 3 receitas usam esta categoria. Mude a categoria dessas receitas primeiro."* |
| **Venda** | é cancelada, não apagada | *"Venda #21 cancelada. O registro continua no banco para consulta, mas saiu do faturamento: de R$ 15.953,00 para R$ 15.369,00."* |
| **Ingrediente** | apaga do catálogo — mas o **banco recusa** se alguma receita usar | *"Não dá para excluir "Farinha de trigo": ele está em Bolo de Cenoura, Bolo de Fubá, Brioche Caseiro e mais 6. Tire o ingrediente dessas receitas primeiro."* |
| **Receita** | recusa se houver produto; sem produto, apaga junto com os vínculos | *"Não dá para excluir "Bolo de Laranja": o produto Bolo de Laranja (inteiro) nasce dela, e produto tem histórico de venda. Exclua o produto primeiro."* |

- [includes/crud-categorias.php:105](includes/crud-categorias.php#L105) - o `catch` do erro 1451 do banco
- [includes/crud-vendas.php:139](includes/crud-vendas.php#L139) - cancelar, medindo o faturamento antes e depois
- [includes/crud-ingredientes.php:104](includes/crud-ingredientes.php#L104) - o `catch` do 1451, que ainda pergunta ao banco **quais** receitas usam
- [includes/crud-receitas.php:125](includes/crud-receitas.php#L125) - recusa por produto, e transação para apagar os vínculos junto

**A frase que vale a nota:** a tela **não decide** se pode excluir. Ela tenta, o
banco recusa pela chave estrangeira, e só então o PHP conta quantas receitas
estavam segurando para escrever o motivo. Quem apagar pelo DBeaver, sem passar
por tela nenhuma, esbarra na mesma trava.

**Por que a mensagem da venda mostra dois valores:** *"de R$ 15.953,00 para
R$ 15.369,00"* prova que a exclusão mexeu no sistema inteiro, e não só apagou uma
linha de tabela. Abra a dashboard e dê F5: o número mudou lá também.

**Prova:** teste 13 de [db/testes-demonstracao.sql](db/testes-demonstracao.sql).
O 13A tenta apagar "Salgado" direto no SQL e recebe o erro 1451; o 13D tira a
Coxinha de linha e mostra o faturamento **não** mudando; o 13E cancela a venda 21
e mostra R$ 15.953,00 virando R$ 15.369,00 com o registro ainda no banco.

---

## ARQUIVOS DE APOIO

- [../README.md](../README.md) - passo a passo de instalação e execução do projeto
- [db/padaria.sql](db/padaria.sql) - cria as 4 tabelas originais (receitas, categorias, ingredientes, N:N)
- [db/dados-teste-9-receitas.sql](db/dados-teste-9-receitas.sql) - popula com as 9 receitas
- [db/dashboard-vendas.sql](db/dashboard-vendas.sql) - tabelas de venda, triggers, views, função e procedures
- [db/dados-historico-vendas.sql](db/dados-historico-vendas.sql) - as duas semanas anteriores, para o recorte por período ter o que cortar
- [db/testes-demonstracao.sql](db/testes-demonstracao.sql) - roteiro de testes para a apresentação
- [testes/teste-logica.js](testes/teste-logica.js) - 39 conferências da lógica da dashboard (`npm test`)
- [testes/teste-navegador.js](testes/teste-navegador.js) - 26 conferências clicando na dashboard (`npm run test:tela`)
- [admin/index.php](admin/index.php) - painel da administração, com os atalhos e a ordem de cadastro
- [includes/admin.php](includes/admin.php) - mensagem, token e leitura de formulário, compartilhados pelos CRUDs

### O teste automatizado

```
npm test
```

Roda `tsc` e depois as 39 conferências. Os valores esperados estão **escritos à
mão**, conferidos com `SELECT` no banco — o teste não repete a fórmula do código,
senão os dois lados poderiam errar igual e ele passaria feliz.

| Bloco | O que cobre | Precisa de servidor? |
|---|---|---|
| TESTE 1 | ranking e recorte sobre os dados reais da API | sim (Apache + MySQL) |
| TESTE 2 | o `filter` isolando a janela de 7 dias | não |
| TESTE 3 | base vazia, data inválida, valor não numérico | não |
| TESTE 4 | o `map` formatando em moeda local | não |

Sem Apache ou MySQL, o TESTE 1 se pula sozinho e avisa; os outros três rodam
normalmente.

### Números para conferir na hora da apresentação

> Dois cadastros de teste ainda estão no banco e aparecem na tela: a categoria
> **"lindo"** (vira o chip `LINDO (0)` na dashboard) e a receita **"buter"** (na
> página de Receitas). Apagando as duas pela administração, os valores marcados
> com ¹ passam para o número entre parênteses.

| O quê | Valor esperado |
|---|---|
| Vendas na tabela bruta | 67 |
| Vendas na `vw_vendas_detalhadas` | 66 (a venda #23, cancelada, some) |
| Vendas canceladas | 1 (a #23). Se der **0**, alguém reativou: os números todos mudam |
| Faturamento total | R$ 15.953,00 |
| Unidades vendidas | 1462 |
| Ticket médio | R$ 241,71 |
| Campeão de vendas | Coxinha de Frango, R$ 4.600,00 |
| Páginas na dashboard | 7 (66 linhas, 10 por página) |
| Top 3 do pódio | Coxinha R$ 4.600,00 · Pão de Mel R$ 2.992,50 · Pão de Fermentação R$ 2.952,00 |
| Última semana de movimento | 17/08 a 23/08, R$ 6.376,00, **+23,2%** |
| Semana anterior (só no cálculo) | R$ 5.175,50 |
| Filtro "Bolo" | 12 vendas, R$ 1.850,00 |
| Busca "coxinha" | 12 vendas, R$ 4.600,00 |
| Linhas na `vw_painel_produtos` | 9 (uma por produto) |
| Categorias cadastradas | 5¹ (4) |
| Receitas cadastradas | 10¹ (9, todas com ingredientes) |
| Ingredientes no catálogo | 22 |
| Excluir "Farinha de trigo" | recusado: está em 9 receitas |
| Receita sem ingrediente | não aparece no site |
| Excluir "Salgado" | recusado: 3 receitas usam |
| Cancelar a venda #21 | R$ 15.953,00 → R$ 15.369,00 |
| `grep -c "\bany\b" src/*.ts` | 0 nos dois arquivos |
| `npm test` | TUDO PASSOU (39 conferências) |
| `npm run test:tela` | TUDO PASSOU NA TELA (26 conferências) |
