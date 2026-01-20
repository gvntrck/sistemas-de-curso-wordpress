# Implementation Plan - Adicionar Edição de Dados e Senha do Aluno

Este plano detalha as alterações necessárias para adicionar a funcionalidade de edição de dados cadastrais (endereço, documentos, contato) e alteração de senha na tela de "Detalhes do Aluno" no painel administrativo.

## User Review Required

> [!IMPORTANT]
> A atualização da senha será feita diretamente usando `wp_update_user`. Isso não requer a senha antiga, pois é uma ação administrativa.

- **Arquivo Principal**: `includes/class-access-control.php`
- **Ação**: Criar novo handler no `admin_process` e modificar o HTML do `render_details_page`.

## Proposed Changes

### 1. Atualizar Módulo de Controle de Acesso
**Arquivo**: `includes/class-access-control.php`

- **Método `admin_process`**:
    - Adicionar verificação para `$_POST['update_student_data']`.
    - Verificar nonce `aluno_update_data`.
    - Sanitizar e atualizar os seguintes campos via `update_user_meta`:
        - Contato: `billing_phone` (e `phone` como fallback), `instagram`.
        - Documentos: `cpf`, `aniversario`.
        - Endereço: `cep`, `rua`, `numero`, `complemento`, `bairro`, `cidade`, `estado`.
    - Verificar se `new_password` foi preenchido.
        - Se sim, atualizar senha via `wp_update_user`.
    - Adicionar mensagem de sucesso `dados_atualizados` ao redirecionamento.

- **Método `render_details_page`**:
    - Envelopar as seções "Documentos e Contato" e "Endereço" em um `<form method="post">`.
    - Adicionar `wp_nonce_field('aluno_update_data')` e input hidden `user_id`.
    - Adicionar hidden input `update_student_data`.
    - Substituir a exibição de texto estático (`echo $format_meta(...)`) por inputs HTML (`<input type="text" ...>`) preenchidos com os valores atuais.
    - Adicionar nova seção (card) "Segurança" ou "Alterar Senha" dentro do formulário.
        - Campo de senha (tipo password) com placeholder sugerindo que deixar em branco mantém a atual.
    - Adicionar botão de submit "Salvar Alterações".
    - Adicionar tratamento para exibir mensagem de sucesso `dados_atualizados`.

### 2. Atualizar Versão do Plugin
**Arquivo**: `sistema-cursos-plugin.php`

- Incrementar a versão do plugin de `1.2.34` para `1.2.35` nas linhas:
    - Cabeçalho do plugin.
    - Constante de versão na classe (se houver/docblock).
    - Função `sistema_cursos_check_version`.

## Verification Plan

### Testes Manuais
1.  Acessar o painel administrativo do WordPress.
2.  Ir para **LMS SuporteRapido > Lista de Alunos**.
3.  Clicar em "Ver Detalhes" de um aluno.
4.  Verificar se os campos de endereço e contato agora são inputs editáveis.
5.  Alterar alguns dados (ex: telefone, cidade) e clicar em "Salvar Alterações".
6.  Verificar se a página recarrega com a mensagem de sucesso e se os dados persistem.
7.  Preencher o campo "Nova Senha" e salvar.
8.  Tentar fazer login com esse usuário em uma janela anônima usando a nova senha para confirmar a alteração.
9.  Deixar o campo de senha em branco, alterar outro dado e salvar. Verificar se a senha *não* foi alterada (login antigo ainda funciona).
