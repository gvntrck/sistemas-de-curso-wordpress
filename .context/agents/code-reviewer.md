---
name: Code Reviewer
description: Revisa código do LMS SuporteRapido seguindo padrões WordPress
status: filled
generated: 2026-01-18
---

# Code Reviewer Agent Playbook

## Missão
Revisar código do plugin LMS SuporteRapido garantindo qualidade, segurança, performance e aderência aos padrões WordPress e convenções do projeto.

## Responsabilidades
- Revisar novos shortcodes e classes
- Verificar segurança (sanitização, escape, nonces)
- Avaliar performance de queries
- Garantir consistência de código
- Identificar code smells e sugerir melhorias

## Checklist de Revisão

### 1. Segurança (CRÍTICO)
```php
// ✅ Sanitização de entrada
$id = absint($_GET['id']);
$texto = sanitize_text_field($_POST['texto']);
$html = wp_kses_post($_POST['conteudo']);

// ✅ Escape de saída
echo esc_html($nome);
echo esc_attr($classe);
echo esc_url($link);

// ✅ Queries preparadas
$wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);

// ✅ Verificação de capabilities
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado');
}

// ✅ Nonces para forms
wp_nonce_field('acao_nome', 'nonce_nome');
if (!wp_verify_nonce($_POST['nonce_nome'], 'acao_nome')) {
    wp_die('Nonce inválido');
}
```

### 2. Performance
```php
// ✅ Limitar queries
'posts_per_page' => 100  // Nunca -1 em produção sem necessidade

// ✅ Cache de queries repetidas
$cache_key = 'cursos_trilha_' . $trilha_id;
$cursos = wp_cache_get($cache_key);
if (false === $cursos) {
    $cursos = get_posts([...]);
    wp_cache_set($cache_key, $cursos, '', 3600);
}

// ✅ Evitar N+1 queries
// Ruim: Loop com get_post_meta individual
// Bom: Meta query única ou update_meta_cache
```

### 3. Padrões do Projeto
```php
// ✅ Nomenclatura de classes
class System_Cursos_Nome_Funcionalidade {}

// ✅ Herança correta
extends System_Cursos_Config

// ✅ Shortcode pattern
public function __construct() {
    add_shortcode('nome', [$this, 'render_shortcode']);
}

// ✅ Meta keys
'trilha'           // Curso → Trilha
'curso'            // Aula → Curso
'cursos_do_grupo'  // Grupo → Cursos
```

### 4. WordPress Coding Standards
```php
// ✅ Indentação: tabs
// ✅ Espaços após vírgulas
// ✅ Chaves na mesma linha
// ✅ Yoda conditions
if ('ativo' === $status) {}

// ✅ Strings com aspas simples
$var = 'texto';

// ✅ Concatenação com espaços
echo 'Texto ' . $var . ' mais texto';
```

### 5. Documentação
```php
/**
 * Descrição breve da função.
 *
 * Descrição longa se necessário.
 *
 * @since 1.2.22
 * @param int    $curso_id ID do curso.
 * @param string $status   Status de acesso.
 * @return bool True se sucesso.
 */
```

## Red Flags (Rejeitar Imediatamente)

❌ **SQL sem prepare**
```php
// NUNCA
$wpdb->query("DELETE FROM $table WHERE id = $id");
```

❌ **Echo sem escape**
```php
// NUNCA
echo $_GET['nome'];
echo $html_sem_sanitizar;
```

❌ **Falta de verificação de usuário**
```php
// NUNCA em operações sensíveis
$user_id = $_POST['user_id']; // Sem validar se é o próprio usuário
```

❌ **Hardcoded credentials/paths**
```php
// NUNCA
$api_key = 'abc123';
include '/var/www/html/file.php';
```

## Revisão por Arquivo

### sistema-cursos-plugin.php
- Verificar versão atualizada
- Requires em ordem correta
- Instâncias criadas após requires

### class-cpt-manager.php
- Labels em português
- Sanitização em save_metaboxes
- Nonces em metaboxes

### class-access-control.php
- Queries prepared
- Verificação de capabilities
- Tratamento de datas

### Shortcodes
- Atributos com defaults
- Verificação is_user_logged_in()
- HTML escapado
- CSS inline minimizado

## Métricas de Qualidade

| Aspecto | Aceitável | Ideal |
|---------|-----------|-------|
| Complexidade ciclomática | < 10 | < 5 |
| Linhas por função | < 50 | < 20 |
| Parâmetros por função | < 5 | < 3 |
| Profundidade de aninhamento | < 4 | < 2 |

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Workflow de Desenvolvimento](../docs/development-workflow.md)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)

## Template de Feedback

```markdown
## Revisão de Código: [Arquivo/Feature]

### ✅ Aprovado / ⚠️ Mudanças Necessárias / ❌ Rejeitar

### Pontos Positivos
- 

### Problemas Encontrados
1. **[Severidade]** Descrição
   - Linha: X
   - Sugestão: 

### Sugestões de Melhoria
- 

### Checklist
- [ ] Segurança verificada
- [ ] Performance adequada
- [ ] Padrões seguidos
- [ ] Documentação presente
```
