# Glossário - LMS SuporteRapido

## Termos do Domínio

### Trilha (Track)
Agrupamento lógico de cursos relacionados que formam uma jornada de aprendizado completa. Exemplo: "Trilha de Desenvolvimento Web" com cursos de HTML, CSS e JavaScript.

### Curso
Unidade principal de conteúdo educacional composta por múltiplas aulas. Possui título, descrição, capa vertical e está associado a uma trilha.

### Aula
Menor unidade de conteúdo dentro de um curso. Tipicamente contém um vídeo (Vimeo) e descrição textual.

### Grupo de Alunos
Conjunto de usuários que compartilham acesso a determinados cursos ou trilhas. Útil para turmas, empresas ou pacotes de cursos.

### Certificado
Documento PDF gerado automaticamente ao concluir 100% de um curso. Pode ter templates personalizados por curso ou grupo.

### Matrícula
Registro de acesso de um aluno a um curso específico. Armazenado na tabela `wp_acesso_cursos`.

### Progresso
Percentual de aulas concluídas em um curso. Calculado como `(aulas_concluidas / total_aulas) * 100`.

## Termos Técnicos

### CPT (Custom Post Type)
Tipo de conteúdo personalizado do WordPress. O plugin registra: `trilha`, `curso`, `aula`, `grupo`, `certificado`.

### Shortcode
Tag WordPress no formato `[nome]` que renderiza conteúdo dinâmico. Exemplo: `[lista-aulas curso="123"]`.

### Meta Key
Chave de metadado associada a posts ou usuários. Usada para armazenar relacionamentos e configurações.

### Hook
Ponto de extensão do WordPress (`action` ou `filter`) usado para adicionar funcionalidades.

### Metabox
Caixa de campos customizados exibida na tela de edição de posts no admin.

## Status de Acesso

| Status | Descrição |
|--------|-----------|
| `ativo` | Aluno pode acessar o curso normalmente |
| `suspenso` | Acesso temporariamente bloqueado |
| `revogado` | Acesso permanentemente removido |

## Campos Customizados

### Curso
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `trilha` | Post ID | ID da trilha associada |
| `capa_vertical` | Attachment ID | Imagem de capa do curso |
| `ordem` | Integer | Posição na trilha |

### Aula
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `curso` | Post ID | ID do curso pai |
| `embed_do_vimeo` | URL | Link do vídeo Vimeo |
| `descricao` | HTML | Conteúdo da aula |
| `ordem` | Integer | Posição no curso |

### Certificado
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `imagem_fundo` | Attachment ID | Background do certificado |
| `largura` | Integer | Largura em pixels |
| `altura` | Integer | Altura em pixels |

### Grupo
| Campo | Tipo | Descrição |
|-------|------|-----------|
| `cursos_do_grupo` | Array | IDs dos cursos incluídos |
| `trilhas_do_grupo` | Array | IDs das trilhas incluídas |
| `alunos_do_grupo` | Array | IDs dos usuários membros |
| `certificado_grupo` | Post ID | Template de certificado do grupo |

## Abreviações

| Sigla | Significado |
|-------|-------------|
| LMS | Learning Management System |
| CPT | Custom Post Type |
| ACF | Advanced Custom Fields (removido) |
| AJAX | Asynchronous JavaScript and XML |
| oEmbed | Open Embed Standard |
