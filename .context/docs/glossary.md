---
type: doc
name: glossary
description: Project terminology, type definitions, domain entities, and business rules
category: glossary
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Glossary & Domain Concepts

Terminologia, entidades de domínio e regras de negócio do LMS SuporteRapido.

## Type Definitions

O projeto não usa TypeScript. As "tipagens" são implícitas nas estruturas PHP:

- **Acesso** (`wp_acesso_cursos`): `{id, user_id, curso_id, status, data_fim, created_at, updated_at, created_by}`
- **Log de Acesso** (`wp_acesso_cursos_log`): `{id, user_id, curso_id, action, actor_id, created_at, details}`
- **Progresso** (`wp_progresso_aluno`): `{id, user_id, aula_id, curso_id, pontuacao, tentativas, data_conclusao}`

## Enumerations

- **Status de Acesso**: `ativo`, `revogado`, `suspenso`
- **Ações de Log**: `concedido`, `revogado`, `suspenso`, `reativado`, `grupo_entrou`, `grupo_saiu`
- **Tipo de Vínculo WooCommerce**: `curso`, `trilha`, `grupo` (ou vazio para produto normal)
- **Fonte de Acesso**: `direct` (matrícula direta), `group` (grupo no curso), `group_trilha` (grupo na trilha)

## Core Terms

- **Trilha**: Agrupamento de cursos em uma sequência lógica de aprendizado. CPT `trilha`. Cursos são vinculados via meta `trilha` no curso.
- **Curso**: Unidade de ensino contendo múltiplas aulas. CPT `curso`. Vinculado a uma trilha via post_meta.
- **Aula**: Conteúdo individual dentro de um curso (vídeo, texto, quiz). CPT `aula`. Vinculada a um curso via post_meta `curso`.
- **Grupo de Alunos**: Mecanismo para conceder acesso em massa a cursos/trilhas. CPT `grupo`. Alunos são vinculados via user_meta `_aluno_grupos`.
- **Matrícula**: Registro de acesso de um aluno a um curso na tabela `wp_acesso_cursos`.
- **Progresso**: Registro de aulas concluídas por aluno na tabela `wp_progresso_aluno`.
- **Certificado**: Documento gerado ao concluir 100% das aulas de um curso.
- **Painel (`[lms-painel]`)**: Interface SPA que unifica todas as views do aluno em uma única página.

## Acronyms & Abbreviations

- **LMS**: Learning Management System
- **CPT**: Custom Post Type (tipo de post personalizado do WordPress)
- **SPA**: Single Page Application (aplicação de página única)
- **ACF**: Advanced Custom Fields (plugin WP — **não utilizado** neste projeto)

## Personas / Actors

- **Aluno**: Usuário com role `aluno`. Acessa cursos, conclui aulas, obtém certificados. Navega pelo `[lms-painel]`.
- **Administrador**: Usuário com `manage_options`. Gerencia trilhas, cursos, aulas, grupos, matrículas. Acessa o painel admin do LMS.
- **Comprador** (WooCommerce): Cliente que adquire produto vinculado a curso/trilha/grupo. Recebe acesso automaticamente após pagamento.

## Domain Rules & Invariants

- Um aluno só pode acessar um curso se tiver: matrícula direta ativa, ou pertencer a um grupo vinculado ao curso, ou pertencer a um grupo vinculado à trilha do curso.
- Matrículas podem ter data de expiração (`data_fim`). Após expirar, `has_access()` retorna `false`.
- Certificados só são gerados quando 100% das aulas do curso estão concluídas.
- Grupos deletados são automaticamente filtrados nas verificações de acesso (proteção contra "grupo fantasma").
- A tabela `wp_acesso_cursos` tem constraint UNIQUE em `(user_id, curso_id)` — um aluno não pode ter duplicatas.

Veja também: [Project Overview](./project-overview.md)
