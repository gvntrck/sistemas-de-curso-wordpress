# Implementation Plan - Refinamento da UX: Modais para Edição de Aluno

Este plano visa reverter o layout da tela de "Detalhes do Aluno" para o modo de visualização (somente leitura) e introduzir botões de ação que abrem modais específicos para edição de dados e alteração de senha, mantendo a interface limpa e organizada.

## User Review Required

> [!NOTE]
> O layout principal voltará a ser de visualização. A edição será feita exclusivamente via modais (popups) acionados por botões no card de perfil.

- **Arquivo Principal**: `includes/class-access-control.php`.
- **Estratégia**: Manter o handler de backend "DRY" (reutilizável), permitindo atualizações parciais (só senha ou só dados) a partir de formulários distintos.

## Proposed Changes

### 1. Reverter Layout de Visualização
**Arquivo**: `includes/class-access-control.php`

- **Método `render_details_page`**:
    - Remover as tags `<form>` e `<input>` das seções "Documentos e Contato" e "Endereço".
    - Restaurar a exibição dos dados usando `echo $format_meta(...)`.
    - Remover a seção "Segurança" (caixa vermelha) que estava exposta na tela principal.

### 2. Adicionar Botões de Ação
**Arquivo**: `includes/class-access-control.php`

- **Método `render_details_page`** (Card de Perfil):
    - Localizar a div do card de perfil (onde tem foto, nome, email).
    - Inserir/Posicionar os novos botões *acima* do botão "Editar Perfil Completo":
        - `<button class="button" onclick="openModal('modal-dados')">Alterar Dados Cadastrais</button>`
        - `<button class="button" onclick="openModal('modal-senha')">Alterar Senha</button>`
    - Ajustar estilos para espaçamento vertical.

### 3. Implementar Modais e Scripts
**Arquivo**: `includes/class-access-control.php`

- **Final do Método `render_details_page`**:
    - Adicionar HTML para dois modais (overlay + content):
        1.  **Modal de Dados Cadastrais**:
            - Formulário contendo todos os campos editáveis (CPF, Aniversário, Telefone, Instagram, Endereço completo).
            - Hidden inputs necessários (`action`, `user_id`, `nonce`).
        2.  **Modal de Senha**:
            - Formulário contendo apenas o campo "Nova Senha".
            - Hidden inputs necessários.
    - Adicionar CSS inline (estilo `display: none` para ocultar, position fixed para overlay).
    - Adicionar JavaScript simples para funções `openModal(id)` e `closeModal(id)`.

## Verification Plan

### Testes Manuais
1.  Acessar **LMS SuporteRapido > Lista de Alunos > Detalhes**.
2.  Verificar se a página carregou com o layout "limpo" (sem inputs, apenas texto).
3.  Verificar se os novos botões aparecem no card de perfil.
4.  Clicar em "Alterar Dados Cadastrais":
    - O modal deve abrir.
    - Preencher dados e salvar.
    - Confirmar se a página recarrega e os dados foram atualizados.
5.  Clicar em "Alterar Senha":
    - O modal deve abrir.
    - Preencher senha e salvar.
    - Verificar login com a nova senha.
6.  Verificar se o estilo está responsivo e não quebrado.
