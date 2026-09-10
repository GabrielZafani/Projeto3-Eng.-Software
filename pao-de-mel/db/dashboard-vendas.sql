-- ============================================================
-- MÓDULO DASHBOARD - Vendas da Padaria Pão de Mel
-- ============================================================
-- Este script complementa padaria.sql. Ele acrescenta a parte
-- financeira do sistema (produtos vendidos e vendas realizadas),
-- as VIEWS ANALÍTICAS com CTE e as TRIGGERS de padronização.
--
-- Pode rodar quantas vezes quiser: tudo é recriado do zero.
-- ============================================================

USE padaria;

-- Remove na ordem certa (dependências primeiro)
DROP PROCEDURE IF EXISTS sp_vendas_buscar;
DROP PROCEDURE IF EXISTS sp_vendas_categorias;
DROP VIEW     IF EXISTS vw_painel_produtos;
DROP FUNCTION IF EXISTS fn_faturamento_periodo;
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
--
-- "cancelada" existe porque venda é histórico: quando o caixa erra e precisa
-- desfazer, a linha não pode simplesmente sumir do banco. Ela fica, marcada,
-- e a view deixa de contá-la. Assim o faturamento se corrige sem que o
-- registro do que aconteceu seja destruído.
CREATE TABLE vendas (
    id         INT     AUTO_INCREMENT PRIMARY KEY,
    id_produto INT     NOT NULL,
    quantidade INT     NOT NULL,
    data_venda DATE    NOT NULL,
    cancelada  BOOLEAN NOT NULL DEFAULT FALSE,
    FOREIGN KEY (id_produto) REFERENCES produtos(id)
);


-- ------------------------------------------------------------
-- NOME ÚNICO - a trava que o CRUD não pode ser o único a fazer
-- ------------------------------------------------------------
-- Validar no formulário não basta: quem inserir pelo DBeaver passa por cima.
-- Sem isto dá para cadastrar "Salgado" duas vezes, e a barra de filtros da
-- dashboard passa a mostrar dois botões idênticos que filtram metade cada.
--
-- O IF NOT EXISTS deixa o script continuar rodável quantas vezes quiser
-- (a tabela categorias, ao contrário de produtos, não é recriada aqui).
ALTER TABLE categorias ADD UNIQUE INDEX IF NOT EXISTS uk_categorias_nome (nome);
ALTER TABLE produtos   ADD UNIQUE INDEX IF NOT EXISTS uk_produtos_nome   (nome);


-- ============================================================
-- 2. TRIGGERS - PADRONIZAÇÃO DE VALORES POSITIVOS
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
    (9, 10, '2026-08-23');  -- produto fora de linha: a venda CONTINUA contando

-- Uma venda cancelada, para a CTE de limpeza ter o que descartar.
-- É esta linha que faz a View devolver 22 de 23: registro preservado no
-- banco, fora do faturamento.
INSERT INTO vendas (id_produto, quantidade, data_venda, cancelada) VALUES
    (6, 3, '2026-08-22', TRUE);


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
-- VIEW 1 - vw_vendas_detalhadas
-- É a view que a API PHP consome. Entrega cada venda já ligada
-- ao seu produto, receita e categoria.
--
-- LIMPEZA feita pelas CTEs:
--   "vendas_limpas"    descarta venda cancelada e quantidade <= 0
--   "produtos_validos" descarta preço <= 0
--
-- Repare no que NÃO é filtrado: produto fora de linha. Ele some do cardápio,
-- mas as vendas que ele já teve continuam contando. Faturamento de mês
-- fechado não pode mudar porque alguém aposentou um produto hoje - descobrir
-- isso custou R$ 95,00 de diferença entre a tela e o relatório.
-- ------------------------------------------------------------
CREATE VIEW vw_vendas_detalhadas AS
WITH vendas_limpas AS (
    SELECT v.id, v.id_produto, v.quantidade, v.data_venda
    FROM vendas v
    WHERE v.quantidade > 0
      AND v.cancelada = FALSE
),
produtos_validos AS (
    SELECT p.id, p.nome, p.valor_unitario, p.id_receita
    FROM produtos p
    WHERE p.valor_unitario > 0
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
INNER JOIN produtos_validos p ON vl.id_produto = p.id
INNER JOIN receitas        r ON p.id_receita  = r.id
INNER JOIN categorias      c ON r.id_categoria = c.id;


-- ------------------------------------------------------------
-- VIEW 2 - vw_faturamento_categoria
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
-- VIEW 3 - vw_ranking_produtos
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


-- ============================================================
-- 5. FUNÇÃO - fn_faturamento_periodo
-- ============================================================
-- RUBRICA: criação de uma função no banco de dados para reutilização
-- de scripts massivos ou complexos.
--
-- Sem a função, quem quisesse o faturamento de um intervalo teria que
-- repetir todo o encadeamento: join de 4 tabelas, descarte de produto
-- inativo, descarte de quantidade <= 0, multiplicação e SUM. Esse
-- mesmo bloco já aparecia copiado em três lugares deste projeto (a
-- view, o roteiro de testes e agora a procedure), e cada cópia é uma
-- chance de alguém esquecer um dos filtros e publicar faturamento
-- errado. A função guarda a regra em UM lugar só.
--
-- O COALESCE existe por causa de um defeito real: SUM() sobre zero
-- linhas devolve NULL, e não zero. Sem ele, um intervalo sem venda
-- fazia a procedure devolver NULL e a tela imprimia "R$ NaN".
--
-- Passar NULL nas duas datas significa "o período inteiro".

DELIMITER //

CREATE FUNCTION fn_faturamento_periodo(
    p_data_ini DATE,
    p_data_fim DATE
)
RETURNS DECIMAL(12, 2)
READS SQL DATA
BEGIN
    DECLARE v_total DECIMAL(12, 2);

    SELECT COALESCE(SUM(quantidade * valor_unitario), 0)
      INTO v_total
      FROM vw_vendas_detalhadas
     WHERE (p_data_ini IS NULL OR data_venda >= p_data_ini)
       AND (p_data_fim IS NULL OR data_venda <= p_data_fim);

    RETURN v_total;
END //

DELIMITER ;


-- ============================================================
-- 6. VIEW CENTRALIZADORA - vw_painel_produtos
-- ============================================================
-- RUBRICA: criação de View que centralize informações importantes do
-- sistema e que estão em diversas tabelas distintas.
--
-- Junta SEIS tabelas em uma linha por produto:
--   produtos, receitas, categorias, vendas,
--   receita_ingrediente e ingredientes.
--
-- Diferença para a vw_vendas_detalhadas (que é a prova da rubrica de
-- CTE): aquela mostra uma linha por VENDA, só do que já está limpo.
-- Esta é o panorama do catálogo, uma linha por PRODUTO, e mostra
-- inclusive o produto fora de linha e o que nunca vendeu - que é
-- justamente a informação que some da outra view.
--
-- Os totais de venda vêm de uma CTE agregada ANTES do join, e não de
-- um SUM no join final. Custou um defeito descobrir isto: juntando
-- vendas e ingredientes na mesma consulta, cada venda era contada uma
-- vez por ingrediente da receita, e um pão com 8 ingredientes
-- aparecia com 8x o faturamento real.

CREATE VIEW vw_painel_produtos AS
WITH totais_venda AS (
    SELECT
        id_produto,
        SUM(quantidade) AS unidades_vendidas,
        COUNT(*)        AS num_vendas,
        MAX(data_venda) AS ultima_venda
    FROM vendas
    WHERE quantidade > 0
      AND cancelada = FALSE
    GROUP BY id_produto
),
ingredientes_da_receita AS (
    SELECT
        ri.id_receita,
        COUNT(*) AS qtd_ingredientes,
        GROUP_CONCAT(i.nome ORDER BY i.nome SEPARATOR ', ') AS lista_ingredientes
    FROM receita_ingrediente ri
    INNER JOIN ingredientes i ON i.id = ri.id_ingrediente
    GROUP BY ri.id_receita
)
SELECT
    p.id                              AS produto_id,
    p.nome                            AS produto,
    c.nome                            AS categoria,
    r.nome                            AS receita,
    r.tempo_preparo                   AS tempo_preparo_min,
    COALESCE(ir.qtd_ingredientes, 0)  AS qtd_ingredientes,
    ir.lista_ingredientes             AS ingredientes,
    p.valor_unitario                  AS preco,
    COALESCE(tv.unidades_vendidas, 0) AS unidades_vendidas,
    COALESCE(tv.num_vendas, 0)        AS num_vendas,
    COALESCE(tv.unidades_vendidas, 0) * p.valor_unitario AS faturamento,
    tv.ultima_venda                   AS ultima_venda,
    IF(p.ativo, 'Ativo', 'Fora de linha') AS situacao
FROM produtos p
INNER JOIN receitas   r ON r.id = p.id_receita
INNER JOIN categorias c ON c.id = r.id_categoria
LEFT  JOIN totais_venda            tv ON tv.id_produto = p.id
LEFT  JOIN ingredientes_da_receita ir ON ir.id_receita = r.id;


-- ============================================================
-- 7. STORED PROCEDURES - BUSCA, FILTRO E PAGINAÇÃO
-- ============================================================
-- RUBRICA: stored procedures otimizadas para centralizar a busca, os
-- filtros e a paginação dos indicadores da dashboard, permitindo que
-- a API em PHP faça chamadas limpas (CALL) e assíncronas.
--
-- A api.php não monta mais nenhum SQL: ela repassa o que veio da tela
-- e chama CALL. Toda a regra de busca, filtro e recorte de página mora
-- aqui, onde o banco pode otimizar.
--
-- Segurança: nada aqui é SQL montado como texto. O texto buscado entra
-- como PARÂMETRO dentro do CONCAT do LIKE, e o LIMIT/OFFSET recebe
-- variável direto (o MariaDB aceita). Por isso não existe brecha de
-- injeção nem quando alguém digita aspas no campo de busca.

DELIMITER //

-- ------------------------------------------------------------
-- sp_vendas_buscar
--   p_busca        texto procurado no nome do produto ('' = tudo)
--   p_categoria    categoria exata ('' ou 'todas' = tudo)
--   p_pagina       número da página, começando em 1
--   p_tamanho      linhas por página (0 = traz tudo, sem paginar)
--   p_total_linhas SAÍDA: quantas linhas o filtro achou no total
--   p_faturamento  SAÍDA: conferência vinda da FUNÇÃO do banco
--
-- O p_tamanho = 0 existe porque a dashboard precisa das duas coisas: a
-- página atual para a tabela e o conjunto inteiro para o reduce
-- calcular os cards. Sem ele, o faturamento total mudaria toda vez que
-- o usuário virasse a página.
-- ------------------------------------------------------------
CREATE PROCEDURE sp_vendas_buscar(
    IN  p_busca        VARCHAR(100),
    IN  p_categoria    VARCHAR(50),
    IN  p_pagina       INT,
    IN  p_tamanho      INT,
    OUT p_total_linhas INT,
    OUT p_faturamento  DECIMAL(12, 2)
)
BEGIN
    DECLARE v_pagina       INT;
    DECLARE v_tamanho      INT;
    DECLARE v_deslocamento INT;

    -- A normalização mora aqui dentro, e não no PHP, para que quem
    -- chamar pelo DBeaver receba exatamente o mesmo tratamento da tela.
    SET p_busca     = COALESCE(NULLIF(TRIM(p_busca), ''), '');
    SET p_categoria = COALESCE(NULLIF(TRIM(p_categoria), ''), 'todas');

    -- Página 0 ou negativa viraria OFFSET negativo, que é erro de
    -- sintaxe no MariaDB e derrubaria a API com 500.
    SET v_pagina  = GREATEST(COALESCE(p_pagina, 1), 1);
    SET v_tamanho = COALESCE(p_tamanho, 10);

    IF v_tamanho <= 0 THEN
        SET v_tamanho = 1000000;
    END IF;

    SET v_deslocamento = (v_pagina - 1) * v_tamanho;

    -- O total ANTES de paginar: é ele que diz quantos botões de página
    -- a tela precisa desenhar.
    SELECT COUNT(*)
      INTO p_total_linhas
      FROM vw_vendas_detalhadas
     WHERE (p_categoria = 'todas' OR categoria = p_categoria)
       AND (p_busca = ''          OR produto LIKE CONCAT('%', p_busca, '%'));

    -- REUTILIZAÇÃO DA FUNÇÃO: em vez de repetir o SUM com todos os
    -- filtros de limpeza, a procedure chama fn_faturamento_periodo.
    SET p_faturamento = fn_faturamento_periodo(NULL, NULL);

    -- As linhas da página, ainda CRUAS (quantidade e valor unitário em
    -- colunas separadas), porque quem multiplica e soma é o reduce do
    -- TypeScript.
    SELECT
        venda_id,
        produto,
        categoria,
        quantidade,
        valor_unitario,
        data_venda
      FROM vw_vendas_detalhadas
     WHERE (p_categoria = 'todas' OR categoria = p_categoria)
       AND (p_busca = ''          OR produto LIKE CONCAT('%', p_busca, '%'))
     ORDER BY data_venda DESC, produto ASC
     LIMIT v_tamanho OFFSET v_deslocamento;
END //


-- ------------------------------------------------------------
-- sp_vendas_categorias
-- Devolve as categorias que REALMENTE têm venda limpa, já com a
-- contagem. A tela monta os botões de filtro a partir daqui, então
-- nunca aparece na barra um botão que não filtra nada.
-- ------------------------------------------------------------
CREATE PROCEDURE sp_vendas_categorias()
BEGIN
    SELECT
        categoria,
        COUNT(*) AS vendas
      FROM vw_vendas_detalhadas
     GROUP BY categoria
     ORDER BY categoria;
END //

DELIMITER ;
