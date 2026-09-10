<?php
// admin.php
//
// ===== RUBRICA DESENVOLVIMENTO WEB AVANÇADA - ESTRUTURA DO PROJETO =====
// Infraestrutura comum das três telas de cadastro. Sem este arquivo, o
// controle de mensagem e o token de formulário estariam copiados três
// vezes - e bastaria esquecer um para abrir um buraco.

require_once __DIR__ . '/funcoes.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// ====================================================================
// MENSAGEM ENTRE REQUISIÇÕES (padrão Post / Redirect / Get)
// ====================================================================
// Depois de gravar, a tela NÃO imprime o resultado direto: ela redireciona
// e a mensagem viaja pela sessão. É isso que evita o clássico "reenviar
// formulário?" do navegador, que ao ser confirmado gravava a venda duas vezes.

function guardarMensagem(string $tipo, string $texto): void
{
    $_SESSION['mensagem'] = ['tipo' => $tipo, 'texto' => $texto];
}

/** Lê e APAGA a mensagem: ela tem que aparecer uma vez só. */
function pegarMensagem(): ?array
{
    if (!isset($_SESSION['mensagem'])) {
        return null;
    }

    $mensagem = $_SESSION['mensagem'];
    unset($_SESSION['mensagem']);

    return $mensagem;
}

function redirecionarCom(string $destino, string $tipo, string $texto): void
{
    guardarMensagem($tipo, $texto);
    header('Location: ' . $destino);
    exit;
}


// ====================================================================
// TOKEN DE FORMULÁRIO (proteção contra requisição forjada)
// ====================================================================
// Sem o token, bastava alguém abrir uma página com
// <img src="admin/vendas.php?acao=cancelar&id=5"> para cancelar a venda de
// quem estivesse logado na administração, sem clique nenhum. O token muda
// por sessão e só quem carregou o formulário de verdade tem o valor.

function tokenFormulario(): string
{
    if (empty($_SESSION['token'])) {
        $_SESSION['token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['token'];
}

function tokenValido(?string $enviado): bool
{
    if (empty($_SESSION['token']) || !is_string($enviado)) {
        return false;
    }

    // hash_equals compara em tempo constante: um == comum vaza, pelo tempo
    // de resposta, quantos caracteres iniciais o atacante já acertou.
    return hash_equals($_SESSION['token'], $enviado);
}

/** Barra qualquer POST sem token válido antes de tocar no banco. */
function exigirToken(string $destino): void
{
    if (!tokenValido($_POST['token'] ?? null)) {
        redirecionarCom($destino, 'erro', 'Sessão expirada. Abra o formulário de novo e repita a operação.');
    }
}

/** Campo escondido que vai em todo formulário de gravação. */
function campoToken(): string
{
    return '<input type="hidden" name="token" value="' . escapar(tokenFormulario()) . '">';
}


// ====================================================================
// ENTRADA DO USUÁRIO
// ====================================================================

/** Texto vindo do formulário: sem espaço nas pontas e com tamanho limitado. */
function textoDoFormulario(string $campo, int $limite): string
{
    $valor = trim((string) ($_POST[$campo] ?? ''));

    // O corte é aqui e não no banco: passar de VARCHAR(100) derruba a
    // gravação com "Data too long", e o usuário via só uma tela de erro.
    if (mb_strlen($valor) > $limite) {
        $valor = mb_substr($valor, 0, $limite);
    }

    return $valor;
}

function inteiroDoFormulario(string $campo): int
{
    return (int) ($_POST[$campo] ?? 0);
}

/** Aceita "12,50" e "12.50": o teclado brasileiro produz vírgula. */
function dinheiroDoFormulario(string $campo): float
{
    $valor = str_replace(',', '.', trim((string) ($_POST[$campo] ?? '')));

    return (float) $valor;
}
