jQuery(document).ready(function ($) {
    const root = $('#sistema-cursos-quiz-root');
    const inputJson = $('#aula_quiz_data_json');

    // Estado inicial
    let quizData = {
        enabled: false,
        passing_score: 70,
        max_attempts: 0,
        questions: []
    };

    // Carregar dados salvos via localize_script
    if (typeof quizBuilderData !== 'undefined' && quizBuilderData.initialData) {
        // Mesclar com defaults para garantir estrutura
        quizData = { ...quizData, ...quizBuilderData.initialData };
    }

    function render() {
        const html = `
            <div class="quiz-header">
                <div class="quiz-config-row">
                    <div class="quiz-config-item">
                        <label>
                            <input type="checkbox" id="quiz_enabled" ${quizData.enabled ? 'checked' : ''}>
                            Habilitar Quiz nesta Aula
                        </label>
                    </div>
                </div>

                <div id="quiz_settings_panel" style="display: ${quizData.enabled ? 'block' : 'none'}; margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">
                    <div class="quiz-config-row">
                        <div class="quiz-config-item">
                            <label>Nota Mínima (%)</label>
                            <input type="number" id="quiz_score" value="${quizData.passing_score}" min="0" max="100" style="width: 80px;">
                        </div>
                        <div class="quiz-config-item">
                            <label>Max. Tentativas (0 = Ilimitado)</label>
                            <input type="number" id="quiz_attempts" value="${quizData.max_attempts}" min="0" style="width: 80px;">
                        </div>
                    </div>
                </div>
            </div>

            <div id="quiz_questions_panel" style="display: ${quizData.enabled ? 'block' : 'none'};">
                <h3>Perguntas</h3>
                <div class="question-list" id="question_list_container">
                    ${renderQuestions()}
                </div>

                <button type="button" class="button button-primary add-question-btn">
                    <span class="dashicons dashicons-plus-alt2"></span> Adicionar Pergunta
                </button>
            </div>
        `;

        root.html(html);
        updateJson();
    }

    function renderQuestions() {
        if (!quizData.questions || quizData.questions.length === 0) {
            return '<div class="quiz-empty-state">Nenhuma pergunta adicionada. Clique abaixo para começar.</div>';
        }

        return quizData.questions.map((q, index) => `
            <div class="question-item" data-index="${index}">
                <div class="question-header">
                    <span class="question-title-preview">#${index + 1} - ${q.title || '(Sem título)'}</span>
                    <div class="question-actions-top">
                        <button type="button" class="button button-small toggle-collapse">Editar</button>
                        <button type="button" class="button button-small button-link-delete delete-question">Excluir</button>
                    </div>
                </div>
                <div class="question-body">
                    <div class="quiz-config-row" style="margin-bottom: 10px;">
                        <input type="text" class="widefat question-title-input" value="${escapeHtml(q.title)}" placeholder="Digite a pergunta aqui...">
                    </div>
                    
                    <div class="quiz-config-row">
                         <div class="quiz-config-item">
                            <label>Pontos</label>
                            <input type="number" class="question-points" value="${q.points}" min="1" style="width: 60px;">
                         </div>
                         <div class="quiz-config-item">
                            <label>Tipo</label>
                            <select class="question-type">
                                <option value="single" ${q.type === 'single' ? 'selected' : ''}>Única Escolha</option>
                                <option value="multiple" ${q.type === 'multiple' ? 'selected' : ''}>Múltipla Escolha</option>
                            </select>
                         </div>
                    </div>

                    <div class="option-list">
                        <label style="font-weight:bold; display:block; margin-bottom:5px;">Alternativas:</label>
                        ${renderOptions(q, index)}
                        <button type="button" class="button button-small add-option-btn">Adicionar Opção</button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    function renderOptions(question, qIndex) {
        if (!question.options) return '';

        return question.options.map((opt, oIndex) => `
            <div class="option-item" data-oindex="${oIndex}">
                <span class="correct-toggle dashicons dashicons-yes ${opt.is_correct ? 'active' : ''}" title="Marcar como correta"></span>
                <input type="text" class="widefat option-text" value="${escapeHtml(opt.text)}" placeholder="Texto da opção">
                <button type="button" class="button button-small button-link-delete delete-option"><span class="dashicons dashicons-trash"></span></button>
            </div>
        `).join('');
    }

    function updateJson() {
        inputJson.val(JSON.stringify(quizData));
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    // --- Event Listeners ---

    // Toggle Enable
    root.on('change', '#quiz_enabled', function () {
        quizData.enabled = $(this).is(':checked');
        render(); // Re-render to show/hide panels
    });

    // Update Global Settings
    root.on('change', '#quiz_score', function () { quizData.passing_score = parseInt($(this).val()); updateJson(); });
    root.on('change', '#quiz_attempts', function () { quizData.max_attempts = parseInt($(this).val()); updateJson(); });

    // Add Question
    root.on('click', '.add-question-btn', function () {
        quizData.questions.push({
            id: Date.now().toString(),
            title: '',
            type: 'single',
            points: 10,
            options: [
                { id: 'o1', text: '', is_correct: false },
                { id: 'o2', text: '', is_correct: false }
            ]
        });
        render();
    });

    // Delete Question
    root.on('click', '.delete-question', function (e) {
        e.stopPropagation();
        if (!confirm('Tem certeza?')) return;
        const index = $(this).closest('.question-item').data('index');
        quizData.questions.splice(index, 1);
        render();
    });

    // Toggle Collapse
    root.on('click', '.toggle-collapse', function (e) {
        e.stopPropagation();
        $(this).closest('.question-item').toggleClass('collapsed');
    });

    // Update Question Title
    root.on('input', '.question-title-input', function () {
        const index = $(this).closest('.question-item').data('index');
        quizData.questions[index].title = $(this).val();
        $(this).closest('.question-item').find('.question-title-preview').text('#' + (index + 1) + ' - ' + $(this).val());
        updateJson();
    });

    // Update Points / Type
    root.on('change', '.question-points', function () {
        const index = $(this).closest('.question-item').data('index');
        quizData.questions[index].points = parseInt($(this).val());
        updateJson();
    });

    root.on('change', '.question-type', function () {
        const index = $(this).closest('.question-item').data('index');
        quizData.questions[index].type = $(this).val();
        updateJson();
    });

    // Add Option
    root.on('click', '.add-option-btn', function () {
        const qIndex = $(this).closest('.question-item').data('index');
        quizData.questions[qIndex].options.push({
            id: Date.now().toString(),
            text: '',
            is_correct: false
        });
        render(); // Re-render to show new option
    });

    // Delete Option
    root.on('click', '.delete-option', function () {
        const qIndex = $(this).closest('.question-item').data('index');
        const oIndex = $(this).closest('.option-item').data('oindex');
        quizData.questions[qIndex].options.splice(oIndex, 1);
        render();
    });

    // Update Option Text
    root.on('input', '.option-text', function () {
        const qIndex = $(this).closest('.question-item').data('index');
        const oIndex = $(this).closest('.option-item').data('oindex');
        quizData.questions[qIndex].options[oIndex].text = $(this).val();
        updateJson();
    });

    // Toggle Correct Option
    root.on('click', '.correct-toggle', function () {
        const qIndex = $(this).closest('.question-item').data('index');
        const oIndex = $(this).closest('.option-item').data('oindex');
        const question = quizData.questions[qIndex];

        if (question.type === 'single') {
            // Uncheck others
            question.options.forEach(o => o.is_correct = false);
            question.options[oIndex].is_correct = true;
        } else {
            // Toggle
            question.options[oIndex].is_correct = !question.options[oIndex].is_correct;
        }
        render();
    });

    // Inicializar
    render();
});
