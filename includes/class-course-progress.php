<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Progress
{
    /**
     * class-course-progress.php
     *
     * Gerencia o rastreamento do progresso dos alunos nos cursos.
     * Cria a tabela de progresso no banco de dados, calcula porcentagens de conclusao,
     * e disponibiliza endpoints AJAX para marcar/desmarcar aulas como concluidas.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_action('init', [$this, 'create_table']);
        add_action('wp_ajax_lista_aulas_toggle_concluida', [$this, 'ajax_toggle_concluida']);
        add_action('wp_ajax_sistema_cursos_get_overall_progress', [$this, 'ajax_get_overall_progress']);
    }

    public function create_table()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            aula_id bigint(20) unsigned NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            pontuacao int(3) DEFAULT 0,
            tentativas int(5) DEFAULT 0,
            data_conclusao datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_aula (user_id, aula_id),
            KEY user_id (user_id),
            KEY aula_id (aula_id),
            KEY curso_id (curso_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Retorna se a aula exige aprovacao via quiz e qual a nota minima.
     */
    private static function get_quiz_requirement_for_lesson($aula_id)
    {
        static $cache = [];

        $aula_id = (int) $aula_id;
        if ($aula_id <= 0) {
            return ['requires_quiz_approval' => false, 'passing_score' => 0];
        }

        if (isset($cache[$aula_id])) {
            return $cache[$aula_id];
        }

        $result = [
            'requires_quiz_approval' => false,
            'passing_score' => 0,
        ];

        if (class_exists('System_Cursos_Quiz_Process') && method_exists('System_Cursos_Quiz_Process', 'get_quiz_data')) {
            $quiz_data = System_Cursos_Quiz_Process::get_quiz_data($aula_id);
            $questions = is_array($quiz_data) ? ($quiz_data['questions'] ?? []) : [];

            if (is_array($questions) && !empty($questions)) {
                $result['requires_quiz_approval'] = true;
                $result['passing_score'] = max(0, min(100, (int) ($quiz_data['passing_score'] ?? 0)));
            }
        }

        $cache[$aula_id] = $result;
        return $result;
    }

    /**
     * Determina se um registro de progresso e valido para concluir a aula.
     */
    private static function is_completion_valid($aula_id, $pontuacao)
    {
        $quiz_requirement = self::get_quiz_requirement_for_lesson($aula_id);

        if (!$quiz_requirement['requires_quiz_approval']) {
            return true;
        }

        return (int) $pontuacao >= (int) $quiz_requirement['passing_score'];
    }

    public static function is_lesson_completed($user_id, $aula_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, pontuacao FROM $table_name WHERE user_id = %d AND aula_id = %d",
            $user_id,
            $aula_id
        ));

        if (!$row) {
            return false;
        }

        return self::is_completion_valid($aula_id, (int) $row->pontuacao);
    }

    public static function get_completed_lessons($user_id, $curso_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT aula_id, pontuacao FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        if (empty($results)) {
            return [];
        }

        $completed_lessons = [];
        foreach ($results as $row) {
            $aula_id = (int) ($row->aula_id ?? 0);
            $pontuacao = (int) ($row->pontuacao ?? 0);

            if ($aula_id <= 0) {
                continue;
            }

            if (self::is_completion_valid($aula_id, $pontuacao)) {
                $completed_lessons[] = $aula_id;
            }
        }

        return $completed_lessons;
    }

    public static function update_user_progress($user_id, $curso_id)
    {
        if (!$user_id || !$curso_id) {
            return false;
        }

        // 1. Total de aulas do curso
        $meta_key = 'curso'; // Padrao do plugin
        $args = [
            'post_type' => 'aula',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => $meta_key,
                    'value' => $curso_id,
                    'compare' => '=',
                ],
                [
                    'key' => $meta_key,
                    'value' => '"' . $curso_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $query = new WP_Query($args);
        $total_aulas = $query->found_posts;

        if ($total_aulas == 0) {
            update_user_meta($user_id, "progresso_curso_{$curso_id}", 0);
            return 0;
        }

        // 2. Aulas concluidas validas
        $concluidas = count(self::get_completed_lessons($user_id, $curso_id));

        // 3. Percentual
        $porcentagem = min(100, round(($concluidas / $total_aulas) * 100));

        // 4. Update Meta
        update_user_meta($user_id, "progresso_curso_{$curso_id}", $porcentagem);

        return $porcentagem;
    }

    public function ajax_toggle_concluida()
    {
        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Voce precisa estar logado para marcar aulas como concluidas.']);
        }

        $aula_id = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;
        $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;

        if ($aula_id <= 0) {
            wp_send_json_error(['message' => 'ID da aula invalido.']);
        }

        if (class_exists('System_Cursos_Lesson_Schedule') && System_Cursos_Lesson_Schedule::is_locked_for_user($aula_id, $user_id)) {
            wp_send_json_error([
                'code' => 'lesson_locked',
                'message' => System_Cursos_Lesson_Schedule::get_lock_message($aula_id),
            ]);
        }

        $quiz_requirement = self::get_quiz_requirement_for_lesson($aula_id);
        if ($quiz_requirement['requires_quiz_approval']) {
            wp_send_json_error([
                'code' => 'quiz_required',
                'message' => 'Esta aula possui quiz ativo. Conclua o quiz para validar a aula.',
            ]);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';
        $ja_concluida = self::is_lesson_completed($user_id, $aula_id);

        if ($ja_concluida) {
            $wpdb->delete(
                $table_name,
                ['user_id' => $user_id, 'aula_id' => $aula_id],
                ['%d', '%d']
            );
            self::update_user_progress($user_id, $curso_id);
            wp_send_json_success(['concluida' => false, 'message' => 'Aula desmarcada.']);
        } else {
            $wpdb->insert(
                $table_name,
                [
                    'user_id' => $user_id,
                    'aula_id' => $aula_id,
                    'curso_id' => $curso_id,
                    'data_conclusao' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%s']
            );
            self::update_user_progress($user_id, $curso_id);
            wp_send_json_success(['concluida' => true, 'message' => 'Aula concluida!']);
        }
    }

    public function ajax_get_overall_progress()
    {
        $user_id = get_current_user_id();

        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Voce precisa estar logado para consultar o progresso.']);
        }

        $progress = self::get_overall_progress($user_id);

        wp_send_json_success([
            'progress' => (int) $progress
        ]);
    }

    /**
     * Retorna detalhes do progresso do aluno em um curso:
     * - Total de aulas
     * - Aulas concluidas
     * - Porcentagem
     * - Data da ultima conclusao
     */
    public static function get_course_progress_details($user_id, $curso_id)
    {
        global $wpdb;
        $table_progresso = $wpdb->prefix . 'progresso_aluno';
        $meta_key = 'curso'; // Padrao do plugin

        // 1. Total de aulas do curso
        $args = [
            'post_type' => 'aula',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => $meta_key,
                    'value' => $curso_id,
                    'compare' => '=',
                ],
                [
                    'key' => $meta_key,
                    'value' => '"' . $curso_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ];

        $query = new WP_Query($args);
        $total_aulas = $query->found_posts;

        if ($total_aulas == 0) {
            return [
                'total' => 0,
                'concluidas' => 0,
                'percent' => 0,
                'last_date' => null
            ];
        }

        // 2. Registros de progresso do curso para filtrar conclusoes validas
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT aula_id, pontuacao, data_conclusao FROM $table_progresso WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        $concluidas = 0;
        $last_date = null;
        $last_timestamp = 0;

        foreach ($rows as $row) {
            $aula_id = (int) ($row->aula_id ?? 0);
            $pontuacao = (int) ($row->pontuacao ?? 0);

            if ($aula_id <= 0 || !self::is_completion_valid($aula_id, $pontuacao)) {
                continue;
            }

            $concluidas++;
            $row_date = (string) ($row->data_conclusao ?? '');
            $row_timestamp = $row_date ? strtotime($row_date) : 0;

            if ($row_timestamp > $last_timestamp) {
                $last_timestamp = $row_timestamp;
                $last_date = $row_date;
            }
        }

        $percent = min(100, round(($concluidas / $total_aulas) * 100));

        return [
            'total' => $total_aulas,
            'concluidas' => $concluidas,
            'percent' => $percent,
            'last_date' => $last_date
        ];
    }

    /**
     * Retorna o progresso geral do aluno (media ponderada por aulas) considerando todos os cursos acessiveis.
     */
    public static function get_overall_progress($user_id)
    {
        $curso_ids = [];
        if (class_exists('System_Cursos_Access_Control') && method_exists('System_Cursos_Access_Control', 'get_user_courses')) {
            $curso_ids = System_Cursos_Access_Control::get_user_courses($user_id);
        }

        if (empty($curso_ids)) {
            return 0;
        }

        $total_geral = 0;
        $concluidas_geral = 0;

        foreach ($curso_ids as $curso_id) {
            $details = self::get_course_progress_details($user_id, $curso_id);
            $total_geral += $details['total'];
            $concluidas_geral += $details['concluidas'];
        }

        if ($total_geral == 0) {
            return 0;
        }

        return min(100, round(($concluidas_geral / $total_geral) * 100));
    }
}
