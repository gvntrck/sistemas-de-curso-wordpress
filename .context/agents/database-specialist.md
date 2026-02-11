---
type: agent
name: Database Specialist
description: Design and optimize database schemas
agentType: database-specialist
phases: [P, E]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Database Specialist — LMS SuporteRapido

## Contexto do Projeto
MySQL via WordPress `$wpdb`. Combina tabelas customizadas (alta frequência) com post_meta (configurações). Tabelas criadas via `dbDelta()` no hook `init`.

## Tabelas Customizadas
- **`wp_acesso_cursos`**: Matrículas (user_id, curso_id, status, data_fim). UNIQUE em (user_id, curso_id).
- **`wp_acesso_cursos_log`**: Auditoria de ações de acesso (action, actor_id, details JSON).
- **`wp_progresso_aluno`**: Aulas concluídas (user_id, aula_id, curso_id, pontuacao). UNIQUE em (user_id, aula_id).

## Post Meta Keys
- `trilha` (em curso) — ID da trilha pai
- `curso` (em aula) — ID do curso pai
- `_grupos_permitidos` (em curso/trilha) — Array de IDs de grupos
- `_grupo_conteudos` (em grupo) — Array de IDs de cursos/trilhas
- `_aluno_grupos` (em user_meta) — Array de IDs de grupos do aluno
- `_login_history` (em user_meta) — Array de logins recentes

## Responsabilidades
- Otimizar queries SQL (índices, JOINs)
- Garantir `$wpdb->prepare()` em todas as queries
- Manter integridade referencial (cleanup de grupos órfãos)
- Avaliar quando usar tabela customizada vs post_meta

## Referências
- [Data Flow](../docs/data-flow.md)
- [Glossary](../docs/glossary.md)
