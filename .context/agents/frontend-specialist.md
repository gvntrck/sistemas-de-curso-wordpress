---
type: agent
name: Frontend Specialist
description: Design and implement user interfaces
agentType: frontend-specialist
phases: [P, E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Frontend Specialist — LMS SuporteRapido

## Contexto do Projeto
Frontend renderizado via shortcodes PHP que geram HTML. JavaScript vanilla + jQuery para interações dinâmicas. CSS puro sem pré-processadores. O `[lms-painel]` funciona como SPA via AJAX.

## Responsabilidades
- Implementar/modificar HTML gerado pelos shortcodes em `includes/shortcodes/`
- Desenvolver interações JS em `assets/js/script.js`
- Estilizar componentes em `assets/css/style.css`
- Garantir que AJAX calls usem nonces e tratem erros

## Stack Técnica
- jQuery (dependência do WordPress core)
- jQuery UI Sortable (admin: ordenação drag-and-drop)
- CSS puro (sem Tailwind, Bootstrap ou SASS)
- AJAX via `jQuery.ajax()` com `ajaxurl` do WordPress

## Funções JS Existentes
- `initMasks()` — Máscaras de input
- `isAulaConcluida()` / `atualizarBarraProgresso()` / `atualizarBotaoConcluir()` — Progresso de aulas
- `imprimirCertificado()` — Impressão de certificado
- `render()` / `renderQuestions()` / `renderOptions()` — Quiz builder admin

## Referências
- [Architecture Notes](../docs/architecture.md)
- [Data Flow](../docs/data-flow.md)
- Templates HTML: `modelo.html`, `modelo-barra-lateral.html`
