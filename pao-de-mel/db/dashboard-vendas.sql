-- ============================================================
-- MÓDULO DASHBOARD — Vendas da Padaria Pão de Mel
-- ============================================================
-- Este script complementa padaria.sql. Ele acrescenta a parte
-- financeira do sistema (produtos vendidos e vendas realizadas),
-- as VIEWS ANALÍTICAS com CTE e as TRIGGERS de padronização.
--
-- Pode rodar quantas vezes quiser: tudo é recriado do zero.
-- ============================================================

USE padaria;

-- Remove na ordem certa (dependências primeiro)
DROP TRIGGER IF EXISTS trg_vendas_valor_positivo;
DROP TRIGGER IF EXISTS trg_produtos_valor_positivo;
DROP TRIGGER IF EXISTS trg_vendas_valor_positivo_ins;
DROP TRIGGER IF EXISTS trg_produtos_valor_positivo_ins;
DROP VIEW    IF EXISTS vw_ranking_produtos;
DROP VIEW    IF EXISTS vw_faturamento_categoria;
DROP VIEW    IF EXISTS vw_vendas_detalhadas;
DROP TABLE   IF EXISTS vendas;
DROP TABLE   IF EXISTS produtos;


-- ============================================================
-- 1. TABELAS DE DADOS BRUTOS
-- ============================================================

-- Cada receita da padaria vira um produto à venda no balcão.
CREATE TABLE produtos (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(100)   NOT NULL,
    valor_unitario DECIMAL(10, 2) NOT NULL,   -- preço de balcão
    id_receita     INT            NOT NULL,
    ativo          BOOLEAN        NOT NULL DEFAULT TRUE,
    FOREIGN KEY (id_receita) REFERENCES receitas(id)
);

-- Cada linha é uma venda no caixa: qual produto e quantas unidades.
CREATE TABLE vendas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    id_produto INT  NOT NULL,
    quantidade INT  NOT NULL,
    data_venda DATE NOT NULL,
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
);


-- ============================================================
-- 2. TRIGGERS — PADRONIZAÇÃO DE VALORES POSITIVOS
-- ============================================================
-- RUBRICA: Triggers (BEFORE UPDATE) para padronizar a inserção
-- de valores positivos.
--
-- Se alguém gravar um número negativo ou zero (digitando no DBeaver,
-- por exemplo), a trigger corrige ANTES de o valor entrar na tabela.
-- GREATEST(ABS(x), 1) transforma -5 em 5, e 0 em 1.
-- Assim o faturamento da dashboard nunca é poluído por valor inválido.
--
-- São duas duplas de triggers, porque no MySQL/MariaDB uma trigger
-- atende a UM evento só:
--   - BEFORE UPDATE -> protege quem EDITA uma linha já existente
--   - BEFORE INSERT -> protege quem CRIA uma linha nova
-- Sem o par de INSERT, um cadastro novo com valor negativo entraria
-- no banco sem passar por nenhuma validação.

-- ----- Protegendo a EDIÇÃO (BEFORE UPDATE) -----

CREATE TRIGGER trg_vendas_valor_positivo
BEFORE UPDATE ON vendas
FOR EACH ROW
    SET NEW.quantidade = GREATEST(ABS(NEW.quantidade), 1);

CREATE TRIGGER trg_produtos_valor_positivo
BEFORE UPDATE ON produtos
FOR EACH ROW
    SET NEW.valor_unitario = GREATEST(ABS(NEW.valor_unitario), 0.01);


-- ----- Protegendo o CADASTRO (BEFORE INSERT) -----

CREATE TRIGGER trg_vendas_valor_positivo_ins
BEFORE INSERT ON vendas
FOR EACH ROW
    SET NEW.quantidade = GREATEST(ABS(NEW.quantidade), 1);

CREATE TRIGGER trg_produtos_valor_positivo_ins
BEFORE INSERT ON produtos
FOR EACH ROW
    SET NEW.valor_unitario = GREATEST(ABS(NEW.valor_unitario), 0.01);


-- ============================================================
-- 3. DADOS DE EXEMPLO
-- ============================================================
-- Um produto para cada uma das 9 receitas cadastradas.
INSERT INTO produtos (nome, valor_unitario, id_receita, ativo) VALUES
    ('Pão de Fermentação Natural (un.)',  18.00, 1, TRUE),
    ('Brioche Caseiro (un.)',             14.50, 2, TRUE),
    ('Pão Integral com Sementes (un.)',   16.00, 3, TRUE),
    ('Pão de Mel Tradicional (un.)',       7.50, 4, TRUE),
    ('Bolo de Fubá Caseiro (inteiro)',    32.00, 5, TRUE),
    ('Bolo de Cenoura (inteiro)',         38.00, 6, TRUE),
    ('Focaccia de Alecrim (fatia)',       12.00, 7, TRUE),
    ('Coxinha de Frango (un.)',            8.00, 8, TRUE),
    ('Empada de Palmito (un.)',            9.50, 9, FALSE);  -- fora de linha: a View filtra

INSERT INTO vendas (id_produto, quantidade, data_venda) VALUES
    (1, 12, '2026-08-17'), (4, 40, '2026-08-17'), (8, 55, '2026-08-17'),
    (2,  8, '2026-08-18'), (5,  3, '2026-08-18'), (8, 61, '2026-08-18'),
    (1, 15, '2026-08-19'), (3,  9, '2026-08-19'), (7, 24, '2026-08-19'),
    (4, 52, '2026-08-20'), (6,  4, '2026-08-20'), (8, 47, '2026-08-20'),
    (1, 18, '2026-08-21'), (2, 11, '2026-08-21'), (5,  6, '2026-08-21'),
    (3, 14, '2026-08-22'), (6,  7, '2026-08-22'), (7, 31, '2026-08-22'),
    (1, 22, '2026-08-23'), (4, 65, '2026-08-23'), (8, 73, '2026-08-23'),
    (9, 10, '2026-08-23');  -- produto inativo: some da View


-- ============================================================
-- 4. VIEWS ANALÍTICAS COM CTE
-- ============================================================
-- RUBRICA: CTEs e Views que LIMPAM e CONSOLIDAM os dados brutos,
-- entregando-os perfeitamente estruturados.
--
-- A CTE (cláusula WITH) funciona como uma "tabela temporária com
-- nome": ela isola a etapa de limpeza da etapa de consolidação,
-- deixando a consulta legível em vez de virar um JOIN gigante.

-- ------------------------------------------------------------
-- VIEW 1 — vw_vendas_detalhadas
-- É a view que a API PHP consome. Entrega cada venda já ligada
-- ao seu produto, receita e categoria.
--
-- LIMPEZA feita pela CTE "vendas_limpas":
--   - descarta quantidade <= 0 (lixo digitado à mão)
--   - descarta produto inativo (fora de linha)
--   - descarta preço <= 0
-- ------------------------------------------------------------
CREATE VIEW vw_vendas_detalhadas AS
WITH vendas_limpas AS (
    SELECT v.id, v.id_produto, v.quantidade, v.data_venda
    FROM vendas v
    WHERE v.quantidade > 0
),
produtos_ativos AS (
    SELECT p.id, p.nome, p.valor_unitario, p.id_receita
    FROM produtos p
    WHERE p.ativo = TRUE
      AND p.valor_unitario > 0
)
SELECT
    vl.id                          AS venda_id,
    p.nome                         AS produto,
    c.nome                         AS categoria,
    r.nome                         AS receita,
    vl.quantidade                  AS quantidade,
    p.valor_unitario               AS valor_unitario,
    (vl.quantidade * p.valor_unitario) AS subtotal,
    vl.data_venda                  AS data_venda
FROM vendas_limpas   vl
INNER JOIN produtos_ativos p ON vl.id_produto = p.id
INNER JOIN receitas        r ON p.id_receita  = r.id
INNER JOIN categorias      c ON r.id_categoria = c.id;


-- ------------------------------------------------------------
-- VIEW 2 — vw_faturamento_categoria
-- Consolida o faturamento agrupado por categoria e já calcula a
-- participação percentual de cada uma no total.
-- ------------------------------------------------------------
CREATE VIEW vw_faturamento_categoria AS
WITH por_categoria AS (
    SELECT
        categoria,
        SUM(quantidade) AS unidades,
        SUM(subtotal)   AS faturamento
    FROM vw_vendas_detalhadas
    GROUP BY categoria
),
total_geral AS (
    SELECT SUM(faturamento) AS total FROM por_categoria
)
SELECT
    pc.categoria,
    pc.unidades,
    pc.faturamento,
    ROUND(100 * pc.faturamento / tg.total, 2) AS percentual
FROM por_categoria pc
CROSS JOIN total_geral tg
ORDER BY pc.faturamento DESC;


-- ------------------------------------------------------------
-- VIEW 3 — vw_ranking_produtos
-- Ranqueia os produtos por faturamento usando window function.
-- ------------------------------------------------------------
CREATE VIEW vw_ranking_produtos AS
WITH totais_produto AS (
    SELECT
        produto,
        categoria,
        SUM(quantidade) AS unidades_vendidas,
        SUM(subtotal)   AS faturamento
    FROM vw_vendas_detalhadas
    GROUP BY produto, categoria
)
SELECT
    ROW_NUMBER() OVER (ORDER BY faturamento DESC) AS posicao,
    produto,
    categoria,
    unidades_vendidas,
    faturamento
FROM totais_produto;
