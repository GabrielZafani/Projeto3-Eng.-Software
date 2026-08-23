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
-- TESTE 1 — AS VIEWS ANALÍTICAS LIMPAM OS DADOS?
-- ============================================================
-- Esperado: 22 linhas brutas viram 21 limpas.
-- A linha que some é a venda do produto 9 (Empada), que está
-- marcado como inativo — a CTE "produtos_ativos" descarta.
SELECT
    (SELECT COUNT(*) FROM vendas)               AS linhas_brutas,
    (SELECT COUNT(*) FROM vw_vendas_detalhadas) AS linhas_limpas;


-- Os dados consolidados, prontos para a API (JOIN de 4 tabelas):
SELECT * FROM vw_vendas_detalhadas ORDER BY venda_id;


-- ============================================================
-- TESTE 2 — VIEW DE FATURAMENTO POR CATEGORIA
-- ============================================================
-- Esperado: 4 categorias, Salgado em primeiro com ~40%.
-- (a soma dos percentuais dá 100,01 por causa do arredondamento
--  de cada linha pelo ROUND — é esperado, não é erro de conta)
SELECT * FROM vw_faturamento_categoria;


-- ============================================================
-- TESTE 3 — VIEW DE RANKING (window function ROW_NUMBER)
-- ============================================================
-- Esperado: posição 1 = Coxinha de Frango, com R$ 1.888,00.
SELECT * FROM vw_ranking_produtos;


-- ============================================================
-- TESTE 4 — A TRIGGER BEFORE UPDATE CORRIGE VALOR NEGATIVO?
-- ============================================================
-- Passo 1: veja o valor atual (deve ser 12).
SELECT id, quantidade AS antes FROM vendas WHERE id = 1;

-- Passo 2: tente gravar um número NEGATIVO.
UPDATE vendas SET quantidade = -99 WHERE id = 1;

-- Passo 3: confira — gravou 99, e não -99. A trigger corrigiu.
SELECT id, quantidade AS depois_do_negativo FROM vendas WHERE id = 1;

-- Passo 4: tente gravar ZERO.
UPDATE vendas SET quantidade = 0 WHERE id = 1;

-- Passo 5: confira — virou 1, porque venda de zero unidade não existe.
SELECT id, quantidade AS depois_do_zero FROM vendas WHERE id = 1;

-- Passo 6: DESFAZER (volta ao valor original).
UPDATE vendas SET quantidade = 12 WHERE id = 1;
SELECT id, quantidade AS restaurado FROM vendas WHERE id = 1;


-- ============================================================
-- TESTE 5 — A TRIGGER TAMBÉM PROTEGE O PREÇO?
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
-- TESTE 5B — E AS TRIGGERS DE CADASTRO (BEFORE INSERT)?
-- ============================================================
-- As triggers BEFORE UPDATE (testes 4 e 5) protegem quem EDITA.
-- Estas protegem quem CADASTRA uma linha nova.

-- Passo 1: tenta cadastrar uma venda com quantidade NEGATIVA.
INSERT INTO vendas (id_produto, quantidade, data_venda)
VALUES (1, -77, '2026-08-23');

-- Passo 2: confira — gravou 77, e não -77.
SELECT id, quantidade AS gravado FROM vendas ORDER BY id DESC LIMIT 1;

-- Passo 3: tenta cadastrar um produto com preço NEGATIVO.
INSERT INTO produtos (nome, valor_unitario, id_receita, ativo)
VALUES ('PRODUTO DE TESTE', -25.00, 1, FALSE);

-- Passo 4: confira — gravou 25.00.
SELECT id, nome, valor_unitario AS gravado FROM produtos ORDER BY id DESC LIMIT 1;

-- Passo 5: DESFAZER (apaga as duas linhas de teste).
DELETE FROM vendas   WHERE quantidade = 77 AND id_produto = 1 AND data_venda = '2026-08-23';
DELETE FROM produtos WHERE nome = 'PRODUTO DE TESTE';

-- Passo 6: confirme que voltou ao normal (22 vendas, 9 produtos, 21 na view).
SELECT (SELECT COUNT(*) FROM vendas)   AS vendas,
       (SELECT COUNT(*) FROM produtos) AS produtos,
       (SELECT COUNT(*) FROM vw_vendas_detalhadas) AS na_view;


-- ============================================================
-- TESTE 6 — O REDUCE DO TYPESCRIPT BATE COM O BANCO?
-- ============================================================
-- Rode esta consulta e compare com os cards da dashboard aberta em
-- http://localhost/pao-de-mel/dashboard.php
--
-- Esperado:
--   faturamento_total .... 6281.00  -> card "Faturamento total"
--   unidades_vendidas .... 577      -> card "Unidades vendidas"
--   ticket_medio ......... 299.10   -> card "Ticket médio"
SELECT
    SUM(quantidade * valor_unitario)          AS faturamento_total,
    SUM(quantidade)                           AS unidades_vendidas,
    ROUND(AVG(quantidade * valor_unitario), 2) AS ticket_medio,
    COUNT(*)                                  AS total_de_vendas
FROM vw_vendas_detalhadas;


-- ============================================================
-- TESTE 7 — EDGE CASE: E SE O BANCO ESTIVER VAZIO?
-- ============================================================
-- Passo 1: desativa todos os produtos (a view fica sem nenhuma linha).
UPDATE produtos SET ativo = FALSE;

-- Passo 2: confirme que a view zerou.
SELECT COUNT(*) AS deve_ser_zero FROM vw_vendas_detalhadas;

-- Passo 3: AGORA RECARREGUE A DASHBOARD NO NAVEGADOR (F5).
--   Esperado: aviso "Nenhum dado registrado", cards em R$ 0,00
--   e a tabela com a mensagem — nada de "NaN" na tela.

-- Passo 4: DESFAZER (o produto 9 continua inativo de propósito).
UPDATE produtos SET ativo = TRUE WHERE id <> 9;

-- Passo 5: confirme que voltou para 21 e recarregue a dashboard.
SELECT COUNT(*) AS deve_ser_21 FROM vw_vendas_detalhadas;


-- ============================================================
-- TESTE 8 — AS CTEs EXISTEM MESMO? (mostrar o código ao professor)
-- ============================================================
-- Exibe o SQL completo da view, com a cláusula WITH das CTEs.
SHOW CREATE VIEW vw_vendas_detalhadas;

-- Lista as triggers instaladas no banco:
SHOW TRIGGERS FROM padaria;
