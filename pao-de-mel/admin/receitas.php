<?php
// admin/receitas.php - CRUD de receitas, com os ingredientes de cada uma
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - CRUD COMPLETO =====
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - RELACIONAMENTO N:N =====
//
// Receita é a ficha técnica: é dela que o produto herda a categoria.
// Por isso esta tela vem antes da de produtos no fluxo de cadastro.
//
// Os ingredientes só aparecem ao EDITAR, e não ao criar: a linha em
// receita_ingrediente precisa do id da receita, que só existe depois de
// gravada. Tentar montar os dois juntos exigiria segurar os ingredientes
// na sessão - mais código e mais lugar para o dado se perder.

$raiz = '../';

require_once __DIR__ . '/../includes/conexao.php';
require_once __DIR__ . '/../includes/admin.php';
require_once __DIR__ . '/../includes/crud-receitas.php';
require_once __DIR__ . '/../includes/crud-categorias.php';

$destino = 'receitas.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirToken($destino);

    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar') {
        $resultado = salvarReceita(
            $pdo,
            inteiroDoFormulario('id'),
            textoDoFormulario('nome', RECEITA_NOME_LIMITE),
            inteiroDoFormulario('id_categoria'),
            inteiroDoFormulario('tempo_preparo'),
            trim((string) ($_POST['modo_preparo'] ?? ''))
        );

        // Receita nova cai direto na edição dela, que é onde ficam os
        // ingredientes. Sem isso o usuário salvava e ficava sem saber
        // onde acrescentá-los.
        if (!empty($resultado['novo_id'])) {
            $destino = 'receitas.php?editar=' . (int) $resultado['novo_id'];
        }

    } elseif ($acao === 'excluir') {
        $resultado = excluirReceita($pdo, inteiroDoFormulario('id'));

    } elseif ($acao === 'add_ingrediente') {
        $idReceita = inteiroDoFormulario('id_receita');
        $resultado = adicionarIngredienteNaReceita(
            $pdo,
            $idReceita,
            inteiroDoFormulario('id_ingrediente'),
            dinheiroDoFormulario('quantidade')
        );
        $destino = 'receitas.php?editar=' . $idReceita;

    } elseif ($acao === 'rem_ingrediente') {
        $idReceita = inteiroDoFormulario('id_receita');
        $resultado = removerIngredienteDaReceita($pdo, $idReceita, inteiroDoFormulario('id_ingrediente'));
        $destino = 'receitas.php?editar=' . $idReceita;

    } else {
        $resultado = ['ok' => false, 'mensagem' => 'Ação não reconhecida.'];
    }

    redirecionarCom($destino, $resultado['ok'] ? 'ok' : 'erro', $resultado['mensagem']);
}

$mensagem   = pegarMensagem();
$editando   = isset($_GET['editar']) ? buscarReceita($pdo, (int) $_GET['editar']) : null;
$receitas   = listarReceitas($pdo);
$categorias = listarCategorias($pdo);

$ingredientesDaReceita = $editando ? listarIngredientesDaReceita($pdo, (int) $editando['id']) : [];
$ingredientesLivres    = $editando ? ingredientesDisponiveis($pdo, (int) $editando['id']) : [];

require __DIR__ . '/../includes/header.php';
?>

<section class="container page-header">
  <a href="index.php" class="voltar-link">← Voltar para a administração</a>
  <h1 class="page-title">Receitas</h1>
  <p class="page-subtitle">
    A ficha técnica do que a padaria sabe fazer: categoria, tempo de preparo,
    ingredientes e modo de preparo.
  </p>
</section>

<?php require __DIR__ . '/../includes/aviso.php'; ?>

<section class="container admin-tela">
  <div class="row g-5">

    <!-- ---------- FORMULÁRIO DA RECEITA ---------- -->
    <div class="col-lg-5">
      <div class="admin-form">
        <h2 class="section-title-sm">
          <?php echo $editando ? 'Editar receita' : 'Nova receita'; ?>
        </h2>

        <form method="post" action="receitas.php">
          <?php echo campoToken(); ?>
          <input type="hidden" name="acao" value="salvar">
          <input type="hidden" name="id" value="<?php echo (int) ($editando['id'] ?? 0); ?>">

          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input type="text" class="form-control" id="nome" name="nome" required
                   maxlength="<?php echo RECEITA_NOME_LIMITE; ?>"
                   value="<?php echo escapar($editando['nome'] ?? ''); ?>"
                   placeholder="ex.: Brioche Caseiro">
          </div>

          <div class="mb-3">
            <label class="form-label" for="id_categoria">Categoria</label>
            <select class="form-select" id="id_categoria" name="id_categoria" required>
              <option value="">Escolha a categoria</option>
              <?php foreach ($categorias as $categoria): ?>
                <option value="<?php echo (int) $categoria['id']; ?>"
                  <?php echo ((int) ($editando['id_categoria'] ?? 0) === (int) $categoria['id']) ? 'selected' : ''; ?>>
                  <?php echo escapar($categoria['nome']); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <small class="campo-dica">É esta categoria que o produto e a dashboard vão herdar.</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="tempo_preparo">Tempo de preparo (minutos)</label>
            <input type="number" class="form-control" id="tempo_preparo" name="tempo_preparo" required
                   min="1" max="<?php echo RECEITA_TEMPO_MAXIMO; ?>"
                   value="<?php echo (int) ($editando['tempo_preparo'] ?? 60); ?>">
            <small class="campo-dica">240 aparece no site como "4h".</small>
          </div>

          <div class="mb-3">
            <label class="form-label" for="modo_preparo">Modo de preparo</label>
            <textarea class="form-control" id="modo_preparo" name="modo_preparo" rows="7" required
                      placeholder="Um passo por linha."><?php echo escapar($editando['modo_preparo'] ?? ''); ?></textarea>
            <small class="campo-dica">Cada linha vira um passo numerado na página da receita.</small>
          </div>

          <button type="submit" class="btn btn-honey">
            <?php echo $editando ? 'Salvar alterações' : 'Cadastrar e escolher ingredientes'; ?>
          </button>

          <?php if ($editando): ?>
            <a href="receitas.php" class="btn btn-outline-honey">Cancelar</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- ---------- INGREDIENTES DA RECEITA (N:N) ---------- -->
    <div class="col-lg-7">
      <?php if ($editando): ?>
        <div class="admin-form">
          <h2 class="section-title-sm">Ingredientes de "<?php echo escapar($editando['nome']); ?>"</h2>

          <?php if (empty($ingredientesDaReceita)): ?>
            <p class="sem-resultados">
              Nenhum ingrediente ainda. Enquanto estiver assim, esta receita
              <strong>não aparece no site</strong>.
            </p>
          <?php else: ?>
            <ul class="lista-vinculos">
              <?php foreach ($ingredientesDaReceita as $item): ?>
                <li>
                  <span class="vinculo-texto">
                    <?php echo escapar(formatarIngrediente(
                        (float) $item['quantidade'],
                        $item['unidade_medida'],
                        $item['nome']
                    )); ?>
                  </span>
                  <form method="post" action="receitas.php" class="form-inline-acao">
                    <?php echo campoToken(); ?>
                    <input type="hidden" name="acao" value="rem_ingrediente">
                    <input type="hidden" name="id_receita" value="<?php echo (int) $editando['id']; ?>">
                    <input type="hidden" name="id_ingrediente" value="<?php echo (int) $item['id_ingrediente']; ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Remover</button>
                  </form>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>

          <hr class="separador-form">

          <h3 class="rotulo-bloco">Acrescentar ingrediente</h3>

          <?php if (empty($ingredientesLivres)): ?>
            <p class="campo-dica">
              Todos os ingredientes do catálogo já estão nesta receita.
              <a href="ingredientes.php">Cadastre um novo</a> se precisar.
            </p>
          <?php else: ?>
            <form method="post" action="receitas.php" class="form-vinculo">
              <?php echo campoToken(); ?>
              <input type="hidden" name="acao" value="add_ingrediente">
              <input type="hidden" name="id_receita" value="<?php echo (int) $editando['id']; ?>">

              <div class="row g-2 align-items-end">
                <div class="col-sm-6">
                  <label class="form-label" for="id_ingrediente">Ingrediente</label>
                  <select class="form-select" id="id_ingrediente" name="id_ingrediente" required>
                    <option value="">Escolha</option>
                    <?php foreach ($ingredientesLivres as $livre): ?>
                      <option value="<?php echo (int) $livre['id']; ?>">
                        <?php echo escapar($livre['nome']); ?> (<?php echo escapar($livre['unidade_medida']); ?>)
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-sm-3">
                  <label class="form-label" for="quantidade">Quantidade</label>
                  <input type="text" class="form-control" id="quantidade" name="quantidade" required
                         inputmode="decimal" placeholder="500">
                </div>
                <div class="col-sm-3">
                  <button type="submit" class="btn btn-honey w-100">Adicionar</button>
                </div>
              </div>
              <small class="campo-dica">A unidade vem do catálogo do ingrediente. Use vírgula para decimais.</small>
            </form>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <div class="admin-aviso-lateral">
          <p><strong>Como cadastrar uma receita nova</strong></p>
          <ol class="passos-curtos">
            <li>Preencha o formulário ao lado e clique em <em>Cadastrar</em>.</li>
            <li>A tela abre a receita para edição, com o quadro de ingredientes aqui.</li>
            <li>Acrescente um ingrediente por vez, com a quantidade.</li>
            <li>Com pelo menos um ingrediente, a receita passa a aparecer no site.</li>
          </ol>
          <p class="campo-dica">
            Falta um ingrediente no catálogo? <a href="ingredientes.php">Cadastre primeiro</a>.
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ---------- LISTA DE RECEITAS ---------- -->
  <div class="row mt-5">
    <div class="col-12">
      <h2 class="section-title-sm">Cadastradas (<?php echo count($receitas); ?>)</h2>

      <?php if (empty($receitas)): ?>
        <p class="sem-resultados">Nenhuma receita cadastrada ainda.</p>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle admin-tabela">
            <thead>
              <tr>
                <th scope="col">Receita</th>
                <th scope="col">Categoria</th>
                <th scope="col" class="text-end">Preparo</th>
                <th scope="col" class="text-end">Ingredientes</th>
                <th scope="col" class="text-end">Produtos</th>
                <th scope="col" class="text-end">Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($receitas as $receita): ?>
                <?php
                  $semIngredientes = (int) $receita['ingredientes'] === 0;
                  $temProduto      = (int) $receita['produtos'] > 0;
                ?>
                <tr class="<?php echo $semIngredientes ? 'linha-inativa' : ''; ?>">
                  <td>
                    <strong><?php echo escapar($receita['nome']); ?></strong>
                    <?php if ($semIngredientes): ?>
                      <span class="linha-secundaria">sem ingredientes — não aparece no site</span>
                    <?php endif; ?>
                  </td>
                  <td><span class="badge badge-categoria"><?php echo escapar($receita['categoria']); ?></span></td>
                  <td class="text-end"><?php echo escapar(formatarTempoPreparo((int) $receita['tempo_preparo'])); ?></td>
                  <td class="text-end"><?php echo (int) $receita['ingredientes']; ?></td>
                  <td class="text-end"><?php echo (int) $receita['produtos']; ?></td>
                  <td class="text-end acoes-linha">
                    <a href="receitas.php?editar=<?php echo (int) $receita['id']; ?>"
                       class="btn btn-sm btn-outline-honey">Editar</a>

                    <?php if ($temProduto): ?>
                      <button type="button" class="btn btn-sm btn-outline-secondary" disabled
                              title="Há produto nascendo desta receita">Excluir</button>
                    <?php else: ?>
                      <form method="post" action="receitas.php" class="form-inline-acao"
                            onsubmit="return confirm('Excluir a receita <?php echo escapar($receita['nome']); ?>?');">
                        <?php echo campoToken(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="id" value="<?php echo (int) $receita['id']; ?>">
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
