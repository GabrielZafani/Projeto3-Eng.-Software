# Guia de Rubricas — Pão de Mel

---

## DESENVOLVIMENTO WEB MODERNA

### Layout dinâmico com PHP
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CRIAR UM LAYOUT MINIMAMENTE AGRADÁVEL E DINÂMICO COM PHP
```
- `includes/header.php` — menu ativo calculado via PHP
- `includes/footer.php` — ano do rodapé com `date('Y')`

---

### Template com PHP
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP
```
- `includes/header.php` — declaração do template
- `index.php`, `receitas.php`, `receita-detalhe.php`, `contato.php` — `require 'includes/header.php'`

---

### Bootstrap (3+ componentes)
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE BOOTSTRAP E PELO MENOS 3 COMPONENTES
```
- `includes/header.php` — Navbar
- `index.php` — Card e Badge
- `receitas.php` — Card e Badge
- `receita-detalhe.php` — Badge
- `contato.php` — Form

---

### Conexão com Banco de Dados
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CONEXÃO COM BANCO DE DADOS
```
- `includes/conexao.php` — conexão mysqli com o banco
- `includes/dados-receitas.php` — require da conexão
- `includes/funcoes.php` — função `buscarReceitasDoBanco()`

---

### Dados recuperados do banco e demonstrados na tela
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - DADOS RECUPERADOS DO BANCO E DEMONSTRADOS NA TELA
```
- `includes/dados-receitas.php` — fluxo banco → array → tela
- `includes/funcoes.php` — queries SELECT dentro de `buscarReceitasDoBanco()`

---

### Comandos PHP (IF, WHILE, FOREACH)
```
RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP
```
- `index.php` — `IF` (array vazio) e `FOREACH` (cards de destaque)
- `receitas.php` — `IF` (sem resultados) e `FOREACH` (cards filtrados)
- `receita-detalhe.php` — `IF` (receita existe) e `FOREACH` (ingredientes e passos)
- `includes/funcoes.php` — `WHILE` nos dois `fetch_assoc()` das queries

---
---

## TECH FORGE

### Armazenamento Estruturado com Arrays
```
RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS
```
- `includes/dados-receitas.php` — onde `$receitas` nasce e é preenchido pelo banco
- `index.php`, `receitas.php`, `receita-detalhe.php` — require do arquivo que cria o array

---

### Modularização com Funções de Processamento
```
RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO
```
- `includes/funcoes.php` — funções `formatarTempoPreparo()`, `classeNavAtiva()`, `obterDestaques()`, `formatarIngrediente()`, `modoPreparoParaPassos()`
- `includes/header.php`, `index.php`, `receitas.php`, `receita-detalhe.php` — require_once de funcoes.php

---

### Fluxo de Dados (Parâmetros e Retorno)
```
RUBRICA TECH FORGE - FLUXO DE DADOS
```
- `includes/funcoes.php` — toda função do arquivo recebe dados por parâmetro e devolve com `return`, sem variável global
- `receita-detalhe.php` — comentário explicando a chamada de `buscarReceitaPorId()`

---

### Lógica de Pesquisa ou Filtro
```
RUBRICA TECH FORGE - LÓGICA DE PESQUISA OU FILTRO
```
- `includes/funcoes.php` — `filtrarPorCategoria()` e `buscarReceitaPorId()`
- `receitas.php` — chamada do filtro com a categoria vinda da URL

---

### Validação de Regras de Negócio com Condicionais
```
RUBRICA TECH FORGE - VALIDAÇÃO DE REGRAS DE NEGÓCIO COM CONDICIONAIS
```
- `includes/funcoes.php` — `receitaValida()` com `if` barrando tempo ≤ 0 e ingredientes vazios
- `index.php`, `receitas.php`, `receita-detalhe.php` — `filtrarReceitasValidas()` chamada antes de qualquer exibição
