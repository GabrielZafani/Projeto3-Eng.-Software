<?php
// admin/vendas.php - CRUD 3 de 3
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - 3 CRUDs COMPLETOS =====
// Inclusão, consulta, edição e exclusão da venda.
//
// A "exclusão" aqui cancela em vez de apagar, porque venda é o próprio
// histórico do caixa. A regra está em includes/crud-vendas.php.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/crud-vendas.php';

$destino = 'vendas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirToken($destino);

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $resultado = salvarVenda(
            $pdo,
            inteiroDoFormulario('id'),
            inteiroDoFormulario('id_produto'),
            inteiroDoFormulario('quantidade'),
            trim((string) ($_POST['data_venda'] ?? ''))
        );
    } elseif ($acao === 'excluir') {
        $resultado = cancelarVenda($pdo, inteiroDoFormulario('id'));
    } elseif ($acao === 'reativar') {
        $resultado = reativarVenda($pdo, inteiroDoFormulario('id'));
    } else {
        $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];
    }

    redirecionarCom($destino, $resultado['ok'] ? 'ok' : 'erro', $resultado['mensagem']);
}

$mensagem    = pegarMensagem();
$editando    = isset($_GET['editar']) ? buscarVenda($pdo, (int) $_GET['editar']) : null;
$vendas      = listarVendas($pdo);
$produtos    = listarProdutosParaVenda($pdo);
$faturamento = faturamentoAtual($pdo);

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <a href="index.php" class="voltar-link">← Voltar para a administração</a>
  <h1 class="page-title">Vendas</h1>
  <p class="page-subtitle">
    Cada linha é uma venda no caixa. Faturamento atual:
    <strong><?php echo escapar(formatarDinheiro($faturamento)); ?></strong>.
  </p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-tela">
  <div class="row g-5">

    <div class="col-lg-4">
      <div class="admin-form">
        <h2 class="section-title-sm">
          <?php echo $editando ? 'Editar venda #' . (int) $editando['id'] : 'Nova venda'; ?>
        </h2>

        <form method="post" action="vendas.php">
          <?php echo campoToken(); ?>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?php echo (int) ($editando['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label" for="id_produto">Produto</label>
            <select class="form-select" id="id_produto" name="id_produto" required>
              <option value="">Escolha o produto</option>
              <?php foreach ($produtos as $produto): ?>
                <option value="<?php echo (int) $produto['id']; ?>"
                  <?php echo ((int) ($editando['id_produto'] ?? 0) === (int) $produto['id']) ? 'selected' : ''; ?>>
                  <?php echo escapar($produto['nome']); ?>
                  — <?php echo escapar(formatarDinheiro((float) $produto['valor_unitario'])); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="campo-dica">Produto fora de linha não aparece aqui.</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="quantidade">Quantidade</label>
            <input type="number" class="form-control" id="quantidade" name="quantidade" required
                   min="1" max="<?php echo VENDA_QUANTIDADE_MAXIMA; ?>"
                   value="<?php echo (int) ($editando['quantidade'] ?? 1); ?>">
          </div>

          <div class="mb-3">
            <label class="form-label" for="data_venda">Data</label>
            <input type="date" class="form-control" id="data_venda" name="data_venda" required
                   max="<?php echo date('Y-m-d'); ?>"
                   value="<?php echo escapar($editando['data_venda'] ?? date('Y-m-d')); ?>">
          </div>

          <button type="submit" class="btn btn-honey">
            <?php echo $editando ? 'Salvar alterações' : 'Registrar venda'; ?>
          </button>

          <?php if ($editando): ?>
            <a href="vendas.php" class="btn btn-outline-honey">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="col-lg-8">
      <h2 class="section-title-sm">Registradas (<?php echo count($vendas); ?>)</h2>

      <?php if (empty($vendas)): ?>
        <p class="sem-resultados">Nenhuma venda registrada ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle admin-tabela">
            <thead>
              <tr>
                <th scope="col">Venda</th>
                <th scope="col">Produto</th>
                <th scope="col">Data</th>
                <th scope="col" class="text-end">Qtd.</th>
                <th scope="col" class="text-end">Subtotal</th>
                <th scope="col" class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($vendas as $venda): ?>
                <?php $cancelada = (bool) $venda['cancelada']; ?>
                <tr class="<?php echo $cancelada ? 'linha-inativa' : ''; ?>">
                  <td>
                    #<?php echo (int) $venda['id']; ?>
                    <?php if ($cancelada): ?>
                      <span class="selo selo-inativo">Cancelada</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <strong><?php echo escapar($venda['produto']); ?></strong>
                    <?php if (!$venda['produto_ativo']): ?>
                      <span class="linha-secundaria">produto fora de linha</span>
                    <?php endif; ?>
                  </td>
                  <td><?php echo escapar(date('d/m/Y', strtotime($venda['data_venda']))); ?></td>
                  <td class="text-end"><?php echo (int) $venda['quantidade']; ?></td>
                  <td class="text-end">
                    <?php echo escapar(formatarDinheiro((float) $venda['subtotal'])); ?>
                  </td>
                  <td class="text-end acoes-linha">
                    <a href="vendas.php?editar=<?php echo (int) $venda['id']; ?>"
                       class="btn btn-sm btn-outline-honey">Editar</a>

                    <?php if ($cancelada): ?>
                      <form method="post" action="vendas.php" class="form-inline-acao">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="reativar">
                        <input type="hidden" name="id" value="<?php echo (int) $venda['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-outline-honey">Reativar</button>
                      </form>
                    <?php else: ?>
                      <form method="post" action="vendas.php" class="form-inline-acao"
                            onsubmit="return confirm('Cancelar a venda #<?php echo (int) $venda['id']; ?>? Ela sai do faturamento, mas o registro fica no banco.');">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo (int) $venda['id']; ?>">
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
