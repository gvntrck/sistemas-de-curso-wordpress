jQuery(document).ready(function ($) {
    if (typeof quizFrontend === 'undefined') return;

    const aulaId = quizFrontend.aulaId;
    const formId = '#sc_quiz_form_' + aulaId;
    const containerId = '#sc_quiz_container_' + aulaId;

    $(document).on('submit', formId, function (e) {
        e.preventDefault();

        const $form = $(this);
        const $btn = $form.find('.sc-btn-submit');
        const $msg = $form.find('.sc-quiz-message');

        // Coletar respostas
        // Estrutra: name="q[QID]" ou name="q[QID][]"
        // serializeArray retorna [{name: "q[123]", value: "o1"}]

        let formData = $form.serializeArray();
        let answers = {};

        formData.forEach(function (field) {
            // Regex para extrair QID
            // q[173740...] ou q[173740...][]
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
                    if (data.passed) {
                        $msg.html(`
                            <h3>🎉 Parabéns!</h3>
                            <p>Você foi aprovado com <strong>${data.score}%</strong> de acerto.</p>
                            <p>Aula marcada como concluída.</p>
                         `).addClass('success').show();

                        // Esconder o mc-body (questões) e deixar apenas a mensagem
                        $form.find('.mc-body').slideUp();


                        // Emitir evento global para atualizar barra de progresso ou sidebar se necessário
                        $(document).trigger('sistema_cursos_lesson_competed', [aulaId]);

                        // Pequeno delay e atualiza a UI da lista (reloading ou manipulando DOM)
                        setTimeout(function () {
                            // Se estiver usando o shortcode de lista, podemos tentar atualizar a classe da sidebar
                            $('.lista-aulas__item[data-aula-id="' + aulaId + '"] .lista-aulas__item-index').addClass('is-concluida').html('<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>');
                            // Se houver botão de concluir que estava escondido, atualizar
                        }, 1000);

                    } else {
                        $msg.html(`
                            <h3>❌ Não foi dessa vez.</h3>
                            <p>Sua nota: <strong>${data.score}%</strong> (Mínimo: ${data.passing_score}%)</p>
                            <p>Tente novamente.</p>
                         `).addClass('error').show();

                        // Sacudir
                        $(containerId).effect('shake');
                    }
                } else {
                    $msg.text('Erro: ' + (response.data || 'Falha desconhecida')).addClass('error').show();
                }
            },
            error: function () {
                $btn.prop('disabled', false).text('Enviar Respostas');
                $msg.text('Erro de conexão. Tente novamente.').addClass('error').show();
            }
        });
    });
});
