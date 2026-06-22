-- ============================================
-- Banco de Dados - Blog de Receitas da Padaria
-- ============================================

CREATE DATABASE IF NOT EXISTS padaria;
USE padaria;

-- Tabela de categorias
CREATE TABLE categorias (
    id   INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL
);

-- Tabela de ingredientes
CREATE TABLE ingredientes (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    nome           VARCHAR(100) NOT NULL,
    unidade_medida VARCHAR(20)  NOT NULL
);

-- Tabela de receitas
CREATE TABLE receitas (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(100) NOT NULL,
    modo_preparo TEXT         NOT NULL,
    tempo_preparo INT         NOT NULL,  -- em minutos
    id_categoria INT          NOT NULL,
    FOREIGN KEY (id_categoria) REFERENCES categorias(id)
);

-- Tabela intermediária (N:N entre receitas e ingredientes)
CREATE TABLE receita_ingrediente (
    id_receita     INT            NOT NULL,
    id_ingrediente INT            NOT NULL,
    quantidade     DECIMAL(10, 2) NOT NULL,
    PRIMARY KEY (id_receita, id_ingrediente),
    FOREIGN KEY (id_receita)     REFERENCES receitas(id),
    FOREIGN KEY (id_ingrediente) REFERENCES ingredientes(id)
);

-- ============================================
-- Dados de exemplo
-- ============================================

INSERT INTO categorias (nome) VALUES
    ('Pães'),
    ('Bolos'),
    ('Sobremesas'),
    ('Salgados');

INSERT INTO ingredientes (nome, unidade_medida) VALUES
    ('Farinha de trigo',   'g'),
    ('Açúcar',            'g'),
    ('Sal',               'g'),
    ('Fermento biológico','g'),
    ('Leite',             'ml'),
    ('Manteiga',          'g'),
    ('Ovos',              'un'),
    ('Chocolate em pó',   'g'),
    ('Queijo mussarela',  'g'),
    ('Presunto',          'g');

INSERT INTO receitas (nome, modo_preparo, tempo_preparo, id_categoria) VALUES
    ('Pão Francês',
     'Misture a farinha, o sal e o fermento. Adicione água morna aos poucos e sove até a massa ficar lisa. Deixe descansar por 40 minutos, modele os pães e asse a 220°C por 20 minutos.',
     90, 1),

    ('Bolo de Chocolate',
     'Bata os ovos com o açúcar. Acrescente a manteiga derretida, o leite e a farinha peneirada com o chocolate em pó. Misture bem e leve ao forno a 180°C por 35 minutos.',
     60, 2),

    ('Brigadeiro',
     'Misture o leite condensado com o chocolate em pó e a manteiga numa panela. Cozinhe em fogo baixo mexendo sempre até desgrudar do fundo. Deixe esfriar e enrole.',
     30, 3),

    ('Croissant',
     'Prepare a massa com farinha, açúcar, sal, fermento e leite. Incorpore a manteiga gelada em camadas dobrando a massa várias vezes. Modele em meia-lua e asse a 200°C por 18 minutos.',
     180, 1),

    ('Pão de Queijo',
     'Escalde o polvilho com leite e manteiga quentes. Acrescente os ovos e o queijo e misture até formar uma massa homogênea. Modele bolinhas e asse a 200°C por 25 minutos.',
     40, 4);

INSERT INTO receita_ingrediente (id_receita, id_ingrediente, quantidade) VALUES
    -- Pão Francês
    (1, 1, 500.00),  -- Farinha de trigo
    (1, 3, 10.00),   -- Sal
    (1, 4, 7.00),    -- Fermento biológico

    -- Bolo de Chocolate
    (2, 1, 300.00),  -- Farinha de trigo
    (2, 2, 200.00),  -- Açúcar
    (2, 6, 100.00),  -- Manteiga
    (2, 7, 3.00),    -- Ovos
    (2, 5, 240.00),  -- Leite
    (2, 8, 80.00),   -- Chocolate em pó

    -- Brigadeiro
    (3, 8, 100.00),  -- Chocolate em pó
    (3, 6, 20.00),   -- Manteiga

    -- Croissant
    (4, 1, 400.00),  -- Farinha de trigo
    (4, 2, 30.00),   -- Açúcar
    (4, 3, 8.00),    -- Sal
    (4, 4, 10.00),   -- Fermento biológico
    (4, 5, 150.00),  -- Leite
    (4, 6, 250.00),  -- Manteiga

    -- Pão de Queijo
    (5, 7, 2.00),    -- Ovos
    (5, 6, 50.00),   -- Manteiga
    (5, 5, 200.00),  -- Leite
    (5, 9, 150.00);  -- Queijo mussarela
