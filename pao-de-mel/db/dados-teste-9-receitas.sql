-- dados-teste.sql
-- Popula o banco "padaria" com 9 receitas de exemplo, no formato exato
-- da estrutura em padaria.sql. Pode rodar quantas vezes quiser: o
-- TRUNCATE no início limpa as 4 tabelas antes de inserir de novo.

SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE receita_ingrediente;
TRUNCATE TABLE receitas;
TRUNCATE TABLE ingredientes;
TRUNCATE TABLE categorias;
SET FOREIGN_KEY_CHECKS = 1;

-- ===== CATEGORIAS (id: 1=Pão, 2=Doce, 3=Salgado, 4=Bolo) =====
INSERT INTO categorias (nome) VALUES ('Pão'), ('Doce'), ('Salgado'), ('Bolo');

-- ===== INGREDIENTES (id 1 a 22, na ordem desta lista) =====
INSERT INTO ingredientes (nome, unidade_medida) VALUES
('Farinha de trigo', 'g'),          -- 1
('Água', 'ml'),                     -- 2
('Fermento biológico seco', 'g'),   -- 3
('Sal', 'g'),                       -- 4
('Açúcar', 'g'),                    -- 5
('Manteiga', 'g'),                  -- 6
('Ovo', ' un.'),                    -- 7
('Leite', 'ml'),                    -- 8
('Mel', 'g'),                       -- 9
('Canela em pó', 'g'),              -- 10
('Fermento em pó', 'g'),            -- 11
('Azeite de oliva', 'ml'),          -- 12
('Alecrim fresco', 'g'),            -- 13
('Fubá', 'g'),                      -- 14
('Cenoura', 'g'),                   -- 15
('Chocolate em pó', 'g'),           -- 16
('Óleo', 'ml'),                     -- 17
('Frango desfiado', 'g'),           -- 18
('Palmito', 'g'),                   -- 19
('Farinha integral', 'g'),          -- 20
('Sementes mistas', 'g'),           -- 21
('Caldo de galinha', 'ml');         -- 22

-- ===== RECEITAS (id na ordem de inserção, 1 a 9) =====

-- 1. Pão de Fermentação Natural - Pão - 240 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Pão de Fermentação Natural', 'Misture a farinha com a água e deixe descansar por 30 minutos.
Adicione o fermento e o sal, misturando bem.
Deixe a massa fermentar por 3 horas, dobrando a cada 30 minutos.
Modele o pão e deixe descansar por mais 1 hora.
Asse em forno bem quente (230°C) por 35 minutos.', 240, 1);

-- 2. Brioche Caseiro - Pão - 180 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Brioche Caseiro', 'Misture a farinha, o açúcar, o sal e o fermento.
Adicione os ovos e amasse até formar uma massa lisa.
Incorpore a manteiga em temperatura ambiente, pouco a pouco.
Deixe descansar por 2 horas, até dobrar de tamanho.
Modele, deixe crescer mais 30 minutos e asse a 180°C por 25 minutos.', 180, 1);

-- 3. Pão Integral com Sementes - Pão - 150 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Pão Integral com Sementes', 'Misture as farinhas, o sal e o fermento.
Adicione a água e amasse até formar uma massa homogênea.
Incorpore as sementes durante o amassamento.
Deixe descansar por 2 horas, até dobrar de tamanho.
Modele, deixe crescer mais 30 minutos e asse a 200°C por 35 minutos.', 150, 1);

-- 4. Pão de Mel Tradicional - Doce - 90 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Pão de Mel Tradicional', 'Misture os ingredientes secos em uma tigela grande.
Em outra tigela, bata os ovos com o mel e o leite.
Combine as duas misturas até obter uma massa homogênea.
Despeje em uma forma untada e leve ao forno preaquecido a 180°C por 35 minutos.
Deixe esfriar antes de desenformar e servir.', 90, 2);

-- 5. Bolo de Fubá Caseiro - Bolo - 60 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Bolo de Fubá Caseiro', 'Bata os ovos com o açúcar até clarear.
Adicione o leite e misture bem.
Incorpore o fubá e a farinha peneirados.
Por último, adicione o fermento e misture delicadamente.
Asse em forma untada a 180°C por 40 minutos.', 60, 4);

-- 6. Bolo de Cenoura com Cobertura de Chocolate - Bolo - 70 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Bolo de Cenoura com Cobertura de Chocolate', 'Bata no liquidificador a cenoura, o óleo e os ovos até ficar homogêneo.
Em uma tigela, misture o açúcar e a farinha, e adicione a mistura batida.
Acrescente o fermento por último, misturando delicadamente.
Despeje em forma untada e asse a 180°C por 35 minutos.
Derreta o chocolate em pó com um pouco de manteiga e cubra o bolo já frio.', 70, 4);

-- 7. Focaccia de Alecrim - Salgado - 120 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Focaccia de Alecrim', 'Dissolva o fermento na água morna e deixe descansar 10 minutos.
Misture a farinha, o sal e a água com fermento até formar uma massa.
Deixe descansar por 1 hora, até dobrar de tamanho.
Espalhe a massa numa assadeira untada, faça furos com os dedos e regue com azeite.
Distribua o alecrim por cima e asse a 220°C por 25 minutos.', 120, 3);

-- 8. Coxinha de Frango - Salgado - 90 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Coxinha de Frango', 'Cozinhe o frango no caldo de galinha até desfiar facilmente.
Em uma panela, doure a farinha na manteiga, adicionando o caldo aos poucos até formar uma massa lisa.
Deixe esfriar e modele as coxinhas recheadas com o frango desfiado.
Passe na farinha de rosca e frite em óleo quente até dourar.
Sirva ainda quente.', 90, 3);

-- 9. Empada de Palmito - Salgado - 80 min
INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
('Empada de Palmito', 'Misture a farinha com a manteiga até formar uma farofa.
Adicione o ovo e amasse até formar uma massa lisa, sem grudar nas mãos.
Forre forminhas untadas com a massa, reservando um pouco para a tampa.
Recheie com o palmito temperado e cubra com a massa reservada.
Asse a 180°C por 25 minutos, até dourar.', 80, 3);

-- ===== RECEITA_INGREDIENTE (liga cada receita aos seus ingredientes) =====

-- 1. Pão de Fermentação Natural
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(1, 1, 500), (1, 2, 350), (1, 3, 10), (1, 4, 10);

-- 2. Brioche Caseiro
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(2, 1, 500), (2, 7, 4), (2, 5, 100), (2, 6, 200), (2, 3, 10), (2, 4, 5);

-- 3. Pão Integral com Sementes
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(3, 20, 400), (3, 1, 100), (3, 2, 300), (3, 3, 10), (3, 21, 50), (3, 4, 10);

-- 4. Pão de Mel Tradicional
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(4, 1, 300), (4, 9, 250), (4, 8, 120), (4, 7, 2), (4, 10, 5), (4, 11, 10);

-- 5. Bolo de Fubá Caseiro
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(5, 14, 250), (5, 1, 120), (5, 7, 3), (5, 5, 200), (5, 8, 240), (5, 11, 15);

-- 6. Bolo de Cenoura com Cobertura de Chocolate
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(6, 15, 300), (6, 17, 150), (6, 7, 3), (6, 5, 200), (6, 1, 250), (6, 11, 10), (6, 16, 100);

-- 7. Focaccia de Alecrim
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(7, 1, 500), (7, 2, 350), (7, 3, 10), (7, 12, 60), (7, 4, 10), (7, 13, 10);

-- 8. Coxinha de Frango
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(8, 18, 300), (8, 1, 300), (8, 22, 500), (8, 6, 30), (8, 4, 5);

-- 9. Empada de Palmito
INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
(9, 1, 300), (9, 6, 150), (9, 19, 200), (9, 7, 1), (9, 4, 5);
