<?php
/**
 * Cria a role "aluno" com as mesmas permissões de "subscriber".
 * Seguro para uso no WPCode (execução idempotente).
 */

if (!function_exists('sistema_cursos_get_aluno_redirect_url')) {
    /**
     * Resolve a URL de redirecionamento para usuarios com role "aluno".
     * Fallback para a home quando nenhuma pagina estiver configurada.
     */
    function sistema_cursos_get_aluno_redirect_url()
    {
        $page_id = (int) get_option('lms_sr_aluno_redirect_page_id', 0);

        if ($page_id > 0) {
            $page_url = get_permalink($page_id);
            if (!empty($page_url)) {
                return $page_url;
            }
        }

        return home_url('/');
    }
}

add_action('init', function () {
    // Evita recriação desnecessária
    if (get_role('aluno')) {
        return;
    }

    $subscriberRole = get_role('subscriber');

    if (!$subscriberRole) {
        return;
    }

    add_role(
        'aluno',
        'Aluno',
        $subscriberRole->capabilities
    );
});

/**
 * Restringe acesso de alunos ao Admin e Admin Bar
 */
add_action('admin_init', function () {
    // Permitir requisições AJAX
    if (defined('DOING_AJAX') && DOING_AJAX) {
        return;
    }

    $user = wp_get_current_user();

    // Se for aluno, redireciona para a pagina configurada
    if (in_array('aluno', (array) $user->roles, true)) {
        wp_safe_redirect(sistema_cursos_get_aluno_redirect_url());
        exit;
    }
});

/**
 * Redireciona "aluno" para a pagina configurada apos login.
 */
add_filter('login_redirect', function ($redirect_to, $requested_redirect_to, $user) {
    if ($user instanceof WP_User && in_array('aluno', (array) $user->roles, true)) {
        return sistema_cursos_get_aluno_redirect_url();
    }

    return $redirect_to;
}, 10, 3);

add_filter('show_admin_bar', function ($show) {
    $user = wp_get_current_user();

    // Se for aluno, esconde a barra
    if (in_array('aluno', (array) $user->roles, true)) {
        return false;
    }

    return $show;
});
