---
status: completed
generated: 2026-01-20
priority: alta
type: bug-fix
agents:
  - type: "bug-fixer"
    role: "Analisar e corrigir o bug visual do hover nos cards"
  - type: "frontend-specialist"
    role: "Implementar correção CSS mantendo a funcionalidade do carrossel"
docs:
  - "project-overview.md"
  - "architecture.md"
phases:
  - id: "analise"
    name: "Análise do Problema"
    prevc: "P"
    status: "completed"
  - id: "implementacao"
    name: "Implementação da Correção"
    prevc: "E"
    status: "completed"
  - id: "verificacao"
    name: "Verificação e Testes"
    prevc: "V"
    status: "pending"
---

# Correção do Bug Visual - Border Hover nos Cards do Carrossel

## Resumo do Bug

**Comportamento Atual:**
- Ao passar o mouse sobre um card de curso no shortcode `[meus-cursos]`, o card faz um pequeno "bounce" para cima (`translateY(-3px)`)
- A borda amarela (`border-color: #FDC110`) aparece em todos os lados, exceto na parte superior
- A borda superior parece "sumir" ou ficar escondida abaixo de algo

**Comportamento Esperado:**
- A borda amarela deve aparecer completamente em todos os 4 lados do card durante o hover
- O efeito de "bounce" deve continuar funcionando normalmente

---

## Análise da Causa Raiz

### Arquivos Envolvidos

| Arquivo | Linha | Código Problemático |
|---------|-------|---------------------|
| `assets/css/style.css` | 1119-1123 | `.mc-carousel-wrapper { overflow: hidden; }` |
| `includes/shortcodes/class-shortcode-meus-cursos.php` | 236-239 | `.curso-item:hover { transform: translateY(-3px); }` |

### Diagnóstico

1. **O container `.mc-carousel-wrapper`** tem `overflow: hidden` para funcionar como carrossel horizontal
2. **O padding do wrapper** é apenas `0 10px` (horizontal), sem padding vertical
3. **Quando o card sobe 3px** no hover, a borda superior ultrapassa o limite do container
4. **O `overflow: hidden`** corta visualmente a parte que ultrapassa, escondendo a borda superior

### Visualização do Problema

```
┌─────────────────────────────────────┐  <-- .mc-carousel-wrapper (overflow: hidden)
│  ┌─────────┐ ┌─────────┐           │
│  │ Card 1  │ │ Card 2  │           │      Estado Normal
│  └─────────┘ └─────────┘           │
└─────────────────────────────────────┘

┌─────────────────────────────────────┐  <-- .mc-carousel-wrapper 
│╔═════════╗ ┌─────────┐             │      Hover: Card 1 sobe
││ Card 1  ║ │ Card 2  │             │      Borda superior CORTADA
│╚═════════╝ └─────────┘             │      pelo overflow: hidden
└─────────────────────────────────────┘
 ↑ A borda superior fica FORA do container
```

---

## Plano de Implementação

### Fase 1: Implementação da Correção (CSS)

**Estratégia Escolhida:** Adicionar padding-top ao wrapper para criar espaço para o efeito hover

#### Passo 1.1 - Modificar `style.css`

**Arquivo:** `assets/css/style.css`  
**Localização:** Linha 1118-1123

**De:**
```css
/* Wrapper do carrossel - esconde overflow */
.mc-carousel-wrapper {
    overflow: hidden;
    width: 100%;
    padding: 0 10px;
}
```

**Para:**
```css
/* Wrapper do carrossel - esconde overflow */
.mc-carousel-wrapper {
    overflow: hidden;
    width: 100%;
    padding: 5px 10px;  /* Adiciona padding-top/bottom para o hover não ser cortado */
}
```

#### Passo 1.2 - Modificar estilos inline no shortcode (opcional)

**Arquivo:** `includes/shortcodes/class-shortcode-meus-cursos.php`

Os estilos inline do shortcode podem sobrescrever o CSS externo. Verificar se há conflitos.

---

### Fase 2: Atualização da Versão do Plugin

**Arquivo:** `sistema-cursos-plugin.php`

Incrementar a versão do plugin no header conforme regra do usuário.

---

### Fase 3: Verificação

1. Testar o shortcode `[meus-cursos]` em uma página
2. Verificar que a borda amarela aparece completamente no hover
3. Confirmar que o carrossel horizontal continua funcionando
4. Testar em diferentes resoluções de tela

---

## Alternativas Consideradas

| Alternativa | Prós | Contras |
|-------------|------|---------|
| **1. Padding no wrapper** ⭐ | Simples, não afeta layout | Adiciona pequeno espaço vertical |
| 2. `overflow: visible` | Resolve o problema | Quebra o carrossel horizontal |
| 3. Usar `box-shadow` ao invés de `border` | Não é cortado pelo overflow | Muda a estética estabelecida |
| 4. Margem negativa no track | Compensa o padding | Mais complexo, pode causar outros bugs |

**Decisão:** Opção 1 - Adicionar padding ao wrapper (solução mais simples e segura)

---

## Checklist de Conclusão

- [x] Modificar `.mc-carousel-wrapper` no `style.css`
- [x] Verificar conflitos com estilos inline do shortcode
- [x] Atualizar versão do plugin (1.2.28 → 1.2.29)
- [ ] Testar hover em todos os cards
- [ ] Testar funcionalidade do carrossel
- [ ] Testar responsividade

---

## Rollback

Caso a correção cause problemas, reverter o padding para `0 10px`:

```css
.mc-carousel-wrapper {
    padding: 0 10px;
}
```
