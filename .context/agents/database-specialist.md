# Database Specialist - LMS SuporteRapido

## Responsabilidades

Gerenciar estrutura de dados, otimizar queries e manter integridade do banco.

## Estrutura de Dados

### User Meta (Principal)
```php
'acesso_cursos' => [curso_id => ['validade' => 'YYYY-MM-DD', 'origem' => 'manual']],
'acesso_trilhas' => [trilha_id => ['validade' => 'vitalicio', 'origem' => 'grupo']],
'aulas_completas' => [curso_id => [aula_id_1, aula_id_2]],
'grupo_aluno' => grupo_id,
'campo_cpf', 'campo_data_nascimento', 'campo_endereco', etc.
```

### Post Meta (CPTs)
```php
// Curso
'trilha' => post_id,
'ordem' => int,
'percentual_conclusao_certificado' => int (0-100),

// Aula
'curso' => post_id,
'link_video' => url,
'ordem' => int,
'quiz_enabled' => bool,
'quiz_data' => json,
```

### Tabelas Customizadas

**wp_acesso_cursos_log**
```sql
CREATE TABLE wp_acesso_cursos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    curso_id INT,
    trilha_id INT,
    grupo_id INT,
    acao VARCHAR(50) NOT NULL,
    origem VARCHAR(50),
    validade VARCHAR(20),
    data_acao DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT,
    INDEX idx_user (user_id),
    INDEX idx_curso (curso_id),
    INDEX idx_data (data_acao)
);
```

**wp_quiz_attempts**
```sql
CREATE TABLE wp_quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aula_id INT NOT NULL,
    score INT,
    max_score INT,
    passed BOOLEAN,
    attempt_number INT,
    answers JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_aula (user_id, aula_id)
);
```

## Queries Otimizadas

### Buscar Cursos do Usuário
```php
global $wpdb;

// Com acesso válido
$hoje = date('Y-m-d');
$acessos = get_user_meta($user_id, 'acesso_cursos', true);
$curso_ids = [];

foreach ($acessos as $id => $dados) {
    if ($dados['validade'] === 'vitalicio' || $dados['validade'] >= $hoje) {
        $curso_ids[] = $id;
    }
}

// Buscar posts em uma query só
$cursos = get_posts([
    'post_type' => 'curso',
    'post__in' => $curso_ids,
    'posts_per_page' => -1,
    'orderby' => 'post__in'
]);
```

### Buscar Aulas de um Curso
```php
// Apenas IDs (mais rápido)
$aula_ids = get_posts([
    'post_type' => 'aula',
    'meta_key' => 'curso',
    'meta_value' => $curso_id,
    'posts_per_page' => -1,
    'fields' => 'ids', // Importante!
    'orderby' => 'meta_value_num',
    'meta_key' => 'ordem',
    'order' => 'ASC'
]);
```

### Log de Acesso (Prepared Statement)
```php
global $wpdb;

$wpdb->insert(
    $wpdb->prefix . 'acesso_cursos_log',
    [
        'user_id' => $user_id,
        'curso_id' => $curso_id,
        'acao' => 'acesso_concedido',
        'origem' => $origem,
        'validade' => $validade,
        'data_acao' => current_time('mysql')
    ],
    ['%d', '%d', '%s', '%s', '%s', '%s']
);
```

## Performance

### Cache com Transients
```php
// Salvar
set_transient("cursos_usuario_{$user_id}", $cursos, HOUR_IN_SECONDS);

// Buscar
$cursos = get_transient("cursos_usuario_{$user_id}");
if ($cursos === false) {
    $cursos = $this->buscar_do_banco($user_id);
    set_transient("cursos_usuario_{$user_id}", $cursos, HOUR_IN_SECONDS);
}

// Limpar quando dados mudam
delete_transient("cursos_usuario_{$user_id}");
```

### Índices
```sql
-- Adicionar índices em tabelas muito usadas
ALTER TABLE wp_acesso_cursos_log ADD INDEX idx_user_curso (user_id, curso_id);
ALTER TABLE wp_quiz_attempts ADD INDEX idx_aula (aula_id);
```

## Manutenção

### Limpeza de Dados Órfãos
```php
// Remover acessos a cursos deletados
function limpar_acessos_orfaos() {
    $usuarios = get_users();
    
    foreach ($usuarios as $user) {
        $acessos = get_user_meta($user->ID, 'acesso_cursos', true);
        if (empty($acessos)) continue;
        
        foreach ($acessos as $curso_id => $dados) {
            if (!get_post($curso_id)) {
                unset($acessos[$curso_id]);
            }
        }
        
        update_user_meta($user->ID, 'acesso_cursos', $acessos);
    }
}
```

## Backup e Restore

### Exportar Dados Customizados
```php
// WP-CLI
wp db export backup_$(date +%Y%m%d).sql

// Apenas tabelas customizadas
wp db export --tables=wp_acesso_cursos_log,wp_quiz_attempts logs_backup.sql
```

## Recursos

- **Arquitetura:** `../docs/architecture.md`
- **WordPress DB Class:** https://developer.wordpress.org/reference/classes/wpdb/
