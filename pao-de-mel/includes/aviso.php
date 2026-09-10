<?php
// aviso.php
//
// A faixa de mensagem das telas de cadastro. Fica num arquivo só porque as
// quatro telas do admin mostram exatamente a mesma coisa - e porque a
// rubrica pede mensagem clara em TODAS as operações, não só na primeira
// que alguém lembrou de escrever.
//
// Espera a variável $mensagem, vinda de pegarMensagem().

if (!empty($mensagem)):
    $classe = ($mensagem['tipo'] ?? '') === 'erro' ? 'alert-danger' : 'alert-success';
?>
<section class="container">
  <div class="alert <?php echo $classe; ?> aviso-operacao" role="alert">
    <?php echo escapar($mensagem['texto'] ?? ''); ?>
  </div>
</section>
<?php endif; ?>
