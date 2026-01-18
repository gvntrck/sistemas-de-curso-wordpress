# Fluxo de Dados - LMS SuporteRapido

## Modelo de Dados

### Entidades Principais

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   TRILHA    │◄────│    CURSO    │◄────│    AULA     │
│             │ 1:N │             │ 1:N │             │
│ - ID        │     │ - ID        │     │ - ID        │
│ - Título    │     │ - Título    │     │ - Título    │
│ - Descrição │     │ - trilha_id │     │ - curso_id  │
│ - Capa      │     │ - Capa Vert │     │ - Vimeo     │
└─────────────┘     │ - Ordem     │     │ - Descrição │
                    └─────────────┘     │ - Ordem     │
                           ▲            └─────────────┘
                           │
                    ┌──────┴──────┐
                    │             │
              ┌─────┴─────┐ ┌─────┴─────┐
              │   GRUPO   │ │  ACESSO   │
              │           │ │  CURSOS   │
              │ - cursos  │ │ - user_id │
              │ - trilhas │ │ - curso_id│
              │ - alunos  │ │ - status  │
              └───────────┘ │ - datas   │
                            └───────────┘
```

### Relacionamentos via Post Meta

| Origem | Meta Key | Destino | Tipo |
|--------|----------|---------|------|
| Curso | `trilha` | Trilha | N:1 |
| Aula | `curso` | Curso | N:1 |
| Grupo | `cursos_do_grupo` | Cursos | N:N |
| Grupo | `trilhas_do_grupo` | Trilhas | N:N |
| Grupo | `alunos_do_grupo` | Users | N:N |
| User | `aulas_concluidas_{curso_id}` | Aulas | N:N |

## Fluxos de Dados

### 1. Verificação de Acesso a Curso

```
┌─────────────┐     ┌─────────────────────┐     ┌──────────────────┐
│   Request   │────►│ acesso_cursos_has() │────►│ wp_acesso_cursos │
│ (user,curso)│     │                     │     │     (direto)     │
└─────────────┘     └──────────┬──────────┘     └──────────────────┘
                               │ se não encontrou
                               ▼
                    ┌──────────────────────┐
                    │  Verifica Grupos     │
                    │  do usuário          │
                    └──────────┬───────────┘
                               │
                    ┌──────────┼──────────┐
                    ▼          ▼          ▼
              ┌─────────┐ ┌─────────┐ ┌─────────┐
              │ Curso   │ │ Trilha  │ │ Sem     │
              │ no Grupo│ │ no Grupo│ │ Acesso  │
              └─────────┘ └─────────┘ └─────────┘
```

### 2. Cálculo de Progresso

```
┌────────────────┐     ┌─────────────────────┐     ┌─────────────────┐
│ Usuário acessa │────►│ get_course_progress │────►│ Query: Aulas do │
│ página curso   │     │ (user_id, curso_id) │     │ curso           │
└────────────────┘     └──────────┬──────────┘     └────────┬────────┘
                                  │                         │
                                  ▼                         ▼
                       ┌─────────────────────┐     ┌─────────────────┐
                       │ get_user_meta       │     │ total_aulas     │
                       │ aulas_concluidas_X  │     │                 │
                       └──────────┬──────────┘     └────────┬────────┘
                                  │                         │
                                  └────────────┬────────────┘
                                               ▼
                                    ┌────────────────────┐
                                    │ progresso = 100 *  │
                                    │ concluidas / total │
                                    └────────────────────┘
```

### 3. Geração de Certificado

```
┌─────────────────┐     ┌─────────────────────┐
│ [certificado]   │────►│ Verifica progresso  │
│ shortcode       │     │ = 100%              │
└─────────────────┘     └──────────┬──────────┘
                                   │ OK
                                   ▼
                        ┌─────────────────────┐
                        │ Busca template:     │
                        │ 1. Certificado Grupo│
                        │ 2. Certificado Curso│
                        │ 3. Template Padrão  │
                        └──────────┬──────────┘
                                   │
                                   ▼
                        ┌─────────────────────┐
                        │ Substitui placeholders│
                        │ {nome}, {curso},     │
                        │ {data}, {horas}      │
                        └──────────┬──────────┘
                                   │
                                   ▼
                        ┌─────────────────────┐
                        │ html2pdf.js         │
                        │ (client-side PDF)   │
                        └─────────────────────┘
```

### 4. Matrícula em Trilha

```
┌─────────────────┐     ┌─────────────────────┐
│ Admin seleciona │────►│ Query: Cursos da    │
│ trilha + aluno  │     │ trilha (meta_query) │
└─────────────────┘     └──────────┬──────────┘
                                   │
                                   ▼
                        ┌─────────────────────┐
                        │ Para cada curso:    │
                        │ INSERT/UPDATE       │
                        │ wp_acesso_cursos    │
                        └─────────────────────┘
```

## API Endpoints (Internal)

### AJAX Handlers

| Action | Método | Parâmetros | Retorno |
|--------|--------|------------|---------|
| `concluir_aula` | POST | aula_id, curso_id | success/error |
| `buscar_cursos` | GET | query | array cursos |

### Meta Queries Comuns

```php
// Cursos de uma Trilha
$args = [
    'post_type' => 'curso',
    'meta_query' => [
        ['key' => 'trilha', 'value' => $trilha_id]
    ],
    'meta_key' => 'ordem',
    'orderby' => 'meta_value_num'
];

// Aulas de um Curso
$args = [
    'post_type' => 'aula',
    'meta_query' => [
        ['key' => 'curso', 'value' => $curso_id]
    ],
    'meta_key' => 'ordem',
    'orderby' => 'meta_value_num'
];

// Verificar Acesso
$access = $wpdb->get_row(
    "SELECT * FROM {$table} 
     WHERE user_id = %d AND curso_id = %d AND status = 'ativo'"
);
```

## Transformações de Dados

### Progresso → Elegibilidade Certificado
```
progresso >= 100% → elegível = true
progresso < 100%  → elegível = false
```

### Status de Acesso → Badge Visual
```
'ativo'    → 🟢 "Ativo" (verde)
'suspenso' → 🟡 "Suspenso" (amarelo)
'revogado' → 🔴 "Revogado" (vermelho)
null       → ⚪ "Sem acesso" (cinza)
```

### Origem do Acesso → Label
```
direto        → "Ativo"
via_grupo     → "Ativo (Via Grupo: Nome)"
via_trilha    → "Ativo (Via Trilha: Nome)"
```
