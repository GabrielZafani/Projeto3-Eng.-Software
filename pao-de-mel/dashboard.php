<?php
require 'includes/header.php'; // RUBRICA DESENVOLVIMENTO WEB MODERNA - UTILIZAÇÃO DE TEMPLATE COM PHP

// Esta página não consulta o banco pelo PHP: quem busca os dados é o
// TypeScript compilado (dist/app.js), via fetch() no endpoint api.php.
// O PHP aqui só monta a estrutura da tela.
?>

<section class="container page-header">
  <h1 class="page-title">Dashboard de Vendas</h1>
  <p class="page-subtitle">Faturamento da padaria calculado em tempo real a partir da API.</p>
</section>

<!--
  RUBRICA - TRATAMENTO DE CENÁRIOS DE EXCEÇÃO (EDGE CASES)
  Os três blocos abaixo são os estados da tela. Começam escondidos
  (classe d-none do Bootstrap) e o TypeScript mostra o que for o caso.
-->

<!-- ESTADO: carregando - Bootstrap: componente Spinner -->
<section class="container">
  <div id="estado-carregando" class="text-center py-5 d-none">
    <div class="spinner-border text-warning" role="status">
      <span class="visually-hidden">Carregando...</span>
    </div>
    <p class="text-muted mt-3 mb-0">Buscando as vendas na API...</p>
  </div>

  <!-- ESTADO: erro de rede/banco - Bootstrap: componente Alert -->
  <div id="estado-erro" class="alert alert-danger d-none" role="alert">
    <strong>Não foi possível carregar os dados.</strong>
    <span id="estado-erro-msg"></span>
    <div class="mt-2">
      <button id="btn-atualizar" type="button" class="btn btn-sm btn-outline-danger">Tentar de novo</button>
    </div>
  </div>

  <!-- ESTADO: banco vazio - Bootstrap: componente Alert -->
  <div id="estado-vazio" class="alert alert-secondary d-none" role="alert">
    Nenhum dado registrado. Assim que houver vendas no banco, os números aparecem aqui.
  </div>
</section>

<!--
  RUBRICA - AGREGAÇÕES E CÁLCULOS FINANCEIROS (USO DE REDUCE)
  Os quatro números abaixo são preenchidos pelo .reduce() no TypeScript,
  a partir do array bruto que o PHP envia. Bootstrap: componente Card.
-->
<section class="container metricas">
  <div class="row g-4">
    <div class="col-md-3 col-sm-6">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Faturamento total</p>
          <p class="metrica-valor" id="card-faturamento">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Unidades vendidas</p>
          <p class="metrica-valor" id="card-unidades">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Ticket médio</p>
          <p class="metrica-valor" id="card-ticket">—</p>
        </div>
      </div>
    </div>
    <div class="col-md-3 col-sm-6">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Campeão de vendas</p>
          <p class="metrica-valor metrica-valor-sm" id="card-campeao">—</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!--
  RUBRICA - ALGORITMOS DE RANKING E FREQUÊNCIA (DESTAQUES)
  RUBRICA - SEGMENTAÇÃO E FILTROS DE NEGÓCIO (USO DE FILTER)
  RUBRICA - TRANSFORMAÇÃO E FORMATAÇÃO DE ESTRUTURAS (USO DE MAP)

  Os dois blocos abaixo não custam nenhuma consulta a mais: saem do
  mesmo array bruto que já alimentou os cards. O ranking usa um objeto
  de contagem com o nome do produto como chave, o recorte da semana usa
  .filter(), e o texto de cada linha sai de .map().

  A semana mostrada é a última COM MOVIMENTO, não os sete dias a contar
  de hoje - senão o painel apareceria zerado sempre que o banco de
  demonstração ficasse alguns dias sem venda nova.

  Bootstrap: componentes Card e Grid (row/col).
-->
<section class="container destaques">
  <h2 class="section-title-sm">Destaques</h2>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Top 3 produtos por faturamento</p>
          <ol id="lista-ranking" class="lista-ranking">
            <li class="ranking-vazio">—</li>
          </ol>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card card-metrica">
        <div class="card-body">
          <p class="metrica-rotulo">Última semana de movimento</p>
          <p class="metrica-valor" id="periodo-faturamento">—</p>
          <p class="periodo-intervalo" id="periodo-intervalo">—</p>
          <p class="periodo-detalhe" id="periodo-detalhe">—</p>
          <p class="periodo-variacao" id="periodo-variacao">—</p>
        </div>
      </div>
    </div>
  </div>
</section>

<!--
  RUBRICA - STORED PROCEDURES: BUSCA, FILTRO E PAGINAÇÃO
  Nenhum destes controles filtra no navegador. Cada mexida aqui vira um
  CALL sp_vendas_buscar() no banco, com os valores como parâmetro.
  Bootstrap: componentes Input group, Form control e Button.
-->
<section class="container barra-filtros">
  <form id="form-busca" class="input-group busca-produto" role="search">
    <span class="input-group-text" id="rotulo-busca">Buscar</span>
    <input
      type="search"
      class="form-control"
      id="campo-busca"
      name="busca"
      maxlength="100"
      placeholder="ex.: coxinha"
      aria-describedby="rotulo-busca">
    <button class="btn btn-honey" type="submit">Procurar</button>
    <button class="btn btn-outline-honey" type="button" id="btn-limpar">Limpar</button>
  </form>

  <!-- Os botões saem do CALL sp_vendas_categorias(), então nunca aparece
       uma categoria que não tem nenhuma venda para mostrar. -->
  <div id="chips-categoria" class="filtro-chips" role="group" aria-label="Filtrar por categoria"></div>
</section>

<!-- Bootstrap: componente Table (responsiva) -->
<section class="container tabela-vendas">
  <h2 class="section-title">Vendas registradas</h2>
  <p id="resumo-resultado" class="resumo-resultado" role="status" aria-live="polite">—</p>
  <div class="table-responsive">
    <table class="table table-hover align-middle">
      <thead>
        <tr>
          <th scope="col">Venda</th>
          <th scope="col">Produto</th>
          <th scope="col">Categoria</th>
          <th scope="col" class="text-end">Qtd.</th>
          <th scope="col" class="text-end">Valor unit.</th>
          <th scope="col" class="text-end">Subtotal</th>
        </tr>
      </thead>
      <tbody id="tabela-vendas-body">
        <!-- preenchido pelo TypeScript -->
      </tbody>
    </table>
  </div>

  <!--
    Bootstrap: componente Pagination.
    Os botões são desenhados pelo TypeScript a partir do total_paginas
    que a procedure devolveu - a tela não sabe contar páginas sozinha.
  -->
  <nav id="paginacao" aria-label="Navegação entre páginas de vendas">
    <ul class="pagination justify-content-center"></ul>
  </nav>
</section>

<!--
  RUBRICA - INTEGRAÇÃO DE AMBIENTES (XAMPP + COMPILAÇÃO)
  Este arquivo é gerado pelo compilador a partir de src/app.ts.
  Para recompilar depois de editar o .ts: npx tsc
-->
<script src="dist/app.js?v=<?php echo filemtime(__DIR__ . '/dist/app.js'); ?>"></script>

<?php require 'includes/footer.php'; ?>
