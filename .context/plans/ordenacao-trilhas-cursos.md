# Sistema de Ordenação de Trilhas e Cursos no Shortcode Meus Cursos

## Visão Geral

**Objetivo:** Implementar um sistema elegante e intuitivo de ordenação manual para trilhas e cursos no shortcode `[meus-cursos]`, permitindo que o administrador da plataforma tenha controle total sobre a ordem de exibição.

**Problema Atual:**
- As trilhas são exibidas na ordem em que foram encontradas (sem ordenação definida)
- Os cursos dentro de cada carrossel seguem a ordem de cadastro ou alfabética
- O dono da plataforma não tem controle sobre a apresentação dos conteúdos

**Solução Proposta:**
- Utilizar o campo nativo `menu_order` do WordPress para ordenação
- Interface drag-and-drop no admin para reordenar trilhas e cursos
- Compatibilidade total com o sistema existente (sem breaking changes)

---

## Arquitetura da Solução

### Stack Tecnológico
- **Backend:** PHP 7.4+, WordPress 6.x
- **Frontend Admin:** JavaScript Vanilla + jQuery UI Sortable (já disponível no WP)
- **Armazenamento:** 
  - `menu_order` (nativo) para ordenação de Trilhas
  - Meta field `ordem_curso_trilha_{trilha_id}` para ordenação de cursos por trilha

### Diagrama de Fluxo

```
┌─────────────────────────────────────────────────────────────────┐
│                    ADMIN WORDPRESS                               │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────────┐    ┌──────────────────────────────────┐  │
│  │  Página Trilha   │    │     Página Settings Plugin       │  │
│  │  ─────────────   │    │     ────────────────────────     │  │
│  │  [Campo Ordem]   │    │     [Ordenar Trilhas]            │  │
│  │                  │    │     [Ordenar Cursos por Trilha]  │  │
│  └────────┬─────────┘    └───────────────┬──────────────────┘  │
│           │                              │                      │
│           ▼                              ▼                      │
│  ┌────────────────────────────────────────────────────────────┐│
│  │                    AJAX Handler                            ││
│  │                    (save_order)                            ││
│  └────────────────────────────────────────────────────────────┘│
└────────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │   Database WP   │
                    │   ───────────   │
                    │   menu_order    │
                    │   post_meta     │
                    └─────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    FRONT-END                                     │
├─────────────────────────────────────────────────────────────────┤
│  ┌──────────────────────────────────────────────────────────┐  │
│  │              Shortcode [meus-cursos]                      │  │
│  │              ─────────────────────────                    │  │
│  │  1. Busca trilhas ordenadas por menu_order                │  │
│  │  2. Para cada trilha, ordena cursos pelo meta_order       │  │
│  │  3. Renderiza carrosséis na ordem definida                │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Fases de Implementação

### Fase 1: Backend - Suporte à Ordenação (P)

**Objetivo:** Habilitar os campos de ordenação nos CPTs e criar a infraestrutura de armazenamento.

#### Tarefas:

1. **Adicionar suporte a `menu_order` no CPT Trilha**
   - Arquivo: `includes/class-cpt-manager.php`
   - Adicionar `'page-attributes'` ao `supports` do CPT `trilha`
   - O campo `menu_order` será automaticamente disponível

2. **Criar meta field para ordem dos cursos na trilha**
   - Novo meta: `curso_ordem` (integer) no CPT Curso
   - Valor padrão: 0 (herda ordem natural)

3. **Modificar o shortcode para respeitar ordenação**
   - Arquivo: `includes/shortcodes/class-shortcode-meus-cursos.php`
   - Ordenar trilhas por `menu_order ASC`
   - Ordenar cursos dentro da trilha por `curso_ordem ASC, title ASC`

#### Arquivos a Modificar:
- `includes/class-cpt-manager.php`
- `includes/shortcodes/class-shortcode-meus-cursos.php`

---

### Fase 2: Interface Admin - Ordenação de Trilhas (R/E)

**Objetivo:** Criar interface visual para ordenar trilhas via drag-and-drop.

#### Tarefas:

1. **Criar nova aba "Ordenação" na página de configurações do plugin**
   - Arquivo: `sistema-cursos-plugin.php` (seção de admin)
   - Tab: "Ordenação de Conteúdos"

2. **Implementar lista sortable de trilhas**
   - Lista todas as trilhas publicadas
   - Interface drag-and-drop usando jQuery UI Sortable
   - Botão "Salvar Ordem" com feedback visual

3. **Handler AJAX para salvar ordem das trilhas**
   - Action: `wp_ajax_salvar_ordem_trilhas`
   - Atualiza `menu_order` de cada trilha

#### Código da Interface (Referência):

```html
<div class="sc-order-section">
    <h3>Ordenar Trilhas</h3>
    <p class="description">Arraste para reordenar. A ordem definida aqui será usada no shortcode [meus-cursos].</p>
    
    <ul id="sortable-trilhas" class="sortable-list">
        <!-- Trilhas serão carregadas dinamicamente -->
    </ul>
    
    <button class="button button-primary" id="salvar-ordem-trilhas">
        💾 Salvar Ordem das Trilhas
    </button>
</div>
```

#### Arquivos a Modificar/Criar:
- `sistema-cursos-plugin.php` (nova aba)
- `assets/js/admin-order.js` (novo arquivo)
- `assets/css/admin-order.css` (estilos inline ou novo arquivo)

---

### Fase 3: Interface Admin - Ordenação de Cursos por Trilha (R/E)

**Objetivo:** Permitir ordenar cursos dentro de cada trilha específica.

#### Tarefas:

1. **Dropdown para selecionar trilha**
   - Ao selecionar uma trilha, carrega os cursos associados

2. **Lista sortable de cursos da trilha selecionada**
   - Mostra thumbnail do curso + título
   - Interface drag-and-drop

3. **Handler AJAX para salvar ordem dos cursos**
   - Action: `wp_ajax_salvar_ordem_cursos_trilha`
   - Atualiza meta `curso_ordem` de cada curso

#### Fluxo de UX:

```
[Selecione uma Trilha ▼]
        │
        ▼
┌───────────────────────────┐
│ 1. 📕 Curso de PHP        │  ⋮⋮
│ 2. 📗 Curso de MySQL      │  ⋮⋮
│ 3. 📘 Curso de APIs       │  ⋮⋮
└───────────────────────────┘
        │
        ▼
[💾 Salvar Ordem dos Cursos]
```

#### Arquivos a Modificar/Criar:
- `sistema-cursos-plugin.php` (mesma aba)
- `assets/js/admin-order.js` (expandir)

---

### Fase 4: Refinamentos e Testes (V)

**Objetivo:** Garantir funcionamento correto e experiência premium.

#### Tarefas:

1. **Validações e Sanitização**
   - Validar nonces em todas as requisições AJAX
   - Sanitizar IDs e valores de ordem
   - Verificar permissões (capability: `manage_options`)

2. **Feedback Visual**
   - Animações suaves ao arrastar
   - Indicador de item sendo arrastado
   - Toast/Notice ao salvar com sucesso

3. **Fallback para Cursos sem Ordem**
   - Cursos com `curso_ordem = 0` seguem ordem alfabética
   - Preserva comportamento atual para cursos não ordenados

4. **Testes Manuais**
   - Criar 3+ trilhas e definir ordem
   - Verificar exibição no frontend
   - Testar com diferentes usuários

---

## Especificações Técnicas

### Nova Estrutura de Meta Fields

| CPT     | Meta Key       | Tipo    | Descrição                              |
|---------|----------------|---------|----------------------------------------|
| Trilha  | `menu_order`   | int     | Ordem global da trilha (nativo WP)     |
| Curso   | `curso_ordem`  | int     | Ordem do curso dentro de sua trilha    |

### Endpoints AJAX

| Action                        | Método | Parâmetros                     | Resposta      |
|-------------------------------|--------|--------------------------------|---------------|
| `salvar_ordem_trilhas`        | POST   | `trilha_ids[]`, `nonce`        | JSON success  |
| `salvar_ordem_cursos_trilha`  | POST   | `trilha_id`, `curso_ids[]`, `nonce` | JSON success |
| `get_cursos_trilha`           | GET    | `trilha_id`, `nonce`           | JSON cursos[] |

### Modificação no Shortcode

```php
// ANTES (linha 119 do shortcode)
foreach ($cursos_por_trilha as $t_id => $cursos_da_trilha):

// DEPOIS
// Ordenar trilhas por menu_order
$trilha_ids = array_keys($cursos_por_trilha);
$trilhas_ordenadas = get_posts([
    'post_type' => 'trilha',
    'post__in' => $trilha_ids,
    'posts_per_page' => -1,
    'orderby' => 'menu_order',
    'order' => 'ASC'
]);

foreach ($trilhas_ordenadas as $trilha_obj):
    $t_id = $trilha_obj->ID;
    if (!isset($cursos_por_trilha[$t_id])) continue;
    
    // Ordenar cursos da trilha
    $cursos_da_trilha = $cursos_por_trilha[$t_id];
    usort($cursos_da_trilha, function($a, $b) {
        $ordem_a = (int) get_post_meta($a->ID, 'curso_ordem', true);
        $ordem_b = (int) get_post_meta($b->ID, 'curso_ordem', true);
        if ($ordem_a === $ordem_b) {
            return strcmp($a->post_title, $b->post_title);
        }
        return $ordem_a - $ordem_b;
    });
```

---

## Estimativas de Tempo

| Fase | Descrição                           | Estimativa  |
|------|-------------------------------------|-------------|
| 1    | Backend - Suporte à Ordenação       | 30 min      |
| 2    | Interface - Ordenação de Trilhas    | 45 min      |
| 3    | Interface - Ordenação de Cursos     | 45 min      |
| 4    | Refinamentos e Testes               | 30 min      |
| **Total** | **Implementação Completa**     | **~2.5h**   |

---

## Critérios de Aceite

- [ ] Trilhas podem ser reordenadas via drag-and-drop no admin
- [ ] Cursos podem ser reordenados dentro de cada trilha
- [ ] A ordem definida é refletida no shortcode `[meus-cursos]`
- [ ] Interface admin é intuitiva e responsiva
- [ ] Não há breaking changes no comportamento existente
- [ ] Feedback visual ao salvar (sucesso/erro)
- [ ] Versão do plugin atualizada

---

## Riscos e Mitigações

| Risco | Probabilidade | Impacto | Mitigação |
|-------|---------------|---------|-----------|
| Conflito com jQuery UI | Baixa | Médio | Usar dependência nativa do WP |
| Performance com muitos cursos | Baixa | Baixo | Limitar a 50 cursos por página |
| Ordem perdida ao editar curso | Média | Médio | Não resetar meta ao salvar curso |

---

## Notas de Implementação

1. **Evitar complexidade desnecessária:** Não criar tabelas customizadas, usar meta fields e `menu_order` nativos.

2. **UI Consistente:** Seguir o padrão visual já existente na página de configurações do plugin.

3. **Compatibilidade:** Manter funcionamento para instalações existentes (fallback para ordem alfabética).

4. **Atualização de Versão:** Incrementar versão do plugin (header) ao finalizar.
