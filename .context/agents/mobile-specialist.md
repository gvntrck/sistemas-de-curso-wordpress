---
name: Mobile Specialist
description: Especialista em responsividade e mobile para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Mobile Specialist Agent Playbook

## Missão
Garantir que o plugin LMS SuporteRapido ofereça uma experiência excelente em dispositivos móveis, otimizando layouts, performance e usabilidade touch.

## Responsabilidades
- Garantir responsividade dos shortcodes
- Otimizar touch targets e gestos
- Adaptar layouts para telas pequenas
- Testar em diferentes dispositivos
- Otimizar performance mobile

## Breakpoints Padrão

```css
/* Mobile First Approach */

/* Base: Mobile (< 768px) */
.sc-container {
    padding: 16px;
    font-size: 16px; /* Evita zoom no iOS */
}

/* Tablet: >= 768px */
@media (min-width: 768px) {
    .sc-container {
        padding: 24px;
    }
}

/* Desktop: >= 1024px */
@media (min-width: 1024px) {
    .sc-container {
        padding: 32px;
    }
}

/* Large: >= 1280px */
@media (min-width: 1280px) {
    .sc-container {
        max-width: 1200px;
        margin: 0 auto;
    }
}
```

## Componentes Responsivos

### Grid de Cursos
```css
.sc-cursos-grid {
    display: grid;
    gap: 16px;
    
    /* Mobile: 1 coluna */
    grid-template-columns: 1fr;
}

@media (min-width: 480px) {
    /* Mobile Large: 2 colunas */
    .sc-cursos-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 768px) {
    /* Tablet: 3 colunas */
    .sc-cursos-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
    }
}

@media (min-width: 1024px) {
    /* Desktop: 4 colunas */
    .sc-cursos-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

### Card de Curso Responsivo
```css
.sc-curso-card {
    display: flex;
    flex-direction: column;
}

.sc-curso-card__imagem {
    width: 100%;
    aspect-ratio: 16/10;
    object-fit: cover;
}

.sc-curso-card__titulo {
    font-size: 16px;
    /* Limitar linhas em mobile */
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

@media (min-width: 768px) {
    .sc-curso-card__titulo {
        font-size: 18px;
        -webkit-line-clamp: 3;
    }
}
```

### Lista de Aulas
```css
.sc-aula-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    gap: 12px;
    
    /* Touch target mínimo: 44px */
    min-height: 44px;
}

.sc-aula-item__checkbox {
    /* Touch target para checkbox */
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

@media (min-width: 768px) {
    .sc-aula-item {
        padding: 16px 24px;
    }
}
```

### Vídeo Player
```css
.sc-video-container {
    position: relative;
    width: 100%;
    padding-bottom: 56.25%; /* 16:9 */
    height: 0;
    overflow: hidden;
}

.sc-video-container iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
}
```

## Touch Optimization

### Touch Targets
```css
/* Mínimo 44x44px para iOS, 48x48dp para Android */
.sc-btn,
.sc-link,
.sc-icon-btn {
    min-width: 44px;
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 12px 16px;
}

.sc-btn--small {
    min-width: 36px;
    min-height: 36px;
    padding: 8px 12px;
}
```

### Espaçamento entre Elementos Interativos
```css
.sc-actions {
    display: flex;
    gap: 8px; /* Mínimo 8px entre botões */
}
```

### Estados de Toque
```css
.sc-btn:active {
    transform: scale(0.98);
    opacity: 0.9;
}

.sc-aula-item:active {
    background: rgba(0, 0, 0, 0.05);
}

/* Remover delay de 300ms no mobile */
.sc-btn {
    touch-action: manipulation;
}
```

## Performance Mobile

### Lazy Loading de Imagens
```html
<img 
    src="placeholder.jpg"
    data-src="curso-capa.jpg"
    loading="lazy"
    class="sc-lazy"
>
```

```javascript
// Intersection Observer para lazy loading
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.dataset.src;
            img.classList.remove('sc-lazy');
            observer.unobserve(img);
        }
    });
});

document.querySelectorAll('.sc-lazy').forEach(img => {
    observer.observe(img);
});
```

### Reduzir Reflows
```css
/* Definir dimensões para evitar layout shift */
.sc-curso-card__imagem {
    width: 100%;
    height: auto;
    aspect-ratio: 16/10;
}

/* Skeleton loading */
.sc-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton 1.5s infinite;
}

@keyframes skeleton {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
```

## Testes Mobile

### Dispositivos Prioritários
1. iPhone 12/13/14 (375px)
2. iPhone SE (320px)
3. Samsung Galaxy S21 (360px)
4. iPad (768px)
5. iPad Pro (1024px)

### Checklist de Teste
- [ ] Layout não quebra em 320px
- [ ] Texto legível sem zoom
- [ ] Touch targets acessíveis
- [ ] Formulários usáveis no mobile
- [ ] Vídeos responsivos
- [ ] Performance < 3s no 3G
- [ ] Scroll suave
- [ ] Orientação landscape funciona

### DevTools Mobile
```
Chrome DevTools > Toggle Device Toolbar (Ctrl+Shift+M)
- Testar diferentes viewports
- Simular touch events
- Throttle de rede (3G/4G)
- Testar landscape/portrait
```

## Acessibilidade Mobile

```css
/* Foco visível para navegação por teclado */
.sc-btn:focus-visible {
    outline: 2px solid #007bff;
    outline-offset: 2px;
}

/* Cores com contraste suficiente */
.sc-texto {
    color: #333; /* Contraste 7:1 com branco */
}

/* Tamanho de fonte mínimo */
.sc-texto {
    font-size: 16px; /* Mínimo para legibilidade */
}
```

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [Frontend Specialist](./frontend-specialist.md)
- [Material Design Guidelines](https://material.io/design)
- [Apple HIG](https://developer.apple.com/design/human-interface-guidelines/)

## Checklist Mobile

- [ ] Mobile-first CSS
- [ ] Touch targets 44x44px mínimo
- [ ] Imagens responsivas com lazy loading
- [ ] Vídeos em container 16:9
- [ ] Formulários otimizados
- [ ] Performance testada em 3G
- [ ] Testado em dispositivos reais
