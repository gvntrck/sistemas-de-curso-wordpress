---
status: completed
generated: 2026-01-18
priority: high
agents:
  - type: "frontend-specialist"
    role: "Implementar carrossel CSS/JS e navegação por setas"
  - type: "code-reviewer"
    role: "Revisar qualidade do código PHP e JavaScript"
docs:
  - "class-shortcode-meus-cursos.php"
  - "style.css"
phases:
  - id: "phase-1"
    name: "Análise e Planejamento"
    prevc: "P"
  - id: "phase-2"
    name: "Implementação do Carrossel"
    prevc: "E"
  - id: "phase-3"
    name: "Testes e Validação"
    prevc: "V"
---

# Implementar Carrossel de Cursos com Setas de Navegação

> **Objetivo:** Transformar a exibição de cursos no shortcode `[meus-cursos]` de um grid que quebra em múltiplas linhas para um carrossel horizontal com navegação por setas no canto superior direito, permitindo navegar um curso por vez.

## Contexto e Motivação

### Problema Atual
- Os cursos são exibidos em um grid flexível com `flex-wrap: wrap`
- Quando há mais de 4 cursos, eles quebram para uma nova linha
- Isso cria uma interface longa e pouco elegante

### Solução Proposta (Referência Visual)
Baseado no print de referência:
- **Layout:** Cards de curso dispostos horizontalmente em uma única linha
- **Navegação:** Duas setas circulares (`<` e `>`) posicionadas no canto superior direito do container
- **Comportamento:** Ao clicar nas setas, o carrossel move um card por vez
- **Overflow:** Cards que excedem a área visível ficam ocultos à direita

## Arquivos a Modificar

| Arquivo | Modificação |
|---------|-------------|
| `includes/shortcodes/class-shortcode-meus-cursos.php` | Adicionar estrutura HTML do carrossel + setas + JavaScript |
| `assets/css/style.css` | Adicionar estilos CSS para o carrossel e setas |
| `sistema-cursos-plugin.php` | Atualizar versão do plugin |

---

## Phase 1: Análise e Planejamento (P)

### Estrutura HTML Proposta

```html
<div class="mc-container">
    <!-- Header com título e setas no canto direito -->
    <div class="mc-header" style="display: flex; justify-content: space-between; align-items: flex-start;">
        <div>
            <h3>Nome da Trilha</h3>
            <p>Descrição curta</p>
        </div>
        <!-- Setas de navegação -->
        <div class="mc-carousel-nav">
            <button class="mc-carousel-btn mc-prev" aria-label="Anterior">
                <svg><!-- Ícone < --></svg>
            </button>
            <button class="mc-carousel-btn mc-next" aria-label="Próximo">
                <svg><!-- Ícone > --></svg>
            </button>
        </div>
    </div>
    
    <!-- Container do Carrossel -->
    <div class="mc-body">
        <div class="mc-carousel-wrapper">
            <div class="mc-carousel-track">
                <!-- Cards dos cursos -->
                <div class="curso-item">...</div>
                <div class="curso-item">...</div>
                <div class="curso-item">...</div>
            </div>
        </div>
    </div>
</div>
```

### Design das Setas (Referência do Print)

- **Formato:** Circular com borda branca/cinza sutil
- **Fundo:** Transparente ou semi-transparente escuro
- **Ícone:** Setas simples `<` e `>`
- **Posição:** Canto superior direito do header, alinhadas horizontalmente
- **Espaçamento:** Gap pequeno entre as duas setas (~8px)

---

## Phase 2: Implementação do Carrossel (E)

### Step 2.1: Atualizar CSS (style.css)

Adicionar os seguintes estilos ao final do arquivo:

```css
/* =========================================
   Componente: Carrossel de Cursos
   ========================================= */

/* Wrapper do carrossel - esconde overflow */
.mc-carousel-wrapper {
    overflow: hidden;
    width: 100%;
    padding: 0 10px;
}

/* Track que contém todos os cards - move horizontalmente */
.mc-carousel-track {
    display: flex;
    gap: 20px;
    transition: transform 0.4s ease;
    will-change: transform;
}

/* Cards do carrossel não encolhem */
.mc-carousel-track .curso-item {
    flex: 0 0 200px;
    min-width: 200px;
}

/* Container das setas de navegação */
.mc-carousel-nav {
    display: flex;
    gap: 8px;
    align-items: center;
}

/* Botões de navegação do carrossel */
.mc-carousel-btn {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    border: 1px solid rgba(255, 255, 255, 0.3);
    background: rgba(255, 255, 255, 0.05);
    color: #fff;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    padding: 0;
}

.mc-carousel-btn:hover {
    border-color: rgba(255, 255, 255, 0.6);
    background: rgba(255, 255, 255, 0.1);
}

.mc-carousel-btn:disabled {
    opacity: 0.3;
    cursor: not-allowed;
}

.mc-carousel-btn svg {
    width: 16px;
    height: 16px;
    fill: none;
    stroke: currentColor;
    stroke-width: 2;
    stroke-linecap: round;
    stroke-linejoin: round;
}
```

### Step 2.2: Modificar PHP (class-shortcode-meus-cursos.php)

#### 2.2.1 Atualizar estrutura do header da trilha

Modificar o bloco do header (linhas 124-134) para incluir as setas:

```php
<div class="mc-header" style="text-align: left; padding: 25px; display: flex; justify-content: space-between; align-items: flex-start;">
    <div>
        <h3 style="margin: 0; font-size: 1.5rem; color: var(--text-heading, #fff);">
            <?php echo esc_html($nome_trilha); ?>
        </h3>
        <?php if ($desc_trilha): ?>
            <p style="margin: 5px 0 0 0; color: var(--text-muted, #888); font-size: 0.95rem;">
                <?php echo esc_html($desc_trilha); ?>
            </p>
        <?php endif; ?>
    </div>
    <!-- Setas de navegação -->
    <div class="mc-carousel-nav">
        <button class="mc-carousel-btn mc-prev" aria-label="Anterior">
            <svg viewBox="0 0 24 24"><polyline points="15 18 9 12 15 6"></polyline></svg>
        </button>
        <button class="mc-carousel-btn mc-next" aria-label="Próximo">
            <svg viewBox="0 0 24 24"><polyline points="9 6 15 12 9 18"></polyline></svg>
        </button>
    </div>
</div>
```

#### 2.2.2 Atualizar estrutura do body da trilha

Substituir o div `cursos-da-trilha` (linhas 136-142) por:

```php
<div class="mc-body" style="padding: 25px;">
    <div class="mc-carousel-wrapper">
        <div class="mc-carousel-track">
            <?php foreach ($cursos_da_trilha as $curso):
                echo $this->render_curso_card($curso, $user_id);
            endforeach; ?>
        </div>
    </div>
</div>
```

#### 2.2.3 Repetir para "Outros Cursos" (cursos sem trilha)

Aplicar a mesma estrutura para o bloco de cursos avulsos (linhas 148-165).

#### 2.2.4 Adicionar JavaScript para navegação

Adicionar script JavaScript no final do shortcode (antes do return):

```php
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar todos os carrosséis na página
    document.querySelectorAll('.mc-container').forEach(function(container) {
        const track = container.querySelector('.mc-carousel-track');
        const prevBtn = container.querySelector('.mc-prev');
        const nextBtn = container.querySelector('.mc-next');
        
        if (!track || !prevBtn || !nextBtn) return;
        
        let currentIndex = 0;
        const items = track.querySelectorAll('.curso-item');
        const itemWidth = 220; // 200px width + 20px gap
        const totalItems = items.length;
        
        function getVisibleItems() {
            const wrapperWidth = container.querySelector('.mc-carousel-wrapper').offsetWidth;
            return Math.floor(wrapperWidth / itemWidth);
        }
        
        function updateCarousel() {
            const visibleItems = getVisibleItems();
            const maxIndex = Math.max(0, totalItems - visibleItems);
            
            // Limitar índice
            if (currentIndex < 0) currentIndex = 0;
            if (currentIndex > maxIndex) currentIndex = maxIndex;
            
            // Mover track
            const offset = currentIndex * itemWidth;
            track.style.transform = 'translateX(-' + offset + 'px)';
            
            // Atualizar estado dos botões
            prevBtn.disabled = currentIndex === 0;
            nextBtn.disabled = currentIndex >= maxIndex;
        }
        
        prevBtn.addEventListener('click', function() {
            currentIndex--;
            updateCarousel();
        });
        
        nextBtn.addEventListener('click', function() {
            currentIndex++;
            updateCarousel();
        });
        
        // Inicializar
        updateCarousel();
        
        // Recalcular ao redimensionar
        window.addEventListener('resize', updateCarousel);
    });
});
</script>
```

### Step 2.3: Atualizar Versão do Plugin

Incrementar a versão no header do plugin (`sistema-cursos-plugin.php`) para `1.2.27`.

---

## Phase 3: Testes e Validação (V)

### Checklist de Testes

- [ ] Carrossel exibe cursos em linha horizontal única
- [ ] Setas aparecem no canto superior direito do header
- [ ] Clique na seta direita (`>`) move o carrossel um card para a direita
- [ ] Clique na seta esquerda (`<`) move o carrossel um card para a esquerda
- [ ] Seta esquerda fica desabilitada quando está no início
- [ ] Seta direita fica desabilitada quando está no final
- [ ] Funciona corretamente com diferentes quantidades de cursos (1, 3, 5, 10+)
- [ ] Responsivo: funciona em telas menores
- [ ] Animação suave ao navegar (transition CSS)
- [ ] Funciona para trilhas E para "Outros Cursos"

### Testes de Navegador

- [ ] Chrome
- [ ] Firefox
- [ ] Safari
- [ ] Edge

---

## Rollback Plan

### Como Reverter
1. Remover estilos CSS adicionados para `.mc-carousel-*`
2. Restaurar estrutura HTML original com `flex-wrap: wrap`
3. Remover código JavaScript do carrossel
4. Decrementar versão do plugin

### Commits Importantes
- Antes: estrutura grid com wrap
- Depois: estrutura carrossel com navegação

---

## Evidências

- [ ] Screenshot do carrossel funcionando
- [ ] Vídeo demonstrando navegação com setas
- [ ] Código revisado e commitado
