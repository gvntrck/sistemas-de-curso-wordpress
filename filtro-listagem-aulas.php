<?php
/**
 * Snippet Name: Filtro por Curso na Listagem de Aulas
 * Description: Adiciona um dropdown de filtro por curso na tabela de listagem de aulas no admin.
 * Version: 1.0.0
 */

// 1. Adiciona o dropdown de filtro na tela de listagem de aulas
add_action('restrict_manage_posts', 'filtro_curso_nas_aulas');
function filtro_curso_nas_aulas($post_type)
{
    if ($post_type === 'aula') {
        $selected = isset($_GET['curso_filter']) ? $_GET['curso_filter'] : '';

        $cursos = get_posts(array(
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ));

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

// 2. Modifica a query para filtrar pelo curso selecionado
add_filter('parse_query', 'aplicar_filtro_curso_nas_aulas');
function aplicar_filtro_curso_nas_aulas($query)
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
