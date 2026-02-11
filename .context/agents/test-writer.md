---
type: agent
name: Test Writer
description: Write comprehensive unit and integration tests
agentType: test-writer
phases: [E, V]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Test Writer — LMS SuporteRapido

## Contexto do Projeto
Atualmente sem testes automatizados. Testes são manuais. Stack recomendada: PHPUnit com WP_UnitTestCase.

## Prioridades de Teste
1. **`System_Cursos_Access_Control`** — Testar `has_access()`, `grant_access()`, `revoke_access()`, `get_user_courses()`, `get_access_source()` com cenários: acesso direto, via grupo, via trilha, expirado, revogado.
2. **`System_Cursos_Progress`** — Testar `is_lesson_completed()`, `get_completed_lessons()`, toggle de conclusão.
3. **`System_Cursos_WooCommerce`** — Testar concessão de acesso pós-compra para curso, trilha e grupo.
4. **Shortcodes** — Testar output para: admin logado, aluno com acesso, aluno sem acesso, visitante.

## Cenários Críticos
- Aluno com acesso direto + grupo → não deve duplicar cursos em `get_user_courses()`
- Grupo deletado → `has_access()` deve retornar false (não crash)
- Matrícula expirada → acesso negado
- Concluir 100% das aulas → certificado disponível

## Referências
- [Testing Strategy](../docs/testing-strategy.md)
- [Glossary](../docs/glossary.md)
