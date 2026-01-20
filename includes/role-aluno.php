<?php
/**
 * Cria a role "aluno" com as mesmas permissões de "subscriber".
 * Seguro para uso no WPCode (execução idempotente).
 */

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

    // Se for aluno, redireciona para a home
    if (in_array('aluno', (array) $user->roles)) {
        wp_redirect(home_url());
        exit;
    }
});

add_filter('show_admin_bar', function ($show) {
    $user = wp_get_current_user();

    // Se for aluno, esconde a barra
    if (in_array('aluno', (array) $user->roles)) {
        return false;
    }

    return $show;
});
