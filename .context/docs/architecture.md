# Arquitetura do LMS SuporteRapido

## Visão Geral da Arquitetura

O plugin segue uma arquitetura modular baseada em classes PHP, aproveitando os hooks e filtros nativos do WordPress.

```
┌─────────────────────────────────────────────────────────────────┐
│                         WordPress Core                           │
├─────────────────────────────────────────────────────────────────┤
│                    Plugin Principal (Bootstrap)                  │
│                   sistema-cursos-plugin.php                      │
├──────────┬──────────┬──────────┬──────────┬──────────┬─────────┤
│   CPT    │  Access  │  Certs   │ Progress │  User    │ Assets  │
│ Manager  │ Control  │          │          │ Fields   │         │
├──────────┴──────────┴──────────┴──────────┴──────────┴─────────┤
│                         Shortcodes Layer                         │
│  [lista-aulas] [meus-cursos] [certificado] [resultado-busca]   │
├─────────────────────────────────────────────────────────────────┤
│                     Custom Database Table                        │
│                       wp_acesso_cursos                           │
└─────────────────────────────────────────────────────────────────┘
```

## Componentes Principais

### 1. CPT Manager (`class-cpt-manager.php`)
**Responsabilidade**: Registro e gerenciamento dos Custom Post Types

**CPTs Registrados**:
| CPT | Slug | Descrição |
|-----|------|-----------|
| `trilha` | trilha | Formação/Track de cursos |
| `curso` | curso | Curso individual |
| `aula` | aula | Aula de um curso |
| `grupo` | grupo | Grupo de alunos |
| `certificado` | certificado | Template de certificado |

**Metaboxes Implementados**:
- Trilha: descrição curta, imagem de capa, seletor de cursos
- Curso: trilha relacionada, capa vertical, seletor de aulas
- Aula: curso relacionado, embed Vimeo, descrição
- Grupo: cursos e trilhas associados, seletor de alunos
- Certificado: imagem de fundo, largura, altura

### 2. Access Control (`class-access-control.php`)
**Responsabilidade**: Controle de matrículas e permissões

**Funcionalidades**:
- Verificação de acesso a cursos (`acesso_cursos_has()`)
- Identificação de origem do acesso (direto, via grupo, via trilha)
- Painel administrativo de alunos
- Matrícula individual e em lote (por trilha)
- Gerenciamento de grupos

**Tabela Customizada** (`wp_acesso_cursos`):
```sql
CREATE TABLE wp_acesso_cursos (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    curso_id BIGINT NOT NULL,
    status ENUM('ativo','suspenso','revogado') DEFAULT 'ativo',
    data_inicio DATETIME,
    data_fim DATETIME NULL,
    data_criacao DATETIME,
    data_atualizacao DATETIME
);
```

### 3. Certificates (`class-certificates.php`)
**Responsabilidade**: Gerenciamento de templates de certificado

**Fluxo de Geração**:
1. Usuário conclui 100% do curso
2. Shortcode `[certificado]` verifica elegibilidade
3. Template é carregado (por grupo > por curso > padrão)
4. HTML é renderizado com placeholders substituídos
5. html2pdf.js converte para PDF no cliente

### 4. Course Progress (`class-course-progress.php`)
**Responsabilidade**: Rastreamento de progresso do aluno

**Armazenamento**: `usermeta` com chave `aulas_concluidas_{curso_id}`

**Cálculos**:
- Total de aulas por curso
- Aulas concluídas por usuário
- Percentual de progresso

### 5. Shortcodes Layer
**Padrão de Implementação**: Classes individuais com método `render_shortcode()`

| Shortcode | Classe | Função |
|-----------|--------|--------|
| `[lista-aulas]` | `System_Cursos_Shortcode_Listar_Aulas` | Lista aulas de um curso |
| `[meus-cursos]` | `System_Cursos_Shortcode_Meus_Cursos` | Cursos do aluno logado |
| `[certificado]` | `System_Cursos_Shortcode_Certificado` | Gera certificado |
| `[resultado-busca]` | `System_Cursos_Shortcode_Resultado_Busca` | Busca de conteúdo |
| `[barra-progresso]` | `System_Cursos_Shortcode_Barra_Progresso` | Barra de progresso |
| `[cursos_da_trilha]` | `System_Cursos_Shortcode_Cursos_Trilha` | Cursos de uma trilha |
| `[single-trilha]` | `System_Cursos_Shortcode_Single_Trilha` | Página de trilha |
| `[redireciona-aula]` | `System_Cursos_Shortcode_Redireciona_Aula` | Redireciona para aula |

## Padrões de Design Utilizados

### Singleton Pattern
Cada classe principal é instanciada uma única vez no bootstrap do plugin.

### Hook-Based Architecture
Uso extensivo de `add_action()` e `add_filter()` do WordPress para extensibilidade.

### Separation of Concerns
- **Classes de negócio**: Lógica de domínio
- **Shortcodes**: Renderização de UI
- **Assets**: CSS/JS separados

### Meta-Query Pattern
Relacionamentos entre CPTs via `meta_key`:
- `trilha` (curso → trilha)
- `curso` (aula → curso)

## Decisões Arquiteturais

### Por que não usar ACF?
O plugin foi refatorado para remover dependência do ACF, usando campos nativos do WordPress. Isso:
- Reduz dependências externas
- Melhora performance
- Facilita manutenção

### Por que tabela customizada para acesso?
A tabela `wp_acesso_cursos` oferece:
- Queries mais eficientes
- Controle granular de status
- Histórico de matrículas
- Suporte a expiração

### Por que html2pdf.js no cliente?
- Evita dependências de bibliotecas PHP pesadas
- Renderização fiel ao HTML/CSS
- Menor carga no servidor
