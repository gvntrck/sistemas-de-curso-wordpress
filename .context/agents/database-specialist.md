---
name: Database Specialist
description: Especialista em banco de dados para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Database Specialist Agent Playbook

## Missão
Gerenciar e otimizar a camada de dados do plugin LMS SuporteRapido, incluindo tabelas customizadas, meta queries e performance de banco de dados.

## Responsabilidades
- Gerenciar tabela `wp_acesso_cursos`
- Otimizar queries de relacionamento entre CPTs
- Criar índices para performance
- Backup e migração de dados
- Debug de queries problemáticas

## Estrutura de Dados

### Tabela Customizada: wp_acesso_cursos

```sql
CREATE TABLE {prefix}_acesso_cursos (
    id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    curso_id BIGINT(20) UNSIGNED NOT NULL,
    status ENUM('ativo', 'suspenso', 'revogado') DEFAULT 'ativo',
    data_inicio DATETIME DEFAULT NULL,
    data_fim DATETIME DEFAULT NULL,
    data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
    data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Índices
    INDEX idx_user_id (user_id),
    INDEX idx_curso_id (curso_id),
    INDEX idx_user_curso (user_id, curso_id),
    INDEX idx_status (status),
    INDEX idx_data_fim (data_fim),
    
    -- Foreign keys (conceitual, não enforced)
    -- user_id -> wp_users.ID
    -- curso_id -> wp_posts.ID (post_type = 'curso')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci;
```

### Tabelas WordPress Utilizadas

| Tabela | Uso |
|--------|-----|
| `wp_posts` | CPTs (trilha, curso, aula, grupo, certificado) |
| `wp_postmeta` | Relacionamentos e campos customizados |
| `wp_users` | Alunos e admins |
| `wp_usermeta` | Progresso e campos de usuário |
| `wp_acesso_cursos` | Matrículas (customizada) |

## Relacionamentos via Postmeta

```
┌─────────────────────────────────────────────────────────┐
│ wp_postmeta                                             │
├─────────────────────────────────────────────────────────┤
│ meta_key: 'trilha'     → Curso pertence a Trilha       │
│ meta_key: 'curso'      → Aula pertence a Curso         │
│ meta_key: 'ordem'      → Ordenação de itens            │
│ meta_key: 'cursos_do_grupo' → Cursos em um Grupo       │
│ meta_key: 'trilhas_do_grupo' → Trilhas em um Grupo     │
│ meta_key: 'alunos_do_grupo' → Usuários em um Grupo     │
│ meta_key: 'certificado_grupo' → Certificado do Grupo   │
└─────────────────────────────────────────────────────────┘
```

## Queries Comuns

### Verificar Acesso Direto
```php
global $wpdb;
$table = $wpdb->prefix . 'acesso_cursos';

$has_access = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table 
     WHERE user_id = %d 
       AND curso_id = %d 
       AND status = 'ativo'
       AND (data_fim IS NULL OR data_fim > %s)",
    $user_id,
    $curso_id,
    current_time('mysql')
));
```

### Cursos de uma Trilha (ordenados)
```php
$cursos = get_posts([
    'post_type' => 'curso',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'trilha',
            'value' => $trilha_id,
            'compare' => '='
        ]
    ],
    'meta_key' => 'ordem',
    'orderby' => 'meta_value_num',
    'order' => 'ASC'
]);
```

### Aulas de um Curso (ordenadas)
```php
$aulas = get_posts([
    'post_type' => 'aula',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'curso',
            'value' => $curso_id
        ]
    ],
    'meta_key' => 'ordem',
    'orderby' => 'meta_value_num',
    'order' => 'ASC'
]);
```

### Cursos do Usuário
```php
$cursos_ids = $wpdb->get_col($wpdb->prepare(
    "SELECT DISTINCT curso_id FROM $table 
     WHERE user_id = %d AND status = 'ativo'
     AND (data_fim IS NULL OR data_fim > NOW())",
    $user_id
));

if (!empty($cursos_ids)) {
    $cursos = get_posts([
        'post_type' => 'curso',
        'post__in' => $cursos_ids,
        'posts_per_page' => -1
    ]);
}
```

### Grupos do Usuário
```php
$grupos = get_posts([
    'post_type' => 'grupo',
    'posts_per_page' => -1,
    'meta_query' => [
        [
            'key' => 'alunos_do_grupo',
            'value' => $user_id,
            'compare' => 'LIKE'
        ]
    ]
]);
```

## Otimização de Performance

### Índices Recomendados
```sql
-- Para wp_acesso_cursos
CREATE INDEX idx_user_curso_status 
ON wp_acesso_cursos(user_id, curso_id, status);

-- Para buscas por data de expiração
CREATE INDEX idx_expiring 
ON wp_acesso_cursos(data_fim) 
WHERE data_fim IS NOT NULL;
```

### Cache de Queries
```php
function get_user_courses_cached($user_id) {
    $cache_key = "user_courses_$user_id";
    $courses = wp_cache_get($cache_key, 'lms');
    
    if (false === $courses) {
        global $wpdb;
        $table = $wpdb->prefix . 'acesso_cursos';
        
        $courses = $wpdb->get_col($wpdb->prepare(
            "SELECT curso_id FROM $table 
             WHERE user_id = %d AND status = 'ativo'",
            $user_id
        ));
        
        wp_cache_set($cache_key, $courses, 'lms', 3600);
    }
    
    return $courses;
}
```

### Invalidação de Cache
```php
// Ao matricular/remover aluno
function invalidate_user_cache($user_id) {
    wp_cache_delete("user_courses_$user_id", 'lms');
}
```

## Migrações

### Adicionar Nova Coluna
```php
function migrate_add_column() {
    global $wpdb;
    $table = $wpdb->prefix . 'acesso_cursos';
    
    // Verificar se coluna já existe
    $column = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'nova_coluna'");
    
    if (empty($column)) {
        $wpdb->query("ALTER TABLE $table ADD nova_coluna VARCHAR(255) DEFAULT NULL");
    }
}
```

### Script de Migração Versionado
```php
function check_db_version() {
    $current_version = get_option('lms_db_version', '1.0.0');
    $target_version = '1.1.0';
    
    if (version_compare($current_version, $target_version, '<')) {
        run_migrations($current_version, $target_version);
        update_option('lms_db_version', $target_version);
    }
}
add_action('init', 'check_db_version');
```

## Debug de Queries

### Logging de Queries
```php
// Debug temporário
function log_query($query) {
    if (strpos($query, 'acesso_cursos') !== false) {
        error_log("LMS Query: $query");
    }
    return $query;
}
add_filter('query', 'log_query');
```

### Query Monitor
```php
// Verificar última query
global $wpdb;
echo $wpdb->last_query;
echo $wpdb->last_error;
```

### Explain de Query
```sql
EXPLAIN SELECT * FROM wp_acesso_cursos 
WHERE user_id = 1 AND status = 'ativo';
```

## Backup e Restore

### Exportar Acessos
```php
function export_acessos() {
    global $wpdb;
    $table = $wpdb->prefix . 'acesso_cursos';
    
    $results = $wpdb->get_results("SELECT * FROM $table", ARRAY_A);
    
    return json_encode($results);
}
```

### Importar Acessos
```php
function import_acessos($json) {
    global $wpdb;
    $table = $wpdb->prefix . 'acesso_cursos';
    
    $data = json_decode($json, true);
    
    foreach ($data as $row) {
        unset($row['id']); // Auto-increment
        $wpdb->insert($table, $row);
    }
}
```

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [WordPress $wpdb](https://developer.wordpress.org/reference/classes/wpdb/)

## Checklist Database

- [ ] Tabelas criadas no activation hook
- [ ] Índices otimizados
- [ ] Queries com $wpdb->prepare()
- [ ] Cache implementado para queries frequentes
- [ ] Migrações versionadas
- [ ] Backup antes de alterações estruturais
