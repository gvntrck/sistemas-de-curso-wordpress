---
type: agent
name: Performance Optimizer
description: Identify performance bottlenecks
agentType: performance-optimizer
phases: [E, V]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Performance Optimizer — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress com tabelas customizadas MySQL. Performance crítica em: verificação de acesso (chamada em todo shortcode), listagem de cursos do aluno, e cálculo de progresso.

## Áreas de Atenção
- **`has_access()`**: Chamado em cada renderização de conteúdo protegido. Faz queries ao banco + get_post_meta + get_user_meta. Considerar cache com transients.
- **`get_user_courses()`**: Combina acesso direto (query SQL) + acesso via grupos (múltiplos get_post_meta). Pode ser lento com muitos grupos.
- **`create_table()` no hook `init`**: `dbDelta()` roda em TODA requisição. Considerar verificação de versão antes.
- **`cleanup_orphaned_group_references()`**: Itera todos os cursos, trilhas e usuários. Deve rodar apenas sob demanda (admin action).
- **N+1 queries**: Shortcodes de listagem podem fazer queries individuais por curso/aula.

## Ferramentas de Diagnóstico
- Plugin Query Monitor para WordPress
- `$wpdb->queries` com `SAVEQUERIES` ativado
- `error_log()` com `microtime(true)` para timing

## Referências
- [Architecture Notes](../docs/architecture.md)
- [Data Flow](../docs/data-flow.md)
