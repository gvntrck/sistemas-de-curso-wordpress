---
type: doc
name: security
description: Security policies, authentication, secrets management, and compliance requirements
category: security
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

## Security & Compliance Notes

O plugin segue as práticas de segurança padrão do WordPress: nonces para CSRF, capability checks para autorização, sanitização de inputs e prepared statements para queries SQL.

## Authentication & Authorization

- **Autenticação**: Delegada ao WordPress core (`wp_get_current_user()`, `is_user_logged_in()`).
- **Roles**: Role customizada `aluno` criada em `includes/role-aluno.php`. Administradores usam a role padrão `administrator`.
- **Capability checks**: Todas as ações admin verificam `current_user_can('manage_options')` antes de processar.
- **Controle de acesso a conteúdo**: `System_Cursos_Access_Control::has_access($user_id, $curso_id)` é o ponto central. Verifica 3 fontes: matrícula direta, grupo do curso, grupo da trilha.
- **Nonces**: Todos os formulários admin e AJAX handlers usam `wp_verify_nonce()` / `wp_create_nonce()`.

## Secrets & Sensitive Data

- **Sem API keys externas**: O plugin não se comunica com serviços externos (exceto WooCommerce que é local).
- **Dados sensíveis em user_meta**: `_login_history` (IP, User-Agent dos últimos 50 logins), `billing_phone`, `instagram`.
- **Senhas**: Gerenciadas pelo WordPress core (`wp_update_user`). Nunca armazenadas em texto plano.
- **Banco de dados**: Todas as queries usam `$wpdb->prepare()` para prevenir SQL injection.
- **Sanitização de inputs**: `sanitize_text_field()`, `intval()`, `sanitize_key()` aplicados em todos os dados de formulário.

## Compliance & Policies

- **Anti-pirataria**: Rastreamento de logins (IP + User-Agent) para detectar compartilhamento de contas.
- **Expiração de acesso**: Matrículas podem ter `data_fim` para controle temporal.
- **Auditoria**: Tabela `wp_acesso_cursos_log` registra todas as ações com timestamp, actor e detalhes.
- **Limpeza de dados**: `cleanup_orphaned_group_references()` mantém integridade referencial.

Veja também: [Architecture Notes](./architecture.md)
