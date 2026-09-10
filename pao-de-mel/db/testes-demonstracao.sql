-- ============================================================
-- ROTEIRO DE TESTES / DEMONSTRAÇÃO
-- ============================================================
-- Abra este arquivo no DBeaver com o banco "padaria" selecionado.
--
-- IMPORTANTE: aqui NÃO use Alt+X (que roda o arquivo inteiro).
-- Use Ctrl+Enter para executar UM comando por vez, acompanhando
-- o resultado de cada teste.
--
-- Todos os testes que alteram dados já trazem a linha que desfaz
-- a alteração logo em seguida.
-- ============================================================

USE padaria;


-- ============================================================
-- TESTE 1 - AS VIEWS ANALÍTICAS LIMPAM OS DADOS?
-- ============================================================
-- Esperado: 23 linhas brutas viram 22 limpas.
-- A linha que some é a venda CANCELADA, descartada pela CTE
-- "vendas_limpas". O registro continua no banco: some do faturamento,
-- não do histórico.
SELECT
    (SELECT COUNT(*) FROM vendas)               AS linhas_brutas,
    (SELECT COUNT(*) FROM vw_vendas_detalhadas) AS linhas_limpas;

-- Qual é a linha que a CTE descartou:
SELECT id, id_produto, quantidade, data_venda, cancelada
FROM vendas WHERE cancelada = TRUE;

-- E o que NÃO é descartado, de propósito: a venda do produto fora de
-- linha (Empada, produto 9) continua contando. Faturamento de mês
-- fechado não muda porque alguém aposentou um produto hoje.
SELECT p.nome, p.ativo AS produto_em_linha, v.quantidade, (v.quantidade * p.valor_unitario) AS conta_no_faturamento
FROM vendas v INNER JOIN produtos p ON p.id = v.id_produto
WHERE p.ativo = FALSE AND v.cancelada = FALSE;


-- Os dados consolidados, prontos para a API (JOIN de 4 tabelas):
SELECT * FROM vw_vendas_detalhadas ORDER BY venda_id;


-- ============================================================
-- TESTE 2 - VIEW DE FATURAMENTO POR CATEGORIA
-- ============================================================
-- Esperado: 4 categorias, Salgado em primeiro com ~40%.
-- (a soma dos percentuais dá 100,01 por causa do arredondamento
--  de cada linha pelo ROUND - é esperado, não é erro de conta)
SELECT * FROM vw_faturamento_categoria;


-- ============================================================
-- TESTE 3 - VIEW DE RANKING (window function ROW_NUMBER)
-- ============================================================
-- Esperado: posição 1 = Coxinha de Frango, com R$ 1.888,00.
SELECT * FROM vw_ranking_produtos;


-- ============================================================
-- TESTE 4 - A TRIGGER BEFORE UPDATE CORRIGE VALOR NEGATIVO?
-- ============================================================
-- Passo 1: veja o valor atual (deve ser 12).
SELECT id, quantidade AS antes FROM vendas WHERE id = 1;

-- Passo 2: tente gravar um número NEGATIVO.
UPDATE vendas SET quantidade = -99 WHERE id = 1;

-- Passo 3: confira - gravou 99, e não -99. A trigger corrigiu.
SELECT id, quantidade AS depois_do_negativo FROM vendas WHERE id = 1;

-- Passo 4: tente gravar ZERO.
UPDATE vendas SET quantidade = 0 WHERE id = 1;

-- Passo 5: confira - virou 1, porque venda de zero unidade não existe.
SELECT id, quantidade AS depois_do_zero FROM vendas WHERE id = 1;

-- Passo 6: DESFAZER (volta ao valor original).
UPDATE vendas SET quantidade = 12 WHERE id = 1;
SELECT id, quantidade AS restaurado FROM vendas WHERE id = 1;


-- ============================================================
-- TESTE 5 - A TRIGGER TAMBÉM PROTEGE O PREÇO?
-- ============================================================
SELECT id, nome, valor_unitario AS antes FROM produtos WHERE id = 8;

-- Tenta gravar preço negativo:
UPDATE produtos SET valor_unitario = -50.00 WHERE id = 8;

-- Esperado: 50.00 (positivo).
SELECT id, nome, valor_unitario AS depois FROM produtos WHERE id = 8;

-- DESFAZER:
UPDATE produtos SET valor_unitario = 8.00 WHERE id = 8;
SELECT id, nome, valor_unitario AS restaurado FROM produtos WHERE id = 8;


-- ============================================================
-- TESTE 5B - E AS TRIGGERS DE CADASTRO (BEFORE INSERT)?
-- ============================================================
-- As triggers BEFORE UPDATE (testes 4 e 5) protegem quem EDITA.
-- Estas protegem quem CADASTRA uma linha nova.

-- Passo 1: tenta cadastrar uma venda com quantidade NEGATIVA.
INSERT INTO vendas (id_produto, quantidade, data_venda)
VALUES (1, -77, '2026-08-23');

-- Passo 2: confira - gravou 77, e não -77.
SELECT id, quantidade AS gravado FROM vendas ORDER BY id DESC LIMIT 1;

-- Passo 3: tenta cadastrar um produto com preço NEGATIVO.
INSERT INTO produtos (nome, valor_unitario, id_receita, ativo)
VALUES ('PRODUTO DE TESTE', -25.00, 1, FALSE);

-- Passo 4: confira - gravou 25.00.
SELECT id, nome, valor_unitario AS gravado FROM produtos ORDER BY id DESC LIMIT 1;

-- Passo 5: DESFAZER (apaga as duas linhas de teste).
DELETE FROM vendas   WHERE quantidade = 77 AND id_produto = 1 AND data_venda = '2026-08-23';
DELETE FROM produtos WHERE nome = 'PRODUTO DE TESTE';

-- Passo 6: confirme que voltou ao normal (23 vendas, 9 produtos, 22 na view).
SELECT (SELECT COUNT(*) FROM vendas)   AS vendas,
       (SELECT COUNT(*) FROM produtos) AS produtos,
       (SELECT COUNT(*) FROM vw_vendas_detalhadas) AS na_view;


-- ============================================================
-- TESTE 6 - O REDUCE DO TYPESCRIPT BATE COM O BANCO?
-- ============================================================
-- Rode esta consulta e compare com os cards da dashboard aberta em
-- http://localhost/pao-de-mel/dashboard.php
--
-- Esperado:
--   faturamento_total .... 6376.00  -> card "Faturamento total"
--   unidades_vendidas .... 587      -> card "Unidades vendidas"
--   ticket_medio ......... 289.82   -> card "Ticket médio"
SELECT
    SUM(quantidade * valor_unitario)          AS faturamento_total,
    SUM(quantidade)                           AS unidades_vendidas,
    ROUND(AVG(quantidade * valor_unitario), 2) AS ticket_medio,
    COUNT(*)                                  AS total_de_vendas
FROM vw_vendas_detalhadas;


-- ============================================================
-- TESTE 7 - EDGE CASE: E SE O BANCO ESTIVER VAZIO?
-- ============================================================
-- ATENÇÃO: desativar produtos NÃO esvazia mais a view - as vendas de
-- produto fora de linha continuam contando de propósito. Quem esvazia
-- agora é o cancelamento.

-- Passo 1: cancela todas as vendas (a view fica sem nenhuma linha).
UPDATE vendas SET cancelada = TRUE;

-- Passo 2: confirme que a view zerou.
SELECT COUNT(*) AS deve_ser_zero FROM vw_vendas_detalhadas;

-- Passo 3: AGORA RECARREGUE A DASHBOARD NO NAVEGADOR (F5).
--   Esperado: aviso "Nenhum dado registrado", cards em R$ 0,00
--   e a tabela com a mensagem - nada de "NaN" na tela.

-- Passo 4: DESFAZER. A venda 23 fica cancelada de propósito: é ela que
-- faz a View devolver 22 de 23 no teste 1.
UPDATE vendas SET cancelada = FALSE WHERE id <> 23;

-- Passo 5: confirme que voltou para 22 e recarregue a dashboard.
SELECT COUNT(*) AS deve_ser_22 FROM vw_vendas_detalhadas;


-- ============================================================
-- TESTE 8 - AS CTEs EXISTEM MESMO? (mostrar o código ao professor)
-- ============================================================
-- Exibe o SQL completo da view, com a cláusula WITH das CTEs.
SHOW CREATE VIEW vw_vendas_detalhadas;

-- Lista as triggers instaladas no banco:
SHOW TRIGGERS FROM padaria;


-- ============================================================
-- TESTE 9 - A FUNÇÃO DO BANCO (fn_faturamento_periodo)
-- ============================================================
-- Uma chamada só substitui o join das 4 tabelas + os filtros de
-- limpeza + o SUM. É esse encadeamento que a função guarda.
--
-- Esperado:
--   periodo_inteiro ....... 6376.00  (mesmo número dos cards)
--   so_dia_23 ............. 1562.50
--   periodo_sem_venda ..... 0.00     (e NÃO "NULL")
SELECT
    fn_faturamento_periodo(NULL, NULL)                 AS periodo_inteiro,
    fn_faturamento_periodo('2026-08-23', '2026-08-23') AS so_dia_23,
    fn_faturamento_periodo('2030-01-01', '2030-12-31') AS periodo_sem_venda;

-- Mostre ao professor o código da função:
SHOW CREATE FUNCTION fn_faturamento_periodo;


-- ============================================================
-- TESTE 10 - A PROCEDURE DE BUSCA, FILTRO E PAGINAÇÃO
-- ============================================================
-- É esta chamada que a api.php faz. Nenhum SELECT é montado no PHP.

-- 10A. Página 1, sem filtro. Esperado: 10 linhas, @total = 22.
CALL sp_vendas_buscar('', 'todas', 1, 10, @total, @fat);
SELECT @total AS total_linhas, @fat AS faturamento_da_funcao;

-- 10B. Página 3. Esperado: sobram 2 linhas (22 = 10 + 10 + 2).
CALL sp_vendas_buscar('', 'todas', 3, 10, @total, @fat);

-- 10C. Filtro por categoria. Esperado: 4 linhas de Bolo.
CALL sp_vendas_buscar('', 'Bolo', 1, 10, @total, @fat);
SELECT @total AS vendas_de_bolo;

-- 10D. Busca por texto. Esperado: as 4 vendas de Coxinha.
CALL sp_vendas_buscar('Coxinha', 'todas', 1, 10, @total, @fat);
SELECT @total AS vendas_de_coxinha;

-- 10E. Tamanho 0 = "traz tudo". É assim que a dashboard calcula os
-- cards com o reduce sobre o filtro inteiro, para o faturamento não
-- mudar quando o usuário vira a página. Esperado: 22 linhas.
CALL sp_vendas_buscar('', 'todas', 1, 0, @total, @fat);

-- 10F. TESTE DE INVASÃO: tentativa de injeção de SQL no campo de busca.
-- Esperado: 0 linhas (o texto foi PROCURADO, não executado) e a tabela
-- de vendas intacta logo abaixo. Se aparecessem as 22 linhas, seria
-- sinal de que o filtro virou comando.
CALL sp_vendas_buscar('\' OR 1=1 -- ', 'todas', 1, 10, @total, @fat);
SELECT @total AS deve_ser_zero;
SELECT COUNT(*) AS vendas_intactas FROM vendas;

-- 10G. Página inválida não derruba a API. Esperado: volta a página 1.
CALL sp_vendas_buscar('', 'todas', 0, 10, @total, @fat);

-- Mostre ao professor o código da procedure:
SHOW CREATE PROCEDURE sp_vendas_buscar;


-- ============================================================
-- TESTE 11 - A VIEW QUE CENTRALIZA 6 TABELAS
-- ============================================================
-- vw_painel_produtos junta produtos, receitas, categorias, vendas,
-- receita_ingrediente e ingredientes numa linha por produto.
--
-- Esperado: 9 linhas (uma por produto), a Empada aparecendo como
-- "Fora de linha" e a Coxinha com faturamento 1888.00.
SELECT produto_id, produto, categoria, tempo_preparo_min, qtd_ingredientes,
       preco, unidades_vendidas, faturamento, situacao
FROM vw_painel_produtos
ORDER BY faturamento DESC;

-- A prova de que a view NÃO multiplicou linha por ingrediente:
-- 9 produtos no banco têm que dar 9 linhas na view, mesmo com
-- receitas de 4 a 7 ingredientes cada.
SELECT
    (SELECT COUNT(*) FROM produtos)           AS produtos_no_banco,
    (SELECT COUNT(*) FROM vw_painel_produtos) AS linhas_na_view;

-- E a informação que só existe nesta view: o produto fora de linha,
-- que a vw_vendas_detalhadas esconde de propósito.
SELECT produto, situacao, unidades_vendidas, faturamento
FROM vw_painel_produtos
WHERE situacao = 'Fora de linha';

-- Mostre ao professor o código da view:
SHOW CREATE VIEW vw_painel_produtos;


-- ============================================================
-- TESTE 12 - O QUE ESTÁ INSTALADO NO BANCO
-- ============================================================
-- Lista, de uma vez, tudo que as rubricas pedem.
SELECT ROUTINE_TYPE AS tipo, ROUTINE_NAME AS nome
FROM information_schema.ROUTINES
WHERE ROUTINE_SCHEMA = 'padaria'
ORDER BY ROUTINE_TYPE, ROUTINE_NAME;

SELECT TABLE_NAME AS views_do_sistema
FROM information_schema.VIEWS
WHERE TABLE_SCHEMA = 'padaria'
ORDER BY TABLE_NAME;

SHOW TRIGGERS FROM padaria;


-- ============================================================
-- TESTE 13 - AS REGRAS DE EXCLUSÃO VALEM SEM A TELA?
-- ============================================================
-- A rubrica pede mensagem clara ao usuário. A mensagem é escrita em PHP,
-- mas quem RECUSA a exclusão é o banco. Este teste prova isso: tudo
-- abaixo roda direto no MariaDB, sem passar por nenhuma tela.

-- 13A. CATEGORIA EM USO - o banco recusa.
-- Esperado: erro 1451, "Cannot delete or update a parent row".
-- É esta recusa que a tela traduz para "3 receitas usam esta categoria".
DELETE FROM categorias WHERE nome = 'Salgado';

-- Confirme que ela continua lá:
SELECT id, nome FROM categorias WHERE nome = 'Salgado';

-- E quantas receitas a estavam segurando:
SELECT COUNT(*) AS receitas_que_usam
FROM receitas r INNER JOIN categorias c ON c.id = r.id_categoria
WHERE c.nome = 'Salgado';


-- 13B. CATEGORIA SEM USO - pode apagar de verdade.
INSERT INTO categorias (nome) VALUES ('Categoria de Teste');
DELETE FROM categorias WHERE nome = 'Categoria de Teste';
SELECT COUNT(*) AS deve_ser_zero FROM categorias WHERE nome = 'Categoria de Teste';


-- 13C. NOME REPETIDO - o banco recusa.
-- Esperado: erro 1062, "Duplicate entry". A tela mostra
-- "Já existe uma categoria chamada X", mas a trava é esta.
INSERT INTO categorias (nome) VALUES ('Salgado');


-- 13D. PRODUTO FORA DE LINHA MANTÉM O HISTÓRICO.
-- Passo 1: faturamento antes (6376.00).
SELECT fn_faturamento_periodo(NULL, NULL) AS antes;

-- Passo 2: tira a Coxinha de linha - é o que o botão "Excluir" da tela faz.
UPDATE produtos SET ativo = FALSE WHERE nome = 'Coxinha de Frango (un.)';

-- Passo 3: o faturamento NÃO muda. As vendas dela continuam contando.
SELECT fn_faturamento_periodo(NULL, NULL) AS depois_de_tirar_de_linha;

-- Passo 4: mas ela não aparece mais para venda nova (a tela lê daqui):
SELECT COUNT(*) AS deve_ser_zero FROM produtos
WHERE ativo = TRUE AND nome = 'Coxinha de Frango (un.)';

-- Passo 5: DESFAZER.
UPDATE produtos SET ativo = TRUE WHERE nome = 'Coxinha de Frango (un.)';


-- 13E. VENDA CANCELADA SAI DO FATURAMENTO, NÃO DO BANCO.
-- Passo 1: antes (6376.00).
SELECT fn_faturamento_periodo(NULL, NULL) AS antes;

-- Passo 2: cancela a venda 21 (73 coxinhas x 8,00 = 584,00).
UPDATE vendas SET cancelada = TRUE WHERE id = 21;

-- Passo 3: faturamento caiu para 5792.00, mas a linha continua no banco.
SELECT fn_faturamento_periodo(NULL, NULL) AS depois,
       (SELECT COUNT(*) FROM vendas WHERE id = 21) AS registro_ainda_existe;

-- Passo 4: DESFAZER.
UPDATE vendas SET cancelada = FALSE WHERE id = 21;
SELECT fn_faturamento_periodo(NULL, NULL) AS restaurado;


-- ============================================================
-- TESTE 14 - RODAR OS 3 CRUDs NA TELA
-- ============================================================
-- Este não é SQL: é o roteiro do que clicar em
-- http://localhost/pao-de-mel/admin/index.php
--
-- CATEGORIAS
--   1. Cadastre "Tortas".              -> "Categoria "Tortas" cadastrada."
--   2. Cadastre "Tortas" de novo.      -> "Já existe uma categoria chamada "Tortas"."
--   3. Deixe o nome em branco.         -> "O nome da categoria não pode ficar em branco."
--   4. Edite "Tortas" para "Tortas Doces".
--   5. Excluir "Salgado": o botão está desabilitado (há receitas usando).
--   6. Exclua "Tortas Doces".          -> "Categoria "Tortas Doces" excluída."
--
-- PRODUTOS
--   1. Cadastre com preço -5,00.       -> "O preço precisa ser maior que zero."
--   2. Cadastre com preço 12,50.       -> "cadastrado e já disponível no caixa."
--   3. Clique em Excluir.              -> "saiu de linha... continuam contando no faturamento."
--   4. Confira que ele sumiu do seletor da tela de Vendas.
--   5. Clique em Reativar.
--
-- VENDAS
--   1. Quantidade 0.                   -> "A quantidade precisa ser pelo menos 1."
--   2. Data de 2030.                   -> "Não dá para registrar uma venda com data futura."
--   3. Venda válida.                   -> "Venda #N registrada. O faturamento agora é R$ ..."
--   4. Clique em Excluir numa venda.   -> "cancelada... de R$ 6.376,00 para R$ ..."
--   5. Abra a dashboard e dê F5: o número mudou lá também.
--   6. Clique em Reativar para desfazer.
