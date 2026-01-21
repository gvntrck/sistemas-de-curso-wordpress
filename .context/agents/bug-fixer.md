# Bug Fixer - LMS SuporteRapido

## Responsabilidades

- Diagnosticar e corrigir bugs reportados
- Investigar comportamentos inesperados
- Implementar hotfixes quando necessário
- Documentar causa raiz e solução
- Prevenir regressões

## Processo de Debug

### 1. Reproduzir o Bug
```
- Ler descrição detalhada
- Seguir passos exatos para reproduzir
- Confirmar ambiente (PHP, WordPress, browser)
- Documentar se conseguiu reproduzir
```

### 2. Isolar a Causa
```
Perguntas-chave:
- Quando começou? (após qual mudança?)
- Onde ocorre? (frontend, backend, AJAX?)
- Quem afeta? (todos usuários, apenas alunos, apenas admin?)
- Consistente ou intermitente?
```

### 3. Investigação

#### Backend (PHP)
```php
// Ativar debug em wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Adicionar logs
error_log('Debug: $user_id = ' . $user_id);
error_log('Acessos: ' . print_r($acessos, true));

// Ver logs em: wp-content/debug.log
```

#### Frontend (JavaScript)
```javascript
// Console logs
console.log('Aula ID:', aulaId);
console.table(respostas);
console.error('Erro:', error);

// DevTools Network tab para AJAX
// Ver status code, request/response
```

#### Banco de Dados
```sql
-- Verificar dados
SELECT * FROM wp_acesso_cursos_log WHERE user_id = 123;
SELECT * FROM wp_quiz_attempts WHERE aula_id = 456;

-- Verificar user_meta
SELECT * FROM wp_usermeta WHERE user_id = 123 AND meta_key LIKE 'acesso%';
```

## Bugs Comuns e Soluções

### Bug: "Shortcode não renderiza"

**Sintomas:** Shortcode aparece como texto `[meus-cursos]`

**Causas Possíveis:**
1. Classe não instanciada
2. `add_shortcode()` não chamado
3. Erro fatal impedindo registro

**Debug:**
```php
// Verificar se shortcode está registrado
global $shortcode_tags;
var_dump(isset($shortcode_tags['meus-cursos']));

// Verificar se classe existe
var_dump(class_exists('System_Cursos_Shortcode_Meus_Cursos'));
```

**Solução:**
```php
// Em sistema-cursos-plugin.php, garantir:
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-meus-cursos.php';
new System_Cursos_Shortcode_Meus_Cursos();
```

### Bug: "AJAX não funciona"

**Sintomas:** Nada acontece ao clicar, erro 400 ou 0

**Causas Possíveis:**
1. Nonce inválido
2. Action não registrada
3. JavaScript com erro de sintaxe
4. ajaxurl não definido

**Debug:**
```javascript
// Ver console do navegador (F12)
console.log('ajaxurl:', lms_ajax.ajaxurl);
console.log('nonce:', lms_ajax.nonce);

// Ver Network tab: Status Code
// 400 = Nonce inválido
// 0 = JavaScript error antes do AJAX
```

**Solução:**
```php
// Garantir wp_localize_script
wp_localize_script('lms-navegacao', 'lms_ajax', [
    'ajaxurl' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('lms_ajax_nonce')
]);

// Garantir hook registrado
add_action('wp_ajax_minha_acao', [$this, 'handle_ajax']);
add_action('wp_ajax_nopriv_minha_acao', [$this, 'handle_ajax']); // Se precisar sem login
```

### Bug: "Progresso não atualiza"

**Sintomas:** Aula marcada como completa mas percentual fica igual

**Causas Possíveis:**
1. Cache do browser
2. Lógica de cálculo errada
3. User meta não salvando

**Debug:**
```php
// Verificar se salvou
$completas = get_user_meta($user_id, 'aulas_completas', true);
error_log('Aulas completas: ' . print_r($completas, true));

// Verificar cálculo
$total_aulas = count(get_posts(['post_type' => 'aula', 'meta_key' => 'curso', 'meta_value' => $curso_id]));
$progresso = (count($completas[$curso_id]) / $total_aulas) * 100;
error_log("Progresso: $progresso% ($count_completas/$total_aulas)");
```

### Bug: "Layout quebrado após AJAX"

**Sintomas:** Quiz ou elementos ficam desformatados após navegar

**Causas:** CSS/JS não reinicializado após mudança de DOM

**Solução:**
```javascript
// Após atualizar DOM via AJAX, reinicializar
success: function(response) {
    $('#content').html(response.data.html);
    
    // Reinicializar plugins/scripts
    reinicializarQuiz();
    aplicarEstilos();
}
```

### Bug: "Certificado não gera"

**Causas:**
1.  Percentual não atingido
2. Hook não disparado
3. Erro ao salvar user_meta

**Debug:**
```php
// Verificar percentual
$progresso = $this->calcular_progresso($user_id, $curso_id);
$percentual_necessario = get_post_meta($curso_id, 'percentual_conclusao_certificado', true) ?: 100;
error_log("Progresso: $progresso, Necessário: $percentual_necessario");

// Verificar se certificado existe
$certificado = get_user_meta($user_id, "certificado_curso_$curso_id", true);
error_log('Certificado: ' . print_r($certificado, true));
```

## Tipos de Bugs

### Crítico (P0)
- Sistema não funciona (fatal error)
- Perda de dados
- Falha de segurança

**Ação:** Hotfix imediato

### Alto (P1)
- Funcionalidade principal quebrada
- AJAX não funciona
- Certificados não geram

**Ação:** Fix em 24h

### Médio (P2)
- Layout quebrado em mobile
- Mensagem de erro confusa
- Performance lenta

**Ação:** Fix na próxima sprint

### Baixo (P3)
- Typos
- Melhorias de UX
- Otimizações menores

**Ação:** Backlog

## Hotfix Workflow

### Para Bugs Críticos

```
1. Criar branch: fix/nome-bug-critico
2. Implementar fix mínimo
3. Testar localmente
4. Atualizar versão (patch: x.x.X)
5. Commit: [hotfix] Descrição
6. Merge para main
7. Deploy imediato
8. Monitorar logs
9. Documentar em changelog
```

## Template de Bug Report

```markdown
## Bug: [Título Descritivo]

**Severidade:** Crítico | Alto | Médio | Baixo

**Ambiente:**
- WordPress: 6.0
- PHP: 8.0
- Browser: Chrome 120
- Plugin Version: 1.3.10

**Descrição:**
O que está acontecendo de errado.

**Passos para Reproduzir:**
1. Login como aluno
2. Acessar curso X
3. Clicar em aula Y
4. Observar erro Z

**Resultado Atual:**
Erro "Nonce inválido"

**Resultado Esperado:**
Aula deve carregar normalmente

**Causa Raiz:**
Nonce não sendo passado no localize_script

**Solução Implementada:**
Adicionado wp_localize_script em class-assets.php

**Arquivos Modificados:**
- includes/class-assets.php (linha 45)

**Testes Realizados:**
- [x] Navegação AJAX funciona
- [x] Quiz carrega corretamente
- [x] Progresso atualiza

**Versão do Fix:** 1.3.11
```

## Prevenção de Bugs

### Code Review Checklist
- [ ] Nonces verificados
- [ ] Inputs sanitizados
- [ ] Prepared statements em queries
- [ ] Tratamento de erros (try/catch, validações)
- [ ] Testes manuais realizados

### Testes Antes de Commit
```
1. Reproduzir cenário original do bug
2. Verificar fix funciona
3. Testar casos relacionados (regressão)
4. Verificar diferentes roles (admin, aluno)
5. Testar em browser diferente
```

## Ferramentas

- **Query Monitor:** Ver queries e hooks
- **Debug Bar:** Barra  de debug no admin
- **Browser DevTools:** Console, Network, Elements
- **WP Debug Log:** wp-content/debug.log

## Recursos

- **Arquitetura:** `../docs/architecture.md`
- **Segurança:** `../docs/security.md`
- **Testes:** `../docs/testing-strategy.md`

## Handoff

### Para QA
- Fornecer passos para validar fix
- Informar cenários de teste de regressão
- Documentar mudanças de comportamento

### Para Documentação
- Atualizar changelog
- Documentar breaking changes (se houver)
- Atualizar troubleshooting guide
