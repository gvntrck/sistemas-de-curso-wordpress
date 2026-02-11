---
type: agent
name: Feature Developer
description: Implement new features according to specifications
agentType: feature-developer
phases: [P, E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Feature Developer — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress LMS. Novas features seguem o padrão: criar classe PHP → registrar hooks no construtor → instanciar no bootstrap → criar shortcode se necessário.

## Checklist para Nova Feature
1. Criar classe em `includes/` (ou `includes/shortcodes/` se for shortcode)
2. Seguir naming: `System_Cursos_<NomeDaFeature>`
3. Registrar hooks no `__construct()`
4. Adicionar `require_once` e `new` no `sistema-cursos-plugin.php`
5. Atualizar versão no header do plugin
6. Se shortcode: documentar na aba admin "Shortcodes"
7. Se tabela nova: criar via `dbDelta()` no hook `init`
8. Se AJAX: registrar `wp_ajax_*` com nonce

## Features Planejadas (roadmap.txt)
- Continuar Assistindo na home
- Log de Atividades
- Aulas em ordem dependente do curso
- Nome do curso no resultado de busca de aula
- Liberar aula/trilha com agenda e mensagem de bloqueio
- Certificado por grupo de alunos

## Referências
- [Architecture Notes](../docs/architecture.md)
- [Development Workflow](../docs/development-workflow.md)
- [Glossary](../docs/glossary.md)
