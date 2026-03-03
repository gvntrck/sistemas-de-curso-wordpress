<?php
/**
 * Snippet para criar o shortcode [estilo-evolucao]
 * Renderiza o questionário "Descubra seu Estilo de Evolução no Chess Broadcasters"
 * baseado no guia de estilos do modelo.html.
 */

function render_estilo_evolucao_shortcode()
{
    ob_start();
    ?>
    <style>
        :root {
            --ee-bg-card: #121212;
            --ee-bg-header: linear-gradient(180deg, #1f1f1f 0%, #161616 100%);
            --ee-text-color: #e0e0e0;
            --ee-text-heading: #fff;
            --ee-border-color: #2a2a2a;
            --ee-accent-color: #FDC110;
            --ee-accent-shadow: rgba(253, 193, 16, 0.2);
            --ee-radius-card: 12px;
            --ee-radius-btn: 6px;
        }

        .ee-quiz-container {
            max-width: 900px;
            margin: 40px auto;
            background-color: var(--ee-bg-card);
            color: var(--ee-text-color);
            border-radius: var(--ee-radius-card);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid var(--ee-border-color);
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
        }

        .ee-header {
            background: var(--ee-bg-header);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid var(--ee-border-color);
        }

        .ee-demo-title {
            color: var(--ee-accent-color);
            text-align: center;
            margin: 0 0 10px 0;
            font-size: 1.5rem !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: bold;
        }

        .ee-subtitle {
            color: #888;
            margin: 0;
            font-size: 1.1rem;
        }

        .ee-body {
            padding: 40px;
        }

        .ee-intro {
            text-align: center;
            margin-bottom: 40px;
            font-size: 1.1rem;
            color: #ccc;
        }

        .ee-question {
            margin-bottom: 35px;
            background: rgba(255, 255, 255, 0.02);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #1f1f1f;
        }

        .ee-question h3 {
            color: var(--ee-text-heading);
            font-size: 1.25rem;
            margin-top: 0;
            margin-bottom: 20px;
        }

        .ee-option {
            display: block;
            background: #1a1a1a;
            border: 1px solid var(--ee-border-color);
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #e0e0e0;
            font-size: 1rem;
            position: relative;
        }

        .ee-option:hover {
            border-color: #444;
            background: #222;
            transform: translateX(5px);
        }

        .ee-option input[type="radio"] {
            display: none;
        }

        .ee-option.selected {
            border-color: var(--ee-accent-color);
            box-shadow: 0 0 0 2px var(--ee-accent-shadow);
            background: #151515;
            color: #fff;
            transform: translateX(5px);
        }

        .ee-option.selected::before {
            content: "✓";
            position: absolute;
            right: 20px;
            color: var(--ee-accent-color);
            font-weight: bold;
        }

        .ee-footer {
            background-color: #161616;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid var(--ee-border-color);
        }

        .ee-btn-save {
            background-color: var(--ee-accent-color);
            color: #000;
            border: none;
            padding: 15px 40px;
            font-size: 1.1rem;
            font-weight: 700;
            border-radius: var(--ee-radius-btn);
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 15px var(--ee-accent-shadow);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ee-btn-save:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
            box-shadow: 0 6px 20px rgba(253, 193, 16, 0.4);
        }

        .ee-result-container {
            display: none;
            text-align: center;
            padding: 60px 40px;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .ee-result-title {
            color: var(--ee-accent-color);
            font-size: 2.5rem;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .ee-result-desc {
            font-size: 1.3rem;
            color: #e0e0e0;
            line-height: 1.8;
            max-width: 700px;
            margin: 0 auto;
        }

        .ee-error {
            color: #ff5252;
            text-align: center;
            margin-bottom: 20px;
            font-weight: bold;
            display: none;
            padding: 15px;
            background: rgba(255, 82, 82, 0.1);
            border-radius: 6px;
            border: 1px solid rgba(255, 82, 82, 0.3);
        }

        .ee-error.active {
            display: block;
        }

        /* Pontuação box setup */
        .ee-scoring-rules {
            margin-top: 40px;
            padding: 20px;
            background: #161616;
            border-radius: 8px;
            border: 1px dashed #333;
            text-align: left;
            font-size: 0.95rem;
            color: #aaa;
        }

        .ee-scoring-rules h4 {
            color: #fff;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .ee-scoring-rules ul {
            padding-left: 20px;
            margin: 0;
        }

        .ee-scoring-rules li {
            margin-bottom: 8px;
        }
    </style>

    <div class="ee-quiz-wrapper-outer">
        <div class="ee-preloader-instance"
            style="text-align: center; padding: 40px; color: var(--ee-accent-color); font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; font-size: 1.2rem; display: flex; justify-content: center; align-items: center; min-height: 200px; font-weight: bold; animation: fadeIn 0.5s ease;">
            ⏳ Carregando...
        </div>

        <div class="ee-quiz-container ee-quiz-container-instance" style="display: none;">
            <div class="ee-header">
                <h1 class="ee-demo-title">🧭 Descubra seu Estilo de Evolução</h1>
                <p class="ee-subtitle">No Chess Broadcasters</p>
            </div>

            <div class="ee-body ee-quiz-body">
                <p class="ee-intro">
                    Responda as 5 perguntas abaixo e veja qual perfil representa melhor o seu jeito de aprender e crescer:
                </p>

                <form class="ee-quiz-form">
                    <!-- Pergunta 1 -->
                    <div class="ee-question">
                        <h3>1. Quando eu quero aprender algo novo...</h3>
                        <label class="ee-option">
                            <input type="radio" name="q1" value="A"> A) Prefiro ver o passo a passo e colocar em prática
                            logo.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q1" value="B"> B) Gosto de conversar sobre o assunto, trocar ideias
                            com
                            outras pessoas.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q1" value="C"> C) Quero entender profundamente o conceito e o “porquê”
                            das
                            coisas.
                        </label>
                    </div>

                    <!-- Pergunta 2 -->
                    <div class="ee-question">
                        <h3>2. Durante as Aulas da Recorrência, eu...</h3>
                        <label class="ee-option">
                            <input type="radio" name="q2" value="A"> A) Já anoto as tarefas e penso em como aplicar no meu
                            negócio.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q2" value="B"> B) Me empolgo com as interações e comentários do grupo.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q2" value="C"> C) Curto ouvir a explicação e conectar os pontos
                            conceituais.
                        </label>
                    </div>

                    <!-- Pergunta 3 -->
                    <div class="ee-question">
                        <h3>3. Quando enfrento um desafio no meu negócio...</h3>
                        <label class="ee-option">
                            <input type="radio" name="q3" value="A"> A) Sigo um checklist ou peço um passo a passo pra
                            resolver.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q3" value="B"> B) Falo com alguém que já passou por isso pra trocar
                            experiências.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q3" value="C"> C) Assisto vídeos ou leio materiais pra entender a
                            causa do
                            problema.
                        </label>
                    </div>

                    <!-- Pergunta 4 -->
                    <div class="ee-question">
                        <h3>4. O que mais me motiva a continuar no RR é...</h3>
                        <label class="ee-option">
                            <input type="radio" name="q4" value="A"> A) Ver o progresso real com base na execução das
                            tarefas.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q4" value="B"> B) Estar com a tribo, sentir a energia e aprender em
                            grupo.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q4" value="C"> C) Aprender novas estratégias e teorias que ampliam
                            minha
                            visão.
                        </label>
                    </div>

                    <!-- Pergunta 5 -->
                    <div class="ee-question">
                        <h3>5. Quando recebo uma orientação nova...</h3>
                        <label class="ee-option">
                            <input type="radio" name="q5" value="A"> A) Quero testar imediatamente.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q5" value="B"> B) Quero discutir com o grupo ou com o mentor.
                        </label>
                        <label class="ee-option">
                            <input type="radio" name="q5" value="C"> C) Quero refletir, entender o raciocínio e só depois
                            aplicar.
                        </label>
                    </div>
                </form>

                <div class="ee-error-msg ee-error">⚠️ Por favor, responda todas as opções antes de ver o resultado.
                </div>
            </div>

            <div class="ee-footer ee-quiz-footer">
                <button class="ee-btn-save ee-btn-submit" type="button">Ver Meu Resultado</button>
            </div>

            <!-- Seção de Resultado -->
            <div class="ee-result-container ee-result-section">
                <h2 class="ee-result-title ee-res-title"></h2>
                <p class="ee-result-desc ee-res-desc"></p>

                <div class="ee-scoring-rules">
                    <h4>💡 Como a pontuação funciona:</h4>
                    <p>O teste soma 1 ponto para cada letra escolhida. A sua maior pontuação define seu perfil principal:
                    </p>
                    <ul>
                        <li><strong>Mais respostas A</strong> → FAZEDOR 🧱 (aprende na prática, checklist e execução)</li>
                        <li><strong>Mais respostas B</strong> → FESTEIRO 🎉 (aprende na troca, comunidade e eventos)</li>
                        <li><strong>Mais respostas C</strong> → APRENDEDOR 📚 (aprende com conceitos e fundamentos)</li>
                    </ul>
                </div>

                <div style="margin-top: 40px;">
                    <button class="ee-btn-save ee-btn-retry" type="button"
                        style="background-color: transparent; border: 2px solid var(--ee-accent-color); color: var(--ee-accent-color);">Fazer
                        o Teste Novamente</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        ; (function () {
            function initEvolutionQuiz(wrapperEl) {
                // Previne dupla inicialização
                if (wrapperEl.hasAttribute('data-ee-initialized')) return;
                wrapperEl.setAttribute('data-ee-initialized', 'true');

                const parent = wrapperEl.parentElement;
                const preloader = parent.querySelector('.ee-preloader-instance');

                // Delay de 2 segundos para apresentar o shortcode
                setTimeout(function () {
                    if (preloader) preloader.style.display = 'none';
                    wrapperEl.style.display = 'block';
                    wrapperEl.style.animation = 'fadeIn 0.5s ease';
                }, 2000);

                const form = wrapperEl.querySelector('.ee-quiz-form');
                if (!form) return;

                const options = form.querySelectorAll('.ee-option');
                const submitBtn = wrapperEl.querySelector('.ee-btn-submit');
                const retryBtn = wrapperEl.querySelector('.ee-btn-retry');
                const bodySection = wrapperEl.querySelector('.ee-quiz-body');
                const footerSection = wrapperEl.querySelector('.ee-quiz-footer');
                const resultSection = wrapperEl.querySelector('.ee-result-section');
                const errorMsg = wrapperEl.querySelector('.ee-error-msg');
                const resTitle = wrapperEl.querySelector('.ee-res-title');
                const resDesc = wrapperEl.querySelector('.ee-res-desc');

                // Lógica visual para seleção de opções
                options.forEach(option => {
                    const radio = option.querySelector('input[type="radio"]');
                    if (!radio) return;

                    radio.addEventListener('change', function () {
                        // Remove classe de selecionar de todas as opções da mesma pergunta
                        const questionName = this.name;
                        const allQuestionOptions = form.querySelectorAll(`input[name="${questionName}"]`);
                        allQuestionOptions.forEach(opt => {
                            opt.closest('.ee-option').classList.remove('selected');
                        });

                        // Adiciona classe na opção escolhida
                        if (this.checked) {
                            option.classList.add('selected');
                        }

                        // Oculta mensagem de erro se as 5 perguntas foram respondidas
                        const formData = new FormData(form);
                        let answeredCount = 0;
                        for (let value of formData.values()) {
                            answeredCount++;
                        }
                        if (answeredCount === 5) {
                            errorMsg.classList.remove('active');
                        }
                    });
                });

                // Lógica ao clicar no botão "Ver Meu Resultado"
                if (submitBtn) {
                    submitBtn.addEventListener('click', function () {
                        const formData = new FormData(form);
                        let counts = { 'A': 0, 'B': 0, 'C': 0 };
                        let answeredCount = 0;

                        for (let value of formData.values()) {
                            if (counts[value] !== undefined) {
                                counts[value]++;
                            }
                            answeredCount++;
                        }

                        // Verifica se faltou alguma pergunta a ser respondida
                        if (answeredCount < 5) {
                            errorMsg.classList.add('active');
                            return;
                        }

                        // Determina o resultado através da maior escolha
                        let maxCount = 0;
                        let resultType = 'A';

                        for (let type in counts) {
                            if (counts[type] > maxCount) {
                                maxCount = counts[type];
                                resultType = type;
                            }
                        }

                        let resultTitleText = "";
                        let resultDescText = "";

                        if (resultType === 'A') {
                            resultTitleText = "FAZEDOR 🧱";
                            resultDescText = "Você aprende na prática, focando em checklist e execução rápida. Seu estilo é colocar a mão na massa e ver resultados através da ação!";
                        } else if (resultType === 'B') {
                            resultTitleText = "FESTEIRO 🎉";
                            resultDescText = "Você aprende na troca, com a comunidade e eventos. Seu estilo é construir conhecimento através de conexões, discussões e vivências em grupo!";
                        } else {
                            resultTitleText = "APRENDEDOR 📚";
                            resultDescText = "Você aprende com conceitos e fundamentos. Seu estilo é entender profundamente o \"porquê\" das coisas antes de agir, buscando estratégias sólidas!";
                        }

                        // Atualiza tela com o resultado
                        if (resTitle) resTitle.innerText = resultTitleText;
                        if (resDesc) resDesc.innerText = resultDescText;

                        if (bodySection) bodySection.style.display = 'none';
                        if (footerSection) footerSection.style.display = 'none';
                        if (resultSection) resultSection.style.display = 'block';

                        // Volta para o topo do quiz suavemente
                        wrapperEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }

                // Lógica ao clicar no botão "Fazer o Teste Novamente"
                if (retryBtn) {
                    retryBtn.addEventListener('click', function () {
                        form.reset(); // Reseta formulário
                        options.forEach(opt => opt.classList.remove('selected')); // Reseta visual

                        if (resultSection) resultSection.style.display = 'none'; // Esconde resultado
                        if (bodySection) bodySection.style.display = 'block'; // Mostra perguntas
                        if (footerSection) footerSection.style.display = 'block'; // Mostra botão

                        wrapperEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    });
                }
            }

            // Iniciar em elementos já existentes no DOM (caso de load normal)
            document.querySelectorAll('.ee-quiz-container-instance').forEach(initEvolutionQuiz);

            // Observador via Polling (funciona em qualquer cenário de AJAX agressivo/innerHTML)
            if (!window.eeQuizPollerInit) {
                window.eeQuizPollerInit = true;
                setInterval(function() {
                    document.querySelectorAll('.ee-quiz-container-instance:not([data-ee-initialized="true"])').forEach(initEvolutionQuiz);
                }, 500); // Verifica a cada meio segundo se há um novo quiz não inicializado na tela
            }
        })();
    </script>
    <?php
    return ob_get_clean();
}
// Registra o shortcode
add_shortcode('estilo-evolucao', 'render_estilo_evolucao_shortcode');
