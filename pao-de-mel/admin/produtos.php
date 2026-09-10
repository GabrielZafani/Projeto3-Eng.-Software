<?php
// admin/produtos.php - CRUD 2 de 3
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - 3 CRUDs COMPLETOS =====
// Inclusão, consulta, edição e exclusão do produto.
//
// A "exclusão" aqui tira de linha em vez de apagar, porque produto aparece
// no histórico de vendas. A regra está em includes/crud-produtos.php.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/crud-produtos.php';

$destino = 'produtos.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirToken($destino);

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $resultado = salvarProduto(
            $pdo,
            inteiroDoFormulario('id'),
            textoDoFormulario('nome', PRODUTO_NOME_LIMITE),
            dinheiroDoFormulario('valor_unitario'),
            inteiroDoFormulario('id_receita')
        );
    } elseif ($acao === 'excluir') {
        $resultado = tirarProdutoDeLinha($pdo, inteiroDoFormulario('id'));
    } elseif ($acao === 'reativar') {
        $resultado = voltarProdutoParaLinha($pdo, inteiroDoFormulario('id'));
    } else {
        $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];
    }

    redirecionarCom($destino, $resultado['ok'] ? 'ok' : 'erro', $resultado['mensagem']);
}

$mensagem = pegarMensagem();
$editando = isset($_GET['editar']) ? buscarProduto($pdo, (int) $_GET['editar']) : null;
$produtos = listarProdutos($pdo);
$receitas = listarReceitasParaProduto($pdo);

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <a href="index.php" class="voltar-link">← Voltar para a administração</a>
  <h1 class="page-title">Produtos</h1>
  <p class="page-subtitle">O que a padaria vende no balcão. Cada produto nasce de uma receita.</p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-tela">
  <div class="row g-5">

    <div class="col-lg-4">
      <div class="admin-form">
        <h2 class="section-title-sm">
          <?php echo $editando ? 'Editar produto' : 'Novo produto'; ?>
        </h2>

        <form method="post" action="produtos.php">
          <?php echo campoToken(); ?>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?php echo (int) ($editando['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required
                   maxlength="<?php echo PRODUTO_NOME_LIMITE; ?>"
                   value="<?php echo escapar($editando['nome'] ?? ''); ?>"
                   placeholder="ex.: Coxinha de Frango (un.)">
          </div>

          <div class="mb-3">
            <label class="form-label" for="valor_unitario">Preço de balcão</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" class="form-control" id="valor_unitario" name="valor_unitario" required
                     inputmode="decimal"
                     value="<?php echo escapar(isset($editando['valor_unitario']) ? number_format((float) $editando['valor_unitario'], 2, ',', '') : ''); ?>"
                     placeholder="8,00">
            </div>
            <small class="campo-dica">Use vírgula para os centavos.</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="id_receita">Receita de origem</label>
            <select class="form-select" id="id_receita" name="id_receita" required>
              <option value="">Escolha uma receita</option>
              <?php foreach ($receitas as $receita): ?>
                <option value="<?php echo (int) $receita['id']; ?>"
                  <?php echo ((int) ($editando['id_receita'] ?? 0) === (int) $receita['id']) ? 'selected' : ''; ?>>
                  <?php echo escapar($receita['nome']); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <button type="submit" class="btn btn-honey">
            <?php echo $editando ? 'Salvar alterações' : 'Cadastrar'; ?>
          </button>

          <?php if ($editando): ?>
            <a href="produtos.php" class="btn btn-outline-honey">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <h2 class="section-title-sm">Cadastrados (<?php echo count($produtos); ?>)</h2>

      <?php if (empty($produtos)): ?>
        <p class="sem-resultados">Nenhum produto cadastrado ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle admin-tabela">
            <thead>
              <tr>
                <th scope="col">Produto</th>
                <th scope="col">Situação</th>
                <th scope="col" class="text-end">Preço</th>
                <th scope="col" class="text-end">Vendas</th>
                <th scope="col" class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($produtos as $produto): ?>
                <?php $ativo = (bool) $produto['ativo']; ?>
                <tr class="<?php echo $ativo ? '' : 'linha-inativa'; ?>">
                  <td>
                    <strong><?php echo escapar($produto['nome']); ?></strong>
                    <span class="linha-secundaria"><?php echo escapar($produto['receita']); ?></span>
                  </td>
                  <td>
                    <span class="selo <?php echo $ativo ? 'selo-ativo' : 'selo-inativo'; ?>">
                      <?php echo $ativo ? 'Em linha' : 'Fora de linha'; ?>
                    </span>
                  </td>
                  <td class="text-end"><?php echo escapar(formatarDinheiro((float) $produto['valor_unitario'])); ?></td>
                  <td class="text-end"><?php echo (int) $produto['vendas']; ?></td>
                  <td class="text-end acoes-linha">
                    <a href="produtos.php?editar=<?php echo (int) $produto['id']; ?>"
                       class="btn btn-sm btn-outline-honey">Editar</a>

                    <?php if ($ativo): ?>
                      <form method="post" action="produtos.php" class="form-inline-acao"
                            onsubmit="return confirm('Tirar de linha o produto <?php echo escapar($produto['nome']); ?>? As vendas dele continuam contando.');">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo (int) $produto['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="produtos.php" class="form-inline-acao">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="reativar">
                        <input type="hidden" name="id" value="<?php echo (int) $produto['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-honey">Reativar</button>
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
