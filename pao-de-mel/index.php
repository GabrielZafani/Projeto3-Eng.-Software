<?php
require 'includes/header.php';
require 'includes/dados-receitas.php'; // RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS
require_once 'includes/funcoes.php'; // RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO
?>

<section class="hero">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <p class="eyebrow">Padaria de bairro desde sempre</p>
        <h1 class="hero-title">Receitas com a alma de quem ama fazer pão</h1>
        <p class="hero-text">Da padaria Pão de Mel para a sua cozinha: ingrediente por ingrediente, passo a passo, sem mistério.</p>
        <a href="receitas.php" class="btn btn-honey">Ver receitas</a>
      </div>
    </div>
  </div>
  <svg class="honey-divider" viewBox="0 0 1200 60" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
    <path d="M0,30 C150,0 300,60 450,30 C600,0 750,60 900,30 C1000,10 1100,40 1190,28" />
    <circle cx="1190" cy="32" r="6"></circle>
  </svg>
</section>

<section class="container destaques">
  <h2 class="section-title">Receitas em destaque</h2>

  <div class="row g-4">
    <?php
    // RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP (FOREACH)
    $destaques = obterDestaques($receitas, 3);
    foreach ($destaques as $receita):
    ?>
      <div class="col-md-4">
        <a href="receita-detalhe.php?id=<?php echo $receita['id']; ?>" class="card-link">
          <div class="card card-receita">
            <div class="card-thumb <?php echo $receita['thumb']; ?>"></div>
            <div class="card-body">
              <span class="badge badge-categoria"><?php echo $receita['categoria']; ?></span>
              <h3 class="card-title"><?php echo $receita['nome']; ?></h3>
              <p class="card-meta">⏱ <?php echo formatarTempoPreparo($receita['tempo_preparo']); ?> · <?php echo $receita['dificuldade']; ?></p>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="text-center mt-5">
    <a href="receitas.php" class="btn btn-outline-honey">Ver todas as receitas</a>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
