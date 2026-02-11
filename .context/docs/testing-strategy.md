---
type: doc
name: testing-strategy
description: Test frameworks, patterns, coverage requirements, and quality gates
category: testing
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Testing Strategy

O projeto atualmente não possui suite de testes automatizados. A qualidade é mantida por testes manuais no WordPress. Abaixo estão as diretrizes para testes futuros e o checklist de testes manuais.

## Test Types

- **Unit**: Não implementado. Recomendado: PHPUnit com WP_UnitTestCase para testar métodos estáticos de `Access_Control` e `Progress`.
- **Integration**: Não implementado. Recomendado: Testar fluxos completos (matrícula → acesso → progresso → certificado).
- **E2E / Manual**: Testes manuais no navegador verificando shortcodes, AJAX, e painel admin.

## Running Tests

Atualmente, testes são manuais:

- **Frontend**: Acessar página com `[lms-painel]` como aluno e verificar todas as seções
- **Admin**: Testar CRUD de trilhas, cursos, aulas, grupos no painel WordPress
- **WooCommerce**: Simular compra e verificar se acesso é concedido automaticamente
- **AJAX**: Verificar console do navegador para erros em interações dinâmicas

## Quality Gates

- Todo formulário deve usar nonce verification
- Toda query SQL deve usar `$wpdb->prepare()`
- Todo input de usuário deve ser sanitizado
- Toda ação admin deve verificar `current_user_can()`
- Shortcodes devem funcionar para: admin, aluno logado, visitante (mostrando mensagem de login)
- Atualizar versão no header do plugin a cada alteração

## Troubleshooting

- **Shortcode não renderiza**: Verificar se o plugin está ativo e se o shortcode está registrado (debug com `has_shortcode()`).
- **AJAX retorna 0**: Verificar se o action está registrado com `wp_ajax_` e se o nonce é válido.
- **Tabelas não criadas**: Verificar se `dbDelta()` está sendo chamado no hook `init`. Desativar e reativar o plugin.
- **Acesso negado indevidamente**: Verificar se há grupos órfãos. Usar a função de limpeza no admin.

Veja também: [Development Workflow](./development-workflow.md)
