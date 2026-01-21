# Workflow de Desenvolvimento

## Processo de Desenvolvimento Diário

### 1. **Preparando o Ambiente**

#### Requisitos
- **PHP:** 7.4 ou superior
- **WordPress:** 5.8 ou superior
- **Ambiente Local:** Local WP, XAMPP, Laragon, ou Docker

#### Instalação Local
```bash
# 1. Clone o repositório (se aplicável)
git clone [repo-url] wp-content/plugins/sistema-cursos-plugin

# 2. Ative o plugin no WordPress
# Admin → Plugins → Ativar "LMS SuporteRapido"

# 3. (Opcional) Instale ACF Pro se não estiver presente
```

### 2. **Estrutura de Branches**

O projeto usa **trunk-based development** simplificado:

```
main (branch principal)
  ├── feature/nova-funcionalidade
  ├── fix/correcao-bug
  └── refactor/melhoria-codigo
```

#### Convenções de Nomenclatura
- `feature/nome-descritivo` → Novas funcionalidades
- `fix/nome-do-bug` → Correções de bugs
- `refactor/o-que-foi-refatorado` → Refatorações
- `docs/o-que-foi-documentado` → Documentação

### 3. **Ciclo de Desenvolvimento**

```
1. Criar branch a partir de main
   ↓
2. Implementar alterações
   ↓
3. Atualizar número de versão no header do plugin
   ↓
4. Testar localmente
   ↓
5. Commit com mensagem descritiva
   ↓
6. Merge para main (se aprovado)
   ↓
7. Deploy (manual ou via FTP sync)
```

## Comandos Locais

### Desenvolvimento Local

```bash
# Não há comandos npm/composer neste projeto
# Desenvolvimento é feito diretamente no WordPress

# Para ativar debug no WordPress (wp-config.php):
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Sync com FTP (Produção)

```python
# Usar o script Python para sincronização
python sync_ftp.py

# Nota: Este arquivo contém credenciais e está em .gitignore
```

## Versionamento

### Atualização de Versão

**CRÍTICO:** Sempre que modificar qualquer arquivo do plugin, atualize a versão em 3 lugares:

```php
// 1. Header do plugin (sistema-cursos-plugin.php)
/**
 * Version: 1.3.11  ← ATUALIZAR AQUI
 */

// 2. Constante de versão
define('SISTEMA_CURSOS_VERSION', '1.3.11');  ← ATUALIZAR AQUI

// 3. Função de verificação de versão
function sistema_cursos_check_version() {
    $current_version = '1.3.11';  ← ATUALIZAR AQUI
    // ...
}
```

### Semântico de Versão (Aproximado)

- **Major (1.x.x):** Mudanças incompatíveis ou reestruturação completa
- **Minor (x.3.x):** Novas funcionalidades compatíveis
- **Patch (x.x.11):** Correções de bugs e pequenas melhorias

## Padrões de Código

### PHP - Princípios SOLID

#### Single Responsibility
```php
// ✅ BOM: Uma classe, uma responsabilidade
class System_Cursos_Progress {
    public function mark_lesson_complete($user_id, $lesson_id) { }
    public function get_course_progress($user_id, $course_id) { }
}

// ❌ RUIM: Classe fazendo muitas coisas
class System_Cursos_Everything {
    public function mark_lesson_complete() { }
    public function send_email() { }
    public function generate_certificate() { }
}
```

#### DRY (Don't Repeat Yourself)
```php
// ✅ BOM: Método reutilizável
private function get_user_access($user_id, $type) {
    return get_user_meta($user_id, "acesso_{$type}", true) ?: [];
}

$cursos = $this->get_user_access($user_id, 'cursos');
$trilhas = $this->get_user_access($user_id, 'trilhas');

// ❌ RUIM: Código duplicado
$cursos = get_user_meta($user_id, 'acesso_cursos', true) ?: [];
$trilhas = get_user_meta($user_id, 'acesso_trilhas', true) ?: [];
```

#### KISS (Keep It Simple, Stupid)
```php
// ✅ BOM: Simples e direto
if (empty($user_id)) {
    return false;
}

// ❌ RUIM: Complexidade desnecessária
if (isset($user_id) && !is_null($user_id) && $user_id !== 0 && $user_id !== '') {
    // ...
}
```

### Convenções de Nomenclatura

```php
// Classes: PascalCase com prefixo
class System_Cursos_Quiz_Builder { }

// Métodos: snake_case
public function get_course_progress() { }

// Variáveis: snake_case
$user_id = get_current_user_id();
$course_progress = 75;

// Constantes: UPPER_SNAKE_CASE
define('SISTEMA_CURSOS_VERSION', '1.3.10');

// Hooks/Actions: snake_case com prefixo
do_action('sistema_cursos_aula_completa', $user_id);
add_filter('sistema_cursos_percentual_certificado', $callback);
```

### JavaScript

```javascript
// Use jQuery via WordPress (não-conflito)
jQuery(document).ready(function($) {
    // Código aqui
});

// AJAX sempre com nonce
$.ajax({
    url: ajaxurl,
    data: {
        action: 'minha_acao',
        nonce: meu_objeto.nonce
    }
});
```

## Code Review

### Checklist de Revisão

Antes de fazer commit, verifique:

- [ ] Versão do plugin atualizada em 3 lugares
- [ ] Código segue SOLID, DRY, KISS
- [ ] Nonces implementados em operações AJAX/POST
- [ ] Sanitização de inputs (`sanitize_text_field`, etc)
- [ ] Escape de outputs (`esc_html`, `esc_url`, `esc_attr`)
- [ ] Capabilities verificadas quando necessário
- [ ] Comentários em funções complexas
- [ ] Sem `var_dump()` ou `console.log()` esquecidos
- [ ] Testado em ambiente local

### Erros de Lint

- **Tolerância:** Erros de lint WordPress são toleráveis
- **Foco:** Priorizar funcionalidade e segurança sobre conformidade estrita com PHPCS

## Testes

### Testes Manuais (Atual)

```
1. Teste de Acesso
   - Criar usuário teste
   - Conceder acesso a curso
   - Verificar se consegue acessar

2. Teste de Progresso
   - Marcar aula como completa
   - Verificar atualização do percentual
   - Verificar emissão de certificado quando aplicável

3. Teste de Quiz
   - Responder quiz com respostas corretas
   - Responder quiz com respostas incorretas
   - Verificar controle de tentativas

4. Teste de WooCommerce (se ativo)
   - Criar produto vinculado a curso
   - Simular compra
   - Verificar matrícula automática
```

### Testes de Regressão

Ao modificar funcionalidades existentes:
1. Verificar shortcodes ainda funcionam
2. Testar navegação AJAX entre aulas
3. Verificar cálculo de progresso
4. Testar geração de certificados

## Git Workflow

### Commit Messages

Formato recomendado:
```
[tipo] Descrição curta

Descrição detalhada se necessário
```

Tipos:
- `[feat]` Nova funcionalidade
- `[fix]` Correção de bug
- `[refactor]` Refatoração
- `[docs]` Documentação
- `[style]` Formatação (sem mudança de lógica)
- `[chore]` Tarefas de manutenção

Exemplos:
```
[feat] Adiciona sistema de quizzes nas aulas

Implementa:
- Builder de quizzes no admin
- Processamento de respostas
- Controle de tentativas
- Integração com progresso do curso

Versão: 1.3.0
```

```
[fix] Corrige layout do quiz após navegação AJAX

O quiz ficava com layout quebrado quando o usuário
navegava para outra aula e voltava via AJAX.

Solução: Reinicializar estilos do quiz no callback AJAX.

Versão: 1.3.10
```

## Deploy

### Manual (FTP)

```python
# 1. Atualizar versão conforme descrito acima

# 2. Executar sync FTP
python sync_ftp.py

# 3. No WordPress de produção, verificar se a versão foi atualizada
# Admin → Plugins → Ver versão do "LMS SuporteRapido"

# 4. Testar funcionalidade modificada em produção
```

### Checklist de Deploy

- [ ] Código testado localmente
- [ ] Versão atualizada
- [ ] Backup do FTP feito (se alteração crítica)
- [ ] Sync FTP executado com sucesso
- [ ] Verificar versão em produção
- [ ] Teste smoke (navegação básica)

## Debug e Troubleshooting

### Logs do WordPress

```php
// Ver logs em wp-content/debug.log
error_log('Debug info: ' . print_r($variable, true));
```

### Debug de AJAX

```javascript
// Frontend
$.ajax(/* ... */).done(function(response) {
    console.log('Resposta:', response);
}).fail(function(xhr, status, error) {
    console.error('Erro AJAX:', error);
});
```

```php
// Backend
add_action('wp_ajax_minha_acao', function() {
    error_log('AJAX chamado: ' . print_r($_POST, true));
    // ...
});
```

### Problemas Comuns

#### 1. "Shortcode não renderiza"
- Verificar se a classe está sendo instanciada em `sistema-cursos-plugin.php`
- Conferir `add_shortcode()` no `__construct()`

#### 2. "AJAX não funciona"
- Verificar nonce
- Conferir `ajaxurl` está definido no frontend
- Ver console do navegador para erros JS

#### 3. "Aulas não aparecem no curso"
- Verificar meta key `curso` está preenchida na aula
- Conferir query no shortcode `[lista-aulas]`

## Recursos Relacionados

- **Arquitetura:** `architecture.md`
- **Testagem:** `testing-strategy.md`
- **Ferramentas:** `tooling.md`
