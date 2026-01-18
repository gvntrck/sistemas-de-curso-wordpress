---
status: completed
generated: 2026-01-18
completed: 2026-01-18
agents:
  - type: "frontend-specialist"
    role: "Implementar a refatoração e otimização do CSS"
  - type: "performance-optimizer"
    role: "Analisar e validar ganhos de performance"
  - type: "code-reviewer"
    role: "Revisar as mudanças para manter qualidade e consistência"
phases:
  - id: "phase-1"
    name: "Análise e Planejamento"
    prevc: "P"
    status: "completed"
  - id: "phase-2"
    name: "Implementação"
    prevc: "E"
    status: "completed"
  - id: "phase-3"
    name: "Validação e Testes"
    prevc: "V"
    status: "pending"
---

# 🎨 Plano de Otimização do CSS do Plugin

> **Objetivo:** Refatorar e otimizar o arquivo CSS do plugin para reduzir seu tamanho (~24KB / 1093 linhas) e melhorar a manutenibilidade.

## 📊 Diagnóstico Atual

### Situação do CSS
- **Arquivo:** `assets/css/style.css`
- **Tamanho atual:** 24.478 bytes (24KB)
- **Total de linhas:** 1.093 linhas
- **Shortcodes estilizados:** 5+ componentes principais

### Problemas Identificados

#### 1. 🔴 Duplicação de Estilos (Alta Prioridade)
| Problema | Linhas | Impacto Estimado |
|----------|--------|------------------|
| `.mc-btn` e `.cert-print-btn` têm estilos quase idênticos | 220-287 | ~30 linhas |
| Variáveis CSS redefinidas em `.meus-cursos-wrapper` | 666-674 | ~10 linhas |
| Estilos de hover/active repetidos em múltiplos botões | Vários | ~20 linhas |
| Cores hardcoded que deveriam usar variáveis | Vários | ~40 linhas |

#### 2. 🟡 Uso Excessivo de `!important` (Média Prioridade)
| Componente | Ocorrências aproximadas |
|------------|------------------------|
| `.mc-btn` e variantes | 15+ |
| `.lista-aulas__btn-concluir` | 10+ |
| `.cert-element` | 20+ |
| `.lista-aulas__anexos-*` | 10+ |

**Impacto:** Dificulta manutenção e sobrescritas futuras, indica problemas de especificidade.

#### 3. 🟡 Falta de Reutilização (Média Prioridade)
| Padrão | Componentes Afetados |
|--------|---------------------|
| Estilos de cards | `.meus-cursos-card`, `.cert-card`, `.curso-item.type-curso` |
| Barras de progresso | `.lista-aulas__progresso-*`, `.meus-cursos-progress-*`, `.barra-progresso-geral-*` |
| Botões | `.mc-btn-save`, `.mc-btn`, `.cert-print-btn`, `.lista-aulas__btn-concluir` |
| Estados de hover/active | Repetidos em 10+ seletores |

#### 4. 🟢 Oportunidades de Melhoria (Baixa Prioridade)
- Cores como `#1a1a1a`, `#333`, `#22c55e` repetidas sem uso de variáveis
- Transições e animações poderiam usar variáveis de tempo
- Espaçamentos poderiam usar sistema de tokens

---

## 📋 Fases de Implementação

### Fase 1: Análise e Planejamento (P)
**Objetivo:** Mapear completamente o CSS e definir a estratégia de refatoração

**Tarefas:**
- [x] ~~Analisar o arquivo CSS completo~~
- [x] ~~Identificar duplicações e padrões~~
- [x] ~~Documentar problemas encontrados~~
- [ ] Criar inventário de todos os seletores por componente
- [ ] Definir sistema de tokens/variáveis expandido
- [ ] Documentar dependências entre shortcodes e estilos

**Entregáveis:**
- Documento de diagnóstico (este plano)
- Inventário de seletores
- Sistema de design tokens proposto

**Checkpoint de Commit:**
```
git commit -m "docs(plan): plano de otimização CSS documentado"
```

---

### Fase 2: Implementação (E)
**Objetivo:** Executar a refatoração mantendo funcionalidade visual

#### Etapa 2.1: Expandir Sistema de Variáveis CSS
**Estimativa:** ~50 linhas de redução

```css
/* ANTES: Variáveis básicas */
:root {
    --bg-color: #121212;
    --accent-color: #FDC110;
    /* ... */
}

/* DEPOIS: Sistema expandido */
:root {
    /* === Cores Base === */
    --color-bg-primary: #121212;
    --color-bg-secondary: #1a1a1a;
    --color-bg-tertiary: #0a0a0a;
    
    /* === Estados === */
    --color-success: #22c55e;
    --color-success-hover: #16a34a;
    --color-error: #ef4444;
    
    /* === Espaçamentos === */
    --spacing-xs: 4px;
    --spacing-sm: 8px;
    --spacing-md: 16px;
    --spacing-lg: 24px;
    --spacing-xl: 40px;
    
    /* === Transições === */
    --transition-fast: 0.2s ease;
    --transition-normal: 0.3s ease;
    
    /* === Sombras === */
    --shadow-card: 0 8px 30px rgba(0, 0, 0, 0.5);
    --shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.4);
}
```

**Tarefas:**
- [ ] Criar sistema completo de variáveis
- [ ] Substituir todos os valores hardcoded por variáveis
- [ ] Remover variáveis duplicadas em `.meus-cursos-wrapper`

---

#### Etapa 2.2: Consolidar Classes de Botões
**Estimativa:** ~80 linhas de redução

```css
/* ANTES: Classes separadas */
.mc-btn { /* 15 propriedades */ }
.mc-btn-save { /* 12 propriedades */ }
.cert-print-btn { /* 17 propriedades duplicadas */ }
.lista-aulas__btn-concluir { /* 15 propriedades */ }
.lista-aulas__btn-login { /* 10 propriedades */ }
.lista-aulas__btn-voltar { /* 9 propriedades */ }

/* DEPOIS: Sistema unificado */
.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-sm) var(--spacing-md);
    font-size: 1rem;
    font-weight: 600;
    border-radius: var(--radius-btn);
    cursor: pointer;
    transition: var(--transition-fast);
    text-decoration: none;
    border: none;
    gap: var(--spacing-sm);
}

.btn:hover { transform: translateY(-1px); }
.btn:active { transform: translateY(1px); }

/* Variantes */
.btn--primary { /* accent color */ }
.btn--secondary { /* dark color */ }
.btn--success { /* green color */ }
.btn--ghost { /* transparent + border */ }
```

**Tarefas:**
- [ ] Criar classe base `.btn`
- [ ] Criar modificadores de cor (--primary, --secondary, --success)
- [ ] Migrar todos os botões para novo sistema
- [ ] Remover classes redundantes

---

#### Etapa 2.3: Consolidar Estilos de Cards
**Estimativa:** ~60 linhas de redução

```css
/* DEPOIS: Sistema de cards unificado */
.card {
    background: var(--color-bg-secondary);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-card);
    transition: transform var(--transition-fast), 
                box-shadow var(--transition-fast),
                border-color var(--transition-fast);
}

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-hover);
    border-color: var(--accent-color);
}

/* Variantes */
.card--dark { background: var(--color-bg-tertiary); }
.card--interactive { cursor: pointer; }
```

**Tarefas:**
- [ ] Criar classe base `.card`
- [ ] Unificar `.meus-cursos-card`, `.cert-card`, `.curso-item.type-curso`
- [ ] Aplicar em shortcodes mantendo compatibilidade

---

#### Etapa 2.4: Consolidar Barras de Progresso
**Estimativa:** ~40 linhas de redução

```css
/* DEPOIS: Sistema unificado de progresso */
.progress {
    width: 100%;
    height: var(--progress-height, 6px);
    background: var(--color-bg-muted);
    border-radius: var(--radius-sm);
    overflow: hidden;
}

.progress__fill {
    height: 100%;
    background: var(--progress-color, var(--accent-color));
    border-radius: var(--radius-sm);
    transition: width 0.4s ease;
}

.progress--success .progress__fill { background: var(--color-success); }
.progress--accent .progress__fill { background: var(--accent-color); }
```

**Tarefas:**
- [ ] Criar classe base `.progress`
- [ ] Unificar 3 implementações diferentes de barra de progresso
- [ ] Adicionar modificadores de cor

---

#### Etapa 2.5: Remover `!important` Desnecessários
**Estimativa:** Melhoria de manutenibilidade

**Estratégia:**
1. Aumentar especificidade com prefixos de componente
2. Usar `:where()` para reset de baixa especificidade
3. Manter `!important` apenas para print styles e override de temas

**Tarefas:**
- [ ] Auditar cada uso de `!important`
- [ ] Refatorar para usar especificidade adequada
- [ ] Documentar casos onde `!important` é necessário

---

#### Etapa 2.6: Minificação e Otimização Final
**Estimativa:** 40-50% redução adicional

**Tarefas:**
- [ ] Considerar criar versão minificada para produção
- [ ] Avaliar separação em módulos (admin vs frontend)
- [ ] Documentar todas as mudanças

---

### Fase 3: Validação e Testes (V)
**Objetivo:** Garantir que todas as mudanças mantêm funcionalidade visual

**Checklist de Testes:**
- [ ] **[meus-cursos]** - Grid de cursos renderiza corretamente
- [ ] **[lista-aulas]** - Player e sidebar funcionam em desktop e mobile
- [ ] **[certificado]** - Certificado renderiza e imprime corretamente  
- [ ] **[resultado-busca]** - Cards e links estilizados
- [ ] **[barra-progresso-geral]** - Barra animada funciona
- [ ] **Painel do Aluno** - Formulários e containers funcionam
- [ ] **Responsividade** - Testar em 3 breakpoints (mobile, tablet, desktop)
- [ ] **Impressão** - Print styles funcionam para certificados

**Entregáveis:**
- CSS refatorado e testado
- Documentação de mudanças
- Guia de migração (se necessário)

**Checkpoint de Commit:**
```
git commit -m "style(css): otimização completa - redução de X%"
```

---

## 📈 Estimativa de Resultados

| Métrica | Antes | Depois (Estimado) | Redução |
|---------|-------|-------------------|---------|
| **Linhas de código** | 1.093 | ~700-800 | 25-35% |
| **Tamanho do arquivo** | 24KB | ~16-18KB | 25-35% |
| **Variáveis CSS** | ~25 | ~50+ | Mais reutilização |
| **Uso de !important** | 50+ | <10 | 80% menos |
| **Classes duplicadas** | ~15 | 0 | 100% |

---

## ⚠️ Plano de Rollback

### Gatilhos para Rollback
- Quebra visual em algum shortcode
- Problema de responsividade em mobile
- CSS não carregando corretamente
- Performance pior após mudanças

### Procedimento
1. Manter backup do CSS original antes de iniciar
2. Fazer commits pequenos e incrementais
3. Testar cada fase visualmente antes de prosseguir
4. Em caso de problemas: `git checkout HEAD~1 -- assets/css/style.css`

---

## 📝 Notas de Implementação

### Compatibilidade
- Manter nomes de classes existentes como aliases se necessário
- Não quebrar shortcodes existentes
- Considerar temas que podem sobrescrever estilos

### Boas Práticas
- Seguir metodologia BEM para novas classes
- Documentar sistema de design resultante
- Criar guia de estilo para futuras adições

---

## 🎯 Próximos Passos

1. **Aprovar** este plano de otimização
2. **Iniciar Fase 2.1** - Expandir sistema de variáveis
3. **Testar** cada etapa antes de prosseguir
4. **Documentar** mudanças no CHANGELOG

