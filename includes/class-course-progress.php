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
     * Cria a tabela de progresso no banco de dados, calcula porcentagens de conclusão,
     * e disponibiliza endpoints AJAX para marcar/desmarcar aulas como concluídas.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_action('init', [$this, 'create_table']);
        add_action('wp_ajax_lista_aulas_toggle_concluida', [$this, 'ajax_toggle_concluida']);
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

    public static function is_lesson_completed($user_id, $aula_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND aula_id = %d",
            $user_id,
            $aula_id
        ));

        return !empty($result);
    }

    public static function get_completed_lessons($user_id, $curso_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $results = $wpdb->get_col($wpdb->prepare(
            "SELECT aula_id FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        return array_map('intval', $results);
    }

    public static function update_user_progress($user_id, $curso_id)
    {
        if (!$user_id || !$curso_id) {
            return false;
        }

        // 1. Total de aulas do curso
        $meta_key = 'curso'; // Padrão do plugin
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

        // 2. Aulas concluídas
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $concluidas = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

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
            wp_send_json_error(['message' => 'Você precisa estar logado para marcar aulas como concluídas.']);
        }

        $aula_id = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;
        $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;

        if ($aula_id <= 0) {
            wp_send_json_error(['message' => 'ID da aula inválido.']);
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
            wp_send_json_success(['concluida' => true, 'message' => 'Aula concluída!']);
        }
    }

    /**
     * Retorna detalhes do progresso do aluno em um curso:
     * - Total de aulas
     * - Aulas concluídas
     * - Porcentagem
     * - Data da última conclusão
     */
    public static function get_course_progress_details($user_id, $curso_id)
    {
        global $wpdb;
        $table_progresso = $wpdb->prefix . 'progresso_aluno';
        $meta_key = 'curso'; // Padrão do plugin

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

        // 2. Aulas concluídas e data da última
        $concluidas_data = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as qtd, MAX(data_conclusao) as last_date 
             FROM $table_progresso 
             WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        $concluidas = (int) ($concluidas_data->qtd ?? 0);
        $last_date = $concluidas_data->last_date ?? null;
        $percent = min(100, round(($concluidas / $total_aulas) * 100));

        return [
            'total' => $total_aulas,
            'concluidas' => $concluidas,
            'percent' => $percent,
            'last_date' => $last_date
        ];
    }

    /**
     * Retorna o progresso geral do aluno (média ponderada por aulas) considerando todos os cursos acessíveis.
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
