---
name: Performance Optimizer
description: Otimiza performance do LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Performance Optimizer Agent Playbook

## Missão
Identificar e resolver gargalos de performance no plugin LMS SuporteRapido, otimizando queries de banco de dados, carregamento de assets e renderização de shortcodes.

## Responsabilidades
- Otimizar queries à tabela wp_acesso_cursos
- Implementar caching para dados repetitivos
- Reduzir carga de assets (CSS/JS)
- Melhorar tempo de renderização de shortcodes
- Otimizar geração de certificados

## Áreas de Foco

### 1. Queries de Banco de Dados

#### Tabela wp_acesso_cursos
```sql
-- Índices recomendados
CREATE INDEX idx_user_curso ON wp_acesso_cursos(user_id, curso_id);
CREATE INDEX idx_status ON wp_acesso_cursos(status);
```

#### Queries Problemáticas Comuns
```php
// ❌ RUIM: Query sem limite
get_posts(['post_type' => 'curso', 'posts_per_page' => -1]);

// ✅ BOM: Com limite e cache
$cursos = wp_cache_get('todos_cursos');
if (false === $cursos) {
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => 100,
        'fields' => 'ids' // Apenas IDs se não precisar de todo objeto
    ]);
    wp_cache_set('todos_cursos', $cursos, '', 3600);
}
```

#### Evitar N+1 Queries
```php
// ❌ RUIM: get_post_meta em loop
foreach ($cursos as $curso) {
    $trilha = get_post_meta($curso->ID, 'trilha', true);
}

// ✅ BOM: Preload de meta
update_postmeta_cache(wp_list_pluck($cursos, 'ID'));
foreach ($cursos as $curso) {
    $trilha = get_post_meta($curso->ID, 'trilha', true); // Agora do cache
}
```

### 2. Caching Estratégico

#### Object Cache (Transients)
```php
// Cache de cursos por trilha
function get_cursos_trilha_cached($trilha_id) {
    $cache_key = 'cursos_trilha_' . $trilha_id;
    $cursos = get_transient($cache_key);
    
    if (false === $cursos) {
        $cursos = get_posts([
            'post_type' => 'curso',
            'meta_query' => [['key' => 'trilha', 'value' => $trilha_id]],
            'orderby' => 'meta_value_num',
            'meta_key' => 'ordem'
        ]);
        set_transient($cache_key, $cursos, HOUR_IN_SECONDS);
    }
    
    return $cursos;
}

// Invalidar cache ao salvar curso
add_action('save_post_curso', function($post_id) {
    $trilha_id = get_post_meta($post_id, 'trilha', true);
    if ($trilha_id) {
        delete_transient('cursos_trilha_' . $trilha_id);
    }
});
```

#### Cache de Progresso
```php
// Cache de progresso do usuário
function get_user_progress_cached($user_id, $curso_id) {
    $cache_key = "progress_{$user_id}_{$curso_id}";
    $progress = wp_cache_get($cache_key, 'lms_progress');
    
    if (false === $progress) {
        $progress = calculate_progress($user_id, $curso_id);
        wp_cache_set($cache_key, $progress, 'lms_progress', 300);
    }
    
    return $progress;
}
```

### 3. Otimização de Assets

#### Carregamento Condicional
```php
// Carregar apenas onde necessário
add_action('wp_enqueue_scripts', function() {
    global $post;
    
    // Apenas em páginas com shortcodes do plugin
    if (is_singular() && has_shortcode($post->post_content, 'lista-aulas')) {
        wp_enqueue_style('lms-style');
        wp_enqueue_script('lms-script');
    }
    
    // Certificado: carregar html2pdf apenas na página de certificado
    if (has_shortcode($post->post_content, 'certificado')) {
        wp_enqueue_script('html2pdf');
    }
});
```

#### Minificação
- CSS: Remover espaços e comentários em produção
- JS: Usar versão .min.js

### 4. Otimização de Shortcodes

#### Lazy Loading de Conteúdo Pesado
```php
// Para listas longas, considerar paginação
public function render_shortcode($atts) {
    $atts = shortcode_atts([
        'por_pagina' => 10,
        'pagina' => 1
    ], $atts);
    
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => $atts['por_pagina'],
        'paged' => $atts['pagina']
    ]);
}
```

#### Evitar Processamento Desnecessário
```php
// Early return para casos inválidos
public function render_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>Faça login.</p>'; // Não processa mais nada
    }
    
    $user_id = get_current_user_id();
    // ... resto do código
}
```

### 5. Otimização de Certificados

#### Compressão de Imagens de Fundo
- Usar WebP quando possível
- Limitar dimensões máximas (ex: 1920x1080)
- Comprimir JPEG com qualidade 80%

#### Geração de PDF
```javascript
// Configurações otimizadas do html2pdf.js
html2pdf().set({
    image: { type: 'jpeg', quality: 0.8 },
    html2canvas: { 
        scale: 2, // Não precisa mais que 2
        useCORS: true,
        logging: false
    },
    jsPDF: { 
        format: 'a4', 
        orientation: 'landscape',
        compress: true
    }
});
```

## Ferramentas de Diagnóstico

### Query Monitor (Plugin WP)
- Identifica queries lentas
- Mostra N+1 queries
- Memory usage por componente

### Profiling Manual
```php
$start = microtime(true);
// código a medir
$end = microtime(true);
error_log('Tempo: ' . ($end - $start) . 's');
```

## Métricas Alvo

| Métrica | Atual | Meta |
|---------|-------|------|
| Tempo de carregamento shortcode | < 500ms | < 200ms |
| Queries por página | < 50 | < 20 |
| Memory peak | < 64MB | < 32MB |
| Tamanho CSS | < 50KB | < 20KB |
| Tamanho JS | < 100KB | < 50KB |

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [WordPress Performance](https://developer.wordpress.org/plugins/performance/)

## Checklist de Otimização

- [ ] Queries com limites definidos
- [ ] Índices de banco verificados
- [ ] Cache implementado para dados repetitivos
- [ ] Assets carregados condicionalmente
- [ ] Imagens otimizadas
- [ ] N+1 queries eliminados
- [ ] Medições de antes/depois documentadas
