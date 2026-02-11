---
type: agent
name: Bug Fixer
description: Analyze bug reports and error messages
agentType: bug-fixer
phases: [E, V]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Bug Fixer — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress LMS com CPTs (Trilha, Curso, Aula, Grupo), tabelas customizadas para acesso e progresso, shortcodes como interface pública, e AJAX para interações dinâmicas.

## Bugs Conhecidos (roadmap.txt)
- Foto na barra lateral não mostra a foto correta
- Informar tabelas e metakeys personalizadas na tela de configuração

## Áreas Comuns de Bugs
- **Acesso negado indevidamente**: Grupos órfãos (deletados mas ainda referenciados). Verificar `get_access_source()` em `class-access-control.php`.
- **AJAX retorna 0**: Action não registrado ou nonce inválido. Verificar `wp_ajax_*` hooks.
- **Shortcode não renderiza**: Plugin desativado ou classe não instanciada no bootstrap.
- **Tabelas não criadas**: `dbDelta()` falhou silenciosamente. Desativar/reativar plugin.
- **Progresso incorreto**: Verificar `wp_progresso_aluno` e a relação aula→curso via post_meta.

## Estratégia de Debug
1. Ativar `WP_DEBUG` e `WP_DEBUG_LOG`
2. Verificar `wp-content/debug.log` para erros PHP
3. Usar console do navegador para erros AJAX
4. Verificar banco de dados diretamente (phpMyAdmin / WP-CLI)
5. Testar com diferentes roles (admin, aluno, visitante)

## Referências
- [Testing Strategy](../docs/testing-strategy.md)
- [Security](../docs/security.md)
