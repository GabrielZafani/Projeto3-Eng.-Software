# Pão de Mel - Blog de Receitas + Dashboard de Vendas

Projeto da disciplina de Engenharia de Software (3º Período).


- **Guia de rubricas** (onde cada item avaliado está no código): [pao-de-mel/guia-rubricas.md](pao-de-mel/guia-rubricas.md)

---

## 1. O que é preciso ter instalado

| Ferramenta | Para quê | Obrigatório? |
|---|---|---|
| **XAMPP** (Apache + MySQL/MariaDB + PHP 8) | rodar o site e o banco | **Sim** |
| Node.js 18+ | recompilar os arquivos `.ts` | Não, o JavaScript já vem compilado em `pao-de-mel/dist/` |

> Não é preciso rodar `npm install` para testar o projeto. A pasta `dist/`
> (JavaScript já compilado) está versionada de propósito justamente para isso.

---

## 2. Colocar o projeto no XAMPP

Copie a pasta **`pao-de-mel`** (só ela, não o repositório inteiro) para dentro do `htdocs`:

```
C:\xampp\htdocs\pao-de-mel\
```

O caminho final tem que ficar assim: `C:\xampp\htdocs\pao-de-mel\index.php`.

Abra o **XAMPP Control Panel** e clique em **Start** em:

- **Apache**
- **MySQL**

Os dois precisam ficar com o fundo verde.

---

## 3. Criar o banco de dados

Os scripts SQL estão em [pao-de-mel/db/](pao-de-mel/db/) e devem ser executados
**nesta ordem** (o segundo depende do primeiro, e o terceiro depende do segundo):

| # | Arquivo | O que faz |
|---|---|---|
| 1 | `padaria.sql` | cria o banco `padaria` e as 4 tabelas de receitas |
| 2 | `dados-teste-9-receitas.sql` | popula com 9 receitas de exemplo |
| 3 | `dashboard-vendas.sql` | cria as tabelas de produtos/vendas, as VIEWS com CTE e as TRIGGERS |

### Opção A: phpMyAdmin (mais simples)

1. Acesse http://localhost/phpmyadmin
2. Aba **Importar**, botão **Escolher arquivo**, selecione `db/padaria.sql` e clique em **Executar**
3. Repita para `db/dados-teste-9-receitas.sql` e depois para `db/dashboard-vendas.sql`

> A partir do arquivo 2, selecione o banco **`padaria`** na barra lateral antes de importar.

### Opção B: linha de comando

```bash
cd C:\xampp\htdocs\pao-de-mel\db
C:\xampp\mysql\bin\mysql -u root < padaria.sql
C:\xampp\mysql\bin\mysql -u root padaria < dados-teste-9-receitas.sql
C:\xampp\mysql\bin\mysql -u root padaria < dashboard-vendas.sql
```

Os scripts 2 e 3 podem ser rodados quantas vezes quiser, pois eles limpam e recriam
tudo antes de inserir.

---

## 4. Conferir as credenciais do banco

As credenciais ficam **só** em [pao-de-mel/includes/conexao.php](pao-de-mel/includes/conexao.php), nas 4 primeiras linhas:

```php
$host   = "localhost";
$dbname = "padaria";
$user   = "root";
$pass   = "";
```

Esses são os valores padrão do XAMPP. **Se o seu MySQL tiver senha para o `root`,
preencha `$pass` com ela.** Nada mais precisa ser alterado.

---

## 5. Abrir o site

Acesse no navegador:

**http://localhost/pao-de-mel/index.php**

> Use o endereço **com `/index.php` no final**. O arquivo `.htaccess` desativa
> a listagem de diretórios por segurança, então `http://localhost/pao-de-mel/`
> (sem o arquivo) retorna 403. Isso é esperado, não é erro de instalação.

### Páginas do sistema

| Página | Endereço | O que mostra |
|---|---|---|
| Início | `/pao-de-mel/index.php` | home com receitas em destaque |
| Receitas | `/pao-de-mel/receitas.php` | listagem com filtro por categoria |
| Detalhe | `/pao-de-mel/receita-detalhe.php?id=1` | ingredientes e modo de preparo |
| **Dashboard** | `/pao-de-mel/dashboard.php` | faturamento calculado em TypeScript a partir da API |
| Contato | `/pao-de-mel/contato.php` | formulário |
| **API (JSON)** | `/pao-de-mel/api.php` | endpoint consumido pelo dashboard |

---

## 6. Verificar se está tudo certo

1. **Banco conectado:** abra `receitas.php` e devem aparecer 9 receitas.
   Se aparecer "Connection failed", revise o passo 4.
2. **API funcionando:** abra `api.php` direto no navegador. Ele deve retornar um
   JSON com 21 linhas de vendas (a 22ª é filtrada pela VIEW, de propósito:
   é um produto inativo).
3. **Dashboard:** abra `dashboard.php` e os 4 cards devem sair do traço para os
   valores calculados, e a tabela deve encher. Se ficar no spinner ou mostrar o
   alerta vermelho, o problema está na API (passo anterior).

### Testar o banco isoladamente

O arquivo [pao-de-mel/db/testes-demonstracao.sql](pao-de-mel/db/testes-demonstracao.sql)
é um roteiro de testes das VIEWS e TRIGGERS, com o resultado esperado comentado
em cada um. Abra no DBeaver/phpMyAdmin com o banco `padaria` selecionado e execute
**um comando por vez** (`Ctrl+Enter` no DBeaver). Não rode o arquivo inteiro.
Todo teste que altera dados já traz a linha que desfaz a alteração logo em seguida.

---

## 7. (Opcional) Recompilar o TypeScript

Só é necessário se você for **editar** os arquivos em `pao-de-mel/src/`:

```bash
cd C:\xampp\htdocs\pao-de-mel
npm install
npm run build     # gera dist/app.js a partir de src/app.ts
```

Para recompilar automaticamente a cada alteração: `npm run watch`.

---

## Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| `403 Forbidden` na pasta | acesso sem `/index.php` | use `http://localhost/pao-de-mel/index.php` |
| `Connection failed: ... Access denied` | senha do MySQL diferente | ajuste `$pass` em `includes/conexao.php` |
| `Unknown database 'padaria'` | scripts SQL não importados | refaça o passo 3, na ordem |
| Dashboard preso no spinner | API retornando erro | abra `api.php` direto e veja a mensagem |
| `api.php` retorna erro de consulta | falta rodar `dashboard-vendas.sql` | importe o arquivo 3 do passo 3 |
| Apache não inicia | porta 80 ocupada (Skype/IIS) | mude a porta do Apache no XAMPP ou encerre o programa que a usa |
| Página baixa em vez de abrir | Apache parado / arquivo fora do `htdocs` | confira o passo 2 |

---

## Estrutura do projeto

```
pao-de-mel/
├── index.php, receitas.php, receita-detalhe.php,
│   contato.php, dashboard.php     # páginas do site
├── api.php                        # endpoint JSON das vendas
├── includes/
│   ├── conexao.php                # conexão PDO (credenciais aqui)
│   ├── header.php / footer.php    # template reaproveitado por todas as páginas
│   ├── funcoes.php                # funções de processamento
│   └── dados-receitas.php
├── src/                           # TypeScript (fonte)
│   ├── app.ts                     # fetch + async/await + reduce
│   └── types.ts
├── dist/                          # JavaScript compilado (versionado)
├── db/                            # scripts SQL
├── assets/css/style.css
└── guia-rubricas.md               # mapa das rubricas para as linhas do código
```
