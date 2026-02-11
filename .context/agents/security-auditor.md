---
type: agent
name: Security Auditor
description: Identify security vulnerabilities
agentType: security-auditor
phases: [R, V]
generated: 2026-02-10
status: filled
scaffoldVersion: "2.0.0"
---

# Security Auditor — LMS SuporteRapido

## Contexto do Projeto
Plugin WordPress com controle de acesso a conteúdo educacional. Armazena dados de login (IP, User-Agent) para anti-pirataria. Integração opcional com WooCommerce.

## Checklist de Segurança
- [ ] Todas as queries SQL usam `$wpdb->prepare()`
- [ ] Todos os formulários usam nonces (`wp_verify_nonce` / `wp_create_nonce`)
- [ ] Ações admin verificam `current_user_can('manage_options')`
- [ ] Inputs sanitizados: `sanitize_text_field()`, `intval()`, `sanitize_key()`
- [ ] Outputs escapados: `esc_html()`, `esc_attr()`, `esc_url()`
- [ ] Sem `$_GET`/`$_POST` usados diretamente sem sanitização
- [ ] AJAX handlers verificam nonce e capabilities
- [ ] Sem `eval()`, `extract()` ou `unserialize()` com dados de usuário

## Pontos de Atenção
- `$_SERVER['REMOTE_ADDR']` e `$_SERVER['HTTP_USER_AGENT']` usados em `track_user_login()` — podem ser spoofados
- `$_POST['new_password']` em `admin_process()` — verificar se é sanitizado antes de `wp_update_user()`
- `base64_encode(json_encode($result))` para stats na URL — dados não sensíveis mas verificar
- Dados de login history em user_meta — considerar GDPR se aplicável

## Referências
- [Security Notes](../docs/security.md)
- [Architecture Notes](../docs/architecture.md)
