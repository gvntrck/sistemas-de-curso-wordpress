---
name: Frontend Specialist
description: Especialista em frontend para o LMS SuporteRapido
status: filled
generated: 2026-01-18
---

# Frontend Specialist Agent Playbook

## Missão
Desenvolver e manter a interface do usuário do plugin LMS SuporteRapido, incluindo estilos CSS, interações JavaScript e renderização de shortcodes.

## Responsabilidades
- Estilizar shortcodes e componentes
- Implementar interações JavaScript
- Garantir responsividade
- Otimizar carregamento de assets
- Integrar com html2pdf.js para certificados

## Stack Frontend

| Componente | Tecnologia |
|------------|------------|
| Estilos | CSS3 (Vanilla) |
| Scripts | JavaScript ES6 |
| PDF | html2pdf.js |
| Vídeo | Vimeo oEmbed |
| Fonts | Google Fonts |

## Arquivos Frontend

```
assets/
├── css/
│   └── style.css          # Estilos principais
├── js/
│   └── script.js          # JavaScript principal
└── images/                # Assets de imagem
```

## Estrutura de Classes CSS

### Prefixo do Plugin
Todas as classes usam o prefixo `sistema-cursos-` ou `sc-`:

```css
/* Containers */
.sistema-cursos-container {}
.sistema-cursos-grid {}

/* Componentes */
.sc-curso-card {}
.sc-aula-item {}
.sc-certificado {}
.sc-barra-progresso {}

/* Estados */
.sc-ativo {}
.sc-concluido {}
.sc-bloqueado {}

/* Responsivo */
@media (max-width: 768px) {
    .sistema-cursos-grid {
        grid-template-columns: 1fr;
    }
}
```

## Padrões de CSS

### Cards de Curso
```css
.sc-curso-card {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s, box-shadow 0.2s;
}

.sc-curso-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.sc-curso-card__imagem {
    width: 100%;
    height: 200px;
    object-fit: cover;
}

.sc-curso-card__conteudo {
    padding: 16px;
}

.sc-curso-card__titulo {
    font-size: 18px;
    font-weight: 600;
    margin: 0 0 8px;
}
```

### Barra de Progresso
```css
.sc-barra-progresso {
    width: 100%;
    height: 8px;
    background: #e0e0e0;
    border-radius: 4px;
    overflow: hidden;
}

.sc-barra-progresso__fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    transition: width 0.3s ease;
}
```

### Lista de Aulas
```css
.sc-aula-item {
    display: flex;
    align-items: center;
    padding: 12px 16px;
    border-bottom: 1px solid #eee;
    cursor: pointer;
}

.sc-aula-item:hover {
    background: #f5f5f5;
}

.sc-aula-item--concluida {
    opacity: 0.7;
}

.sc-aula-item--concluida::before {
    content: '✓';
    color: #4CAF50;
    margin-right: 8px;
}
```

## JavaScript

### Estrutura do script.js
```javascript
(function($) {
    'use strict';
    
    // Variáveis globais do plugin (via wp_localize_script)
    const ajaxUrl = sistemasCursosData.ajaxUrl;
    const nonce = sistemasCursosData.nonce;
    
    // Inicialização
    $(document).ready(function() {
        initProgressBars();
        initAulaButtons();
        initCertificado();
    });
    
    // Funções...
})(jQuery);
```

### Concluir Aula
```javascript
function concluirAula(aulaId, cursoId) {
    $.ajax({
        url: ajaxUrl,
        type: 'POST',
        data: {
            action: 'concluir_aula',
            aula_id: aulaId,
            curso_id: cursoId,
            nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                atualizarBarraProgresso(cursoId, response.data.progresso);
                atualizarItemLista(aulaId);
            }
        }
    });
}
```

### Atualizar Barra de Progresso
```javascript
function atualizarBarraProgresso(cursoId, percentual) {
    const barra = document.querySelector(`[data-curso="${cursoId}"] .sc-barra-progresso__fill`);
    if (barra) {
        barra.style.width = percentual + '%';
    }
}
```

### Geração de Certificado
```javascript
function gerarCertificado() {
    const element = document.getElementById('certificado-container');
    
    html2pdf().set({
        margin: 0,
        filename: 'certificado.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2,
            useCORS: true,
            letterRendering: true
        },
        jsPDF: { 
            unit: 'px', 
            format: [element.offsetWidth, element.offsetHeight],
            orientation: 'landscape'
        }
    }).from(element).save();
}
```

## Enqueue de Assets

```php
// Em class-assets.php
add_action('wp_enqueue_scripts', function() {
    // CSS
    wp_enqueue_style(
        'sistema-cursos-style',
        plugin_dir_url(__FILE__) . '../assets/css/style.css',
        [],
        '1.2.22'
    );
    
    // JS
    wp_enqueue_script(
        'sistema-cursos-script',
        plugin_dir_url(__FILE__) . '../assets/js/script.js',
        ['jquery'],
        '1.2.22',
        true
    );
    
    // Dados para JS
    wp_localize_script('sistema-cursos-script', 'sistemasCursosData', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('sistema_cursos_nonce')
    ]);
    
    // html2pdf.js (condicional)
    global $post;
    if (has_shortcode($post->post_content, 'certificado')) {
        wp_enqueue_script(
            'html2pdf',
            'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js',
            [],
            '0.10.1',
            true
        );
    }
});
```

## Responsividade

### Breakpoints
```css
/* Mobile first */
.sc-grid {
    display: grid;
    gap: 16px;
    grid-template-columns: 1fr;
}

/* Tablet */
@media (min-width: 768px) {
    .sc-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .sc-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* Large */
@media (min-width: 1280px) {
    .sc-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}
```

## Integração com Temas

```css
/* Variáveis CSS para theming */
:root {
    --sc-primary: #2196F3;
    --sc-success: #4CAF50;
    --sc-warning: #FF9800;
    --sc-danger: #f44336;
    --sc-text: #333;
    --sc-bg: #fff;
    --sc-border: #ddd;
    --sc-radius: 8px;
}

/* Respeitar cores do tema */
.sc-btn-primary {
    background: var(--sc-primary);
    color: #fff;
}
```

## Documentação de Referência
- [Arquitetura](../docs/architecture.md)
- [modelo.html](../../modelo.html) - Template de referência

## Checklist Frontend

- [ ] Classes com prefixo sc- ou sistema-cursos-
- [ ] Responsivo mobile-first
- [ ] Transições suaves
- [ ] Acessibilidade básica (contrast, focus)
- [ ] Assets minificados em produção
- [ ] Sem conflito com temas populares
