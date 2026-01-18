# Workflow de Desenvolvimento - LMS SuporteRapido

## Ambiente de Desenvolvimento

### Requisitos
- WordPress 6.x+
- PHP 7.4+
- MySQL 5.7+
- Servidor local (Local WP, XAMPP, MAMP)

### Instalação
1. Clone o repositório na pasta `wp-content/plugins/`
2. Ative o plugin no painel admin
3. Os CPTs e tabelas são criados automaticamente

## Convenções de Código

### Nomenclatura de Classes
```php
// Padrão: System_Cursos_{Funcionalidade}
class System_Cursos_CPT_Manager {}
class System_Cursos_Access_Control {}
class System_Cursos_Shortcode_Meus_Cursos {}
```

### Nomenclatura de Arquivos
```
class-{funcionalidade}.php          # Classes principais
class-shortcode-{nome}.php          # Shortcodes
```

### Prefixos
- Funções globais: `sistema_cursos_*`
- Hooks customizados: `sistema_cursos/*`
- Meta keys: Sem prefixo (ex: `trilha`, `curso`)

## Padrões de Implementação

### Criar Novo Shortcode

1. Criar arquivo em `includes/shortcodes/`:
```php
<?php
class System_Cursos_Shortcode_Novo extends System_Cursos_Config {
    public function __construct() {
        add_shortcode('novo-shortcode', [$this, 'render_shortcode']);
    }
    
    public function render_shortcode($atts) {
        $atts = shortcode_atts([
            'parametro' => 'valor_padrao'
        ], $atts);
        
        ob_start();
        // HTML aqui
        return ob_get_clean();
    }
}
```

2. Registrar no arquivo principal:
```php
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-novo.php';
new System_Cursos_Shortcode_Novo();
```

### Adicionar Campo a CPT Existente

1. Localizar metabox no `class-cpt-manager.php`
2. Adicionar campo no HTML do metabox
3. Adicionar lógica de salvamento em `save_metaboxes()`

### Modificar Controle de Acesso

1. Editar `class-access-control.php`
2. Usar `acesso_cursos_has($user_id, $curso_id)` para verificações
3. Usar `get_access_source()` para identificar origem

## Versionamento

### Atualizar Versão do Plugin
Sempre atualizar o header em `sistema-cursos-plugin.php`:
```php
/**
 * Version: X.Y.Z
 */
```

### Esquema de Versão
- **X** (Major): Mudanças que quebram compatibilidade
- **Y** (Minor): Novas funcionalidades
- **Z** (Patch): Correções de bugs

## Debugging

### Logs Úteis
```php
error_log('Debug: ' . print_r($variable, true));
```

### Query Debug
```php
$wpdb->show_errors();
echo $wpdb->last_query;
echo $wpdb->last_error;
```

## Checklist de Deploy

- [ ] Atualizar versão no header do plugin
- [ ] Testar em ambiente staging
- [ ] Verificar compatibilidade com tema ativo
- [ ] Testar shortcodes principais
- [ ] Verificar console JavaScript
- [ ] Fazer backup do banco
- [ ] Limpar cache após deploy

## Boas Práticas

### SOLID
- **S**ingle Responsibility: Cada classe com uma responsabilidade
- **O**pen/Closed: Extensível via hooks
- **L**iskov: Classes de shortcode intercambiáveis
- **I**nterface Segregation: Métodos focados
- **D**ependency Inversion: Usar injeção onde possível

### Clean Code
- Nomes descritivos para funções e variáveis
- Funções pequenas e focadas
- Comentários explicando "por quê", não "o quê"

### DRY
- Usar `System_Cursos_Config` como base
- Reutilizar queries comuns
- Centralizar lógica de acesso

### KISS
- Evitar abstrações desnecessárias
- Preferir clareza sobre concisão
- Manter compatibilidade com WordPress
