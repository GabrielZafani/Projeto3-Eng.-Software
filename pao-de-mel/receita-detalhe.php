<?php
require 'includes/header.php'; // RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP
require 'includes/dados-receitas.php'; // RUBRICA TECH FORGE - ARMAZENAMENTO ESTRUTURADO COM ARRAYS
require_once 'includes/funcoes.php'; // RUBRICA TECH FORGE - MODULARIZAÇÃO COM FUNÇÕES DE PROCESSAMENTO

// RUBRICA TECH FORGE - VALIDAÇÃO DE REGRAS DE NEGÓCIO COM CONDICIONAIS
// Uma receita com dado inconsistente (ex: tempo de preparo zerado) nunca
// chega até a busca por id — para a página, ela simplesmente não existe.
$receitas = filtrarReceitasValidas($receitas);

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// A busca que antes era um foreach+if direto na página agora é uma função
// reutilizável (RUBRICA TECH FORGE - FLUXO DE DADOS: parâmetro de entrada,
// retorno com o resultado, sem variável global).
$receitaEncontrada = buscarReceitaPorId($receitas, $id);
?>

<section class="container receita-detalhe">
  <a href="receitas.php" class="voltar-link">← Voltar para receitas</a>

  <?php
  // RUBRICA DESENVOLVIMENTO WEB MODERNA - CORRETA UTILIZAÇÃO DE COMANDOS NO PHP (IF, FOREACH)
  if ($receitaEncontrada): ?>
    <div class="receita-header">
      <span class="badge badge-categoria"><?php echo $receitaEncontrada['categoria']; ?></span> <!-- Bootstrap: componente Badge -->
      <h1 class="receita-title"><?php echo $receitaEncontrada['nome']; ?></h1>
      <p class="receita-meta">⏱ Preparo: <?php echo formatarTempoPreparo($receitaEncontrada['tempo_preparo']); ?></p>
    </div>

    <div class="card-thumb <?php echo $receitaEncontrada['thumb']; ?> receita-thumb">
      <?php if ($receitaEncontrada['imagem'] !== ''): ?>
        <img src="<?php echo $receitaEncontrada['imagem']; ?>" alt="<?php echo $receitaEncontrada['nome']; ?>">
      <?php endif; ?>
    </div>

    <div class="row g-5 receita-conteudo">
      <div class="col-lg-4">
        <h2 class="section-title-sm">Ingredientes</h2>
        <ul class="lista-ingredientes">
          <?php foreach ($receitaEncontrada['ingredientes'] as $ingrediente): ?>
            <li><?php echo $ingrediente; ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <div class="col-lg-8">
        <h2 class="section-title-sm">Modo de preparo</h2>
        <ol class="lista-passos">
          <?php foreach ($receitaEncontrada['modo_preparo'] as $passo): ?>
            <li><?php echo $passo; ?></li>
          <?php endforeach; ?>
        </ol>
      </div>
    </div>
  <?php else: ?>
    <div class="page-header">
      <h1 class="page-title">Receita não encontrada</h1>
      <p class="page-subtitle">O id informado não existe na nossa lista de receitas.</p>
    </div>
  <?php endif; ?>
</section>

<?php require 'includes/footer.php'; ?>
