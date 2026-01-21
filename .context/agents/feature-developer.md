# Feature Developer - LMS SuporteRapido

## Responsabilidades

Desenvolver novas funcionalidades completas (frontend + backend + testes) seguindo padrões do projeto.

## Workflow de Feature

```
1. Entender requisito → Ler spec/ticket
2. Planejar arquitetura → Quais classes/arquivos modificar
3. Backend primeiro → Lógica + AJAX handlers
4. Frontend depois → UI + interação
5. Integrar → Conectar frontend com backend
6. Testar → Casos de uso + edge cases
7. Documentar → Atualizar docs se necessário
8. Atualizar versão → 3 lugares!
9. Commit → Mensagem descritiva
```

## Template de Nova Feature

### 1. Backend (PHP)

**Arquivo:** `includes/class-nova-feature.php`

```php
<?php
if (!defined('ABSPATH')) exit;

class System_Cursos_Nova_Feature {
    public function __construct() {
        add_action('wp_ajax_processar_feature', [$this, 'handle_ajax']);
        add_action('init', [$this, 'register_hooks']);
    }
    
    public function handle_ajax() {
        if (!wp_verify_nonce($_POST['nonce'], 'feature_nonce')) {
            wp_send_json_error('Nonce inválido');
        }
        
        $data = sanitize_text_field($_POST['data']);
        $result = $this->processar($data);
        
        wp_send_json_success($result);
    }
    
    private function processar($data) {
        // Lógica aqui
        return ['status' => 'sucesso'];
    }
}
```

**Registrar em** `sistema-cursos-plugin.php`:
```php
require_once plugin_dir_path(__FILE__) . 'includes/class-nova-feature.php';
new System_Cursos_Nova_Feature();
```

### 2. Frontend (JavaScript)

**Arquivo:** `assets/js/nova-feature.js`

```javascript
jQuery(document).ready(function($) {
    $('.btn-feature').on('click', function() {
        const data = $(this).data('value');
        
        $.ajax({
            url: lms_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'processar_feature',
                data: data,
                nonce: lms_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    alert('Sucesso!');
                }
            }
        });
    });
});
```

**Enqueue:** `class-assets.php`
```php
wp_enqueue_script(
    'lms-nova-feature',
    plugin_dir_url(__FILE__) . '../assets/js/nova-feature.js',
    ['jquery'],
    SISTEMA_CURSOS_VERSION,
    true
);
```

### 3. Shortcode (se aplicável)

**Arquivo:** `includes/shortcodes/class-shortcode-nova-feature.php`

```php
class System_Cursos_Shortcode_Nova_Feature {
    public function __construct() {
        add_shortcode('nova-feature', [$this, 'render']);
    }
    
    public function render($atts) {
        $atts = shortcode_atts([
            'param' => 'default'
        ], $atts);
        
        ob_start();
        ?>
        <div class="nova-feature">
            <h3><?php echo esc_html($atts['param']); ?></h3>
            <button class="btn-feature">Ação</button>
        </div>
        <?php
        return ob_get_clean();
    }
}
```

## Exemplos de Features Recentes

### Feature: Sistema de Quizzes

**Arquivos Criados:**
- `includes/class-quiz-builder.php` - Interface admin para criar quiz
- `includes/class-quiz-process.php` - Processar respostas
- `assets/js/quiz-handler.js` - Interação frontend
- Tabela: `wp_quiz_attempts`

**Versão:** 1.3.0 → 1.3.1

### Feature: Integração WooCommerce

**Arquivos Criados:**
- `includes/class-woocommerce-integration.php`

**Hooks Usados:**
- `woocommerce_order_status_completed`

**Versão:** 1.2.0 → 1.2.1

## Checklist de Feature Completa

### Backend
- [ ] Classe criada e instanciada
- [ ] AJAX handlers com nonce verification
- [ ] Sanitização de inputs
- [ ] Prepared statements em queries
- [ ] Hooks (actions/filters) para extensibilidade
- [ ] Tratamento de erros

### Frontend
- [ ] JavaScript enfileirado corretamente
- [ ] AJAX com feedback visual (loading)
- [ ] Tratamento de erros (alert ou mensagem)
- [ ] Responsivo (mobile, tablet, desktop)
- [ ] Acessibilidade (labels, aria, keyboard)

### Testes
- [ ] Caso feliz (fluxo normal)
- [ ] Edge cases (inputs vazios, valores grandes)
- [ ] Permissões (aluno vs admin)
- [ ] Regressão (features antigas ainda funcionam)

### Documentação
- [ ] Atualizar `architecture.md` se necessário
- [ ] Adicionar ao `README.md` se é shortcode
- [ ] Documentar novos hooks em comentários
- [ ] Changelog atualizado

### Versionamento
- [ ] Header do plugin
- [ ] Constante SISTEMA_CURSOS_VERSION
-  [ ] Função sistema_cursos_check_version

## Padrões de Código

Seguir **SOLID, DRY, KISS**:

```php
// ✅ BOM: DRY
private function get_access($user_id, $type) {
    return get_user_meta($user_id, "acesso_{$type}", true) ?: [];
}

$cursos = $this->get_access($user_id, 'cursos');
$trilhas = $this->get_access($user_id, 'trilhas');

// ❌ RUIM: Repetição
$cursos = get_user_meta($user_id, 'acesso_cursos', true) ?: [];
$trilhas = get_user_meta($user_id, 'acesso_trilhas', true) ?: [];
```

## Recursos

- **Arquitetura:** `../docs/architecture.md`
- **Workflow:** `../docs/development-workflow.md`
- **Backend Specialist:** `backend-specialist.md`
- **Frontend Specialist:** `frontend-specialist.md`
