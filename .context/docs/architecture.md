---
type: doc
name: architecture
description: System architecture, layers, patterns, and design decisions
category: architecture
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Architecture Notes

O LMS SuporteRapido é um plugin WordPress monolítico que segue o padrão de arquitetura de plugins WP: um arquivo principal que carrega classes especializadas via `require_once`, cada uma registrando seus próprios hooks. O design prioriza simplicidade e independência de dependências externas (sem ACF, sem frameworks JS pesados).

## System Architecture Overview

O plugin opera como um **monolito modular** dentro do ecossistema WordPress. Todas as requisições passam pelo ciclo de vida padrão do WP (`init` → `template_redirect` → render). O shortcode `[lms-painel]` funciona como uma SPA server-side, onde seções são carregadas via AJAX sem recarregar a página.

Fluxo principal:
1. WordPress carrega `sistema-cursos-plugin.php`
2. Todas as classes são instanciadas e registram seus hooks
3. No frontend, shortcodes renderizam HTML
4. Interações do aluno (concluir aula, navegar) usam AJAX → PHP handlers → banco de dados

## Architectural Layers

- **Bootstrap**: Arquivo principal `sistema-cursos-plugin.php` — carrega dependências e inicializa classes
- **Core**: `includes/` — Classes de domínio (CPT Manager, Access Control, Progress, Certificates, Quiz)
- **Shortcodes**: `includes/shortcodes/` — Camada de apresentação, cada shortcode em classe isolada
- **Assets**: `assets/js/` e `assets/css/` — Scripts e estilos frontend/admin
- **Integração**: `class-woocommerce-integration.php` — Ponte com WooCommerce

> Veja [`codebase-map.json`](./codebase-map.json) para contagem completa de símbolos e grafos de dependência.

## Detected Design Patterns

| Pattern | Confidence | Locations | Description |
|---------|------------|-----------|-------------|
| Singleton-like Init | 90% | `sistema-cursos-plugin.php` | Cada classe é instanciada uma vez no bootstrap |
| Active Record | 85% | `class-access-control.php`, `class-course-progress.php` | Métodos estáticos operam diretamente no banco |
| Observer (Hooks) | 95% | Todas as classes | WordPress action/filter hooks para desacoplamento |
| Strategy | 70% | `get_access_source()` | Verifica acesso por múltiplas fontes (direto, grupo, trilha) |
| Template Method | 75% | Shortcodes | Cada shortcode segue padrão: verificar login → verificar acesso → renderizar |

## Entry Points

- [`sistema-cursos-plugin.php`](../../sistema-cursos-plugin.php) — Bootstrap principal
- AJAX endpoints registrados via `wp_ajax_*` (ex: `lista_aulas_toggle_concluida`, `salvar_ordem_trilhas`)
- Shortcodes registrados via `add_shortcode()` em cada classe de shortcode

## Public API

| Symbol | Type | Location |
|--------|------|----------|
| `System_Cursos_Access_Control::has_access()` | static method | `includes/class-access-control.php` |
| `System_Cursos_Access_Control::grant_access()` | static method | `includes/class-access-control.php` |
| `System_Cursos_Access_Control::revoke_access()` | static method | `includes/class-access-control.php` |
| `System_Cursos_Access_Control::get_user_courses()` | static method | `includes/class-access-control.php` |
| `System_Cursos_Progress::is_lesson_completed()` | static method | `includes/class-course-progress.php` |
| `System_Cursos_Progress::get_completed_lessons()` | static method | `includes/class-course-progress.php` |
| `System_Cursos_Certificates` | class | `includes/class-certificates.php` |
| `System_Cursos_CPT_Manager::register_cpts()` | method | `includes/class-cpt-manager.php` |

## Internal System Boundaries

- **Domínio de Acesso**: `class-access-control.php` é o ponto central para verificar/conceder/revogar acessos. Todas as outras classes consultam `has_access()`.
- **Domínio de Progresso**: `class-course-progress.php` gerencia a tabela `wp_progresso_aluno` e é consultado pelos shortcodes de listagem.
- **Domínio de Conteúdo**: `class-cpt-manager.php` registra CPTs e metaboxes. Shortcodes consomem dados via `get_post_meta()` e `WP_Query`.
- **Domínio de E-commerce**: `class-woocommerce-integration.php` é isolado e só carrega se WooCommerce estiver ativo.

## Key Decisions & Trade-offs

- **Sem ACF**: Metaboxes nativos para eliminar dependência de plugin externo.
- **Tabelas customizadas** para acesso e progresso (performance) em vez de apenas `post_meta`.
- **jQuery** em vez de framework JS moderno — compatibilidade com WordPress core.
- **Shortcodes** como API pública — padrão WordPress familiar para administradores.
- **SPA via AJAX** no `[lms-painel]` — experiência moderna sem necessidade de React/Vue.

## Top Directories Snapshot

- `includes/` — 13 arquivos PHP (classes core)
- `includes/shortcodes/` — 12 arquivos PHP (shortcodes)
- `assets/js/` — 5 arquivos JavaScript
- `assets/css/` — 3 arquivos CSS

## Related Resources

- [Project Overview](./project-overview.md)
- [Data Flow & Integrations](./data-flow.md)
- [`codebase-map.json`](./codebase-map.json)
