---
type: doc
name: development-workflow
description: Day-to-day engineering processes, branching, and contribution guidelines
category: workflow
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Development Workflow

O LMS SuporteRapido é um plugin WordPress sem pipeline de build. O fluxo de desenvolvimento consiste em editar arquivos PHP/JS/CSS diretamente e testar em uma instalação WordPress local ou staging.

## Branching & Releases

- **Branch principal**: `main` — código estável em produção
- **Feature branches**: `feature/<nome>` para novas funcionalidades
- **Bugfix branches**: `fix/<nome>` para correções
- **Versionamento**: Atualizar o header `Version:` em `sistema-cursos-plugin.php` e a constante `SISTEMA_CURSOS_VERSION` a cada release
- **Convenção de commits**: Conventional Commits (ex: `feat(shortcode): adicionar painel SPA`)

## Local Development

- **Pré-requisito**: WordPress instalado localmente (XAMPP, Local by Flywheel, Docker, etc.)
- **Instalar**: Copiar/clonar a pasta do plugin para `wp-content/plugins/sistema-cursos-plugin/`
- **Ativar**: Painel WP → Plugins → Ativar "LMS SuporteRapido"
- **Testar**: Acessar o frontend com um usuário com role `aluno` e verificar shortcodes
- **Admin**: Acessar **LMS SuporteRapido** no menu admin para configuração

Não há `npm install`, `composer install` ou etapa de build. O plugin é carregado diretamente pelo WordPress.

## Code Review Expectations

- Verificar se nonces são usados em todos os formulários e AJAX handlers
- Confirmar que `current_user_can()` é verificado antes de ações administrativas
- Validar que dados de entrada são sanitizados (`sanitize_text_field`, `intval`, etc.)
- Testar shortcodes no frontend com diferentes roles (admin, aluno, visitante)
- Verificar compatibilidade com WooCommerce quando alterações tocam `class-woocommerce-integration.php`
- Consultar `AGENTS.md` para dicas de colaboração com agentes AI

## Onboarding Tasks

1. Leia o [Project Overview](./project-overview.md) para entender o escopo
2. Familiarize-se com os CPTs: Trilha, Curso, Aula, Grupo
3. Crie conteúdo de teste (1 trilha, 2 cursos, 3 aulas cada)
4. Teste o shortcode `[lms-painel]` como aluno
5. Revise o `roadmap.txt` para próximas tarefas

Veja também: [Testing Strategy](./testing-strategy.md) | [Tooling](./tooling.md)
