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
    }

    public function filter_curso_nas_aulas($post_type)
    {
        if ($post_type === 'aula') {
            $selected = isset($_GET['curso_filter']) ? $_GET['curso_filter'] : '';

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

        $post_type = isset($_GET['post_type']) ? $_GET['post_type'] : '';
        $curso_id = isset($_GET['curso_filter']) ? $_GET['curso_filter'] : '';

        if (is_admin() && $pagenow === 'edit.php' && $post_type === 'aula' && !empty($curso_id)) {
            $query->query_vars['meta_key'] = 'curso';
            $query->query_vars['meta_value'] = $curso_id;
            $query->query_vars['meta_compare'] = '=';
        }
    }
}
