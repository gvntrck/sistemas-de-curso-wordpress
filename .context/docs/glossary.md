# Glossário do LMS SuporteRapido

## Termos Gerais

### **LMS (Learning Management System)**
Sistema de gerenciamento de aprendizagem. Plataforma para criação, distribuição e acompanhamento de cursos online.

### **CPT (Custom Post Type)**
Tipo de conteúdo personalizado do WordPress. No plugin, temos: Curso, Aula, Trilha e Grupo.

### **ACF (Advanced Custom Fields)**
Plugin WordPress para criação de campos personalizados (metaboxes) de forma visual.

## Entidades do Sistema

### **Curso**
Unidade principal de conteúdo educacional. Contém múltiplas aulas e pode pertencer a uma trilha.

**Post Type:** `curso`  
**Meta Keys Principais:**
- `trilha` - ID da trilha pai (se houver)
- `ordem` - Ordem de exibição dentro da trilha
- `percentual_conclusao_certificado` - % de conclusão necessário para emitir certificado

### **Aula**
Unidade individual de conteúdo dentro de um curso. Geralmente contém um vídeo e pode ter um quiz.

**Post Type:** `aula`  
**Meta Keys Principais:**
- `curso` - ID do curso pai (obrigatório)
- `link_video` - URL do vídeo (YouTube, Vimeo, etc)
- `ordem` - Ordem da aula dentro do curso
- `quiz_enabled` - Se a aula possui quiz
- `quiz_data` - Dados do quiz (JSON)

### **Trilha**
Agrupamento de cursos relacionados que formam uma jornada de aprendizado completa.

**Post Type:** `trilha`  
**Relacionamento:** 1 Trilha → N Cursos

### **Grupo**
Coleção de alunos que compartilham permissões de acesso aos mesmos cursos/trilhas.

**Post Type:** `grupo_aluno`  
**Meta Keys:**
- `membros` - IDs dos usuários do grupo

### **Aluno**
Usuário com role `aluno`, que tem acesso restrito apenas ao conteúdo dos cursos.

**Role:** `aluno`  
**Capabilities:** Limitadas (sem acesso ao admin)

## Conceitos de Acesso

### **Acesso Manual**
Permissão de acesso concedida diretamente pelo administrador no painel de "Detalhes do Aluno".

**Origem:** `manual`

### **Acesso via WooCommerce**
Matrícula automática após compra de produto vinculado a curso/trilha.

**Origem:** `woocommerce`

### **Acesso via Grupo**
Acesso herdado por pertencer a um grupo que tem permissão para determinados cursos/trilhas.

**Origem:** `grupo`

### **Validade**
Prazo de acesso a um curso ou trilha. Pode ser:
- **Vitalício:** `'vitalicio'` - Sem data de expiração
- **Data específica:** `'YYYY-MM-DD'` - Acesso expira nesta data

## Progresso e Conclusão

### **Aula Completa**
Aula que foi marcada como concluída pelo aluno. Registrada em `user_meta['aulas_completas']`.

### **Progresso do Curso**
Percentual de aulas concluídas em relação ao total de aulas do curso.

**Cálculo:**  
```
Progresso = (Aulas Completas / Total de Aulas) × 100
```

### **Percentual para Certificado**
Percentual mínimo de conclusão do curso necessário para que o aluno receba o certificado.

**Campo:** `percentual_conclusao_certificado` (padrão: 100%)

## Certificados

### **Certificado**
Documento digital gerado automaticamente quando o aluno atinge o percentual necessário de conclusão.

**Geração:** Classe `System_Cursos_Certificates`  
**Template:** Pode ser personalizado por curso  
**Shortcode:** `[certificado]`

### **Modelo de Certificado**
Template HTML/CSS usado para renderizar o certificado. Pode ser diferente para cada curso.

## Quiz

### **Quiz**
Questionário com perguntas de múltipla escolha dentro de uma aula.

**Armazenamento:** Campo `quiz_data` (JSON) na aula  
**Tentativas:** Configurável (ex: máximo 3 tentativas)

### **Tentativa de Quiz**
Registro de uma submissão do quiz pelo aluno.

**Tabela:** `wp_quiz_attempts`  
**Campos:**
- `user_id`, `aula_id`
- `score` - Pontuação obtida
- `max_score` - Pontuação máxima possível
- `passed` - Se passou (boolean)
- `attempt_number` - Número da tentativa

### **Resposta de Quiz**
Opção escolhida pelo aluno para cada pergunta.

**Formato JSON:**
```json
{
  "question_1": "opcao_a",
  "question_2": "opcao_c"
}
```

## Shortcodes

### **Shortcode**
Código especial do WordPress que pode ser inserido em páginas/posts e é substituído por conteúdo dinâmico.

**Formato:** `[nome-shortcode parametro="valor"]`

**Exemplos:**
- `[meus-cursos]` - Lista cursos do usuário
- `[lista-aulas curso_id="10"]` - Player + sidebar de aulas

## Logs e Auditoria

### **Log de Acesso**
Registro de todas as alterações de permissões de acesso (concessão, remoção, entrada em grupo).

**Tabela:** `wp_acesso_cursos_log`  
**Ações Registradas:**
- `acesso_concedido`
- `acesso_removido`
- `acesso_reativado`
- `entrou_grupo`
- `saiu_grupo`

### **Anti-Pirataria**
Sistema de detecção de múltiplos acessos simultâneos da mesma conta.

**Implementação:** Rastreamento de IP/User-Agent em logs de acesso

## Termos Técnicos

### **Hook**
Ponto de extensão no WordPress que permite executar código customizado.

**Tipos:**
- **Action:** `do_action()` - Executa código em um ponto específico
- **Filter:** `apply_filters()` - Modifica um valor antes de usá-lo

**Exemplos:**
```php
do_action('sistema_cursos_aula_completa', $user_id, $curso_id);
apply_filters('sistema_cursos_percentual_certificado', 100, $curso_id);
```

### **User Meta**
Dados adicionais associados a um usuário no WordPress, armazenados na tabela `wp_usermeta`.

**Funções:**
- `get_user_meta($user_id, $meta_key, true)` - Ler
- `update_user_meta($user_id, $meta_key, $value)` - Atualizar

### **Post Meta**
Dados adicionais associados a um post/página no WordPress, armazenados na tabela `wp_postmeta`.

**Funções:**
- `get_post_meta($post_id, $meta_key, true)` - Ler
- `update_post_meta($post_id, $meta_key, $value)` - Atualizar

### **AJAX**
Técnica de comunicação assíncrona entre frontend e backend sem recarregar a página.

**No Plugin:** Usado para:
- Navegação entre aulas sem reload
- Marcação de aulas como completas
- Submissão de quizzes

### **Nonce**
"Number Used Once" - Token de segurança do WordPress para prevenir CSRF (Cross-Site Request Forgery).

**Criação:**
```php
$nonce = wp_create_nonce('minha_acao');
```

**Verificação:**
```php
if (!wp_verify_nonce($_POST['nonce'], 'minha_acao')) {
    wp_die('Nonce inválido');
}
```

### **Sanitização**
Limpeza de dados de entrada para remover código malicioso.

**Funções:**
- `sanitize_text_field()` - Texto simples
- `sanitize_email()` - Email
- `sanitize_url()` - URL

### **Escape**
Preparação de dados para exibição segura em HTML.

**Funções:**
- `esc_html()` - Texto HTML
- `esc_url()` - URLs
- `esc_attr()` - Atributos HTML

## Integrações

### **WooCommerce**
Plugin de e-commerce para WordPress. O LMS se integra para vender cursos.

**Classe:** `System_Cursos_WooCommerce`  
**Hook Principal:** `woocommerce_order_status_completed` - Dispara matrícula automática

### **Advanced Custom Fields (ACF)**
Plugin para campos personalizados visuais.

**Uso no LMS:** Criação de metaboxes para Curso, Aula, Trilha

## Estrutura de Dados

### **Relacionamento Hierárquico**
```
Trilha
  └─ Curso
      └─ Aula
          └─ Quiz (opcional)
```

### **Acesso do Aluno**
```json
{
  "acesso_cursos": {
    "123": {
      "validade": "2026-12-31",
      "origem": "woocommerce"
    }
  },
  "acesso_trilhas": {
    "45": {
      "validade": "vitalicio",
      "origem": "manual"
    }
  },
  "aulas_completas": {
    "123": [101, 102, 103]  // IDs das aulas concluídas
  }
}
```

## Siglas e Abreviações

- **CPT:** Custom Post Type
- **ACF:** Advanced Custom Fields
- **AJAX:** Asynchronous JavaScript and XML
- **JSON:** JavaScript Object Notation
- **CSV:** Comma-Separated Values (usado para importação de alunos)
- **LMS:** Learning Management System
- **CRM:** Customer Relationship Management (notas de aluno)
- **IP:** Internet Protocol (rastreamento anti-pirataria)
- **CSRF:** Cross-Site Request Forgery
