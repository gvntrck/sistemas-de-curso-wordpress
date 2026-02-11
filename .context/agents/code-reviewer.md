---
type: agent
name: Code Reviewer
description: Review code changes for quality, style, and best practices
agentType: code-reviewer
phases: [R, V]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Code Reviewer — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress PHP puro. Sem build pipeline, sem linter configurado. Erros de lint são toleráveis (regra do projeto).

## Checklist de Review
- [ ] Nonces em todos os formulários e AJAX handlers
- [ ] `current_user_can()` antes de ações administrativas
- [ ] `$wpdb->prepare()` em todas as queries SQL
- [ ] Sanitização de inputs (`sanitize_text_field`, `intval`, `sanitize_key`)
- [ ] Escaping de output (`esc_html`, `esc_attr`, `esc_url`)
- [ ] Versão do plugin atualizada no header ao fazer alterações
- [ ] Classe segue padrão existente (hooks no construtor, métodos estáticos para banco)
- [ ] Shortcodes testados com roles: admin, aluno, visitante

## Code Style
- Indentação: 4 espaços
- Chaves na mesma linha (estilo Allman para classes, K&R para funções)
- Prefixo `System_Cursos_` para classes
- Prefixo `sistema_cursos_` para funções globais
- Nomes de tabelas: `$wpdb->prefix . 'nome_tabela'`

## Referências
- [Development Workflow](../docs/development-workflow.md)
- [Security](../docs/security.md)
