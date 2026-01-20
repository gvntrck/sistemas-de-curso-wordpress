---
status: done
generated: 2026-01-20
complexity: QUICK
estimated_time: "5-10 minutos"
agents:
  - type: "feature-developer"
    role: "Implementar a alteração no código PHP"
phases:
  - id: "execute"
    name: "Execução"
    prevc: "E"
  - id: "verify"
    name: "Verificação"
    prevc: "V"
---

# Link para Edição do Curso na Tela Detalhes do Aluno

## Objetivo

Adicionar um link clicável na coluna **Curso** da tabela **Cursos e Permissões** na tela de Detalhes do Aluno. O link deve direcionar para a página de edição do CPT (Custom Post Type) `curso` no admin do WordPress.

## Contexto

Atualmente, na tela `Detalhes do Aluno` (acessada via `admin.php?page=acesso-cursos-alunos&action=view&user_id=X`), a tabela "Cursos e Permissões" exibe o nome do curso como texto simples. Para facilitar a navegação do administrador, o nome do curso deve se tornar um link que leva diretamente à página de edição daquele curso.

## Arquivo Alvo

- **Arquivo**: `includes/class-access-control.php`
- **Método**: `render_details_page()`
- **Linhas**: ~1350-1352

## Código Atual

```php
<td><strong>
    <?php echo esc_html($curso->post_title); ?>
</strong></td>
```

## Código Proposto

```php
<td><strong>
    <a href="<?php echo esc_url(admin_url('post.php?post=' . $curso->ID . '&action=edit')); ?>" 
       title="Editar curso: <?php echo esc_attr($curso->post_title); ?>"
       style="text-decoration: none; color: #2271b1;">
        <?php echo esc_html($curso->post_title); ?>
    </a>
</strong></td>
```

## Detalhes da Implementação

### Fase E: Execução

| Passo | Descrição | Status |
|-------|-----------|--------|
| 1 | Localizar o método `render_details_page()` no arquivo `class-access-control.php` | ⬜ |
| 2 | Encontrar a célula `<td>` que exibe o nome do curso (linha ~1350) | ⬜ |
| 3 | Envolver o título do curso com um link `<a>` para a página de edição | ⬜ |
| 4 | Adicionar atributos de acessibilidade (title) e estilização | ⬜ |
| 5 | Atualizar a versão do plugin no header | ⬜ |

### Fase V: Verificação

| Passo | Descrição | Status |
|-------|-----------|--------|
| 1 | Acessar a tela de Detalhes de um Aluno no admin | ⬜ |
| 2 | Verificar que os nomes dos cursos na tabela são links clicáveis | ⬜ |
| 3 | Clicar em um link e confirmar que redireciona para a página de edição do curso | ⬜ |
| 4 | Verificar que o link abre corretamente para diferentes cursos | ⬜ |

## Considerações Técnicas

### Segurança
- ✅ Usar `esc_url()` para sanitizar a URL
- ✅ Usar `esc_attr()` para o atributo title
- ✅ Usar `esc_html()` para o texto do link

### UX/UI
- O link mantém a estética atual (negrito)
- Cor padrão do WordPress admin (`#2271b1`)
- Atributo `title` fornece contexto adicional ao passar o mouse
- `text-decoration: none` remove o sublinhado para visual mais limpo

### WordPress Standards
- Uso da função `admin_url()` para construir URLs de admin
- Compatibilidade com a estrutura padrão de edição de posts (`post.php?post=ID&action=edit`)

## Resultado Esperado

O administrador poderá:
1. Visualizar os nomes dos cursos como links na tabela "Cursos e Permissões"
2. Clicar no nome de qualquer curso para ir diretamente à página de edição daquele curso
3. Retornar facilmente usando o botão "Voltar" do navegador

## Rollback

Caso necessário, reverter para o código original que exibe apenas texto sem link:

```php
<td><strong>
    <?php echo esc_html($curso->post_title); ?>
</strong></td>
```
