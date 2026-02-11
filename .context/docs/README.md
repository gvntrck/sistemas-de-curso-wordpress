# Índice de Documentação — LMS SuporteRapido

Base de conhecimento do plugin WordPress LMS SuporteRapido. Comece pelo Project Overview e depois explore os guias específicos.

## Guias Principais
- [Project Overview](./project-overview.md) — Visão geral do projeto, stack, entry points e getting started
- [Architecture Notes](./architecture.md) — Arquitetura, camadas, padrões de design e decisões técnicas
- [Development Workflow](./development-workflow.md) — Fluxo de desenvolvimento, branching e contribuição
- [Testing Strategy](./testing-strategy.md) — Estratégia de testes, quality gates e troubleshooting
- [Glossary & Domain Concepts](./glossary.md) — Terminologia, entidades de domínio e regras de negócio
- [Data Flow & Integrations](./data-flow.md) — Fluxo de dados, dependências entre módulos e integrações
- [Security & Compliance Notes](./security.md) — Segurança, autenticação, dados sensíveis
- [Tooling & Productivity Guide](./tooling.md) — Ferramentas, IDE, automação e dicas de produtividade

## Estrutura do Repositório
- `sistema-cursos-plugin.php` — Arquivo principal do plugin (bootstrap, admin, configuração)
- `includes/` — Classes PHP do core (13 arquivos)
- `includes/shortcodes/` — Classes de shortcodes (12 arquivos)
- `assets/css/` — Estilos (3 arquivos)
- `assets/js/` — Scripts (5 arquivos)
- `modelo.html` / `modelo-barra-lateral.html` — Templates HTML de referência
- `roadmap.txt` — Features planejadas e bugs conhecidos

## Mapa de Documentos
| Guia | Arquivo | Conteúdo Principal |
| --- | --- | --- |
| Project Overview | `project-overview.md` | Visão geral, stack, entry points, getting started |
| Architecture Notes | `architecture.md` | Camadas, padrões, API pública, trade-offs |
| Development Workflow | `development-workflow.md` | Branching, dev local, code review, onboarding |
| Testing Strategy | `testing-strategy.md` | Tipos de teste, quality gates, troubleshooting |
| Glossary | `glossary.md` | CPTs, tabelas, enums, personas, regras de domínio |
| Data Flow | `data-flow.md` | Dependências, fluxo AJAX, WooCommerce, observabilidade |
| Security | `security.md` | Auth, nonces, sanitização, anti-pirataria |
| Tooling | `tooling.md` | PHP, WP-CLI, debug, IDE setup |

## Recursos Relacionados
- [Agent Handbook](../agents/README.md) — Playbooks para agentes AI
- [Plans](../plans/README.md) — Planos de execução
- [Codebase Map](./codebase-map.json) — Análise automatizada do codebase
- [AGENTS.md](../../AGENTS.md) — Guia de colaboração com agentes
