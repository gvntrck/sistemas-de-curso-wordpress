---
type: doc
name: project-overview
description: High-level overview of the project, its purpose, and key components
category: overview
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Project Overview

O **LMS SuporteRapido** é um plugin WordPress que funciona como um sistema completo de gerenciamento de cursos online (LMS), alternativa ao LearnDash. Ele permite criar trilhas de aprendizado, cursos, aulas, quizzes, certificados e gerenciar matrículas de alunos com controle de acesso granular. Beneficia administradores de plataformas educacionais e alunos que consomem conteúdo.

## Codebase Reference

> **Análise Detalhada**: Para contagem completa de símbolos, camadas de arquitetura e grafos de dependência, veja [`codebase-map.json`](./codebase-map.json).

## Quick Facts

- **Root**: `c:\Users\Administrador\Documents\windsurf\94\sistemas-de-curso-wordpress`
- **Linguagem principal**: PHP (20+ arquivos), JavaScript (5 arquivos), CSS (3 arquivos)
- **Entry point**: `sistema-cursos-plugin.php`
- **Versão atual**: 1.5.9
- **Autor**: Giovani Tureck
- **Text Domain**: `lms-suporte-rapido`
- **Análise completa**: [`codebase-map.json`](./codebase-map.json)

## Entry Points

- [`sistema-cursos-plugin.php`](../../sistema-cursos-plugin.php) — Arquivo principal do plugin, carrega todas as dependências e inicializa classes.
- [`includes/class-access-control.php`](../../includes/class-access-control.php) — Controle de acesso e painel administrativo de alunos.
- [`includes/class-woocommerce-integration.php`](../../includes/class-woocommerce-integration.php) — Integração com WooCommerce para venda de cursos.

## Key Exports

O plugin registra shortcodes WordPress como API pública:

- `[lms-painel]` — Painel SPA completo (recomendado)
- `[barra-lateral-aluno]` — Sidebar de navegação do aluno
- `[barra-progresso-geral]` — Barra de progresso visual
- `[cadastro-usuario]` — Formulário de cadastro de alunos
- `[certificado]` — Exibição/geração de certificados
- `[lista-aulas]` — Listagem de aulas de um curso
- `[meus-cursos]` — Cursos matriculados do aluno
- `[minha-conta]` — Perfil do aluno
- `[cursos-trilha]` — Cursos de uma trilha
- `[single-trilha]` — Página individual de trilha
- `[resultado-busca]` — Resultados de busca de aulas

Referência completa em [`codebase-map.json`](./codebase-map.json).

## File Structure & Code Organization

- `sistema-cursos-plugin.php` — Arquivo principal: bootstrap, menu admin, configurações.
- `includes/` — Classes PHP do core (CPT Manager, Access Control, Progress, Certificates, Quiz, WooCommerce, Customizer, etc.).
- `includes/shortcodes/` — Classes de shortcodes (cada shortcode em seu próprio arquivo).
- `assets/css/` — Estilos frontend e admin (style.css, admin-quiz-builder.css, frontend-quiz.css).
- `assets/js/` — Scripts frontend e admin (script.js, certificado.js, admin-quiz-builder.js, admin-metaboxes.js).
- `modelo.html` / `modelo-barra-lateral.html` — Templates HTML de referência.
- `roadmap.txt` — Funcionalidades planejadas e bugs conhecidos.

## Technology Stack Summary

- **Runtime**: PHP 7.4+ no WordPress 5.0+
- **Frontend**: JavaScript vanilla + jQuery (dependência do WordPress)
- **CSS**: CSS puro, sem pré-processadores
- **Banco de dados**: MySQL via WordPress `$wpdb` (tabelas customizadas + post meta)
- **Integração**: WooCommerce (opcional, para venda de cursos)
- **Build**: Nenhum — o plugin é carregado diretamente pelo WordPress

## Core Framework Stack

- **Backend**: WordPress Plugin API (hooks, actions, filters, shortcodes)
- **Custom Post Types**: Trilha, Curso, Aula, Grupo de Alunos (via `register_post_type`)
- **Tabelas customizadas**: `wp_acesso_cursos`, `wp_acesso_cursos_log`, `wp_progresso_aluno`
- **Admin**: WordPress Admin API (menus, metaboxes, AJAX handlers)
- **E-commerce**: WooCommerce Product API (campos customizados, hooks de pedido)

## Getting Started Checklist

1. Copie a pasta do plugin para `wp-content/plugins/`.
2. Ative o plugin no painel WordPress em **Plugins**.
3. Acesse **LMS SuporteRapido > Configuração** no menu admin.
4. Crie Trilhas, Cursos e Aulas nos respectivos menus.
5. Crie uma página e insira o shortcode `[lms-painel]` para o painel completo.
6. Reveja [Development Workflow](./development-workflow.md) para tarefas do dia a dia.

## Next Steps

- Consulte o `roadmap.txt` para funcionalidades planejadas (agendamento de aulas, log de atividades, certificado por grupo).
- Veja [Architecture Notes](./architecture.md) para entender a estrutura interna.
- Veja [Tooling](./tooling.md) para configuração do ambiente de desenvolvimento.
