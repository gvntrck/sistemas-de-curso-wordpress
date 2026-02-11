---
type: doc
name: tooling
description: Scripts, IDE settings, automation, and developer productivity tips
category: tooling
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Tooling & Productivity Guide

Ferramentas e configurações para desenvolvimento eficiente do LMS SuporteRapido.

## Required Tooling

- **PHP 7.4+**: Runtime do WordPress. Instalado via XAMPP, WAMP, Docker ou servidor dedicado.
- **WordPress 5.0+**: Plataforma base. Instalação local para desenvolvimento.
- **MySQL 5.7+ / MariaDB 10.3+**: Banco de dados. Vem com XAMPP/Docker.
- **Navegador moderno**: Chrome/Firefox com DevTools para debug de AJAX e console JS.
- **Editor/IDE**: VS Code com extensões PHP Intelephense, WordPress Snippets. Ou Windsurf/Cursor.

## Recommended Automation

- **WP-CLI** (opcional): Útil para ativar/desativar plugin, flush rewrite rules, e gerenciar usuários via terminal:
  ```bash
  wp plugin activate sistema-cursos-plugin
  wp rewrite flush
  wp user create aluno-teste aluno@teste.com --role=aluno
  ```
- **Debug**: Ativar `WP_DEBUG` e `WP_DEBUG_LOG` no `wp-config.php` para capturar erros PHP:
  ```php
  define('WP_DEBUG', true);
  define('WP_DEBUG_LOG', true);
  define('WP_DEBUG_DISPLAY', false);
  ```
- **Query Monitor**: Plugin WP recomendado para debug de queries SQL, hooks e performance.

## IDE / Editor Setup

- **PHP Intelephense** (VS Code): Autocomplete e análise estática para PHP
- **WordPress Snippets**: Snippets para hooks, filters e funções WP
- **EditorConfig**: Manter indentação consistente (4 espaços para PHP)
- **GitLens**: Visualizar histórico de alterações por linha

## Productivity Tips

- Use `error_log()` para debug rápido — logs aparecem em `wp-content/debug.log`
- Teste shortcodes criando uma página de teste dedicada no WP
- Use o Customizer do WordPress para ajustar cores do LMS (via `class-customizer.php`)
- Mantenha o `roadmap.txt` atualizado com bugs e features pendentes

Veja também: [Development Workflow](./development-workflow.md)
