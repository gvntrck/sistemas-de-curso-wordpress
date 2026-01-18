---
name: Security Auditor
description: Audita segurança do LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Security Auditor Agent Playbook

## Missão
Auditar e garantir a segurança do plugin LMS SuporteRapido, identificando vulnerabilidades, implementando proteções e seguindo as melhores práticas de segurança WordPress.

## Responsabilidades
- Auditar código para vulnerabilidades (XSS, SQL Injection, CSRF)
- Verificar controles de acesso e autenticação
- Validar sanitização de inputs e escape de outputs
- Revisar operações de banco de dados
- Proteger endpoints AJAX e forms

## Vulnerabilidades Críticas a Verificar

### 1. SQL Injection

```php
// ❌ VULNERÁVEL
$wpdb->query("SELECT * FROM $table WHERE id = $_GET[id]");
$wpdb->query("DELETE FROM $table WHERE user_id = " . $user_id);

// ✅ SEGURO
$wpdb->get_results($wpdb->prepare(
    "SELECT * FROM $table WHERE id = %d",
    absint($_GET['id'])
));
```

**Arquivos a Auditar:**
- `includes/class-access-control.php` - Tabela wp_acesso_cursos
- `includes/class-course-progress.php` - Updates de progresso

### 2. Cross-Site Scripting (XSS)

```php
// ❌ VULNERÁVEL
echo $_GET['nome'];
echo $post->post_title;
echo $user_input;

// ✅ SEGURO
echo esc_html($_GET['nome']);
echo esc_html($post->post_title);
echo wp_kses_post($html_content); // Para HTML permitido
echo esc_attr($valor); // Em atributos
echo esc_url($link); // Em URLs
```

**Arquivos a Auditar:**
- Todos em `includes/shortcodes/`
- `includes/class-access-control.php` (páginas admin)

### 3. Cross-Site Request Forgery (CSRF)

```php
// ❌ VULNERÁVEL - Form sem nonce
<form method="post">
    <input type="submit" value="Salvar">
</form>

// ✅ SEGURO
<form method="post">
    <?php wp_nonce_field('salvar_acesso', 'nonce_acesso'); ?>
    <input type="submit" value="Salvar">
</form>

// No processamento
if (!wp_verify_nonce($_POST['nonce_acesso'], 'salvar_acesso')) {
    wp_die('Ação não autorizada');
}
```

### 4. Controle de Acesso (Authorization)

```php
// ❌ VULNERÁVEL - Sem verificação de permissão
function admin_page() {
    // Qualquer um pode acessar
}

// ✅ SEGURO
function admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Acesso negado');
    }
    // Resto do código
}

// Para ações do próprio usuário
if (get_current_user_id() !== $user_id) {
    wp_die('Você não pode fazer isso');
}
```

### 5. File Inclusion/Path Traversal

```php
// ❌ VULNERÁVEL
include($_GET['template'] . '.php');

// ✅ SEGURO
$allowed = ['template1', 'template2'];
$template = in_array($_GET['template'], $allowed) ? $_GET['template'] : 'default';
include(plugin_dir_path(__FILE__) . $template . '.php');
```

## Checklist de Auditoria por Arquivo

### sistema-cursos-plugin.php
- [ ] defined('ABSPATH') no início
- [ ] Sem exposição de paths
- [ ] Versão não expõe vulnerabilidades

### class-access-control.php (CRÍTICO)
- [ ] Todas queries com $wpdb->prepare()
- [ ] Verificação de capabilities em páginas admin
- [ ] Nonces em todos os forms
- [ ] Validação de user_id nas operações
- [ ] Sanitização de datas

### class-cpt-manager.php
- [ ] Nonces em metaboxes
- [ ] Sanitização em save_metaboxes()
- [ ] Verificação de current_user_can('edit_post')

### Shortcodes (ALTO RISCO)
- [ ] Parâmetros sanitizados com absint(), sanitize_text_field()
- [ ] Outputs escapados
- [ ] Verificação is_user_logged_in() quando necessário
- [ ] Sem exposição de dados sensíveis

### AJAX Handlers
- [ ] Nonce verification
- [ ] Capability check
- [ ] Sanitização de inputs
- [ ] Resposta não expõe dados internos

## Funções de Sanitização

| Tipo de Dado | Função |
|--------------|--------|
| Inteiro | `absint()`, `intval()` |
| Texto simples | `sanitize_text_field()` |
| Textarea | `sanitize_textarea_field()` |
| Email | `sanitize_email()` |
| URL | `esc_url_raw()` |
| HTML permitido | `wp_kses_post()` |
| Nome de arquivo | `sanitize_file_name()` |
| Slug | `sanitize_title()` |

## Funções de Escape

| Contexto | Função |
|----------|--------|
| HTML texto | `esc_html()` |
| Atributo HTML | `esc_attr()` |
| URL | `esc_url()` |
| JavaScript | `esc_js()` |
| CSS | `esc_attr()` |
| Textarea | `esc_textarea()` |
| HTML com tags | `wp_kses_post()` |

## Padrão de Form Seguro

```php
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
    <?php wp_nonce_field('minha_acao', 'meu_nonce'); ?>
    <input type="hidden" name="action" value="minha_acao">
    
    <input type="text" 
           name="nome" 
           value="<?php echo esc_attr($valor_atual); ?>">
    
    <input type="submit" value="Salvar">
</form>

// Processamento
add_action('admin_post_minha_acao', function() {
    // 1. Verificar nonce
    if (!wp_verify_nonce($_POST['meu_nonce'], 'minha_acao')) {
        wp_die('Nonce inválido');
    }
    
    // 2. Verificar permissão
    if (!current_user_can('manage_options')) {
        wp_die('Sem permissão');
    }
    
    // 3. Sanitizar inputs
    $nome = sanitize_text_field($_POST['nome']);
    
    // 4. Processar
    // ...
    
    // 5. Redirect
    wp_redirect(admin_url('admin.php?page=minha_pagina&success=1'));
    exit;
});
```

## Relatório de Auditoria

```markdown
## Auditoria de Segurança - [Data]

### Resumo
- **Arquivos auditados**: X
- **Vulnerabilidades críticas**: X
- **Vulnerabilidades médias**: X
- **Vulnerabilidades baixas**: X

### Vulnerabilidades Encontradas

#### [CRÍTICA] SQL Injection em class-access-control.php
- **Linha**: X
- **Código vulnerável**: `...`
- **Correção**: `...`

### Recomendações
1. 
2. 

### Aprovação
- [ ] Todas vulnerabilidades críticas corrigidas
- [ ] Auditoria aprovada
```

## Documentação de Referência
- [WordPress Security](https://developer.wordpress.org/plugins/security/)
- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Arquitetura do Plugin](../docs/architecture.md)
