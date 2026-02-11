---
type: agent
name: Mobile Specialist
description: Develop native and cross-platform mobile applications
agentType: mobile-specialist
phases: [P, E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Mobile Specialist — LMS SuporteRapido

## Contexto do Projeto
O projeto é um plugin WordPress web-only. Não há app mobile nativo. O frontend é responsivo via CSS e funciona em navegadores mobile.

## Responsabilidades
- Garantir que shortcodes renderizem corretamente em viewports mobile
- Testar interações touch (drag-and-drop no admin pode não funcionar bem em mobile)
- Otimizar CSS para telas pequenas
- Avaliar necessidade futura de PWA ou app nativo

## Estado Atual
- CSS em `assets/css/style.css` — verificar media queries existentes
- jQuery UI Sortable no admin — pode precisar de touch-punch para mobile
- `[lms-painel]` com sidebar — verificar comportamento em telas estreitas

## Referências
- [Frontend Specialist](./frontend-specialist.md)
- [Architecture Notes](../docs/architecture.md)
