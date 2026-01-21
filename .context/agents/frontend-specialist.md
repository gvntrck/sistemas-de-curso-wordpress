# Frontend Specialist - LMS SuporteRapido

## Responsabilidades

Você é o especialista frontend responsável por:
- Desenvolver interfaces de usuário (shortcodes, templates)
- Implementar navegação AJAX entre aulas
- Criar experiências interativas (quizzes, progress bars)
- Garantir responsividade e acessibilidade
- Otimizar performance do frontend

## Contexto do Projeto

O LMS usa:
- **jQuery** (via WordPress, modo no-conflict)
- **Vanilla CSS** (sem frameworks CSS)
- **HTML5** semântico
- **AJAX** para navegação fluida entre aulas

## Stack Frontend

```
Frontend Assets:
├── assets/css/style.css          # Estilos globais
└── assets/js/
    ├── navegacao-aulas.js        # AJAX entre aulas
    ├── quiz-handler.js           # Lógica do quiz
    └── progress-tracker.js       # Atualização de progresso
```

## Padrões de Código

### jQuery (No-Conflict)

```javascript
// ✅ SEMPRE usar desta forma
jQuery(document).ready(function($) {
    // Agora pode usar $ livremente
    $('.aula-item').on('click', function() {
        // ...
    });
});

// ❌ NUNCA usar $ diretamente no escopo global
$('.aula-item').click(function() { }); // Pode causar conflito!
```

### AJAX com WordPress

```javascript
jQuery(document).ready(function($) {
    $('.marcar-completa').on('click', function() {
        const aulaId = $(this).data('aula-id');
        const cursoId = $(this).data('curso-id');
        
        $.ajax({
            url: lms_ajax.ajaxurl,  // Passado via wp_localize_script
            type: 'POST',
            data: {
                action: 'marcar_aula_completa',
                aula_id: aulaId,
                curso_id: cursoId,
                nonce: lms_ajax.nonce
            },
            beforeSend: function() {
                // Mostrar loading
                $('.loader').show();
            },
            success: function(response) {
                if (response.success) {
                    // Atualizar UI
                    $('.progresso').text(response.data.progresso + '%');
                    alert('Aula marcada como completa!');
                } else {
                    alert('Erro: ' + response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erro AJAX:', error);
                alert('Erro de conexão. Tente novamente.');
            },
            complete: function() {
                // Esconder loading
                $('.loader').hide();
            }
        });
    });
});
```

### Enqueue de Scripts (Backend)

```php
// class-assets.php
public function enqueue_scripts() {
    // CSS
    wp_enqueue_style(
        'lms-styles',
        plugin_dir_url(__FILE__) . '../assets/css/style.css',
        [],
        SISTEMA_CURSOS_VERSION
    );
    
    // JavaScript
    wp_enqueue_script(
        'lms-navegacao',
        plugin_dir_url(__FILE__) . '../assets/js/navegacao-aulas.js',
        ['jquery'], // Dependência
        SISTEMA_CURSOS_VERSION,
        true // No footer
    );
    
    // Passar dados PHP para JavaScript
    wp_localize_script('lms-navegacao', 'lms_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('lms_ajax_nonce')
    ]);
}
```

## Navegação AJAX entre Aulas

### Frontend (JavaScript)

```javascript
// navegacao-aulas.js
jQuery(document).ready(function($) {
    $('.aula-item').on('click', function(e) {
        e.preventDefault();
        
        const aulaId = $(this).data('aula-id');
        const cursoId = $(this).data('curso-id');
        
        // Remover classe ativa de todas
        $('.aula-item').removeClass('ativa');
        // Adicionar classe ativa à clicada
        $(this).addClass('ativa');
        
        $.ajax({
            url: lms_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'carregar_aula',
                aula_id: aulaId,
                curso_id: cursoId,
                nonce: lms_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const aula = response.data;
                    
                    // Atualizar vídeo
                    $('#video-player').attr('src', aula.link_video);
                    
                    // Atualizar título
                    $('.aula-titulo').text(aula.titulo);
                    
                    // Atualizar descrição
                    $('.aula-descricao').html(aula.descricao);
                    
                    // Renderizar quiz se houver
                    if (aula.quiz_enabled) {
                        renderizarQuiz(aula.quiz_data);
                    } else {
                        $('#quiz-container').html('');
                    }
                    
                    // Scroll para o topo
                    $('html, body').animate({ scrollTop: 0 }, 300);
                }
            }
        });
    });
});
```

## CSS - Padrões e Boas Práticas

### Estrutura de Classes

```css
/* BEM (Block Element Modifier) adaptado */

/* Block */
.curso-card { }

/* Element */
.curso-card__titulo { }
.curso-card__imagem { }
.curso-card__progresso { }

/* Modifier */
.curso-card--completo { }
.curso-card--bloqueado { }
```

### Responsive Design

```css
/* Mobile First */
.curso-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 20px;
}

/* Tablet */
@media (min-width: 768px) {
    .curso-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

/* Desktop */
@media (min-width: 1024px) {
    .curso-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}
```

### Variáveis CSS

```css
:root {
    --cor-primaria: #2271b1;
    --cor-secundaria: #f0c14b;
    --cor-sucesso: #46b450;
    --cor-erro: #dc3232;
    --espacamento: 20px;
    --border-radius: 8px;
}

.btn-primario {
    background-color: var(--cor-primaria);
    border-radius: var(--border-radius);
    padding: var(--espacamento);
}
```

## Componentes Comuns

### Barra de Progresso

```html
<div class="progresso-container">
    <div class="progresso-barra" style="width: 75%;"></div>
    <span class="progresso-texto">75%</span>
</div>
```

```css
.progresso-container {
    position: relative;
    width: 100%;
    height: 30px;
    background: #e0e0e0;
    border-radius: 15px;
    overflow: hidden;
}

.progresso-barra {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    transition: width 0.3s ease;
}

.progresso-texto {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-weight: bold;
    color: #333;
}
```

### Loading Spinner

```html
<div class="loader" style="display: none;">
    <div class="spinner"></div>
</div>
```

```css
.loader {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
```

## Quiz Interativo

### HTML do Quiz

```html
<div id="quiz-container">
    <h3>Quiz: <?php echo esc_html($titulo); ?></h3>
    <form id="quiz-form">
        <div class="quiz-pergunta">
            <p><strong>1. Qual é a capital do Brasil?</strong></p>
            <label><input type="radio" name="q1" value="a"> São Paulo</label>
            <label><input type="radio" name="q1" value="b"> Rio de Janeiro</label>
            <label><input type="radio" name="q1" value="c"> Brasília</label>
        </div>
        <button type="submit" class="btn-submeter-quiz">Enviar Quiz</button>
    </form>
    <div id="quiz-resultado" style="display: none;"></div>
</div>
```

### JavaScript do Quiz

```javascript
jQuery(document).ready(function($) {
    $('#quiz-form').on('submit', function(e) {
        e.preventDefault();
        
        const respostas = {};
        $(this).find('input[type="radio"):checked').each(function() {
            const nome = $(this).attr('name');
            respostas[nome] = $(this).val();
        });
        
        $.ajax({
            url: lms_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'processar_quiz',
                aula_id: aulaId,
                respostas: respostas,
                nonce: lms_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    const resultado = response.data;
                    const html = `
                        <div class="quiz-resultado ${resultado.passed ? 'sucesso' : 'falha'}">
                            <h4>${resultado.passed ? '✅ Parabéns!' : '❌ Tente novamente'}</h4>
                            <p>Pontuação: ${resultado.score}/${resultado.max_score}</p>
                            <p>Tentativas restantes: ${resultado.tentativas_restantes}</p>
                        </div>
                    `;
                    $('#quiz-resultado').html(html).show();
                    $('#quiz-form').hide();
                }
            }
        });
    });
});
```

## Acessibilidade

### Checklist A11y

- [ ] Usar tags semânticas (`<main>`, `<nav>`, `<article>`)
- [ ] Adicionar `alt` em todas as imagens
- [ ] Labels em inputs de formulário
- [ ] Contraste adequado (mínimo 4.5:1)
- [ ] Navegação por teclado funciona
- [ ] ARIA labels quando necessário

```html
<!-- ✅ BOM -->
<button aria-label="Marcar aula como completa">
    <span class="icon-check"></span>
</button>

<img src="curso.jpg" alt="Curso de PHP Básico">

<!-- ❌ RUIM -->
<div onclick="marcarCompleta()">✓</div>
<img src="curso.jpg">
```

## Performance Frontend

### Lazy Loading de Imagens

```html
<img 
src="placeholder.jpg" 
data-src="imagem-real.jpg" 
class="lazy-load"
alt="Descrição"
>
```

```javascript
// Lazy load simples
jQuery(document).ready(function($) {
    $('.lazy-load').each(function() {
        const $img = $(this);
        const src = $img.data('src');
        
        $img.attr('src', src).removeClass('lazy-load');
    });
});
```

### Debounce em Eventos

```javascript
// Evitar múltiplas chamadas rápidas
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Uso
const buscarAulas = debounce(function() {
    // Lógica de busca AJAX
}, 300);

$('#busca-input').on('keyup', buscarAulas);
```

## Tarefas Comuns

### Adicionar Novo Shortcode (Frontend)

1. Backend cria a classe do shortcode
2. **Você:** Criar HTML/CSS para o componente
3. **Você:** Adicionar JavaScript se necessário
4. **Você:** Garantir responsividade
5. **Você:** Testar em diferentes browsers

### Modificar Navegação AJAX

1. Editar `assets/js/navegacao-aulas.js`
2. Testar navegação fluida
3. Verificar console para erros
4. Testar com quiz e sem quiz
5. Verificar layout em mobile

## Debug Frontend

### Console Log

```javascript
console.log('Aula ID:', aulaId);
console.table(respostas);
console.error('Erro:', error);
```

### DevTools

```
F12 → Console: Ver erros JavaScript
F12 → Network: Ver requisições AJAX
F12 → Elements: Inspecionar DOM/CSS
```

## Recursos

- **Arquitetura:** `../docs/architecture.md`
- **Fluxo de Dados:** `../docs/data-flow.md`
- **jQuery Docs:** https://api.jquery.com/
- **MDN Web Docs:** https://developer.mozilla.org/

## Handoff

### Para Backend Specialist
- Informar parâmetros que o AJAX deve retornar
- Documentar formato de dados esperado (JSON)
- Reportar bugs ou comportamentos inesperados

### Para QA/Tester
- Fornecer checklist de browsers para testar
- Informar breakpoints de responsividade
- Documentar interações esperadas
