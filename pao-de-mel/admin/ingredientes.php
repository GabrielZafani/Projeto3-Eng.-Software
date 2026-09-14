<?php
// admin/ingredientes.php - CRUD do catálogo de ingredientes
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD COMPLETO =====
// Inclusão, consulta, edição e exclusão do ingrediente.
//
// Este é o catálogo: a lista do que a padaria usa. Quem liga ingrediente
// a receita é a tela de receitas.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/crud-ingredientes.php';

$destino = 'ingredientes.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirToken($destino);

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $resultado = salvarIngrediente(
            $pdo,
            inteiroDoFormulario('id'),
            textoDoFormulario('nome', INGREDIENTE_NOME_LIMITE),
            textoDoFormulario('unidade_medida', INGREDIENTE_UNIDADE_LIMITE)
        );
    } elseif ($acao === 'excluir') {
        $resultado = excluirIngrediente($pdo, inteiroDoFormulario('id'));
    } else {
        $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];
    }

    redirecionarCom($destino, $resultado['ok'] ? 'ok' : 'erro', $resultado['mensagem']);
}

$mensagem     = pegarMensagem();
$editando     = isset($_GET['editar']) ? buscarIngrediente($pdo, (int) $_GET['editar']) : null;
$ingredientes = listarIngredientes($pdo);

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <a href="index.php" class="voltar-link">← Voltar para a administração</a>
  <h1 class="page-title">Ingredientes</h1>
  <p class="page-subtitle">O catálogo do que a padaria usa. Cada um com a unidade em que é medido.</p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-tela">
  <div class="row g-5">

    <div class="col-lg-4">
      <div class="admin-form">
        <h2 class="section-title-sm">
          <?php echo $editando ? 'Editar ingrediente' : 'Novo ingrediente'; ?>
        </h2>

        <form method="post" action="ingredientes.php">
          <?php echo campoToken(); ?>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?php echo (int) ($editando['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required
                   maxlength="<?php echo INGREDIENTE_NOME_LIMITE; ?>"
                   value="<?php echo escapar($editando['nome'] ?? ''); ?>"
                   placeholder="ex.: Farinha de trigo">
          </div>

          <div class="mb-3">
            <label class="form-label" for="unidade_medida">Unidade de medida</label>
            <select class="form-select" id="unidade_medida" name="unidade_medida" required>
              <option value="">Escolha</option>
              <?php foreach (INGREDIENTE_UNIDADES as $unidade): ?>
                <option value="<?php echo escapar($unidade); ?>"
                  <?php echo (($editando['unidade_medida'] ?? '') === $unidade) ? 'selected' : ''; ?>>
                  <?php echo escapar($unidade); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="campo-dica">Lista fechada de propósito: sem isso o grama vira "g", "gr" e "gramas".</small>
          </div>

          <button type="submit" class="btn btn-honey">
            <?php echo $editando ? 'Salvar alterações' : 'Cadastrar'; ?>
          </button>

          <?php if ($editando): ?>
            <a href="ingredientes.php" class="btn btn-outline-honey">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <h2 class="section-title-sm">No catálogo (<?php echo count($ingredientes); ?>)</h2>

      <?php if (empty($ingredientes)): ?>
        <p class="sem-resultados">Nenhum ingrediente cadastrado ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle admin-tabela">
            <thead>
              <tr>
                <th scope="col">Ingrediente</th>
                <th scope="col">Unidade</th>
                <th scope="col" class="text-end">Receitas</th>
                <th scope="col" class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($ingredientes as $ingrediente): ?>
                <?php $emUso = (int) $ingrediente['receitas'] > 0; ?>
                <tr>
                  <td><strong><?php echo escapar($ingrediente['nome']); ?></strong></td>
                  <td><span class="selo selo-ativo"><?php echo escapar($ingrediente['unidade_medida']); ?></span></td>
                  <td class="text-end"><?php echo (int) $ingrediente['receitas']; ?></td>
                  <td class="text-end acoes-linha">
                    <a href="ingredientes.php?editar=<?php echo (int) $ingrediente['id']; ?>"
                       class="btn btn-sm btn-outline-honey">Editar</a>

                    <?php if ($emUso): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                              title="Há receitas usando este ingrediente">Excluir</button>
                    <?php else: ?>
                      <form method="post" action="ingredientes.php" class="form-inline-acao"
                            onsubmit="return confirm('Excluir <?php echo escapar($ingrediente['nome']); ?> do catálogo?');">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo (int) $ingrediente['id']; ?>">
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
