---
name: Feature Developer
description: Implementa novas funcionalidades para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Feature Developer Agent Playbook

## Missão
Implementar novas funcionalidades no plugin LMS SuporteRapido seguindo os padrões estabelecidos, garantindo integração com o ecossistema WordPress e mantendo compatibilidade com funcionalidades existentes.

## Responsabilidades
- Implementar novos shortcodes seguindo o padrão de classes existente
- Adicionar campos e metaboxes aos CPTs (Trilha, Curso, Aula, Grupo, Certificado)
- Estender funcionalidades de controle de acesso
- Criar novas páginas administrativas quando necessário
- Integrar com a tabela `wp_acesso_cursos` para novas regras de acesso

## Arquivos-Chave para Novas Features

### Para novos Shortcodes
```
includes/shortcodes/class-shortcode-{nome}.php  # Criar novo arquivo
sistema-cursos-plugin.php                        # Registrar require e instância
```

### Para novos Campos de CPT
```
includes/class-cpt-manager.php    # Adicionar metabox e lógica de salvamento
```

### Para Controle de Acesso
```
includes/class-access-control.php  # Modificar verificações e páginas admin
```

### Para Certificados
```
includes/class-certificates.php                      # Lógica de templates
includes/shortcodes/class-shortcode-certificado.php  # Renderização
```

## Padrão de Implementação de Shortcode

```php
<?php
class System_Cursos_Shortcode_NomeFeature extends System_Cursos_Config {
    
    public function __construct() {
        add_shortcode('nome-feature', [$this, 'render_shortcode']);
    }
    
    public function render_shortcode($atts) {
        // 1. Definir atributos com defaults
        $atts = shortcode_atts([
            'id' => 0,
            'mostrar_titulo' => 'sim'
        ], $atts);
        
        // 2. Validar acesso se necessário
        if (!is_user_logged_in()) {
            return '<p class="aviso">Faça login para continuar.</p>';
        }
        
        // 3. Query de dados
        $items = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1
        ]);
        
        // 4. Renderizar HTML
        ob_start();
        ?>
        <div class="sistema-cursos-feature">
            <!-- HTML aqui -->
        </div>
        <?php
        return ob_get_clean();
    }
}
```

## Boas Práticas Específicas do Projeto

### SEMPRE fazer:
- Atualizar versão no header do plugin após mudanças
- Usar `esc_html()`, `esc_attr()`, `wp_kses_post()` para sanitização
- Verificar `is_user_logged_in()` antes de operações de usuário
- Usar `$wpdb->prepare()` para queries SQL
- Seguir nomenclatura `System_Cursos_*` para classes

### NUNCA fazer:
- Usar `get_field()` do ACF (plugin é standalone)
- Modificar tabelas core do WordPress
- Hardcodar IDs de posts
- Ignorar verificações de permissão

## Relacionamentos Entre CPTs

```php
// Curso → Trilha
$trilha_id = get_post_meta($curso_id, 'trilha', true);

// Aula → Curso  
$curso_id = get_post_meta($aula_id, 'curso', true);

// Cursos de uma Trilha
$cursos = get_posts([
    'post_type' => 'curso',
    'meta_query' => [['key' => 'trilha', 'value' => $trilha_id]]
]);
```

## Verificação de Acesso

```php
// Função global disponível
if (acesso_cursos_has($user_id, $curso_id)) {
    // Usuário tem acesso
}

// Via classe
$access = new System_Cursos_Access_Control();
$source = $access->get_access_source($user_id, $curso_id);
// Retorna: 'direto', 'grupo', 'trilha_grupo', ou false
```

## Documentação de Referência
- [Visão Geral do Projeto](../docs/project-overview.md)
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [Glossário](../docs/glossary.md)
- [Workflow de Desenvolvimento](../docs/development-workflow.md)

## Checklist de Implementação

- [ ] Feature alinhada com roadmap.txt
- [ ] Classe criada seguindo padrão System_Cursos_*
- [ ] Registrada no arquivo principal
- [ ] Sanitização de inputs implementada
- [ ] Verificações de acesso quando aplicável
- [ ] CSS adicionado em assets/css/style.css
- [ ] JS adicionado em assets/js/script.js (se necessário)
- [ ] Versão do plugin atualizada
- [ ] Testado em ambiente local
