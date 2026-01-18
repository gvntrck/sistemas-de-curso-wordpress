---
name: Bug Fixer
description: Analisa e corrige bugs no LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Bug Fixer Agent Playbook

## Missão
Diagnosticar e corrigir bugs no plugin LMS SuporteRapido, garantindo estabilidade e funcionamento correto de todas as funcionalidades de cursos, acesso e certificados.

## Responsabilidades
- Analisar erros PHP e JavaScript reportados
- Identificar causas raiz de problemas de acesso
- Corrigir bugs de renderização em shortcodes
- Resolver problemas de geração de certificados
- Debugar queries SQL na tabela `wp_acesso_cursos`

## Arquivos Críticos por Área de Bug

### Erros de Acesso a Cursos
```
includes/class-access-control.php    # Lógica de verificação
includes/class-cpt-manager.php       # Relacionamentos CPT
```

### Erros em Shortcodes
```
includes/shortcodes/class-shortcode-*.php  # Shortcode específico
sistema-cursos-plugin.php                   # Registro e inicialização
```

### Erros de Certificado
```
includes/class-certificates.php                      # Templates e metaboxes
includes/shortcodes/class-shortcode-certificado.php  # Geração de PDF
assets/js/script.js                                  # html2pdf.js
```

### Erros de Progresso
```
includes/class-course-progress.php   # Cálculo de progresso
assets/js/script.js                  # AJAX de conclusão de aula
```

### Erros de Admin
```
includes/class-access-control.php    # Páginas admin de alunos
includes/class-user-fields.php       # Campos de usuário
```

## Estratégia de Debug

### 1. PHP Errors
```php
// Ativar debug no wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Log manual
error_log('Debug LMS: ' . print_r($variable, true));
```

### 2. SQL Errors
```php
global $wpdb;
$wpdb->show_errors();

// Após query
echo $wpdb->last_query;
echo $wpdb->last_error;
```

### 3. JavaScript Errors
```javascript
console.log('Debug LMS:', variable);
// Verificar console do navegador (F12)
```

## Bugs Comuns e Soluções

### "Parse error: unexpected token"
**Causa**: Sintaxe PHP incorreta, geralmente `endif` órfão
**Solução**: Verificar pareamento de `if/endif`, `foreach/endforeach`

### "Call to undefined function acesso_cursos_has()"
**Causa**: Arquivo não incluído ou ordem de load incorreta
**Solução**: Verificar require_once no sistema-cursos-plugin.php

### Certificado não gera PDF
**Causa**: html2pdf.js não carregado ou erro de CORS
**Solução**: Verificar enqueue de scripts e imagens de fundo

### Acesso negado mesmo com matrícula
**Causa**: Status 'suspenso' ou 'revogado', ou data_fim expirada
**Solução**: Verificar tabela wp_acesso_cursos:
```sql
SELECT * FROM wp_acesso_cursos 
WHERE user_id = X AND curso_id = Y;
```

### Progresso não atualiza
**Causa**: AJAX falhando ou meta key incorreta
**Solução**: Verificar `aulas_concluidas_{curso_id}` no usermeta

### 404 em páginas de curso
**Causa**: Rewrite rules não atualizadas
**Solução**: Atualizar versão do plugin (força flush) ou:
```php
flush_rewrite_rules();
```

## Verificações de Diagnóstico

### Verificar Acesso
```php
$user_id = get_current_user_id();
$curso_id = get_the_ID();

// Acesso direto
global $wpdb;
$table = $wpdb->prefix . 'acesso_cursos';
$result = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table WHERE user_id = %d AND curso_id = %d",
    $user_id, $curso_id
));
var_dump($result);

// Acesso via grupo
$grupos = get_posts(['post_type' => 'grupo', 'posts_per_page' => -1]);
foreach ($grupos as $grupo) {
    $alunos = get_post_meta($grupo->ID, 'alunos_do_grupo', true);
    if (in_array($user_id, (array)$alunos)) {
        echo "Usuário está no grupo: " . $grupo->post_title;
    }
}
```

### Verificar Relacionamento CPT
```php
// Curso da aula
$curso_id = get_post_meta($aula_id, 'curso', true);
echo "Aula $aula_id pertence ao curso: $curso_id";

// Trilha do curso
$trilha_id = get_post_meta($curso_id, 'trilha', true);
echo "Curso $curso_id pertence à trilha: $trilha_id";
```

## Boas Práticas de Correção

### SEMPRE:
- Reproduzir o bug antes de corrigir
- Fazer backup antes de alterações
- Testar correção em ambiente local
- Atualizar versão do plugin
- Documentar a correção

### NUNCA:
- Corrigir diretamente em produção
- Ignorar validação de dados
- Remover verificações de segurança
- Alterar estrutura da tabela sem migração

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Fluxo de Dados](../docs/data-flow.md)
- [Glossário](../docs/glossary.md)

## Checklist de Correção

- [ ] Bug reproduzido localmente
- [ ] Causa raiz identificada
- [ ] Correção implementada
- [ ] Testado cenário original
- [ ] Testados cenários relacionados
- [ ] Sem regressões
- [ ] Versão do plugin atualizada
- [ ] Correção documentada
