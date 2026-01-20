---
status: completed
generated: 2026-01-20
description: Restringir acesso ao painel admin e barra superior para a role 'aluno'.
agents:
  - type: "feature-developer"
    role: "Implementar as restrições de acesso e visualização"
  - type: "security-auditor"
    role: "Garantir que o bloqueio seja efetivo e seguro"
docs:
  - "includes/role-aluno.php"
phases:
  - id: "phase-1"
    name: "Implementação"
    prevc: "E"
  - id: "phase-2"
    name: "Validação"
    prevc: "V"
---

# Plano de Restrição de Acesso para Alunos

> Implementar restrições de segurança para usuários com a role 'aluno', impedindo acesso ao painel administrativo (wp-admin) e ocultando a barra de ferramentas (admin bar) no front-end.

## Objetivo
Garantir que alunos tenham uma experiência focada apenas no conteúdo do curso (front-end), sem acesso a ferramentas administrativas do WordPress.

## Arquivos Afetados
- `includes/role-aluno.php`: Será modificado para incluir os hooks de restrição.

## Estratégia de Implementação

### 1. Bloqueio de Acesso ao Admin
Utilizar o hook `admin_init` para verificar as permissões do usuário atual.
- **Verificação:** Se o usuário possui a role `aluno` e NÃO é uma requisição AJAX.
- **Ação:** Redirecionar para a página inicial (`home_url()`).

### 2. Ocultar Barra de Administração
Utilizar o filtro `show_admin_bar` (ou hook `after_setup_theme`).
- **Verificação:** Se o usuário possui a role `aluno`.
- **Ação:** Retornar `false` para ocultar a barra.

## Código Proposto

```php
// Adicionar ao final de includes/role-aluno.php

add_action('admin_init', function() {
    // Permitir AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    $user = wp_get_current_user();
    
    // Verificar se é aluno e não tem permissões administrativas extras
    if (in_array('aluno', (array) $user->roles)) {
        wp_redirect(home_url());
        exit;
    }
});

add_action('after_setup_theme', function() {
    $user = wp_get_current_user();
    if (in_array('aluno', (array) $user->roles)) {
        show_admin_bar(false);
    }
});
```

## Validação
- **Caso de Teste 1:** Logar como aluno e tentar acessar `/wp-admin`. Resultado esperado: Redirecionamento para a home.
- **Caso de Teste 2:** Logar como aluno e visualizar a home. Resultado esperado: Barra preta do WP não deve aparecer.
- **Caso de Teste 3:** Logar como administrador. Resultado esperado: Acesso normal ao admin e barra visível.
