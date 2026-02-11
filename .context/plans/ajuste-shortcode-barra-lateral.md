---
status: filled
generated: 2026-02-10
agents:
  - type: "code-reviewer"
    role: "Review code changes for quality, style, and best practices"
  - type: "bug-fixer"
    role: "Analyze bug reports and error messages"
  - type: "feature-developer"
    role: "Implement new features according to specifications"
  - type: "refactoring-specialist"
    role: "Identify code smells and improvement opportunities"
  - type: "test-writer"
    role: "Write comprehensive unit and integration tests"
  - type: "documentation-writer"
    role: "Create clear, comprehensive documentation"
  - type: "performance-optimizer"
    role: "Identify performance bottlenecks"
  - type: "security-auditor"
    role: "Identify security vulnerabilities"
  - type: "backend-specialist"
    role: "Design and implement server-side architecture"
  - type: "frontend-specialist"
    role: "Design and implement user interfaces"
  - type: "architect-specialist"
    role: "Design overall system architecture and patterns"
  - type: "devops-specialist"
    role: "Design and maintain CI/CD pipelines"
  - type: "database-specialist"
    role: "Design and optimize database schemas"
  - type: "mobile-specialist"
    role: "Develop native and cross-platform mobile applications"
docs:
  - "project-overview.md"
  - "architecture.md"
  - "development-workflow.md"
  - "testing-strategy.md"
  - "glossary.md"
  - "data-flow.md"
  - "security.md"
  - "tooling.md"
phases:
  - id: "phase-1"
    name: "Discovery & Alignment"
    prevc: "P"
  - id: "phase-2"
    name: "Implementation & Iteration"
    prevc: "E"
  - id: "phase-3"
    name: "Validation & Handoff"
    prevc: "V"
---

# Ajuste de Shortcode Barra Lateral Plan

> Ajuste de Shortcode Barra Lateral

## Task Snapshot
- **Primary goal:** Corrigir e aprimorar o shortcode `[barra-lateral-aluno]` — incluindo bug da foto que não mostra corretamente (roadmap.txt) e melhorias de UX.
- **Success signal:** Barra lateral exibe avatar correto do aluno, links funcionais, progresso geral atualizado, e layout responsivo em mobile.
- **Key references:**
  - [Documentation Index](../docs/README.md)
  - [Agent Handbook](../agents/README.md)
  - [Plans Index](./README.md)

## Codebase Context
- **Arquivo principal:** `includes/shortcodes/class-shortcode-barra-lateral.php`
- **Template de referência:** `modelo-barra-lateral.html`
- **CSS:** `assets/css/style.css`
- **Dependências:** `System_Cursos_Access_Control`, `System_Cursos_Progress`

## Agent Lineup
| Agent | Role in this plan | Playbook | First responsibility focus |
| --- | --- | --- | --- |
| Bug Fixer | Investigar e corrigir bug da foto incorreta na barra lateral | [Bug Fixer](../agents/bug-fixer.md) | Diagnosticar causa raiz do avatar incorreto |
| Frontend Specialist | Ajustar HTML/CSS da barra lateral para responsividade | [Frontend Specialist](../agents/frontend-specialist.md) | Corrigir layout e estilos |
| Code Reviewer | Revisar alterações antes do merge | [Code Reviewer](../agents/code-reviewer.md) | Validar segurança e padrões |

## Documentation Touchpoints
| Guide | File | Primary Inputs |
| --- | --- | --- |
| Project Overview | [project-overview.md](../docs/project-overview.md) | Roadmap, README, stakeholder notes |
| Architecture Notes | [architecture.md](../docs/architecture.md) | ADRs, service boundaries, dependency graphs |
| Development Workflow | [development-workflow.md](../docs/development-workflow.md) | Branching rules, CI config, contributing guide |
| Testing Strategy | [testing-strategy.md](../docs/testing-strategy.md) | Test configs, CI gates, known flaky suites |
| Glossary & Domain Concepts | [glossary.md](../docs/glossary.md) | Business terminology, user personas, domain rules |
| Data Flow & Integrations | [data-flow.md](../docs/data-flow.md) | System diagrams, integration specs, queue topics |
| Security & Compliance Notes | [security.md](../docs/security.md) | Auth model, secrets management, compliance requirements |
| Tooling & Productivity Guide | [tooling.md](../docs/tooling.md) | CLI scripts, IDE configs, automation workflows |

## Risk Assessment
Identify potential blockers, dependencies, and mitigation strategies before beginning work.

### Identified Risks
| Risk | Probability | Impact | Mitigation Strategy | Owner |
| --- | --- | --- | --- | --- |
| Avatar depende de get_avatar() do WP | Medium | Medium | Verificar se Gravatar ou user_meta photo está configurado | Bug Fixer |
| CSS pode conflitar com tema ativo | Low | Medium | Usar prefixos CSS específicos do plugin | Frontend Specialist |

### Dependencies
- **Internal:** `System_Cursos_Progress` para cálculo de progresso geral
- **External:** WordPress `get_avatar()` / Gravatar
- **Technical:** Nenhuma atualização de dependência necessária

### Assumptions
- O avatar do aluno é gerenciado via Gravatar ou plugin de avatar local
- Os links da barra lateral são configuráveis via atributos do shortcode

## Resource Estimation

### Time Allocation
| Phase | Estimated Effort | Calendar Time | Team Size |
| --- | --- | --- | --- |
| Phase 1 - Discovery | 1 hora | 1 dia | 1 pessoa |
| Phase 2 - Implementation | 2-3 horas | 1 dia | 1 pessoa |
| Phase 3 - Validation | 1 hora | 1 dia | 1 pessoa |
| **Total** | **4-5 horas** | **1-2 dias** | **1 pessoa** |

### Required Skills
- PHP WordPress (shortcodes, get_avatar, user_meta)
- CSS responsivo
- Debug de AJAX/frontend

## Working Phases
### Phase 1 — Discovery & Alignment
**Steps**
1. Analisar `class-shortcode-barra-lateral.php` para entender como o avatar é carregado.
2. Identificar se o bug é no `get_avatar()`, no user_meta, ou no CSS.
3. Verificar `modelo-barra-lateral.html` para comparar com o output atual.

**Commit Checkpoint**
- After completing this phase, capture the agreed context and create a commit (for example, `git commit -m "chore(plan): complete phase 1 discovery"`).

### Phase 2 — Implementation & Iteration
**Steps**
1. Corrigir a lógica de carregamento do avatar no shortcode.
2. Ajustar CSS para responsividade mobile da barra lateral.
3. Atualizar versão no header do plugin.

**Commit Checkpoint**
- Summarize progress, update cross-links, and create a commit documenting the outcomes of this phase (for example, `git commit -m "chore(plan): complete phase 2 implementation"`).

### Phase 3 — Validation & Handoff
**Steps**
1. Testar barra lateral com diferentes alunos (com/sem foto, com/sem cursos).
2. Testar em viewport mobile e desktop.
3. Verificar que progresso geral é calculado corretamente.

**Commit Checkpoint**
- Record the validation evidence and create a commit signalling the handoff completion (for example, `git commit -m "chore(plan): complete phase 3 validation"`).

## Rollback Plan
Document how to revert changes if issues arise during or after implementation.

### Rollback Triggers
When to initiate rollback:
- Critical bugs affecting core functionality
- Performance degradation beyond acceptable thresholds
- Data integrity issues detected
- Security vulnerabilities introduced
- User-facing errors exceeding alert thresholds

### Rollback Procedures
#### Phase 1 Rollback
- Action: Discard discovery branch, restore previous documentation state
- Data Impact: None (no production changes)
- Estimated Time: < 1 hour

#### Phase 2 Rollback
- Action: Reverter commits no Git, restaurar versão anterior do shortcode
- Data Impact: Nenhum (alterações apenas em código PHP/CSS)
- Estimated Time: < 30 minutos

#### Phase 3 Rollback
- Action: Reverter para versão anterior do plugin
- Data Impact: Nenhum
- Estimated Time: < 15 minutos

### Post-Rollback Actions
1. Document reason for rollback in incident report
2. Notify stakeholders of rollback and impact
3. Schedule post-mortem to analyze failure
4. Update plan with lessons learned before retry

## Evidence & Follow-up

List artifacts to collect (logs, PR links, test runs, design notes). Record follow-up actions or owners.
