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
           (SELECT COUNT(*) FROM categorias)                    AS categorias_total,
           (SELECT COUNT(*) FROM receitas)                      AS receitas_total,
           (SELECT COUNT(*) FROM receitas r
             WHERE NOT EXISTS (SELECT 1 FROM receita_ingrediente ri WHERE ri.id_receita = r.id))
                                                                AS receitas_sem_ingrediente,
           (SELECT COUNT(*) FROM ingredientes)                  AS ingredientes_total,
           fn_faturamento_periodo(NULL, NULL)                   AS faturamento
")->fetch();

$mensagem = pegarMensagem();

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <h1 class="page-title">Administração</h1>
  <p class="page-subtitle">
    Cadastro de categorias, ingredientes, receitas e vendas.
    Tudo que muda aqui aparece no site e na dashboard.
  </p>
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
      <a href="categorias.php" class="admin-card">
        <span class="admin-card-rotulo">Categorias</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['categorias_total']; ?></span>
        <span class="admin-card-nota">usadas pelas receitas</span>
      </a>
    </div>

    <div class="col-md-4">
      <a href="receitas.php" class="admin-card">
        <span class="admin-card-rotulo">Receitas</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['receitas_total']; ?></span>
        <span class="admin-card-nota">
          <?php echo (int) $resumo['receitas_sem_ingrediente']; ?> sem ingrediente
        </span>
      </a>
    </div>

    <div class="col-md-4">
      <a href="ingredientes.php" class="admin-card">
        <span class="admin-card-rotulo">Ingredientes</span>
        <span class="admin-card-valor"><?php echo (int) $resumo['ingredientes_total']; ?></span>
        <span class="admin-card-nota">catálogo com a unidade de cada um</span>
      </a>
    </div>

  </div>

  <div class="admin-fluxo">
    <h2 class="section-title-sm">A ordem de cadastro</h2>
    <p class="campo-dica">
      Cada etapa depende da anterior: não dá para criar a receita antes da categoria dela.
    </p>
    <ol class="fluxo-etapas">
      <li><strong>Categoria</strong><span>Pão, Doce, Salgado…</span></li>
      <li><strong>Ingrediente</strong><span>com a unidade de medida</span></li>
      <li><strong>Receita</strong><span>escolhe a categoria e os ingredientes</span></li>
      <li><strong>Venda</strong><span>entra no faturamento da dashboard</span></li>
    </ol>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
