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
