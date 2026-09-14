<?php
require 'includes/header.php'; // RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP
require 'includes/dados-receitas.php'; // RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS
require_once 'includes/funcoes.php'; // RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO

// RUBRICA TECH FORGE - VALIDAÇÃO DE REGRAS DE NEGÓCIO COM CONDICIONAIS
$receitas = filtrarReceitasValidas($receitas);

// A página lê o $_GET aqui (uma vez só) e passa o valor como parâmetro
// pra função - a função em si não toca em variável global.
$categoriaSelecionada = isset($_GET['categoria']) ? $_GET['categoria'] : 'todas';

// RUBRICA TECH FORGE - LÓGICA DE PESQUISA OU FILTRO
$receitasExibidas = filtrarPorCategoria($receitas, $categoriaSelecionada);
$categorias = $categoriasCadastradas;
?>

<section class="container page-header">
  <h1 class="page-title">Nossas Receitas</h1>
  <p class="page-subtitle">Filtre por categoria pra achar exatamente o que procura.</p>
</section>

<section class="container filtro-categorias">
  <div class="filtro-chips">
    <a href="receitas.php" class="chip<?php echo classeNavAtiva($categoriaSelecionada, ['todas']); ?>">Todas</a>
    <?php foreach ($categorias as $categoria): ?>
      <a href="receitas.php?categoria=<?php echo urlencode($categoria); ?>" class="chip<?php echo classeNavAtiva($categoriaSelecionada, [$categoria]); ?>"><?php echo $categoria; ?></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="container receitas-grid">
  <div class="row g-4">
    <?php
    // RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP (IF, FOREACH)
    if (empty($receitasExibidas)): ?>
      <p class="sem-resultados">Nenhuma receita encontrada nessa categoria.</p>
    <?php else: ?>
      <?php foreach ($receitasExibidas as $receita): ?>
        <div class="col-md-4">
          <a href="receita-detalhe.php?id=<?php echo $receita['id']; ?>" class="card-link">
            <div class="card card-receita"> <!-- Bootstrap: componente Card -->
              <div class="card-thumb <?php echo $receita['thumb']; ?>">
                <?php if ($receita['imagem'] !== ''): ?>
                  <img src="<?php echo $receita['imagem']; ?>" alt="<?php echo $receita['nome']; ?>">
                <?php endif; ?>
              </div>
              <div class="card-body">
                <span class="badge badge-categoria"><?php echo $receita['categoria']; ?></span> <!-- Bootstrap: componente Badge -->
                <h3 class="card-title"><?php echo $receita['nome']; ?></h3>
                <p class="card-meta">⏱ <?php echo formatarTempoPreparo($receita['tempo_preparo']); ?></p>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
