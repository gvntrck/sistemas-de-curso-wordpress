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

    // Máscara CEP: 00000-000
    const maskCEP = (v) => {
        v = v.replace(/\D/g, "");
        if (v.length > 8) v = v.substring(0, 8);
        v = v.replace(/(\d{5})(\d)/, "$1-$2");
        return v;
    };

    // Auto-initialize masks based on IDs (as used in Minha Conta)
    const cpfInput = document.getElementById('mc_cpf');
    const dataInput = document.getElementById('mc_aniversario');
    const cepInput = document.getElementById('mc_cep');

    if (cpfInput) applyMask(cpfInput, maskCPF);
    if (dataInput) applyMask(dataInput, maskDate);
    if (cepInput) applyMask(cepInput, maskCEP);

    // Auto-initialize masks based on classes (future proofing)
    document.querySelectorAll('.mask-cpf').forEach(el => applyMask(el, maskCPF));
    document.querySelectorAll('.mask-date').forEach(el => applyMask(el, maskDate));
    document.querySelectorAll('.mask-cep').forEach(el => applyMask(el, maskCEP));

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
});

// Global Namespace for specific component logic
window.SystemCursos = window.SystemCursos || {};

window.SystemCursos.initListaAulas = function (containerId) {
    var container = document.getElementById(containerId);
    if (!container) return;

    var items = container.querySelectorAll('.lista-aulas__item[data-aula-id]');
    var videoContainer = container.querySelector('.lista-aulas__video');
    var tituloEl = container.querySelector('.lista-aulas__titulo');
    var descricaoEl = container.querySelector('.lista-aulas__descricao');
    var anexosWrapper = container.querySelector('.lista-aulas__anexos-wrapper'); // Wrapper dedicated
    var mainEl = container.querySelector('.lista-aulas__main');
    var btnConcluir = container.querySelector('.lista-aulas__btn-concluir');
    var progWrapper = container.querySelector('.lista-aulas__progresso-wrapper');

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
                            aulasConcluidas.push(parseInt(aulaId));
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

    // Handle Item Click (Navigation)
    items.forEach(function (item) {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            var aulaId = this.getAttribute('data-aula-id');
            if (!aulaId) return;

            // Update Active State
            items.forEach(function (el) { el.classList.remove('is-active'); });
            this.classList.add('is-active');

            // Update Button
            atualizarBotaoConcluir(aulaId);

            // Update URL
            var newUrl = this.getAttribute('href');
            if (window.history && window.history.pushState) {
                window.history.pushState({ aula: aulaId }, '', newUrl);
            }

            // AJAX Fetch Lesson
            var formData = new FormData();
            formData.append('action', 'lista_aulas_get_aula');
            formData.append('aula_id', aulaId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: formData
            })
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    if (data.success && data.data) {
                        if (videoContainer) videoContainer.innerHTML = data.data.embed;
                        if (tituloEl) tituloEl.textContent = data.data.titulo;

                        // Description
                        if (data.data.descricao) {
                            if (descricaoEl) {
                                descricaoEl.innerHTML = data.data.descricao;
                                descricaoEl.style.display = '';
                            } else {
                                // Create if missing
                                var newDesc = document.createElement('div');
                                newDesc.className = 'lista-aulas__descricao';
                                newDesc.innerHTML = data.data.descricao;
                                tituloEl.insertAdjacentElement('afterend', newDesc);
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
                                var target = descricaoEl || tituloEl;
                                if (target) target.insertAdjacentElement('afterend', newWrapper);
                                anexosWrapper = newWrapper;
                            }
                        } else {
                            if (anexosWrapper) anexosWrapper.innerHTML = '';
                        }

                        mainEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(function (err) {
                    console.error('Erro ao carregar aula:', err);
                });
        });
    });

    // History Support
    window.addEventListener('popstate', function (e) {
        if (e.state && e.state.aula) {
            var targetItem = container.querySelector('.lista-aulas__item[data-aula-id="' + e.state.aula + '"]');
            if (targetItem) {
                targetItem.click();
            }
        }
    });
};
