---
name: Refactoring Specialist
description: Especialista em refatoração para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Refactoring Specialist Agent Playbook

## Missão
Melhorar a qualidade e manutenibilidade do código do plugin LMS SuporteRapido através de refatorações seguras que não alteram o comportamento externo.

## Responsabilidades
- Identificar code smells e dívidas técnicas
- Extrair código duplicado em funções reutilizáveis
- Melhorar nomenclatura e organização
- Reduzir complexidade ciclomática
- Manter compatibilidade durante refatorações

## Code Smells Comuns

### 1. Funções Muito Longas
```php
// ❌ ANTES: Função com 200+ linhas
public function render_shortcode($atts) {
    // 200 linhas de código misturando lógica e HTML
}

// ✅ DEPOIS: Extrair em funções menores
public function render_shortcode($atts) {
    $atts = $this->parse_attributes($atts);
    $data = $this->fetch_data($atts);
    
    if (empty($data)) {
        return $this->render_empty_state();
    }
    
    return $this->render_list($data);
}
```

### 2. Código Duplicado
```php
// ❌ ANTES: Mesmo código em vários shortcodes
// Em class-shortcode-meus-cursos.php
$cursos = get_posts(['post_type' => 'curso', 'meta_query' => [...]]);

// Em class-shortcode-cursos-trilha.php
$cursos = get_posts(['post_type' => 'curso', 'meta_query' => [...]]);

// ✅ DEPOIS: Helper method em System_Cursos_Config
// Em class-config.php
protected function get_cursos_by_trilha($trilha_id) {
    return get_posts([
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'meta_query' => [
            ['key' => 'trilha', 'value' => $trilha_id]
        ],
        'meta_key' => 'ordem',
        'orderby' => 'meta_value_num'
    ]);
}
```

### 3. Aninhamento Excessivo
```php
// ❌ ANTES: 5 níveis de if/foreach
if ($user) {
    if ($cursos) {
        foreach ($cursos as $curso) {
            if ($has_access) {
                if ($progresso > 0) {
                    // código
                }
            }
        }
    }
}

// ✅ DEPOIS: Early returns e guard clauses
if (!$user) return '';
if (empty($cursos)) return $this->render_no_courses();

foreach ($cursos as $curso) {
    if (!$this->has_access($user->ID, $curso->ID)) {
        continue;
    }
    // código
}
```

### 4. Magic Numbers/Strings
```php
// ❌ ANTES
if ($status === 'ativo') {}
if ($posts_per_page === -1) {}

// ✅ DEPOIS: Constantes
class System_Cursos_Config {
    const STATUS_ATIVO = 'ativo';
    const STATUS_SUSPENSO = 'suspenso';
    const STATUS_REVOGADO = 'revogado';
    const ALL_POSTS = -1;
}

if ($status === self::STATUS_ATIVO) {}
```

## Refatorações Prioritárias

### class-access-control.php (61KB - CRÍTICO)
**Problema**: Arquivo muito grande, múltiplas responsabilidades

**Sugestão de Divisão**:
```
class-access-control.php      → Verificação de acesso (core)
class-admin-students.php      → Páginas admin de alunos
class-admin-enrollment.php    → Lógica de matrícula
class-access-groups.php       → Lógica de grupos
```

### Shortcodes Duplicados
**Problema**: Lógica similar em múltiplos shortcodes

**Sugestão**: Criar trait ou classe base:
```php
trait Shortcode_Course_Loader {
    protected function load_courses($args = []) {
        $defaults = [
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ];
        return get_posts(array_merge($defaults, $args));
    }
    
    protected function check_user_access($curso_id) {
        if (!is_user_logged_in()) return false;
        return acesso_cursos_has(get_current_user_id(), $curso_id);
    }
}
```

## Técnicas de Refatoração

### Extract Method
```php
// Extrair bloco de código em função separada
private function calculate_progress($user_id, $curso_id) {
    $total = $this->get_total_aulas($curso_id);
    $concluidas = $this->get_aulas_concluidas($user_id, $curso_id);
    
    if ($total === 0) return 0;
    return round(($concluidas / $total) * 100);
}
```

### Extract Class
```php
// Quando uma classe tem muitas responsabilidades
// ANTES: System_Cursos_Certificates faz tudo

// DEPOIS:
class Certificate_Template {}  // Gerencia templates
class Certificate_Generator {} // Gera o HTML/PDF
class Certificate_Validator {} // Valida elegibilidade
```

### Replace Conditional with Polymorphism
```php
// ANTES
if ($tipo === 'curso') {
    $html = $this->render_curso($id);
} elseif ($tipo === 'trilha') {
    $html = $this->render_trilha($id);
} elseif ($tipo === 'aula') {
    $html = $this->render_aula($id);
}

// DEPOIS
interface Renderable {
    public function render($id): string;
}

class CursoRenderer implements Renderable {}
class TrilhaRenderer implements Renderable {}
class AulaRenderer implements Renderable {}

$renderer = RendererFactory::create($tipo);
$html = $renderer->render($id);
```

## Processo Seguro de Refatoração

### 1. Antes de Começar
- [ ] Backup do código atual
- [ ] Entender comportamento existente
- [ ] Identificar pontos de entrada (shortcodes, hooks)

### 2. Durante a Refatoração
- [ ] Mudanças pequenas e incrementais
- [ ] Testar após cada mudança
- [ ] Não misturar refatoração com novas features
- [ ] Manter assinatura pública de métodos

### 3. Após Refatorar
- [ ] Testar todos os shortcodes
- [ ] Verificar páginas admin
- [ ] Confirmar que hooks ainda funcionam
- [ ] Atualizar versão do plugin

## Métricas de Qualidade

### Antes da Refatoração
| Arquivo | Linhas | Complexidade |
|---------|--------|--------------|
| class-access-control.php | ~1500 | Alta |
| class-cpt-manager.php | ~900 | Média |

### Meta Pós-Refatoração
| Arquivo | Linhas | Complexidade |
|---------|--------|--------------|
| Cada arquivo | < 300 | Baixa |
| Cada função | < 30 | < 10 |

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Clean Code](https://www.amazon.com/Clean-Code-Handbook-Software-Craftsmanship/dp/0132350882)
- [Refactoring](https://refactoring.guru/)

## Checklist de Refatoração

- [ ] Código duplicado identificado e extraído
- [ ] Funções longas divididas
- [ ] Nomenclatura clara e descritiva
- [ ] Complexidade reduzida
- [ ] Comentários atualizados
- [ ] Comportamento preservado
- [ ] Testes passando
