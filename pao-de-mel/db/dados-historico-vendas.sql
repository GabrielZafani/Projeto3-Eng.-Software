-- ============================================================
-- HISTÓRICO DE VENDAS - duas semanas anteriores
-- ============================================================
-- Rode DEPOIS de db/dashboard-vendas.sql. Aquele script começa com
-- DROP TABLE vendas; se rodar na ordem trocada, este histórico some.
--
-- Por que ele existe: as 22 vendas originais são todas de 17 a 23/08,
-- exatamente sete dias. Com isso, o painel "Última semana de movimento"
-- da dashboard recortava a base inteira e mostrava o mesmo número do
-- card de faturamento total, com a frase "Sem semana anterior para
-- comparar". O .filter() estava certo; faltava passado para ele cortar.
--
-- O movimento cresce de semana em semana de propósito (a padaria
-- vendendo mais), para a comparação percentual dar um número positivo
-- e legível em vez de uma variação de meio por cento.
--
-- Rodar duas vezes não duplica nada: a variável abaixo é lida UMA vez,
-- antes do INSERT, e zera a inserção se já houver venda no período.
-- (Fazer o mesmo com NOT EXISTS dentro do INSERT não funcionaria: a
-- partir da segunda linha ele já enxergaria a primeira recém-inserida.)

USE padaria;

SET @ja_tem_historico := (
    SELECT COUNT(*) FROM vendas WHERE data_venda < '2026-08-17'
);

INSERT INTO vendas (id_produto, quantidade, data_venda)
SELECT d.id_produto, d.quantidade, d.data_venda
FROM (
    -- ----- Semana de 03/08 a 09/08 -----
    SELECT 1 AS id_produto,  8 AS quantidade, '2026-08-03' AS data_venda
    UNION ALL SELECT 4, 30, '2026-08-03'
    UNION ALL SELECT 8, 35, '2026-08-03'
    UNION ALL SELECT 2,  6, '2026-08-04'
    UNION ALL SELECT 5,  2, '2026-08-04'
    UNION ALL SELECT 8, 40, '2026-08-04'
    UNION ALL SELECT 1, 10, '2026-08-05'
    UNION ALL SELECT 3,  7, '2026-08-05'
    UNION ALL SELECT 7, 18, '2026-08-05'
    UNION ALL SELECT 4, 38, '2026-08-06'
    UNION ALL SELECT 6,  4, '2026-08-06'
    UNION ALL SELECT 8, 33, '2026-08-06'
    UNION ALL SELECT 1, 11, '2026-08-07'
    UNION ALL SELECT 2,  8, '2026-08-07'
    UNION ALL SELECT 5,  3, '2026-08-07'
    UNION ALL SELECT 3,  9, '2026-08-08'
    UNION ALL SELECT 6,  5, '2026-08-08'
    UNION ALL SELECT 7, 22, '2026-08-08'
    UNION ALL SELECT 1, 15, '2026-08-09'
    UNION ALL SELECT 4, 45, '2026-08-09'
    UNION ALL SELECT 8, 50, '2026-08-09'
    UNION ALL SELECT 9,  6, '2026-08-09'

    -- ----- Semana de 10/08 a 16/08 -----
    UNION ALL SELECT 1, 10, '2026-08-10'
    UNION ALL SELECT 4, 35, '2026-08-10'
    UNION ALL SELECT 8, 40, '2026-08-10'
    UNION ALL SELECT 2,  7, '2026-08-11'
    UNION ALL SELECT 5,  3, '2026-08-11'
    UNION ALL SELECT 8, 45, '2026-08-11'
    UNION ALL SELECT 1, 12, '2026-08-12'
    UNION ALL SELECT 3,  8, '2026-08-12'
    UNION ALL SELECT 7, 20, '2026-08-12'
    UNION ALL SELECT 4, 44, '2026-08-13'
    UNION ALL SELECT 6,  5, '2026-08-13'
    UNION ALL SELECT 8, 38, '2026-08-13'
    UNION ALL SELECT 1, 14, '2026-08-14'
    UNION ALL SELECT 2,  9, '2026-08-14'
    UNION ALL SELECT 5,  4, '2026-08-14'
    UNION ALL SELECT 3, 11, '2026-08-15'
    UNION ALL SELECT 6,  6, '2026-08-15'
    UNION ALL SELECT 7, 26, '2026-08-15'
    UNION ALL SELECT 1, 17, '2026-08-16'
    UNION ALL SELECT 4, 50, '2026-08-16'
    UNION ALL SELECT 8, 58, '2026-08-16'
    UNION ALL SELECT 9,  8, '2026-08-16'
) AS d
WHERE @ja_tem_historico = 0;

-- Conferência: três semanas, com o faturamento subindo a cada uma.
SELECT
    CASE
        WHEN data_venda >= '2026-08-17' THEN '3. de 17/08 a 23/08 (atual)'
        WHEN data_venda >= '2026-08-10' THEN '2. de 10/08 a 16/08'
        ELSE                                 '1. de 03/08 a 09/08'
    END                   AS semana,
    COUNT(*)              AS vendas,
    SUM(quantidade)       AS unidades,
    ROUND(SUM(subtotal),2) AS faturamento
FROM vw_vendas_detalhadas
GROUP BY semana
ORDER BY semana;
