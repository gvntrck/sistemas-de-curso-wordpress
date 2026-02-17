jQuery(document).ready(function ($) {
    // If quizFrontend is not defined, we can't do anything (url/nonce)
    if (typeof quizFrontend === 'undefined') return;

    // Use Event Delegation for dynamically loaded forms via AJAX
    $(document).on('submit', '.sc-quiz-form', function (e) {
        e.preventDefault();

        const $form = $(this);
        // Get aulaId from data attribute in the form
        const aulaId = $form.attr('data-aula-id');

        if (!aulaId) {
            console.error('Aula ID not found on form');
            return;
        }

        const containerId = '#sc_quiz_container_' + aulaId;
        const $container = $(containerId);
        const $btn = $form.find('.sc-btn-submit');
        const $msg = $form.find('.sc-quiz-message');

        // Coletar respostas
        let formData = $form.serializeArray();
        let answers = {};

        formData.forEach(function (field) {
            let match = field.name.match(/q\[(\d+)\]/);
            if (match) {
                let qId = match[1];
                if (field.name.indexOf('[]') !== -1) {
                    if (!answers[qId]) answers[qId] = [];
                    answers[qId].push(field.value);
                } else {
                    answers[qId] = field.value;
                }
            }
        });

        // Validar se respondeu algo
        if (Object.keys(answers).length === 0) {
            alert('Por favor, responda as perguntas antes de enviar.');
            return;
        }

        $btn.prop('disabled', true).text('Processando...');
        $msg.hide().removeClass('success error');

        $.ajax({
            url: quizFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'sistema_cursos_submit_quiz',
                nonce: quizFrontend.nonce,
                aula_id: aulaId,
                answers: answers
            },
            success: function (response) {
                $btn.prop('disabled', false).text('Enviar Respostas');

                if (response.success) {
                    const data = response.data;

                    // Atualizar contador de tentativas no DOM
                    if (data.max_attempts > 0) {
                        $container.find('.attempts-count').text(
                            data.attempts_used + ' de ' + data.max_attempts + ' usadas'
                        );
                        $container.attr('data-used-attempts', data.attempts_used);
                    }

                    if (data.passed) {
                        $msg.html(`
                            <h3>🎉 Parabéns!</h3>
                            <p>Você foi aprovado com <strong>${data.score}%</strong> de acerto.</p>
                            <p>Aula marcada como concluída.</p>
                         `).addClass('success').show();

                        // Esconder o mc-body (questões) e botão submit
                        $form.find('.mc-body').slideUp();
                        $btn.hide();

                        // Emitir evento global para atualizar barra de progresso ou sidebar
                        $(document).trigger('sistema_cursos_lesson_competed', [aulaId]);

                        setTimeout(function () {
                            $('.lista-aulas__item[data-aula-id="' + aulaId + '"] .lista-aulas__item-index').addClass('is-concluida').html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
                            var $btnConcluir = $('.lista-aulas__btn-concluir[data-aula-id="' + aulaId + '"]');
                            if ($btnConcluir.length) {
                                $btnConcluir.addClass('is-concluida');
                                $btnConcluir.find('.lista-aulas__btn-texto').text('Concluído');
                                $btnConcluir.show();
                            }
                        }, 1000);

                    } else {
                        // Verificar se tentativas esgotadas após esta reprovação
                        if (data.attempts_remaining === 0) {
                            $msg.html(`
                                <h3>🚫 Tentativas Esgotadas</h3>
                                <p>Sua nota: <strong>${data.score}%</strong> (Mínimo: ${data.passing_score}%)</p>
                                <p>Você usou todas as <strong>${data.max_attempts}</strong> tentativas permitidas.</p>
                             `).addClass('error').show();

                            // Desabilitar formulário
                            $form.find('.mc-body').slideUp();
                            $btn.prop('disabled', true).text('Tentativas Esgotadas').hide();
                        } else {
                            let tentativasMsg = '';
                            if (data.max_attempts > 0) {
                                tentativasMsg = '<p>Tentativa ' + data.attempts_used + ' de ' + data.max_attempts + '.</p>';
                            }

                            // Ocultar questões e botão de envio
                            $form.find('.mc-body').slideUp();
                            $btn.hide();

                            $msg.html(`
                                <h3>❌ Não foi dessa vez.</h3>
                                <p>Sua nota: <strong>${data.score}%</strong> (Mínimo: ${data.passing_score}%)</p>
                                ${tentativasMsg}
                                <button type="button" class="sc-btn sc-btn-restart" style="margin-top:10px;">Tentar Novamente</button>
                             `).addClass('error').show();

                            // Bind unique click event for restart logic
                            $msg.find('.sc-btn-restart').one('click', function () {
                                $form.find('.mc-body').slideDown();
                                $btn.show();
                                $msg.hide().removeClass('error').empty();
                            });

                            // Sacudir container como feedback visual
                            if ($.fn.effect) {
                                $container.effect('shake');
                            }
                        }
                    }
                } else {
                    // Tratar erro de tentativas esgotadas retornado pelo backend
                    if (response.data && response.data.code === 'attempts_exhausted') {
                        $msg.html(`
                            <h3>🚫 Tentativas Esgotadas</h3>
                            <p>${response.data.message}</p>
                         `).addClass('error').show();

                        $form.find('.mc-body').slideUp();
                        $btn.prop('disabled', true).hide();
                    } else {
                        var errorMsg = (response.data && response.data.message) ? response.data.message : (response.data || 'Falha desconhecida');
                        $msg.text('Erro: ' + errorMsg).addClass('error').show();
                    }
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Enviar Respostas');
                $msg.text('Erro de conexão. Tente novamente.').addClass('error').show();
            }
        });
    });
});
