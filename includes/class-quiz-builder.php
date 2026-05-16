<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Quiz_Builder
{
    /**
     * class-quiz-builder.php
     *
     * Gerencia a criação e edição de Quizzes dentro da edição da Aula.
     * Adiciona um metabox com interface React-like (feita em jQuery/Simples) para
     * adicionar perguntas, respostas e configurações.
     *
     * @package SistemaCursos
     * @version 1.0.0
     */
    public function __construct()
    {
        add_action('add_meta_boxes', [$this, 'add_metabox']);
        add_action('save_post', [$this, 'save_quiz_data']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function enqueue_scripts($hook)
    {
        global $post;

        if (!$post || get_post_type($post) !== 'aula') {
            return;
        }

        if ($hook == 'post-new.php' || $hook == 'post.php') {
            wp_enqueue_script(
                'sistema-cursos-quiz-builder',
                plugins_url('../assets/js/admin-quiz-builder.js', __FILE__),
                ['jquery', 'jquery-ui-sortable'],
                sistema_cursos_asset_version('assets/js/admin-quiz-builder.js'),
                true
            );

            wp_enqueue_style(
                'sistema-cursos-quiz-builder-css',
                plugins_url('../assets/css/admin-quiz-builder.css', __FILE__),
                [],
                sistema_cursos_asset_version('assets/css/admin-quiz-builder.css')
            );

            // Passar dados iniciais para o JS
            $quiz_data = get_post_meta($post->ID, '_aula_quiz_data', true);
            if (empty($quiz_data) || !is_array($quiz_data)) {
                $quiz_data = [
                    'enabled' => false,
                    'passing_score' => 70,
                    'max_attempts' => 0, // 0 = ilimitado
                    'questions' => []
                ];
            }

            wp_localize_script('sistema-cursos-quiz-builder', 'quizBuilderData', [
                'initialData' => $quiz_data
            ]);
        }
    }

    public function add_metabox()
    {
        add_meta_box(
            'aula_quiz_builder',
            'Avaliação / Quiz da Aula',
            [$this, 'render_metabox'],
            'aula',
            'normal',
            'high' // Fica logo abaixo do editor principal ou acima de outros metads
        );
    }

    public function render_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_quiz', 'sistema_cursos_quiz_nonce');
        ?>
        <div id="sistema-cursos-quiz-root">
            <!-- O JS vai renderizar a interface aqui -->
            <p>Carregando construtor de quiz...</p>
        </div>

        <!-- Campo hidden para salvar o JSON final -->
        <input type="hidden" name="aula_quiz_data_json" id="aula_quiz_data_json" value="">
        <?php
    }

    public function save_quiz_data($post_id)
    {
        // Verificações de segurança
        if (!isset($_POST['sistema_cursos_quiz_nonce']) || !wp_verify_nonce($_POST['sistema_cursos_quiz_nonce'], 'sistema_cursos_save_quiz')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Salvar JSON
        if (isset($_POST['aula_quiz_data_json'])) {
            // Decodifica para sanitizar recursivamente, se necessário, ou confia no JSON.
            // Para segurança extra, é bom decodificar e limpar HTML das perguntas.
            $json = stripslashes($_POST['aula_quiz_data_json']);
            $data = json_decode($json, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Sanitização Básica
                $clean_data = [
                    'enabled' => filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    'passing_score' => intval($data['passing_score'] ?? 70),
                    'max_attempts' => intval($data['max_attempts'] ?? 0),
                    'questions' => []
                ];

                if (isset($data['questions']) && is_array($data['questions'])) {
                    foreach ($data['questions'] as $q) {
                        $clean_q = [
                            'id' => sanitize_text_field($q['id'] ?? uniqid()),
                            'title' => wp_kses_post($q['title'] ?? ''),
                            'type' => $q['type'] === 'multiple' ? 'multiple' : 'single',
                            'points' => intval($q['points'] ?? 10),
                            'options' => []
                        ];

                        if (isset($q['options']) && is_array($q['options'])) {
                            foreach ($q['options'] as $opt) {
                                $clean_q['options'][] = [
                                    'id' => sanitize_text_field($opt['id'] ?? uniqid()),
                                    'text' => sanitize_text_field($opt['text'] ?? ''),
                                    'is_correct' => filter_var($opt['is_correct'] ?? false, FILTER_VALIDATE_BOOLEAN)
                                ];
                            }
                        }
                        $clean_data['questions'][] = $clean_q;
                    }
                }

                update_post_meta($post_id, '_aula_quiz_data', $clean_data);
            }
        }
    }
}
