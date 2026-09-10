<?php
// admin/index.php - painel da área de administração
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - ESTRUTURA DO PROJETO =====
// A área administrativa fica numa pasta só dela. O site que o visitante vê
// continua na raiz, sem se misturar com as telas de cadastro.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';

// Números do painel, para a tela abrir mostrando o estado real do sistema
// em vez de três botões soltos.
$resumo = $pdo->query("
    SELECT (SELECT COUNT(*) FROM vendas)                        AS vendas_total,
           (SELECT COUNT(*) FROM vendas WHERE cancelada = TRUE) AS vendas_canceladas,
           (SELECT COUNT(*) FROM produtos)                      AS produtos_total,
           (SELECT COUNT(*) FROM produtos WHERE ativo = FALSE)  AS produtos_fora,
           (SELECT COUNT(*) FROM categorias)                    AS categorias_total,
           fn_faturamento_periodo(NULL, NULL)                   AS faturamento
")->fetch();

$mensagem = pegarMensagem();

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <h1 class="page-title">Administração</h1>
  <p class="page-subtitle">Cadastro de vendas, produtos e categorias. Tudo que muda aqui aparece na dashboard.</p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-painel">
  <div class="row g-4">

    <div class="col-md-4">
      <a href="vendas.php" class="admin-card">
        <span class="admin-card-rotulo">Vendas</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['vendas_total']; ?></span>
        <span class="admin-card-nota">
          <?php echo (int) $resumo['vendas_canceladas']; ?> cancelada(s) ·
          faturamento <?php echo escapar(formatarDinheiro((float) $resumo['faturamento'])); ?>
        </span>
      </a>
    </div>

    <div class="col-md-4">
      <a href="produtos.php" class="admin-card">
        <span class="admin-card-rotulo">Produtos</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['produtos_total']; ?></span>
        <span class="admin-card-nota"><?php echo (int) $resumo['produtos_fora']; ?> fora de linha</span>
      </a>
    </div>

    <div class="col-md-4">
      <a href="categorias.php" class="admin-card">
        <span class="admin-card-rotulo">Categorias</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['categorias_total']; ?></span>
        <span class="admin-card-nota">usadas pelas receitas</span>
      </a>
    </div>

  </div>

  <div class="admin-regras">
    <h2 class="section-title-sm">O que acontece ao excluir</h2>
    <dl class="lista-regras">
      <dt>Categoria</dt>
      <dd>É apagada de verdade — mas o banco recusa enquanto houver receita usando ela.</dd>

      <dt>Produto</dt>
      <dd>Sai de linha em vez de ser apagado. As vendas que ele já teve continuam contando no faturamento.</dd>

      <dt>Venda</dt>
      <dd>É cancelada, não apagada. O registro fica no banco e sai do faturamento.</dd>
    </dl>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
