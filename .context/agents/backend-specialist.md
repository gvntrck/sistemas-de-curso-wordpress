---
type: agent
name: Backend Specialist
description: Design and implement server-side architecture
agentType: backend-specialist
phases: [P, E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Backend Specialist — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress PHP puro. Backend opera via WordPress Plugin API (hooks, actions, filters). Banco de dados MySQL via `$wpdb` com tabelas customizadas e post_meta.

## Responsabilidades
- Implementar classes PHP em `includes/` seguindo o padrão existente
- Criar/modificar AJAX handlers (`wp_ajax_*`)
- Gerenciar tabelas customizadas via `dbDelta()`
- Implementar lógica de negócio (acesso, progresso, certificados, quizzes)

## Stack Técnica
- PHP 7.4+ / WordPress 5.0+
- MySQL via `$wpdb->prepare()` (obrigatório para segurança)
- Hooks: `add_action()`, `add_filter()`, `add_shortcode()`
- Nonces para CSRF, `current_user_can()` para autorização

## Padrões
- Classe por funcionalidade, instanciada no bootstrap (`sistema-cursos-plugin.php`)
- Métodos estáticos para operações de banco
- Sanitização de inputs: `sanitize_text_field()`, `intval()`, `sanitize_key()`
- Sempre atualizar versão no header do plugin ao fazer alterações

## Referências
- [Architecture Notes](../docs/architecture.md)
- [Security](../docs/security.md)
