---
type: agent
name: Refactoring Specialist
description: Identify code smells and improvement opportunities
agentType: refactoring-specialist
phases: [E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Refactoring Specialist — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress com ~30 arquivos PHP. Código funcional mas com oportunidades de melhoria em organização e reutilização.

## Code Smells Identificados
- **God File**: `sistema-cursos-plugin.php` mistura bootstrap, admin page rendering, CSS inline e JavaScript inline (~1500 linhas)
- **`create_table()` no `init`**: Roda `dbDelta()` em toda requisição. Deveria verificar versão antes.
- **HTML inline em PHP**: Shortcodes geram HTML diretamente em strings PHP. Considerar templates separados.
- **CSS/JS inline no admin**: Estilos e scripts embutidos no PHP do admin page. Considerar enqueue separado.
- **Duplicação**: Padrão de verificação de grupos órfãos repetido em `get_access_source()` e `cleanup_orphaned_group_references()`.

## Oportunidades de Refactoring
- Extrair admin page rendering para arquivo separado
- Criar classe base para shortcodes com método `verify_access()` compartilhado
- Mover CSS/JS inline do admin para arquivos em `assets/`
- Implementar cache layer para `has_access()` e `get_user_courses()`

## Referências
- [Architecture Notes](../docs/architecture.md)
- [Development Workflow](../docs/development-workflow.md)
