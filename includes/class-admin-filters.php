<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Admin_Filters
{
    /**
     * class-admin-filters.php
     *
     * Adiciona filtros na listagem de aulas no admin do WordPress.
     * Permite filtrar aulas por curso específico na tabela de listagem de posts do tipo 'aula'.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_action('restrict_manage_posts', [$this, 'filter_curso_nas_aulas']);
        add_filter('parse_query', [$this, 'aplicar_filtro_curso_nas_aulas']);
        add_filter('manage_edit-aula_columns', [$this, 'adicionar_coluna_curso_nas_aulas']);
        add_action('manage_aula_posts_custom_column', [$this, 'render_coluna_curso_nas_aulas'], 10, 2);
    }

    public function filter_curso_nas_aulas($post_type)
    {
        if ($post_type === 'aula') {
            $selected = isset($_GET['curso_filter']) ? absint($_GET['curso_filter']) : 0;

            $cursos = get_posts([
                'post_type' => 'curso',
                'posts_per_page' => -1,
                'orderby' => 'title',
                'order' => 'ASC'
            ]);

            if ($cursos) {
                echo '<select name="curso_filter">';
                echo '<option value="">Filtrar por Curso</option>';
                foreach ($cursos as $curso) {
                    printf(
                        '<option value="%s" %s>%s</option>',
                        esc_attr($curso->ID),
                        selected($selected, $curso->ID, false),
                        esc_html($curso->post_title)
                    );
                }
                echo '</select>';
            }
        }
    }

    public function aplicar_filtro_curso_nas_aulas($query)
    {
        global $pagenow;

        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if (empty($post_type) && isset($_GET['post_type'])) {
            $post_type = sanitize_key($_GET['post_type']);
        }

        $curso_id = isset($_GET['curso_filter']) ? absint($_GET['curso_filter']) : 0;

        if ($pagenow === 'edit.php' && $post_type === 'aula' && $curso_id > 0) {
            $query->set('meta_query', [
                'relation' => 'OR',
                [
                    'key' => 'curso',
                    'value' => (string) $curso_id,
                    'compare' => '='
                ],
                [
                    'key' => 'curso',
                    'value' => '"' . $curso_id . '"',
                    'compare' => 'LIKE'
                ]
            ]);
        }
    }

    public function adicionar_coluna_curso_nas_aulas($columns)
    {
        if (!is_array($columns)) {
            return $columns;
        }

        $new_columns = [];
        foreach ($columns as $key => $label) {
            $new_columns[$key] = $label;
            if ($key === 'title') {
                $new_columns['curso_relacionado'] = 'Curso';
            }
        }

        if (!isset($new_columns['curso_relacionado'])) {
            $new_columns['curso_relacionado'] = 'Curso';
        }

        return $new_columns;
    }

    public function render_coluna_curso_nas_aulas($column, $post_id)
    {
        if ($column !== 'curso_relacionado') {
            return;
        }

        $curso_id = absint(get_post_meta($post_id, 'curso', true));
        if ($curso_id <= 0) {
            echo '&mdash;';
            return;
        }

        $curso = get_post($curso_id);
        if (!$curso || $curso->post_type !== 'curso') {
            echo esc_html('Curso nao encontrado');
            return;
        }

        $filter_url = add_query_arg(
            [
                'post_type' => 'aula',
                'curso_filter' => $curso_id
            ],
            admin_url('edit.php')
        );

        printf(
            '<a href="%s">%s</a>',
            esc_url($filter_url),
            esc_html(get_the_title($curso_id))
        );
    }
}
