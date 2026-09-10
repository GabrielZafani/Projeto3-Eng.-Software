<?php
// admin/categorias.php - CRUD 1 de 3
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - 3 CRUDs COMPLETOS =====
// Inclusão, consulta, edição e exclusão da categoria.
//
// A tela não conhece nenhuma regra: ela chama as funções de
// includes/crud-categorias.php e mostra a frase que voltou de lá.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/crud-categorias.php';

$destino = 'categorias.php';

// ---------------------------------------------------------------
// GRAVAÇÃO (Post / Redirect / Get)
// Todo POST termina em redirecionamento. Sem isso, um F5 depois de
// cadastrar reenviava o formulário e criava a linha de novo.
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirToken($destino);

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $resultado = salvarCategoria(
            $pdo,
            inteiroDoFormulario('id'),
            textoDoFormulario('nome', CATEGORIA_NOME_LIMITE)
        );
    } elseif ($acao === 'excluir') {
        $resultado = excluirCategoria($pdo, inteiroDoFormulario('id'));
    } else {
        $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];
    }

    redirecionarCom($destino, $resultado['ok'] ? 'ok' : 'erro', $resultado['mensagem']);
}

// ---------------------------------------------------------------
// LEITURA
// ---------------------------------------------------------------
$mensagem   = pegarMensagem();
$editando   = isset($_GET['editar']) ? buscarCategoria($pdo, (int) $_GET['editar']) : null;
$categorias = listarCategorias($pdo);

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <a href="index.php" class="voltar-link">← Voltar para a administração</a>
  <h1 class="page-title">Categorias</h1>
  <p class="page-subtitle">O rótulo que agrupa as receitas. É ele que vira botão de filtro na dashboard.</p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-tela">
  <div class="row g-5">

    <!-- ---------- FORMULÁRIO: INCLUIR e EDITAR ---------- -->
    <div class="col-lg-4">
      <div class="admin-form">
        <h2 class="section-title-sm">
          <?php echo $editando ? 'Editar categoria' : 'Nova categoria'; ?>
        </h2>

        <form method="post" action="categorias.php">
          <?php echo campoToken(); ?>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?php echo (int) ($editando['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required
                   maxlength="<?php echo CATEGORIA_NOME_LIMITE; ?>"
                   value="<?php echo escapar($editando['nome'] ?? ''); ?>"
                   placeholder="ex.: Salgado">
          </div>

          <button type="submit" class="btn btn-honey">
            <?php echo $editando ? 'Salvar alterações' : 'Cadastrar'; ?>
          </button>

          <?php if ($editando): ?>
            <a href="categorias.php" class="btn btn-outline-honey">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- ---------- LISTA: CONSULTAR e EXCLUIR ---------- -->
    <div class="col-lg-8">
      <h2 class="section-title-sm">Cadastradas (<?php echo count($categorias); ?>)</h2>

      <?php if (empty($categorias)): ?>
        <p class="sem-resultados">Nenhuma categoria cadastrada ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle admin-tabela">
            <thead>
              <tr>
                <th scope="col">Nome</th>
                <th scope="col" class="text-end">Receitas</th>
                <th scope="col" class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($categorias as $categoria): ?>
                <?php $emUso = (int) $categoria['receitas'] > 0; ?>
                <tr>
                  <td><strong><?php echo escapar($categoria['nome']); ?></strong></td>
                  <td class="text-end"><?php echo (int) $categoria['receitas']; ?></td>
                  <td class="text-end acoes-linha">
                    <a href="categorias.php?editar=<?php echo (int) $categoria['id']; ?>"
                       class="btn btn-sm btn-outline-honey">Editar</a>

                    <!--
                      O botão de excluir aparece desabilitado quando a
                      categoria está em uso. Isso é só cortesia: quem forjar
                      o POST ainda esbarra na chave estrangeira do banco,
                      que é a trava de verdade.
                    -->
                    <?php if ($emUso): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                              title="Há receitas usando esta categoria">Excluir</button>
                    <?php else: ?>
                      <form method="post" action="categorias.php" class="form-inline-acao"
                            onsubmit="return confirm('Excluir a categoria <?php echo escapar($categoria['nome']); ?>?');">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo (int) $categoria['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                      </form>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
