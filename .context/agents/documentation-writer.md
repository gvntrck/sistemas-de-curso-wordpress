---
name: Documentation Writer
description: Documenta o LMS SuporteRapido para desenvolvedores e usuários
status: filled
generated: 2026-01-18
---

# Documentation Writer Agent Playbook

## Missão
Criar e manter documentação clara e útil para o plugin LMS SuporteRapido, incluindo documentação técnica para desenvolvedores e guias de uso para administradores WordPress.

## Responsabilidades
- Documentar shortcodes e seus parâmetros
- Manter documentação de arquitetura atualizada
- Criar guias de configuração de CPTs
- Documentar APIs internas e funções públicas
- Atualizar changelog com mudanças

## Estrutura de Documentação

```
.context/
├── docs/
│   ├── project-overview.md    # Visão geral
│   ├── architecture.md        # Arquitetura técnica
│   ├── data-flow.md          # Fluxo de dados
│   ├── glossary.md           # Termos do domínio
│   └── development-workflow.md # Workflow dev
│
plugin/
├── documentacao.txt          # Doc rápida (legado)
└── README.md                 # Doc principal (a criar)
```

## Padrões de Documentação

### Documentação de Shortcode

```markdown
## [nome-shortcode]

**Descrição**: O que o shortcode faz.

**Uso**:
```
[nome-shortcode parametro="valor"]
```

**Parâmetros**:
| Parâmetro | Tipo | Padrão | Descrição |
|-----------|------|--------|-----------|
| id | int | 0 | ID do item |
| mostrar | bool | true | Exibe título |

**Exemplo**:
```
[nome-shortcode id="123" mostrar="false"]
```

**Requisitos**:
- Usuário logado: Sim/Não
- Permissão: aluno/admin

**Arquivo**: `includes/shortcodes/class-shortcode-nome.php`
```

### Documentação de Função

```php
/**
 * Verifica se usuário tem acesso a um curso.
 *
 * Consulta a tabela wp_acesso_cursos e grupos para determinar
 * se o usuário possui acesso ativo ao curso especificado.
 *
 * @since 1.0.0
 * @since 1.2.10 Adicionado suporte a grupos.
 *
 * @global wpdb $wpdb WordPress database object.
 *
 * @param int $user_id  ID do usuário WordPress.
 * @param int $curso_id ID do post do curso.
 * @return bool True se tem acesso, false caso contrário.
 *
 * @example
 * if (acesso_cursos_has(get_current_user_id(), 123)) {
 *     echo 'Você tem acesso!';
 * }
 */
function acesso_cursos_has($user_id, $curso_id) {
```

### Documentação de CPT

```markdown
## CPT: Curso

**Slug**: `curso`

**Descrição**: Representa um curso individual na plataforma.

**Campos Customizados**:
| Campo | Meta Key | Tipo | Descrição |
|-------|----------|------|-----------|
| Trilha | `trilha` | int | ID da trilha pai |
| Capa Vertical | `capa_vertical` | int | ID do attachment |
| Ordem | `ordem` | int | Posição na trilha |

**Relacionamentos**:
- Pertence a: `trilha` (1:N)
- Contém: `aula` (1:N)

**Capabilities**: Usa `post` capabilities padrão.

**Admin UI**: Metaboxes em `includes/class-cpt-manager.php`
```

## Shortcodes Existentes para Documentar

| Shortcode | Arquivo | Status Doc |
|-----------|---------|------------|
| `[lista-aulas]` | class-shortcode-listar-aulas.php | ⚪ |
| `[meus-cursos]` | class-shortcode-meus-cursos.php | ⚪ |
| `[certificado]` | class-shortcode-certificado.php | ⚪ |
| `[resultado-busca]` | class-shortcode-resultado-busca.php | ⚪ |
| `[barra-progresso]` | class-shortcode-barra-progresso.php | ⚪ |
| `[cursos_da_trilha]` | class-shortcode-cursos-trilha.php | ⚪ |
| `[single-trilha]` | class-shortcode-single-trilha.php | ⚪ |
| `[redireciona-aula]` | class-shortcode-redireciona-aula.php | ⚪ |
| `[cadastro-usuario]` | class-shortcode-cadastro-usuario.php | ⚪ |
| `[minha-conta]` | class-shortcode-minha-conta.php | ⚪ |

## Changelog Pattern

```markdown
## [1.2.22] - 2026-01-18

### Adicionado
- Certificado por grupo de alunos
- Parâmetro `mostrar_todos` no shortcode [meus-cursos]

### Alterado
- Melhorada exibição de cursos por trilha

### Corrigido
- Erro 404 em páginas de curso após refresh
- Nome do aluno não aparecia no preview do certificado

### Removido
- Dependência do ACF (Advanced Custom Fields)
```

## Documentação Inline

### Arquivos PHP
```php
<?php
/**
 * Gerenciador de Certificados
 *
 * Handles certificate templates and generation.
 *
 * @package    LMS_SuporteRapido
 * @subpackage Certificates
 * @since      1.0.0
 */

// Bloco de código precisa de explicação
// Por que fazemos X em vez de Y
```

### JavaScript
```javascript
/**
 * Marca aula como concluída via AJAX.
 * 
 * Atualiza UI localmente e persiste no banco.
 * 
 * @param {number} aulaId - ID da aula.
 * @param {number} cursoId - ID do curso.
 */
function concluirAula(aulaId, cursoId) {
```

## Guia para Administradores

### Estrutura
1. **Primeiros Passos**
   - Instalação do plugin
   - Configuração inicial
   
2. **Criando Conteúdo**
   - Como criar trilhas
   - Como criar cursos
   - Como adicionar aulas
   
3. **Gerenciando Alunos**
   - Matricular alunos
   - Grupos de alunos
   - Acompanhar progresso
   
4. **Certificados**
   - Configurar templates
   - Personalizar por curso/grupo

## Documentação de Referência
- [Visão Geral](../docs/project-overview.md)
- [Arquitetura](../docs/architecture.md)
- [Glossário](../docs/glossary.md)

## Checklist de Documentação

- [ ] Todos shortcodes documentados
- [ ] Funções públicas com PHPDoc
- [ ] CPTs e campos descritos
- [ ] Changelog atualizado
- [ ] Guia de instalação criado
- [ ] FAQ criado
- [ ] Exemplos de código incluídos
