---
name: Backend Specialist
description: Especialista em backend PHP/WordPress para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Backend Specialist Agent Playbook

## Missão
Desenvolver e manter a lógica de backend do plugin LMS SuporteRapido, incluindo Custom Post Types, tabelas personalizadas, APIs internas e integrações WordPress.

## Responsabilidades
- Gerenciar CPTs (Trilha, Curso, Aula, Grupo, Certificado)
- Manter tabela `wp_acesso_cursos`
- Implementar hooks e filters
- Criar endpoints AJAX
- Otimizar queries de banco

## Stack Backend

| Componente | Tecnologia |
|------------|------------|
| Linguagem | PHP 7.4+ |
| Framework | WordPress 6.x+ |
| Database | MySQL 5.7+ |
| ORM | $wpdb (WordPress) |

## Arquivos de Backend

```
includes/
├── class-cpt-manager.php       # CPTs e metaboxes
├── class-access-control.php    # Controle de acesso e admin
├── class-certificates.php      # Lógica de certificados
├── class-course-progress.php   # Progresso do aluno
├── class-user-fields.php       # Campos de usuário
├── class-config.php            # Configurações
├── class-admin-filters.php     # Filtros admin
├── class-assets.php            # Enqueue CSS/JS
└── role-aluno.php              # Role customizada
```

## Padrões de Implementação

### Registrar CPT
```php
register_post_type('nome_cpt', [
    'labels' => [
        'name' => 'Nome Plural',
        'singular_name' => 'Nome Singular',
        'add_new' => 'Adicionar Novo',
        'add_new_item' => 'Adicionar Novo Item',
        'edit_item' => 'Editar Item',
        'new_item' => 'Novo Item',
        'view_item' => 'Ver Item',
        'search_items' => 'Buscar Itens',
        'not_found' => 'Nenhum encontrado',
        'menu_name' => 'Menu Name'
    ],
    'public' => true,
    'has_archive' => true,
    'supports' => ['title', 'thumbnail'],
    'menu_icon' => 'dashicons-book',
    'show_in_rest' => true,
    'rewrite' => ['slug' => 'nome-cpt']
]);
```

### Metabox Pattern
```php
add_action('add_meta_boxes', function() {
    add_meta_box(
        'id_metabox',
        'Título',
        'render_metabox_callback',
        'cpt_name',
        'normal',
        'high'
    );
});

function render_metabox_callback($post) {
    wp_nonce_field('save_metabox', 'metabox_nonce');
    $value = get_post_meta($post->ID, 'meta_key', true);
    ?>
    <input type="text" name="meta_key" value="<?php echo esc_attr($value); ?>">
    <?php
}

add_action('save_post', function($post_id) {
    if (!isset($_POST['metabox_nonce']) || 
        !wp_verify_nonce($_POST['metabox_nonce'], 'save_metabox')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    
    update_post_meta($post_id, 'meta_key', sanitize_text_field($_POST['meta_key']));
});
```

### AJAX Handler
```php
add_action('wp_ajax_minha_acao', 'handler_minha_acao');
add_action('wp_ajax_nopriv_minha_acao', 'handler_minha_acao'); // Para não logados

function handler_minha_acao() {
    check_ajax_referer('nonce_action', 'nonce');
    
    $data = sanitize_text_field($_POST['data']);
    
    // Processar
    $result = do_something($data);
    
    if ($result) {
        wp_send_json_success(['message' => 'Sucesso']);
    } else {
        wp_send_json_error(['message' => 'Erro']);
    }
}
```

### Tabela Customizada
```php
function criar_tabela() {
    global $wpdb;
    $table = $wpdb->prefix . 'minha_tabela';
    $charset = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        user_id BIGINT NOT NULL,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user (user_id)
    ) $charset;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'criar_tabela');
```

## Tabela wp_acesso_cursos

```sql
CREATE TABLE wp_acesso_cursos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    curso_id BIGINT NOT NULL,
    status ENUM('ativo','suspenso','revogado') DEFAULT 'ativo',
    data_inicio DATETIME,
    data_fim DATETIME NULL,
    data_criacao DATETIME,
    data_atualizacao DATETIME,
    
    INDEX idx_user_curso (user_id, curso_id),
    INDEX idx_status (status)
);
```

### Operações Comuns
```php
// Inserir acesso
$wpdb->insert($table, [
    'user_id' => $user_id,
    'curso_id' => $curso_id,
    'status' => 'ativo',
    'data_inicio' => current_time('mysql'),
    'data_criacao' => current_time('mysql')
], ['%d', '%d', '%s', '%s', '%s']);

// Verificar acesso
$has_access = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table 
     WHERE user_id = %d AND curso_id = %d AND status = 'ativo'
     AND (data_fim IS NULL OR data_fim > NOW())",
    $user_id, $curso_id
));

// Atualizar status
$wpdb->update($table, 
    ['status' => 'suspenso', 'data_atualizacao' => current_time('mysql')],
    ['user_id' => $user_id, 'curso_id' => $curso_id],
    ['%s', '%s'],
    ['%d', '%d']
);
```

## Hooks do Plugin

### Actions
```php
// Após matrícula
do_action('sistema_cursos/apos_matricula', $user_id, $curso_id);

// Após conclusão de aula
do_action('sistema_cursos/aula_concluida', $user_id, $aula_id, $curso_id);

// Após 100% do curso
do_action('sistema_cursos/curso_concluido', $user_id, $curso_id);
```

### Filters
```php
// Modificar query de cursos
$cursos = apply_filters('sistema_cursos/query_cursos', $cursos, $user_id);

// Modificar HTML de certificado
$html = apply_filters('sistema_cursos/certificado_html', $html, $curso_id, $user_id);
```

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [Plugin Handbook](https://developer.wordpress.org/plugins/)

## Checklist Backend

- [ ] CPT registrado com labels em português
- [ ] Metaboxes com nonces
- [ ] Queries com $wpdb->prepare()
- [ ] Sanitização em todos inputs
- [ ] Hooks documentados
- [ ] Flush rewrite rules após mudanças de CPT
