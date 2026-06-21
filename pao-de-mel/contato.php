<?php require 'includes/header.php'; ?>

<section class="container page-header">
  <h1 class="page-title">Fale com a Pão de Mel</h1>
  <p class="page-subtitle">Visite a padaria ou mande uma mensagem.</p>
</section>

<section class="container contato-grid">
  <div class="row g-5">
    <div class="col-lg-6">
      <h2 class="section-title-sm">Onde estamos</h2>
      <ul class="lista-contato">
        <li><strong>Endereço:</strong> Rua das Tulipas, 123 — Centro</li>
        <li><strong>Telefone:</strong> (44) 99999-0000</li>
        <li><strong>Horário:</strong> Terça a domingo, 06h às 19h</li>
      </ul>
    </div>
    <div class="col-lg-6">
      <h2 class="section-title-sm">Mande uma mensagem</h2>
      <form>
        <div class="mb-3">
          <label class="form-label" for="nome">Nome</label>
          <input type="text" class="form-control" id="nome" name="nome">
        </div>
        <div class="mb-3">
          <label class="form-label" for="mensagem">Mensagem</label>
          <textarea class="form-control" id="mensagem" name="mensagem" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-honey">Enviar</button>
      </form>
    </div>
  </div>
</section>

<?php require 'includes/footer.php'; ?>
