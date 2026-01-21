<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Quiz_Process
{
    /**
     * class-quiz-process.php
     *
     * Gerencia a renderização e o processamento do Quiz no frontend.
     *
     * @package SistemaCursos
     * @version 1.0.0
     */
    public function __construct()
    {
        add_action('wp_ajax_sistema_cursos_submit_quiz', [$this, 'ajax_submit_quiz']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets()
    {
        // Só carregar se estiver numa aula (simplificado, poderia checar has_shortcode)
        if (is_singular('aula') || is_singular('curso') || is_page()) {
            wp_register_script(
                'sistema-cursos-quiz-frontend',
                plugins_url('../assets/js/frontend-quiz.js', __FILE__),
                ['jquery'],
                SISTEMA_CURSOS_VERSION,
                true
            );

            wp_enqueue_script('sistema-cursos-quiz-frontend');

            wp_register_style(
                'sistema-cursos-quiz-frontend-css',
                plugins_url('../assets/css/frontend-quiz.css', __FILE__),
                [],
                SISTEMA_CURSOS_VERSION
            );

            wp_enqueue_style('sistema-cursos-quiz-frontend-css');

            // Localize script globally to ensure base params exist
            wp_localize_script('sistema-cursos-quiz-frontend', 'quizFrontend', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('sistema_cursos_quiz_submit')
            ]);
        }
    }

    /**
     * Pega os dados do quiz da aula
     */
    public static function get_quiz_data($aula_id)
    {
        $data = get_post_meta($aula_id, '_aula_quiz_data', true);
        if (empty($data) || !is_array($data) || empty($data['enabled'])) {
            return false;
        }
        return $data;
    }

    /**
     * Verifica se o usuário já passou no quiz desta aula
     */
    public static function user_passed($user_id, $aula_id)
    {
        if ($user_id <= 0)
            return false;

        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        // Busca pontuação na tabela (assumindo que só salva lá se passar ou tentaiva)
        // OBS: Na nossa lógica simplificada, se está na tabela 'progresso_aluno' é pq concluiu com sucesso?
        // NÃO NECESSARIAMENTE. Se implementamos quiz, a tabela progresso DEVE ter a nota.
        // Mas se o aluno concluiu ANTES do quiz existir, ele está aprovado? Sim.

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT pontuacao, data_conclusao FROM $table_name WHERE user_id = %d AND aula_id = %d",
            $user_id,
            $aula_id
        ));

        if (!$row)
            return false;

        // Se tem registro, verificamos.
        // Se pontuacao > 0, ok.
        // Se pontuacao == 0 mas data_conclusao existe (histórico legado), consideramos aprovado?
        // Sim, para não bloquear alunos antigos.
        return true;
    }

    /**
     * Renderiza o HTML do Quiz
     */
    public static function render_quiz($aula_id)
    {
        $quiz = self::get_quiz_data($aula_id);
        if (!$quiz)
            return '';

        // Enqueue handled in enqueue_assets
        // Localize is also global now, but we can keep aulaId here if needed via data attributes, which is cleaner for ajax.
        // But for compatibility let's keep it safe.


        $questions = $quiz['questions'] ?? [];
        if (empty($questions))
            return '';

        ob_start();
        ?>
        <div class="mc-container sc-quiz-container" id="sc_quiz_container_<?php echo $aula_id; ?>">
            <!-- Intro/Header do Quiz -->
            <div class="mc-header sc-quiz-intro">
                <h3>📝 Avaliação de Conhecimento</h3>
                <p>Para concluir esta aula, você precisa responder corretamente a este questionário.</p>
                <ul>
                    <li><strong>Questões:</strong>
                        <?php echo count($questions); ?>
                    </li>
                    <li><strong>Nota Mínima:</strong>
                        <?php echo $quiz['passing_score']; ?>%
                    </li>
                    <?php if ($quiz['max_attempts'] > 0): ?>
                        <li><strong>Tentativas Restantes:</strong> <span class="attempts-count">Calculando...</span></li>
                    <?php endif; ?>
                </ul>
                <button type="button" class="mc-btn-save sc-btn sc-btn-start"
                    onclick="document.getElementById('sc_quiz_form_<?php echo $aula_id; ?>').style.display='block'; this.parentElement.style.display='none';">Iniciar
                    Avaliação</button>
            </div>

            <!-- Formulário do Quiz -->
            <form id="sc_quiz_form_<?php echo $aula_id; ?>" class="sc-quiz-form" style="display:none;" data-aula-id="<?php echo $aula_id; ?>">
                <div class="mc-body">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="sc-quiz-question" data-type="<?php echo esc_attr($q['type']); ?>">
                            <div class="sc-quiz-q-header">
                                <span class="sc-quiz-q-number">
                                    <?php echo $index + 1; ?>.
                                </span>
                                <span class="sc-quiz-q-text">
                                    <?php echo esc_html($q['title']); ?>
                                </span>
                                <span class="sc-quiz-q-points">(
                                    <?php echo $q['points']; ?> pontos)
                                </span>
                            </div>
                            <div class="sc-quiz-q-options">
                                <?php
                                $options = $q['options'] ?? [];
                                foreach ($options as $opt):
                                    $inputId = 'q' . $index . '_' . $opt['id'];
                                    $inputName = 'q[' . $q['id'] . ']';
                                    if ($q['type'] === 'multiple') {
                                        $inputName .= '[]';
                                    }
                                ?>
                                    <label class="sc-quiz-option" for="<?php echo $inputId; ?>">
                                        <input type="<?php echo $q['type'] === 'multiple' ? 'checkbox' : 'radio'; ?>"
                                            name="<?php echo $inputName; ?>" value="<?php echo esc_attr($opt['id']); ?>"
                                            id="<?php echo $inputId; ?>">
                                        <span class="sc-quiz-opt-text">
                                            <?php echo esc_html($opt['text']); ?>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Footer do Quiz -->
                <div class="mc-footer sc-quiz-footer">
                    <div class="sc-quiz-message"></div>
                    <button type="submit" class="mc-btn-save sc-btn sc-btn-submit">Enviar Respostas</button>
                </div>
            </form>
        </div>

        <?php
        return ob_get_clean();
    }

    /**
     * AJAX: Processa a submissão
     */
    public function ajax_submit_quiz()
    {
        check_ajax_referer('sistema_cursos_quiz_submit', 'nonce');

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            wp_send_json_error('Você precisa estar logado.');
        }

        $aula_id = isset($_POST['aula_id']) ? intval($_POST['aula_id']) : 0;
        $respostas = isset($_POST['answers']) ? $_POST['answers'] : []; // Array [q_id => [opt_ids]]

        $quiz_data = self::get_quiz_data($aula_id);
        if (!$quiz_data) {
            wp_send_json_error('Quiz inválido.');
        }

        // 1. Calcular Nota
        $total_points = 0;
        $user_points = 0;

        foreach ($quiz_data['questions'] as $q) {
            $q_id = $q['id'];
            $q_points = intval($q['points']);
            $total_points += $q_points;

            // Gabarito
            $correct_opts = [];
            foreach ($q['options'] as $opt) {
                if (!empty($opt['is_correct'])) {
                    $correct_opts[] = $opt['id'];
                }
            }

            // Resposta do Usuário
            // O frontend manda um array ou string dependendo do jQuery serialize. 
            // Precisamos garantir que seja array de IDs.
            // Formato esperado das respostas: q_id => opt_id (single) ou [opt_id1, opt_id2] (multiple)

            $user_answer = $respostas[$q_id] ?? null;
            if (!$user_answer)
                continue; // Não respondeu

            if (!is_array($user_answer)) {
                $user_answer = [$user_answer];
            }

            // Comparação
            // Simplificação: Para ganhar ponto, tem que acertar EXATAMENTE (todas as corretas e nenhuma errada)
            // Lógica de Sets: Se diff(gabarito, user) é vazio E diff(user, gabarito) é vazio.

            $diff1 = array_diff($correct_opts, $user_answer);
            $diff2 = array_diff($user_answer, $correct_opts);

            if (empty($diff1) && empty($diff2)) {
                $user_points += $q_points;
            }
        }

        $score_percent = ($total_points > 0) ? round(($user_points / $total_points) * 100) : 0;
        $passed = $score_percent >= intval($quiz_data['passing_score']);

        // 2. Salvar no Banco (Independente se passou ou não, podemos salvar tentativa no futuro)
        // Por enquanto, salvamos só se passar para marcar conclusão.

        if ($passed) {
            // Marcar aula como concluída
            if (class_exists('System_Cursos_Progress')) {
                // Precisamos do curso_id. Podemos pegar da aula.
                $curso_id = get_post_meta($aula_id, 'curso', true);

                global $wpdb;
                $table_name = $wpdb->prefix . 'progresso_aluno';

                // Verifica se já existe (update ou insert)
                // Como usaremos DB direto para salvar pontuação...

                // Upsert manual
                $existing = $wpdb->get_row($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE user_id = %d AND aula_id = %d",
                    $user_id,
                    $aula_id
                ));

                if ($existing) {
                    $wpdb->update(
                        $table_name,
                        ['pontuacao' => $score_percent, 'data_conclusao' => current_time('mysql')],
                        ['id' => $existing->id]
                    );
                } else {
                    $wpdb->insert(
                        $table_name,
                        [
                            'user_id' => $user_id,
                            'aula_id' => $aula_id,
                            'curso_id' => $curso_id ?: 0,
                            'pontuacao' => $score_percent,
                            'tentativas' => 1, // Reset ou incremento
                            'data_conclusao' => current_time('mysql')
                        ]
                    );
                }

                // Atualizar meta de progresso do curso
                if ($curso_id) {
                    System_Cursos_Progress::update_user_progress($user_id, $curso_id);
                }
            }
        } else {
            // Incrementar tentativas (Futuro)
        }

        wp_send_json_success([
            'passed' => $passed,
            'score' => $score_percent,
            'points' => $user_points,
            'total' => $total_points,
            'passing_score' => intval($quiz_data['passing_score'])
        ]);
    }
}
