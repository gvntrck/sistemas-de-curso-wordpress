# Performance Optimizer - LMS SuporteRapido

## Otimização de Queries

### Buscar apenas IDs
```php
// Ao invés de objetos completos
$aula_ids = get_posts([
    'post_type' => 'aula',
    'fields' => 'ids', // ← Importante!
    'posts_per_page' => -1
]);
```

### Cache com Transients
```php
$cursos = get_transient('cursos_user_' . $user_id);
if (!$cursos) {
    $cursos = // buscar do banco
    set_transient('cursos_user_' . $user_id, $cursos, HOUR_IN_SECONDS);
}
```

### Índices em Tabelas Customizadas
```sql
ALTER TABLE wp_acesso_cursos_log ADD INDEX idx_user_curso (user_id, curso_id);
```

## Frontend

### Lazy Load de Imagens
```html
<img data-src="imagem.jpg" class="lazy">
```

### Debounce em Busca
```javascript
const buscar = debounce(function() {
    // AJAX  
}, 300);
```

## Ferramentas
- Query Monitor plugin
- P3 Plugin Profiler
- GTmetrix

## Recursos
- **Architecture:** `../docs/architecture.md`
