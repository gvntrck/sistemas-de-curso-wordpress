---
status: filled
generated: 2026-01-18
---

# Segurança e Conformidade - LMS SuporteRapido

Este documento descreve as políticas e práticas de segurança do plugin LMS SuporteRapido para WordPress.

## Autenticação e Autorização

### Sistema de Autenticação
O plugin utiliza o sistema nativo de autenticação do WordPress:
- Login via `wp_authenticate()`
- Sessões gerenciadas pelo WordPress Core
- Cookies de autenticação padrão do WordPress

### Controle de Acesso (Authorization)

#### Role Customizada: Aluno
```php
// Role definida em includes/role-aluno.php
add_role('aluno', 'Aluno', [
    'read' => true,
    'upload_files' => true
]);
```

#### Verificação de Acesso a Cursos
```php
// Função global de verificação
function acesso_cursos_has($user_id, $curso_id) {
    // 1. Verifica acesso direto na tabela wp_acesso_cursos
    // 2. Verifica acesso via grupos (cursos_do_grupo)
    // 3. Verifica acesso via trilhas em grupos (trilhas_do_grupo)
    // Retorna: true/false
}
```

#### Níveis de Acesso
| Nível | Usuário | Permissões |
|-------|---------|------------|
| Admin | `administrator` | Gestão completa de cursos, alunos, grupos |
| Aluno | `aluno` | Visualizar cursos matriculados, progresso, certificados |
| Visitante | - | Apenas visualização pública (se habilitado) |

### Proteção de Conteúdo
- Aulas verificam `is_user_logged_in()` antes de exibir vídeo
- Certificados só são gerados após verificação de 100% de progresso
- Páginas admin requerem `current_user_can('manage_options')`

## Sanitização e Escapamento

### Inputs (Sanitização)
**SEMPRE** sanitizar dados de entrada:

```php
// Strings textuais
$titulo = sanitize_text_field($_POST['titulo']);

// HTML permitido (descrições)
$descricao = wp_kses_post($_POST['descricao']);

// Números inteiros
$id = intval($_GET['id']);
$id = absint($_GET['id']); // Apenas positivos

// Arrays
$ids = array_map('intval', (array)$_POST['ids']);

// URLs
$url = esc_url_raw($_POST['video_url']);
```

### Outputs (Escapamento)
**SEMPRE** escapar dados de saída:

```php
// HTML genérico
echo esc_html($titulo);

// Atributos HTML
echo '<input value="' . esc_attr($valor) . '">';

// URLs
echo esc_url($link);

// HTML com tags permitidas
echo wp_kses_post($descricao);

// JavaScript inline
echo esc_js($variavel);
```

## Proteção contra CSRF

### Implementação de Nonces
```php
// Formulários (backend)
wp_nonce_field('salvar_acesso_curso', 'acesso_curso_nonce');

// Verificação
if (!wp_verify_nonce($_POST['acesso_curso_nonce'], 'salvar_acesso_curso')) {
    wp_die('Verificação de segurança falhou');
}
```

### AJAX com Nonces
```php
// Enqueue com localização
wp_localize_script('sistema-cursos-script', 'sistemaCursosAjax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('sistema_cursos_nonce')
]);

// Handler AJAX
check_ajax_referer('sistema_cursos_nonce', 'nonce');
```

## Proteção SQL (Injection)

### Uso Obrigatório de $wpdb->prepare()
```php
global $wpdb;
$table = $wpdb->prefix . 'acesso_cursos';

// ✅ Correto
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table WHERE user_id = %d AND curso_id = %d",
    $user_id, $curso_id
));

// ❌ NUNCA fazer
$result = $wpdb->get_row("SELECT * FROM $table WHERE user_id = $user_id");
```

### Placeholders de Formato
| Placeholder | Tipo | Uso |
|-------------|------|-----|
| `%d` | Integer | IDs, contadores |
| `%s` | String | Textos (já escapa aspas) |
| `%f` | Float | Valores decimais |

## Dados Sensíveis

### Dados Armazenados
| Dado | Localização | Sensibilidade |
|------|-------------|---------------|
| Senhas | wp_users (hash) | Alta - gerenciado por WP |
| Email | wp_users | Média |
| Matrículas | wp_acesso_cursos | Baixa |
| Progresso | wp_usermeta | Baixa |
| Foto do Aluno | wp_usermeta (URL) | Baixa |

### Não Armazena
- Dados de pagamento (usar gateway externo)
- Documentos pessoais
- Logs de IP (exceto WordPress core)

## Validações de Permissão

### Padrão para Páginas Admin
```php
public function render_admin_page() {
    // Verificar capacidade
    if (!current_user_can('manage_options')) {
        wp_die('Acesso negado');
    }
    
    // Verificar nonce se houver form
    if (isset($_POST['submit'])) {
        check_admin_referer('acao_admin', 'nonce_field');
    }
    
    // ... renderizar página
}
```

### Padrão para Shortcodes Protegidos
```php
public function render_shortcode($atts) {
    // Verificar login
    if (!is_user_logged_in()) {
        return '<p class="aviso">Faça login para acessar.</p>';
    }
    
    // Verificar acesso ao curso
    $user_id = get_current_user_id();
    $curso_id = intval($atts['curso']);
    
    if (!acesso_cursos_has($user_id, $curso_id)) {
        return '<p class="erro">Você não tem acesso a este curso.</p>';
    }
    
    // ... renderizar conteúdo
}
```

## Boas Práticas de Segurança

### Checklist de Implementação
- [ ] Inputs sanitizados (`sanitize_*`, `intval`, `absint`)
- [ ] Outputs escapados (`esc_*`, `wp_kses_post`)
- [ ] Nonces em formulários (`wp_nonce_field`, `check_admin_referer`)
- [ ] Nonces em AJAX (`wp_create_nonce`, `check_ajax_referer`)
- [ ] Queries SQL preparadas (`$wpdb->prepare()`)
- [ ] Verificação de capacidade (`current_user_can()`)
- [ ] Verificação de login (`is_user_logged_in()`)

### Vulnerabilidades Comuns a Evitar
| Vulnerabilidade | Prevenção |
|-----------------|-----------|
| XSS | Escapar todos os outputs |
| SQL Injection | Usar `$wpdb->prepare()` |
| CSRF | Implementar nonces |
| Privilege Escalation | Verificar `current_user_can()` |
| Object Injection | Não usar `unserialize()` em inputs |

## Resposta a Incidentes

### Contatos
- **Desenvolvedor**: Giovani Tureck - SuporteRapido
- **Hospedagem**: Contatar suporte do provedor

### Passos em Caso de Incidente
1. Desativar plugin se necessário
2. Verificar logs do WordPress (`wp-content/debug.log`)
3. Analisar tabelas do banco (especialmente `wp_acesso_cursos`)
4. Restaurar backup se comprometido
5. Aplicar correção e atualizar versão
