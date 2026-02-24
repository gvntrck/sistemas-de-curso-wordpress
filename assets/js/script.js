document.addEventListener('DOMContentLoaded', function () {
    // Utility for applying masks
    const applyMask = (input, maskFunc) => {
        if (!input) return;
        input.addEventListener('input', (e) => {
            let value = e.target.value;
            e.target.value = maskFunc(value);
        });
    };

    // Máscara CPF: 000.000.000-00
    const maskCPF = (v) => {
        v = v.replace(/\D/g, "");
        if (v.length > 11) v = v.substring(0, 11);
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d)/, "$1.$2");
        v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
        return v;
    };

    // Máscara Data: 00/00/0000
    const maskDate = (v) => {
        v = v.replace(/\D/g, "");
        if (v.length > 8) v = v.substring(0, 8);
        v = v.replace(/(\d{2})(\d)/, "$1/$2");
        v = v.replace(/(\d{2})(\d)/, "$1/$2");
        return v;
    };

    // Máscara Telefone: (00) 00000-0000 ou (00) 0000-0000
    const maskTelefone = (v) => {
        v = v.replace(/\D/g, "");
        if (v.length > 11) v = v.substring(0, 11);
        if (v.length > 10) {
            v = v.replace(/(\d{2})(\d{5})(\d{4})/, "($1) $2-$3");
        } else if (v.length > 6) {
            v = v.replace(/(\d{2})(\d{4})(\d{0,4})/, "($1) $2-$3");
        } else if (v.length > 2) {
            v = v.replace(/(\d{2})(\d{0,5})/, "($1) $2");
        } else if (v.length > 0) {
            v = v.replace(/(\d{0,2})/, "($1");
        }
        return v;
    };

    // Máscara CEP: 00000-000
    const maskCEP = (v) => {
        v = v.replace(/\D/g, "");
        if (v.length > 8) v = v.substring(0, 8);
        v = v.replace(/(\d{5})(\d)/, "$1-$2");
        return v;
    };

    // Função global para (re)inicializar máscaras — chamada no DOMContentLoaded e após AJAX
    function initMasks(scope) {
        var root = scope || document;

        // IDs específicos (Minha Conta)
        var cpfInput = root.querySelector('#mc_cpf');
        var dataInput = root.querySelector('#mc_aniversario');
        var cepInput = root.querySelector('#mc_cep');
        var telInput = root.querySelector('#mc_telefone');

        if (cpfInput && !cpfInput._masked) { applyMask(cpfInput, maskCPF); cpfInput._masked = true; }
        if (dataInput && !dataInput._masked) { applyMask(dataInput, maskDate); dataInput._masked = true; }
        if (cepInput && !cepInput._masked) { applyMask(cepInput, maskCEP); cepInput._masked = true; }
        if (telInput && !telInput._masked) { applyMask(telInput, maskTelefone); telInput._masked = true; }

        // Classes genéricas
        root.querySelectorAll('.mask-cpf').forEach(el => { if (!el._masked) { applyMask(el, maskCPF); el._masked = true; } });
        root.querySelectorAll('.mask-date').forEach(el => { if (!el._masked) { applyMask(el, maskDate); el._masked = true; } });
        root.querySelectorAll('.mask-cep').forEach(el => { if (!el._masked) { applyMask(el, maskCEP); el._masked = true; } });
        root.querySelectorAll('.mask-telefone').forEach(el => { if (!el._masked) { applyMask(el, maskTelefone); el._masked = true; } });
    }

    // Expor globalmente para o painel SPA poder chamar após AJAX
    window.SystemCursos = window.SystemCursos || {};
    window.SystemCursos.initMasks = initMasks;

    // Inicialização no carregamento da página
    initMasks(document);

    // CEP Address Fetch Logic
    const initAddressFetch = (cepInputId) => {
        const cepInput = document.getElementById(cepInputId);
        if (cepInput) {
            cepInput.addEventListener('blur', function () {
                var cep = this.value.replace(/\D/g, '');
                if (cep.length === 8) {
                    // Visual feedback
                    document.body.style.cursor = 'wait';

                    fetch('https://viacep.com.br/ws/' + cep + '/json/')
                        .then(response => response.json())
                        .then(data => {
                            document.body.style.cursor = 'default';
                            if (!data.erro) {
                                // Maps standard IDs found in our forms
                                const map = {
                                    'rua': data.logradouro,
                                    'bairro': data.bairro,
                                    'cidade': data.localidade,
                                    'estado': data.uf
                                };

                                for (const [id, value] of Object.entries(map)) {
                                    const input = document.getElementById(id);
                                    if (input) input.value = value || '';
                                }

                                // Auto-focus number if exists
                                const numInput = document.getElementById('numero');
                                if (numInput) numInput.focus();
                            }
                        })
                        .catch(error => {
                            console.log('Erro ao buscar CEP:', error);
                            document.body.style.cursor = 'default';
                        });
                }
            });
        }
    };

    // Initialize for standard ID 'cep' (used in new and legacy forms) or 'mc_cep'
    initAddressFetch('cep');
    initAddressFetch('mc_cep');

    if (window.SystemCursos && window.SystemCursos.initBannerCarousel) {
        window.SystemCursos.initBannerCarousel(document);
    }
});

// Global Namespace for specific component logic (initMasks já registrado acima)
window.SystemCursos = window.SystemCursos || {};

window.SystemCursos.initBannerCarousel = function (scope) {
    var root = scope || document;
    var carousels = root.querySelectorAll('.lms-banner-carousel');

    carousels.forEach(function (carousel) {
        if (carousel._lmsBannerInited) return;
        carousel._lmsBannerInited = true;

        var slides = carousel.querySelectorAll('.lms-banner-slide');
        if (!slides.length) return;

        var prevBtn = carousel.querySelector('.lms-banner-arrow-prev');
        var nextBtn = carousel.querySelector('.lms-banner-arrow-next');
        var dots = carousel.querySelectorAll('.lms-banner-dot');

        var currentIndex = 0;
        var timer = null;
        var autoplayMs = parseInt(carousel.getAttribute('data-autoplay'), 10);
        if (isNaN(autoplayMs) || autoplayMs < 2000) {
            autoplayMs = 5000;
        }

        function showSlide(index) {
            if (index < 0) index = slides.length - 1;
            if (index >= slides.length) index = 0;

            slides.forEach(function (slide, slideIndex) {
                slide.classList.toggle('is-active', slideIndex === index);
            });

            dots.forEach(function (dot, dotIndex) {
                dot.classList.toggle('is-active', dotIndex === index);
            });

            currentIndex = index;
        }

        function stopAutoplay() {
            if (!timer) return;
            clearInterval(timer);
            timer = null;
        }

        function startAutoplay() {
            stopAutoplay();
            if (slides.length <= 1) return;
            timer = setInterval(function () {
                showSlide(currentIndex + 1);
            }, autoplayMs);
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function (event) {
                event.preventDefault();
                showSlide(currentIndex - 1);
                startAutoplay();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function (event) {
                event.preventDefault();
                showSlide(currentIndex + 1);
                startAutoplay();
            });
        }

        dots.forEach(function (dot) {
            dot.addEventListener('click', function (event) {
                event.preventDefault();
                var targetIndex = parseInt(dot.getAttribute('data-index'), 10);
                if (isNaN(targetIndex)) return;
                showSlide(targetIndex);
                startAutoplay();
            });
        });

        carousel.addEventListener('mouseenter', stopAutoplay);
        carousel.addEventListener('mouseleave', startAutoplay);
        carousel.addEventListener('focusin', stopAutoplay);
        carousel.addEventListener('focusout', function (event) {
            if (!carousel.contains(event.relatedTarget)) {
                startAutoplay();
            }
        });

        showSlide(0);
        startAutoplay();
    });
};

function sistemaCursosResolveAjaxUrl(preferredUrl) {
    if (preferredUrl) return preferredUrl;

    var listaAulas = document.querySelector('.lista-aulas[data-ajax-url]');
    if (listaAulas) {
        var listaAulasUrl = listaAulas.getAttribute('data-ajax-url');
        if (listaAulasUrl) return listaAulasUrl;
    }

    if (window.quizFrontend && window.quizFrontend.ajaxUrl) {
        return window.quizFrontend.ajaxUrl;
    }

    return '';
}

function sistemaCursosApplyOverallProgress(progressValue) {
    var progress = parseInt(progressValue, 10);
    if (isNaN(progress)) progress = 0;
    progress = Math.max(0, Math.min(100, progress));

    var wrappers = document.querySelectorAll('.barra-progresso-geral-wrapper');
    wrappers.forEach(function (wrapper) {
        var fill = wrapper.querySelector('.barra-progresso-geral-fill');
        var percentEl = wrapper.querySelector('.barra-progresso-geral-percent');

        if (!percentEl) {
            var header = wrapper.querySelector('.barra-progresso-geral-header');
            if (header) {
                var spans = header.querySelectorAll('span');
                if (spans.length > 1) {
                    percentEl = spans[spans.length - 1];
                }
            }
        }

        if (fill) fill.style.width = progress + '%';
        if (percentEl) percentEl.textContent = progress + '%';
    });
}

window.SystemCursos.refreshOverallProgressBar = function (preferredAjaxUrl) {
    var ajaxUrl = sistemaCursosResolveAjaxUrl(preferredAjaxUrl);
    if (!ajaxUrl) return Promise.resolve(null);

    var formData = new FormData();
    formData.append('action', 'sistema_cursos_get_overall_progress');

    return fetch(ajaxUrl, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
        .then(function (response) { return response.json(); })
        .then(function (data) {
            if (data && data.success && data.data && typeof data.data.progress !== 'undefined') {
                sistemaCursosApplyOverallProgress(data.data.progress);
                return data.data.progress;
            }
            return null;
        })
        .catch(function (err) {
            console.error('Erro ao atualizar barra de progresso geral:', err);
            return null;
        });
};

window.SystemCursos.applyOverallProgress = sistemaCursosApplyOverallProgress;

document.addEventListener('DOMContentLoaded', function () {
    if (window.SystemCursos._quizProgressListenerBound) return;
    if (typeof window.jQuery === 'undefined') return;

    window.SystemCursos._quizProgressListenerBound = true;

    window.jQuery(document).on('sistema_cursos_lesson_competed sistema_cursos_lesson_completed', function () {
        if (window.SystemCursos && window.SystemCursos.refreshOverallProgressBar) {
            window.SystemCursos.refreshOverallProgressBar();
        }
    });
});

window.SystemCursos.initListaAulas = function (containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var items = container.querySelectorAll('.lista-aulas__item[data-aula-id]');
    var videoContainer = container.querySelector('.lista-aulas__video');
    var tituloEl = container.querySelector('.lista-aulas__titulo');
    var descricaoEl = container.querySelector('.lista-aulas__descricao');
    var anexosWrapper = container.querySelector('.lista-aulas__anexos-wrapper'); // Wrapper dedicated
    var comentariosWrapper = container.querySelector('.lista-aulas__comentarios-wrapper');
    var mainEl = container.querySelector('.lista-aulas__main');
    var btnConcluir = container.querySelector('.lista-aulas__btn-concluir');
    var progWrapper = container.querySelector('.lista-aulas__progresso-wrapper');
    var lessonLoadingEl = null;
    var isLessonLoading = false;
    var lessonRequestToken = 0;

    var ajaxUrl = container.getAttribute('data-ajax-url');

    // Data from attributes
    var aulasConcluidas = [];
    var totalAulas = 0;

    if (progWrapper) {
        try {
            aulasConcluidas = JSON.parse(progWrapper.getAttribute('data-concluidas'));
            totalAulas = parseInt(progWrapper.getAttribute('data-total-aulas'));
        } catch (e) {
            console.error('Error parsing progress data', e);
        }
    }

    function isAulaConcluida(aulaId) {
        return aulasConcluidas.indexOf(parseInt(aulaId)) !== -1;
    }

    function atualizarBarraProgresso() {
        var qtd = aulasConcluidas.length;
        var pct = totalAulas > 0 ? Math.min(100, Math.round((qtd / totalAulas) * 100)) : 0;

        var fill = container.querySelector('.lista-aulas__progresso-fill');
        var txt = container.querySelector('.lista-aulas__progresso-texto');

        if (fill) fill.style.width = pct + '%';
        if (txt) txt.textContent = pct + '%';
    }

    function atualizarBotaoConcluir(aulaId) {
        if (!btnConcluir) return;
        btnConcluir.setAttribute('data-aula-id', aulaId);
        var concluida = isAulaConcluida(aulaId);
        if (concluida) {
            btnConcluir.classList.add('is-concluida');
            btnConcluir.querySelector('.lista-aulas__btn-texto').textContent = 'Concluído';
        } else {
            btnConcluir.classList.remove('is-concluida');
            btnConcluir.querySelector('.lista-aulas__btn-texto').textContent = 'Marcar como concluído';
        }
    }

    function atualizarItemLista(aulaId, concluida) {
        var item = container.querySelector('.lista-aulas__item[data-aula-id="' + aulaId + '"]');
        if (!item) return;
        var indexEl = item.querySelector('.lista-aulas__item-index');
        if (!indexEl) return;

        if (concluida) {
            indexEl.classList.add('is-concluida');
            indexEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
        } else {
            indexEl.classList.remove('is-concluida');
            // Recover index (assumes order in DOM)
            var allItems = container.querySelectorAll('.lista-aulas__item[data-aula-id]');
            var idx = Array.prototype.indexOf.call(allItems, item) + 1;
            indexEl.textContent = idx;
        }
    }

    function ensureLessonLoadingEl() {
        if (!mainEl) return null;
        if (lessonLoadingEl && lessonLoadingEl.parentNode === mainEl) return lessonLoadingEl;

        lessonLoadingEl = mainEl.querySelector('.lista-aulas__loading');
        if (!lessonLoadingEl) {
            lessonLoadingEl = document.createElement('div');
            lessonLoadingEl.className = 'lista-aulas__loading';
            lessonLoadingEl.setAttribute('aria-hidden', 'true');
            lessonLoadingEl.innerHTML = '<div class="lista-aulas__spinner"></div>';
            mainEl.appendChild(lessonLoadingEl);
        }
        return lessonLoadingEl;
    }

    function setLessonLoading(active) {
        if (!mainEl) return;

        isLessonLoading = !!active;
        mainEl.classList.toggle('is-loading', isLessonLoading);
        mainEl.setAttribute('aria-busy', isLessonLoading ? 'true' : 'false');

        var loader = ensureLessonLoadingEl();
        if (loader) {
            loader.classList.toggle('active', isLessonLoading);
        }

        items.forEach(function (itemEl) {
            if (isLessonLoading) {
                itemEl.setAttribute('aria-disabled', 'true');
            } else {
                itemEl.removeAttribute('aria-disabled');
            }
        });
    }

    if (mainEl) {
        mainEl.setAttribute('aria-busy', 'false');
        ensureLessonLoadingEl();
    }

    function executeInlineScripts(container) {
        var scripts = container.querySelectorAll('script');
        scripts.forEach(function(oldScript) {
            var newScript = document.createElement('script');
            if (oldScript.src) {
                newScript.src = oldScript.src;
            } else {
                newScript.textContent = oldScript.textContent;
            }
            oldScript.parentNode.replaceChild(newScript, oldScript);
        });
    }

    function normalizeMainSectionsOrder() {
        if (!mainEl) return;

        var headerEl = container.querySelector('.lista-aulas__header');
        if (!headerEl) return;

        var anchor = headerEl;
        var quizWrapper = container.querySelector('.lista-aulas__quiz-wrapper');

        if (descricaoEl) {
            if (descricaoEl.parentNode !== mainEl || anchor.nextElementSibling !== descricaoEl) {
                anchor.insertAdjacentElement('afterend', descricaoEl);
            }
            anchor = descricaoEl;
        }

        if (anexosWrapper) {
            if (anexosWrapper.parentNode !== mainEl || anchor.nextElementSibling !== anexosWrapper) {
                anchor.insertAdjacentElement('afterend', anexosWrapper);
            }
            anchor = anexosWrapper;
        }

        if (quizWrapper) {
            if (quizWrapper.parentNode !== mainEl || anchor.nextElementSibling !== quizWrapper) {
                anchor.insertAdjacentElement('afterend', quizWrapper);
            }
            anchor = quizWrapper;
        }

        if (comentariosWrapper) {
            if (comentariosWrapper.parentNode !== mainEl || anchor.nextElementSibling !== comentariosWrapper) {
                anchor.insertAdjacentElement('afterend', comentariosWrapper);
            }
        }
    }

    normalizeMainSectionsOrder();

    function atualizarComentariosSection(comentariosHtml) {
        var html = comentariosHtml || '';

        if (html) {
            if (comentariosWrapper) {
                comentariosWrapper.innerHTML = html;
                normalizeMainSectionsOrder();
                return;
            }

            var newComentariosWrapper = document.createElement('div');
            newComentariosWrapper.className = 'lista-aulas__comentarios-wrapper';
            newComentariosWrapper.innerHTML = html;

            if (mainEl) {
                mainEl.appendChild(newComentariosWrapper);
            }

            comentariosWrapper = newComentariosWrapper;
            normalizeMainSectionsOrder();
            return;
        }

        if (comentariosWrapper) {
            comentariosWrapper.remove();
            comentariosWrapper = null;
            normalizeMainSectionsOrder();
        }
    }

    // Handle Toggle Complete
    if (btnConcluir) {
        btnConcluir.addEventListener('click', function () {
            var aulaId = this.getAttribute('data-aula-id');
            var cursoId = this.getAttribute('data-curso-id');
            if (!aulaId) return;

            this.disabled = true;

            var formData = new FormData();
            formData.append('action', 'lista_aulas_toggle_concluida');
            formData.append('aula_id', aulaId);
            formData.append('curso_id', cursoId);

            var btn = this;

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    btn.disabled = false;
                    if (data.success) {
                        var concluida = data.data.concluida;
                        if (concluida) {
                            var parsedAulaId = parseInt(aulaId, 10);
                            if (aulasConcluidas.indexOf(parsedAulaId) === -1) {
                                aulasConcluidas.push(parsedAulaId);
                            }
                            btn.classList.add('is-concluida');
                            btn.querySelector('.lista-aulas__btn-texto').textContent = 'Concluído';
                        } else {
                            var idx = aulasConcluidas.indexOf(parseInt(aulaId));
                            if (idx > -1) aulasConcluidas.splice(idx, 1);
                            btn.classList.remove('is-concluida');
                            btn.querySelector('.lista-aulas__btn-texto').textContent = 'Marcar como concluído';
                        }
                        atualizarItemLista(aulaId, concluida);
                        atualizarBarraProgresso();
                        if (window.SystemCursos && window.SystemCursos.refreshOverallProgressBar) {
                            window.SystemCursos.refreshOverallProgressBar(ajaxUrl);
                        }
                    } else {
                        alert(data.data.message || 'Erro ao atualizar.');
                    }
                })
                .catch(function (err) {
                    btn.disabled = false;
                    console.error('Erro:', err);
                });
        });
    }

    // Detectar se está dentro do painel SPA
    container.addEventListener('submit', function (event) {
        var form = event.target.closest('.sc-aula-comments__form');
        if (!form || !container.contains(form)) return;

        event.preventDefault();

        if (form.getAttribute('data-loading') === '1') return;

        var aulaInput = form.querySelector('input[name="aula_id"]');
        var nonceInput = form.querySelector('input[name="nonce"]');
        var contentInput = form.querySelector('textarea[name="comment_content"]');
        var statusEl = form.querySelector('.sc-aula-comments__form-status');

        var aulaId = aulaInput ? aulaInput.value : '';
        var nonce = nonceInput ? nonceInput.value : '';
        var content = contentInput ? contentInput.value.trim() : '';

        if (!aulaId || !nonce) return;

        if (!content) {
            if (statusEl) {
                statusEl.textContent = 'Escreva um comentario antes de enviar.';
                statusEl.classList.add('is-error');
                statusEl.classList.remove('is-success');
            }
            return;
        }

        form.setAttribute('data-loading', '1');

        if (statusEl) {
            statusEl.textContent = 'Enviando comentario...';
            statusEl.classList.remove('is-error');
            statusEl.classList.remove('is-success');
        }

        var formData = new FormData();
        formData.append('action', 'sistema_cursos_add_comment');
        formData.append('aula_id', aulaId);
        formData.append('nonce', nonce);
        formData.append('content', content);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    atualizarComentariosSection(data.data.section_html || '');
                    return;
                }

                var message = (data && data.data && data.data.message)
                    ? data.data.message
                    : 'Nao foi possivel enviar o comentario.';

                if (statusEl) {
                    statusEl.textContent = message;
                    statusEl.classList.add('is-error');
                    statusEl.classList.remove('is-success');
                }
            })
            .catch(function (err) {
                console.error('Erro ao enviar comentario:', err);
                if (statusEl) {
                    statusEl.textContent = 'Erro ao enviar comentario. Tente novamente.';
                    statusEl.classList.add('is-error');
                    statusEl.classList.remove('is-success');
                }
            })
            .finally(function () {
                form.removeAttribute('data-loading');
            });
    });

    container.addEventListener('click', function (event) {
        var deleteBtn = event.target.closest('.sc-aula-comments__delete');
        if (!deleteBtn || !container.contains(deleteBtn)) return;

        event.preventDefault();

        var commentId = deleteBtn.getAttribute('data-comment-id');
        var aulaId = deleteBtn.getAttribute('data-aula-id');
        var nonce = deleteBtn.getAttribute('data-nonce');

        if (!commentId || !aulaId || !nonce) return;

        if (!window.confirm('Deseja apagar este comentario?')) {
            return;
        }

        deleteBtn.disabled = true;

        var formData = new FormData();
        formData.append('action', 'sistema_cursos_delete_comment');
        formData.append('comment_id', commentId);
        formData.append('aula_id', aulaId);
        formData.append('nonce', nonce);

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success && data.data) {
                    atualizarComentariosSection(data.data.section_html || '');
                    return;
                }

                var message = (data && data.data && data.data.message)
                    ? data.data.message
                    : 'Nao foi possivel apagar o comentario.';
                alert(message);
                deleteBtn.disabled = false;
            })
            .catch(function (err) {
                console.error('Erro ao apagar comentario:', err);
                deleteBtn.disabled = false;
            });
    });

    var isInsidePainel = !!document.getElementById('lms-painel');

    // Handle Item Click (Navigation)
    items.forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            if (isLessonLoading) return;

            var aulaId = this.getAttribute('data-aula-id');
            if (!aulaId) return;

            // Update Active State
            items.forEach(function (el) { el.classList.remove('is-active'); });
            this.classList.add('is-active');

            // Update Button
            atualizarBotaoConcluir(aulaId);

            // Update URL (SPA-aware)
            if (window.history && window.history.pushState) {
                if (isInsidePainel) {
                    // Dentro do painel SPA: usar parâmetros do painel
                    var url = new URL(window.location);
                    url.searchParams.set('lms_view', 'curso');
                    url.searchParams.set('target_aula', aulaId);
                    // curso_id já está na URL se estamos na view de curso
                    window.history.pushState({ target_aula: aulaId, lmsView: 'curso', cursoId: url.searchParams.get('curso_id') }, '', url);
                } else {
                    // Fora do painel: usar URL original
                    var newUrl = this.getAttribute('href');
                    window.history.pushState({ target_aula: aulaId }, '', newUrl);
                }
            }

            // AJAX Fetch Lesson
            var formData = new FormData();
            formData.append('action', 'lista_aulas_get_aula');
            formData.append('aula_id', aulaId);
            var requestToken = ++lessonRequestToken;
            setLessonLoading(true);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (requestToken !== lessonRequestToken) return;
                    if (data.success && data.data) {
                        if (videoContainer) videoContainer.innerHTML = data.data.embed;
                        if (tituloEl) tituloEl.textContent = data.data.titulo;

                        // Description
                        if (data.data.descricao) {
                            if (descricaoEl) {
                                descricaoEl.innerHTML = data.data.descricao;
                                executeInlineScripts(descricaoEl);
                                descricaoEl.style.display = '';
                            } else {
                                // Create if missing
                                var newDesc = document.createElement('div');
                                newDesc.className = 'lista-aulas__descricao';
                                newDesc.innerHTML = data.data.descricao;
                                executeInlineScripts(newDesc);
                                if (mainEl) {
                                    mainEl.appendChild(newDesc);
                                }
                                descricaoEl = newDesc; // update ref
                            }
                        } else {
                            if (descricaoEl) descricaoEl.style.display = 'none';
                        }

                        // Anexos
                        if (data.data.anexos) {
                            if (anexosWrapper) {
                                anexosWrapper.innerHTML = data.data.anexos;
                            } else {
                                // Create wrapper if missing
                                var newWrapper = document.createElement('div');
                                newWrapper.className = 'lista-aulas__anexos-wrapper';
                                newWrapper.innerHTML = data.data.anexos;
                                if (mainEl) {
                                    mainEl.appendChild(newWrapper);
                                }
                                anexosWrapper = newWrapper;
                            }
                        } else {
                            if (anexosWrapper) anexosWrapper.innerHTML = '';
                        }

                        // Quiz (NEW - Fix for AJAX navigation)
                        var quizWrapper = container.querySelector('.lista-aulas__quiz-wrapper');
                        if (data.data.quiz) {
                            if (quizWrapper) {
                                quizWrapper.innerHTML = data.data.quiz;
                            } else {
                                // Create wrapper if missing
                                var newQuizWrapper = document.createElement('div');
                                newQuizWrapper.className = 'lista-aulas__quiz-wrapper';
                                newQuizWrapper.innerHTML = data.data.quiz;

                                if (mainEl) {
                                    mainEl.appendChild(newQuizWrapper);
                                }
                                quizWrapper = newQuizWrapper;
                            }
                        } else {
                            // No quiz: remove wrapper if exists
                            if (quizWrapper) {
                                quizWrapper.remove();
                            }
                        }

                        normalizeMainSectionsOrder();
                        atualizarComentariosSection(data.data.comentarios || '');

                        // Update button visibility (NEW - Fix for AJAX navigation)
                        if (btnConcluir) {
                            if (data.data.esconder_botao_manual) {
                                btnConcluir.style.display = 'none';
                            } else {
                                btnConcluir.style.display = '';
                            }
                        }

                        mainEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(function (err) {
                    if (requestToken !== lessonRequestToken) return;
                    console.error('Erro ao carregar aula:', err);
                })
                .finally(function () {
                    if (requestToken !== lessonRequestToken) return;
                    setLessonLoading(false);
                });
        });
    });

    // History Support
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.target_aula) {
            var targetItem = container.querySelector('.lista-aulas__item[data-aula-id="' + e.state.target_aula + '"]');
            if (targetItem) {
                targetItem.click();
            }
        }
    });
};
