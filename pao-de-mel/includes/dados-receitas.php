<?php
// dados-receitas.php
//
// ===== RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS =====
// Todos os dados principais do sistema vivem dentro de UM único array,
// $receitas. Cada receita é um item desse array (um array associativo),
// nunca uma variável solta como $receita1, $receita2 etc.
//
// Por enquanto os dados são digitados aqui à mão (array de teste). Na Fase 6,
// esse array vai ser preenchido a partir do banco de dados (mysqli) — o resto
// do site não vai precisar mudar, porque só lida com $receitas, não importa
// de onde ele veio.

$receitas = [
    [
        'id' => 1,
        'nome' => 'Pão de Fermentação Natural',
        'categoria' => 'Pão',
        'tempo_preparo' => 240, // em minutos
        'dificuldade' => 'Médio',
        'thumb' => 'thumb-1',
        'ingredientes' => [
            '500g de farinha de trigo',
            '350ml de água',
            '150g de fermento natural (levain)',
            '10g de sal',
        ],
        'modo_preparo' => [
            'Misture a farinha com a água e deixe descansar por 30 minutos.',
            'Adicione o fermento natural e o sal, misturando bem.',
            'Deixe a massa fermentar por 3 horas, dobrando a cada 30 minutos.',
            'Modele o pão e deixe descansar por mais 1 hora.',
            'Asse em forno bem quente (230°C) por 35 minutos.',
        ],
    ],
    [
        'id' => 2,
        'nome' => 'Pão de Mel Tradicional',
        'categoria' => 'Doce',
        'tempo_preparo' => 90,
        'dificuldade' => 'Fácil',
        'thumb' => 'thumb-2',
        'ingredientes' => [
            '2 xícaras de farinha de trigo',
            '1 xícara de mel',
            '1/2 xícara de leite',
            '2 ovos',
            '1 colher de chá de canela em pó',
            '1 colher de chá de fermento em pó',
        ],
        'modo_preparo' => [
            'Misture os ingredientes secos em uma tigela grande.',
            'Em outra tigela, bata os ovos com o mel e o leite.',
            'Combine as duas misturas até obter uma massa homogênea.',
            'Despeje em uma forma untada e leve ao forno preaquecido a 180°C por 35 minutos.',
            'Deixe esfriar antes de desenformar e servir.',
        ],
    ],
    [
        'id' => 3,
        'nome' => 'Focaccia de Alecrim',
        'categoria' => 'Salgado',
        'tempo_preparo' => 120,
        'dificuldade' => 'Médio',
        'thumb' => 'thumb-3',
        'ingredientes' => [
            '500g de farinha de trigo',
            '350ml de água morna',
            '10g de fermento biológico seco',
            '60ml de azeite de oliva',
            '10g de sal',
            'Ramos de alecrim fresco',
        ],
        'modo_preparo' => [
            'Dissolva o fermento na água morna e deixe descansar 10 minutos.',
            'Misture a farinha, o sal e a água com fermento até formar uma massa.',
            'Deixe descansar por 1 hora, até dobrar de tamanho.',
            'Espalhe a massa numa assadeira untada, faça furos com os dedos e regue com azeite.',
            'Distribua o alecrim por cima e asse a 220°C por 25 minutos.',
        ],
    ],
    [
        'id' => 4,
        'nome' => 'Bolo de Fubá Caseiro',
        'categoria' => 'Bolo',
        'tempo_preparo' => 60,
        'dificuldade' => 'Fácil',
        'thumb' => 'thumb-1',
        'ingredientes' => [
            '2 xícaras de fubá',
            '1 xícara de farinha de trigo',
            '3 ovos',
            '1 xícara de açúcar',
            '1 xícara de leite',
            '1 colher de sopa de fermento em pó',
        ],
        'modo_preparo' => [
            'Bata os ovos com o açúcar até clarear.',
            'Adicione o leite e misture bem.',
            'Incorpore o fubá e a farinha peneirados.',
            'Por último, adicione o fermento e misture delicadamente.',
            'Asse em forma untada a 180°C por 40 minutos.',
        ],
    ],
    [
        'id' => 5,
        'nome' => 'Brioche Caseiro',
        'categoria' => 'Pão',
        'tempo_preparo' => 180,
        'dificuldade' => 'Médio',
        'thumb' => 'thumb-2',
        'ingredientes' => [
            '500g de farinha de trigo',
            '4 ovos',
            '100g de açúcar',
            '200g de manteiga',
            '10g de fermento biológico',
            '1 pitada de sal',
        ],
        'modo_preparo' => [
            'Misture a farinha, o açúcar, o sal e o fermento.',
            'Adicione os ovos e amasse até formar uma massa lisa.',
            'Incorpore a manteiga em temperatura ambiente, pouco a pouco.',
            'Deixe descansar por 2 horas, até dobrar de tamanho.',
            'Modele, deixe crescer mais 30 minutos e asse a 180°C por 25 minutos.',
        ],
    ],
    [
        'id' => 6,
        'nome' => 'Pão Integral com Sementes',
        'categoria' => 'Salgado',
        'tempo_preparo' => 150,
        'dificuldade' => 'Médio',
        'thumb' => 'thumb-3',
        'ingredientes' => [
            '400g de farinha integral',
            '100g de farinha de trigo',
            '300ml de água',
            '10g de fermento biológico',
            '50g de mix de sementes (girassol, linhaça, gergelim)',
            '10g de sal',
        ],
        'modo_preparo' => [
            'Misture as farinhas, o sal e o fermento.',
            'Adicione a água e amasse até formar uma massa homogênea.',
            'Incorpore as sementes durante o amassamento.',
            'Deixe descansar por 2 horas, até dobrar de tamanho.',
            'Modele, deixe crescer mais 30 minutos e asse a 200°C por 35 minutos.',
        ],
    ],
];
