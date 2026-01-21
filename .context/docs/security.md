# Segurança

## Princípios de Segurança

O LMS SuporteRapido implementa múltiplas camadas de segurança seguindo as melhores práticas do WordPress:

1. **Nonces** - Proteção contra CSRF
2. **Capabilities** - Controle de permissões
3. **Sanitização** - Limpeza de entrada
4. **Escape** - Proteção de saída
5. **Validação** - Verificação de dados

## Proteção CSRF (Cross-Site Request Forgery)

### Nonces em Formulários

```php
// Criação do nonce
$nonce = wp_create_nonce('minha_acao_segura');

// HTML
<input type="hidden" name="nonce" value="<?php echo $nonce; ?>">

// Verificação
if (!wp_verify_nonce($_POST['nonce'], 'minha_acao_segura')) {
    wp_die('Requisição inválida');
}
```

### Nonces em AJAX

```javascript
// Frontend
$.ajax({
    url: ajaxurl,
    data: {
        action: 'marcar_aula_completa',
        aula_id: 123,
        nonce: lms_ajax.nonce  // Passado via wp_localize_script
    }
});
```

```php
// Backend (class-assets.php)
wp_localize_script('navegacao-aulas', 'lms_ajax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('lms_ajax_nonce')
]);

// Handler AJAX
add_action('wp_ajax_marcar_aula_completa', function() {
    if (!wp_verify_nonce($_POST['nonce'], 'lms_ajax_nonce')) {
        wp_send_json_error('Nonce inválido');
    }
    // Processar...
});
```

## Controle de Acesso e Capabilities

### Verificação de Permissões Admin

```php
// Proteger páginas admin
if (!current_user_can('manage_options')) {
    wp_die(__('Você não tem permissão para acessar esta página.'));
}

// Proteger AJAX handlers admin
add_action('wp_ajax_admin_only_action', function() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Permissão negada');
    }
    // Processar...
});
```

### Verificação de Propriedade de Dados

```php
// Usuário só pode editar seus próprios dados
function atualizar_perfil_usuario() {
    $user_id = intval($_POST['user_id']);
    
    // Verificar se é o próprio usuário OU admin
    if ($user_id !== get_current_user_id() && !current_user_can('manage_options')) {
        wp_send_json_error('Você não pode editar este perfil');
    }
    
    // Processar atualização...
}
```

### Role Aluno - Restrição de Acesso

```php
// role-aluno.php

// Bloquear acesso ao admin
add_action('admin_init', function() {
    if (current_user_can('aluno') && !wp_doing_ajax()) {
        wp_redirect(home_url());
        exit;
    }
});

// Ocultar barra de admin
add_action('after_setup_theme', function() {
    if (current_user_can('aluno')) {
        show_admin_bar(false);
    }
});
```

## Sanitização de Entrada

### Dados de Formulário

```php
// Texto simples
$nome = sanitize_text_field($_POST['nome']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$website = sanitize_url($_POST['website']);

// Número inteiro
$user_id = intval($_POST['user_id']);

// Array de inteiros
$curso_ids = array_map('intval', $_POST['curso_ids']);

// Textarea (permite quebras de linha)
$descricao = sanitize_textarea_field($_POST['descricao']);

// HTML limitado (permite tags básicas)
$conteudo = wp_kses_post($_POST['conteudo']);
```

### Validação de Dados

```php
// CPF
function validar_cpf($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) !== 11) {
        return false;
    }
    
    // Verificar dígitos verificadores...
    return true;
}

// Data
function validar_data($data) {
    $d = DateTime::createFromFormat('Y-m-d', $data);
    return $d && $d->format('Y-m-d') === $data;
}

// Email
if (!is_email($email)) {
    return new WP_Error('invalid_email', 'Email inválido');
}
```

## Escape de Saída

### HTML

```php
// Texto
echo esc_html($titulo_curso);

// Atributo
<div class="<?php echo esc_attr($classe); ?>">

// URL
<a href="<?php echo esc_url($link); ?>">

// JavaScript
<script>
var titulo = '<?php echo esc_js($titulo); ?>';
</script>

// Textarea
<textarea><?php echo esc_textarea($descricao); ?></textarea>
```

### SQL Queries (Prepared Statements)

```php
global $wpdb;

// ✅ CORRETO - Prepared statement
$results = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}acesso_cursos_log 
     WHERE user_id = %d AND curso_id = %d",
    $user_id,
    $curso_id
));

// ❌ ERRADO - SQL Injection vulnerability
$results = $wpdb->get_results(
    "SELECT * FROM wp_acesso_cursos_log 
     WHERE user_id = {$user_id}"
);
```

## Proteção de Arquivos Sensíveis

### .gitignore

```gitignore
# Credenciais FTP
sync_ftp.py

# Configurações locais
wp-config-local.php

# Logs
*.log
debug.log

# Ambientes
.env
.env.local
```

### Cabeçalhos de Segurança

```php
// Prevenir acesso direto a arquivos PHP
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
```

## Anti-Pirataria

### Detecção de Múltiplos Acessos

```php
// Registrar acesso
function registrar_acesso_aula($user_id, $aula_id) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    // Salvar em log
    update_user_meta($user_id, 'ultimo_acesso', [
        'ip' => $ip,
        'user_agent' => $user_agent,
        'timestamp' => time(),
        'aula_id' => $aula_id
    ]);
    
    // Verificar acessos simultâneos
    $acessos_recentes = get_acessos_ultimos_30min($user_id);
    
    if (count($acessos_recentes) > 3) {
        // Alerta de possível compartilhamento
        do_action('lms_alerta_antipirataria', $user_id, $acessos_recentes);
    }
}
```

### Limitação de Dispositivos

```php
// Registrar dispositivo
function registrar_dispositivo($user_id) {
    $device_hash = md5($_SERVER['HTTP_USER_AGENT'] . $_SERVER['REMOTE_ADDR']);
    
    $dispositivos = get_user_meta($user_id, 'dispositivos_registrados', true) ?: [];
    
    if (!in_array($device_hash, $dispositivos)) {
        $dispositivos[] = $device_hash;
        
        // Limite de 3 dispositivos
        if (count($dispositivos) > 3) {
            return new WP_Error('max_devices', 'Limite de dispositivos atingido');
        }
        
        update_user_meta($user_id, 'dispositivos_registrados', $dispositivos);
    }
}
```

## Segurança de Senha

### Requisitos de Senha Forte

```php
// Validar senha no cadastro
function validar_senha_forte($senha) {
    $min_length = 8;
    
    if (strlen($senha) < $min_length) {
        return false;
    }
    
    // Verificar complexidade
    $tem_maiuscula = preg_match('/[A-Z]/', $senha);
    $tem_minuscula = preg_match('/[a-z]/', $senha);
    $tem_numero = preg_match('/[0-9]/', $senha);
    
    return $tem_maiuscula && $tem_minuscula && $tem_numero;
}
```

### Hashing (WordPress nativo)

```php
// WordPress usa wp_hash_password() e wp_check_password() automaticamente
// Usa bcrypt com salt único por usuário

// Criar usuário (senha é hasheada automaticamente)
wp_create_user($username, $password, $email);

// Atualizar senha
wp_set_password($new_password, $user_id);
```

## Segurança em Upload de Arquivos

### Validação de CSV (Importação de Alunos)

```php
function importar_alunos_csv($arquivo) {
    // Verificar extensão
    $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
    if ($extensao !== 'csv') {
        return new WP_Error('invalid_file', 'Apenas arquivos CSV são permitidos');
    }
    
    // Verificar MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $arquivo['tmp_name']);
    finfo_close($finfo);
    
    $mimes_permitidos = ['text/csv', 'text/plain', 'application/csv'];
    if (!in_array($mime, $mimes_permitidos)) {
        return new WP_Error('invalid_mime', 'Tipo de arquivo inválido');
    }
    
    // Processar CSV...
}
```

## Rate Limiting

### Proteção contra Brute Force em Quiz

```php
function verificar_rate_limit_quiz($user_id, $aula_id) {
    $cache_key = "quiz_attempts_{$user_id}_{$aula_id}";
    $tentativas = wp_cache_get($cache_key);
    
    if ($tentativas === false) {
        $tentativas = 0;
    }
    
    if ($tentativas > 5) {
        return new WP_Error('rate_limit', 'Muitas tentativas. Aguarde 1 minuto.');
    }
    
    wp_cache_set($cache_key, $tentativas + 1, '', 60); // 60 segundos
    return true;
}
```

## Logs de Auditoria

### Registro de Ações Sensíveis

```php
function registrar_log_auditoria($acao, $detalhes) {
    global $wpdb;
    
    $wpdb->insert(
        $wpdb->prefix . 'acesso_cursos_log',
        [
            'user_id' => get_current_user_id(),
            'acao' => $acao,
            'observacao' => wp_json_encode($detalhes),
            'data_acao' => current_time('mysql'),
            'ip_address' => $_SERVER['REMOTE_ADDR']
        ],
        ['%d', '%s', '%s', '%s', '%s']
    );
}

// Uso
registrar_log_auditoria('alteracao_permissao', [
    'aluno_id' => 123,
    'curso_id' => 456,
    'acao' => 'acesso_concedido'
]);
```

## Headers de Segurança

```php
// Adicionar headers de segurança (wp-config.php ou .htaccess)

// X-Content-Type-Options
header('X-Content-Type-Options: nosniff');

// X-Frame-Options (prevenir clickjacking)
header('X-Frame-Options: SAMEORIGIN');

// X-XSS-Protection
header('X-XSS-Protection: 1; mode=block');

// Content-Security-Policy (básico)
header("Content-Security-Policy: default-src 'self'");
```

## Checklist de Segurança

Antes de deploy, verificar:

- [ ] Todos os AJAX handlers têm verificação de nonce
- [ ] Capabilities verificadas em operações admin
- [ ] Inputs sanitizados com funções apropriadas
- [ ] Outputs escapados com esc_* functions
- [ ] SQL queries usam prepared statements
- [ ] Arquivos sensíveis em .gitignore
- [ ] Uploads de arquivo validados (extensão + MIME)
- [ ] Rate limiting em operações críticas
- [ ] Logs de auditoria em ações sensíveis
- [ ] Headers de segurança configurados

## Vulnerabilidades Comuns a Evitar

### 1. SQL Injection
```php
// ❌ VULNERÁVEL
$wpdb->query("DELETE FROM table WHERE id = {$_POST['id']}");

// ✅ SEGURO
$wpdb->query($wpdb->prepare("DELETE FROM table WHERE id = %d", $_POST['id']));
```

### 2. XSS (Cross-Site Scripting)
```php
// ❌ VULNERÁVEL
echo $_POST['mensagem'];

// ✅ SEGURO
echo esc_html($_POST['mensagem']);
```

### 3. CSRF
```php
// ❌ VULNERÁVEL (sem nonce)
if (isset($_POST['deletar'])) {
    deletar_curso($_POST['curso_id']);
}

// ✅ SEGURO (com nonce)
if (isset($_POST['deletar']) && wp_verify_nonce($_POST['nonce'], 'deletar_curso')) {
    deletar_curso($_POST['curso_id']);
}
```

### 4. Information Disclosure
```php
// ❌ VULNERÁVEL
echo "Erro SQL: " . $wpdb->last_error;

// ✅ SEGURO
error_log("Erro SQL: " . $wpdb->last_error);
echo "Erro ao processar. Contate o administrador.";
```

## Recursos Relacionados

- **Arquitetura:** `architecture.md`
- **Desenvolvimento:** `development-workflow.md`
- **WordPress Security Handbook:** https://developer.wordpress.org/apis/security/
