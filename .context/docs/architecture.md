# Arquitetura do Sistema

## Visão Geral da Arquitetura

O **LMS SuporteRapido** segue uma arquitetura modular baseada em classes, aproveitando os hooks e filtros do WordPress para máxima extensibilidade e manutenibilidade.

## Padrões Arquiteturais

### 1. **MVC Adaptado para WordPress**
- **Models:** Representados pelos Custom Post Types e metadados
- **Views:** Templates gerados por shortcodes e classes de renderização
- **Controllers:** Classes que gerenciam lógica de negócio (`class-*`.php`)

### 2. **Single Responsibility Principle (SOLID)**
Cada classe tem uma responsabilidade única e bem definida:
- `class-cpt-manager.php` → Apenas registro de CPTs
- `class-access-control.php` → Apenas controle de acesso
- `class-course-progress.php` → Apenas rastreamento de progresso

### 3. **Injeção de Dependências via WordPress Hooks**
As classes se comunicam através do sistema de hooks do WordPress (`add_action`, `add_filter`), mantendo baixo acoplamento.

## Camadas do Sistema

### Camada de Dados (Data Layer)

#### Custom Post Types
```
┌─────────────┐
│   Trilha    │ (1) ───── (N) ┌─────────────┐
└─────────────┘               │    Curso    │ (1) ───── (N) ┌─────────────┐
                              └─────────────┘               │     Aula    │
                                                            └─────────────┘
┌─────────────┐
│Grupo        │ (M) ───── (N) ┌─────────────┐
└─────────────┘               │   Usuário   │
                              └─────────────┘
```

#### Estrutura de Meta Keys

**Curso (`post_type: curso`)**
- `trilha` (Post Object) - ID da trilha pai
- `ordem` (Number) - Ordem dentro da trilha
- `percentual_conclusao_certificado` (Number) - % necessário para certificado
- `modelo_certificado` (Select/Text) - Template do certificado

**Aula (`post_type: aula`)**
- `curso` (Post Object) - ID do curso pai
- `link_video` (URL) - Link do vídeo (YouTube, Vimeo, etc)
- `ordem` (Number) - Ordem dentro do curso
- `quiz_enabled` (True/False) - Se possui quiz
- `quiz_data` (JSON) - Dados do quiz (perguntas, respostas, configurações)

**Trilha (`post_type: trilha`)**
- `ordem` (Number) - Ordem geral de exibição
- `descricao` (Textarea) - Descrição da trilha

**Grupo (`post_type: grupo_aluno`)**
- `membros` (Relationship) - IDs dos usuários membros

#### User Meta

```php
[
    'acesso_cursos' => [
        curso_id => [
            'validade' => 'YYYY-MM-DD' | 'vitalicio',
            'origem' => 'manual' | 'woocommerce' | 'grupo'
        ]
    ],
    'acesso_trilhas' => [
        trilha_id => [
            'validade' => 'YYYY-MM-DD' | 'vitalicio',
            'origem' => 'manual' | 'woocommerce' | 'grupo'
        ]
    ],
    'aulas_completas' => [curso_id => [au la_id_1, aula_id_2, ...]],
    'grupo_aluno' => grupo_id,
    
    // Campos extras de usuário
    'campo_cpf' => string,
    'campo_data_nascimento' => 'YYYY-MM-DD',
    'campo_endereco' => string,
    'campo_numero' => string,
    'campo_bairro' => string,
    'campo_cidade' => string,
    ' campo_estado' => string,
    'campo_cep' => string
]
```

#### Tabelas Customizadas

**wp_acesso_cursos_log**
```sql
CREATE TABLE wp_acesso_cursos_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    curso_id INT DEFAULT NULL,
    trilha_id INT DEFAULT NULL,
    grupo_id INT DEFAULT NULL,
    acao VARCHAR(50) NOT NULL, -- 'acesso_concedido', 'acesso_removido', 'entrou_grupo', etc
    origem VARCHAR(50), -- 'manual', 'woocommerce', 'grupo'
    validade VARCHAR(20),
    data_acao DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacao TEXT,
    INDEX (user_id),
    INDEX (curso_id),
    INDEX (trilha_id)
);
```

**wp_quiz_attempts**
```sql
CREATE TABLE wp_quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    aula_id INT NOT NULL,
    quiz_id VARCHAR(100),
    score INT,
    max_score INT,
    passed BOOLEAN,
    attempt_number INT,
    answers JSON,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX (user_id, aula_id)
);
```

### Camada de Lógica de Negócio (Business Logic Layer)

#### Classes Core

```
includes/
├── class-cpt-manager.php          → Registro de CPTs e taxonomias
├── class-access-control.php       → Controle de acesso a cursos/trilhas
├── class-course-progress.php      → Cálculo e armaz enamento de progresso
├── class-certificates.php         → Geração e gerenciamento de certificados
├── class-quiz-builder.php         → Interface admin para criação de quizzes
├── class-quiz-process.php         → Processamento e validação de respostas
├── class-woocommerce-integration.php → Integração com WooCommerce
├── class-user-fields.php          → Campos adicionais de usuário
├── class-admin-filters.php        → Filtros no painel admin
├── class-assets.php               → Enqueue de CSS/JS
├── class-config.php               → Configurações globais
└── role-aluno.php                 → Definição da role de aluno
```

#### Fluxo de Inicialização

```php
1. sistema-cursos-plugin.php (arquivo principal)
   ↓
2. require_once de todas as classes
   ↓
3. new ClassName() para instanciar cada classe
   ↓
4. Cada classe registra seus hooks no __construct()
   ↓
5. WordPress dispara os hooks conforme necessário
```

### Camada de Apresentação (Presentation Layer)

#### Shortcodes

```
shortcodes/
├── class-shortcode-meus-cursos.php        → [meus-cursos]
├── class-shortcode-lista-aulas.php        → [lista-aulas]
├── class-shortcode-minha-conta.php        → [minha-conta]
├── class-shortcode-cadastro-usuario.php   → [cadastro-usuario]
├── class-shortcode-certificado.php        → [certificado]
├── class-shortcode-barra-progresso.php    → [barra-progresso-geral]
├── class-shortcode-single-trilha.php      → [single-trilha]
├── class-shortcode-cursos-trilha.php      → [cursos_da_trilha]
├── class-shortcode-resultado-busca.php    → [resultado-busca]
└── class-shortcode-redireciona-aula.php   → [redireciona-aula]
```

Cada classe de shortcode:
1. Registra o shortcode no `__construct()`
2. Implementa método de renderização
3. Retorna HTML formatado

#### Assets (Frontend)

```
assets/
├── css/
│   └── style.css                → Estilos globais do plugin
└── js/
    ├── navegacao-aulas.js       → AJAX para navegação entre aulas
    ├── quiz-handler.js          → Lógica do quiz no frontend
    └── progress-tracker.js      → Atualização de progresso
```

## Padrões de Comunicação

### 1. **WordPress Hooks**

```php
// Exemplo: Quando uma aula é marcada como completa
do_action('sistema_cursos_aula_completa', $user_id, $curso_id, $aula_id);

// Outras classes podem ouvir este hook
add_action('sistema_cursos_aula_completa', function($user_id, $curso_id, $aula_id) {
    // Verificar se curso está completo
    // Emitir certificado se aplicável
});
```

### 2. **AJAX Handlers**

```php
// Frontend envia:
$.ajax({
    url: ajaxurl,
    data: {
        action: 'marcar_aula_completa',
        aula_id: 123,
        curso_id: 45,
        nonce: '...'
    }
});

// Backend processa:
add_action('wp_ajax_marcar_aula_completa', 'handle_marcar_aula_completa');
```

### 3. **Filters para Extensibilidade**

```php
// Permitir modificação externa do percentual necessário
$percentual = apply_filters(
    'sistema_cursos_percentual_certificado',
    $percentual_padrao,
    $curso_id
);
```

## Decisões Arquiteturais Importantes

### 1. **Por que usar User Meta ao invés de tabela separada para acessos?**
- **Prós:** Integração nativa com WP, fácil de consultar
- **Contras:** Performance pode degradar com muitos usuários
- **Decisão:** User Meta para dados estruturados + Tabela de Log para auditoria

###  2. **Por que AJAX para navegação de aulas?**
- Melhora UX sem recarregar a página
- Mantém player de vídeo ativo
- Reduz consumo de dados

### 3. **Por que classes estáticas vs instâncias?**
- Usamos **instâncias** (`new ClassName()`) para permitir múltiplas configurações
- Facilita testes e extensões

### 4. **Integração com ACF**
- ACF é assumido como presente para metaboxes complexos
- Fallback para `update_post_meta()` nativo quando necessário

## Segurança

### Nonces
Todas as operações AJAX e formulários usam nonces WordPress:
```php
wp_verify_nonce($_POST['nonce'], 'action_name');
```

### Capabilities
```php
if (!current_user_can('manage_options')) {
    wp_die('Acesso negado');
}
```

### Sanitização
```php
$user_input = sanitize_text_field($_POST['campo']);
$email = sanitize_email($_POST['email']);
```

### Escape de Output
```php
echo esc_html($titulo);
echo esc_url($link);
echo esc_attr($classe);
```

## Escalabilidade

### Limitações Atuais
- User meta pode ficar pesado com 10.000+ usuários
- Consultas de progresso não otimizadas para centenas de cursos

### Sugestões para Escala
1. Implementar cache (WP Transients ou Redis)
2. Migrar acessos para tabela customizada
3. Implementar paginação em listagens
4. Usar WP_Query com meta_query otimizado

## Diagramas

### Fluxo de Acesso a um Curso

```
Usuário → Página do Curso
    ↓
class-access-control::has_access()
    ↓
Verificar user_meta['acesso_cursos'][curso_id]
    ↓
    ├─ Tem acesso? → Renderizar [lista-aulas]
    └─ Não tem? → Exibir mensagem "Sem acesso"
```

### Fluxo de Conclusão de Aula

```
Usuário clica "Marcar como Completa"
    ↓
AJAX → wp_ajax_marcar_aula_completa
    ↓
class-course-progress::mark_lesson_complete()
    ↓
Atualizar user_meta['aulas_completas']
    ↓
Recalcular progresso do curso
    ↓
do_action('sistema_cursos_aula_completa')
    ↓
class-certificates::check_and_issue_certificate()
```

## Recursos Relacionados

- **Visão Geral:** `project-overview.md`
- **Fluxo de Dados:** `data-flow.md`
- **Mapa do Código:** `codebase-map.json`
