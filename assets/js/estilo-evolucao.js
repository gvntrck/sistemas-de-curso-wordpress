/**
 * Estilo Evolução Quiz – Lógica Interativa
 * Registrado via wp_enqueue_script para funcionar em carregamento AJAX.
 * Usa event delegation no document.body para capturar eventos de elementos
 * injetados dinamicamente.
 *
 * @package SistemaCursos
 */
; (function () {
    'use strict';

    // ── Helpers ──────────────────────────────────────────────────────────
    function closest(el, selector) {
        while (el && !el.matches(selector)) el = el.parentElement;
        return el;
    }

    function getWrapper(el) {
        return closest(el, '.ee-quiz-container-instance');
    }

    // ── Seleção de opções (radio) ────────────────────────────────────────
    document.addEventListener('change', function (e) {
        if (!e.target.matches('.ee-quiz-container-instance .ee-option input[type="radio"]')) return;

        var radio = e.target;
        var wrapper = getWrapper(radio);
        if (!wrapper) return;

        var form = wrapper.querySelector('.ee-quiz-form');
        if (!form) return;

        // Remove seleção visual de todas as opções da mesma pergunta
        var name = radio.name;
        var siblings = form.querySelectorAll('input[name="' + name + '"]');
        siblings.forEach(function (sib) {
            var label = sib.closest('.ee-option');
            if (label) label.classList.remove('selected');
        });

        // Marca a opção clicada
        var parentLabel = radio.closest('.ee-option');
        if (parentLabel && radio.checked) {
            parentLabel.classList.add('selected');
        }

        // Esconde erro se todas as 5 perguntas respondidas
        var formData = new FormData(form);
        var count = 0;
        formData.forEach(function () { count++; });
        if (count === 5) {
            var errorMsg = wrapper.querySelector('.ee-error-msg');
            if (errorMsg) errorMsg.classList.remove('active');
        }
    });

    // ── Botão "Ver Meu Resultado" ────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!e.target.matches('.ee-btn-submit')) return;

        var wrapper = getWrapper(e.target);
        if (!wrapper) return;

        var form = wrapper.querySelector('.ee-quiz-form');
        if (!form) return;

        var formData = new FormData(form);
        var counts = { A: 0, B: 0, C: 0 };
        var answeredCount = 0;

        formData.forEach(function (value) {
            if (counts[value] !== undefined) counts[value]++;
            answeredCount++;
        });

        if (answeredCount < 5) {
            var errorMsg = wrapper.querySelector('.ee-error-msg');
            if (errorMsg) errorMsg.classList.add('active');
            return;
        }

        var maxCount = 0;
        var resultType = 'A';
        for (var type in counts) {
            if (counts[type] > maxCount) {
                maxCount = counts[type];
                resultType = type;
            }
        }

        var titleText = '';
        var descText = '';

        if (resultType === 'A') {
            titleText = 'FAZEDOR 🧱';
            descText = 'Você aprende na prática, focando em checklist e execução rápida. Seu estilo é colocar a mão na massa e ver resultados através da ação!';
        } else if (resultType === 'B') {
            titleText = 'FESTEIRO 🎉';
            descText = 'Você aprende na troca, com a comunidade e eventos. Seu estilo é construir conhecimento através de conexões, discussões e vivências em grupo!';
        } else {
            titleText = 'APRENDEDOR 📚';
            descText = 'Você aprende com conceitos e fundamentos. Seu estilo é entender profundamente o "porquê" das coisas antes de agir, buscando estratégias sólidas!';
        }

        var resTitle = wrapper.querySelector('.ee-res-title');
        var resDesc = wrapper.querySelector('.ee-res-desc');
        if (resTitle) resTitle.innerText = titleText;
        if (resDesc) resDesc.innerText = descText;

        var body = wrapper.querySelector('.ee-quiz-body');
        var footer = wrapper.querySelector('.ee-quiz-footer');
        var result = wrapper.querySelector('.ee-result-section');

        if (body) body.style.display = 'none';
        if (footer) footer.style.display = 'none';
        if (result) result.style.display = 'block';

        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // ── Botão "Fazer o Teste Novamente" ──────────────────────────────────
    document.addEventListener('click', function (e) {
        if (!e.target.matches('.ee-btn-retry')) return;

        var wrapper = getWrapper(e.target);
        if (!wrapper) return;

        var form = wrapper.querySelector('.ee-quiz-form');
        if (form) form.reset();

        wrapper.querySelectorAll('.ee-option').forEach(function (opt) {
            opt.classList.remove('selected');
        });

        var result = wrapper.querySelector('.ee-result-section');
        var body = wrapper.querySelector('.ee-quiz-body');
        var footer = wrapper.querySelector('.ee-quiz-footer');

        if (result) result.style.display = 'none';
        if (body) body.style.display = 'block';
        if (footer) footer.style.display = 'block';

        wrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });
})();
