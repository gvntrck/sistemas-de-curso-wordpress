<?php
/**
 * Shortcode: [barra-progresso-geral]
 * Versão: 1.0.2
 *
 * Exibe uma barra de progresso geral, considerando todas as aulas
 * de todos os cursos que o aluno tem acesso.
 *
 * Dependências:
 * - controle-acesso.php (para saber quais cursos o aluno tem acesso)
 * - Tabela DB: {prefix}progresso_aluno
 * - CPT: curso, aula
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('barra-progresso-geral', function ($atts) {
    // 1. Verificar login
    if (!is_user_logged_in()) {
        return '';
    }

    $user_id = get_current_user_id();

    // 2. Obter cursos do usuário
    // Se a função de controle de acesso não existir, não podemos calcular.
    if (!function_exists('acesso_cursos_get_user_cursos')) {
        return '<!-- Erro: função acesso_cursos_get_user_cursos não encontrada -->';
    }

    $curso_ids = acesso_cursos_get_user_cursos($user_id);

    // Se não tiver cursos, progresso é 0%
    if (empty($curso_ids)) {
        $porcentagem_geral = 0;
        $total_aulas_geral = 0;
        $total_concluidas_geral = 0;
    } else {
        global $wpdb;
        $table_progresso = $wpdb->prefix . 'progresso_aluno';

        // 3. Contar total de aulas concluídas nesses cursos
        // Filtramos pelo user_id e pelos cursos permitidos
        $ids_string = implode(',', array_map('intval', $curso_ids));

        $sql_concluidas = "SELECT COUNT(DISTINCT aula_id) FROM $table_progresso WHERE user_id = %d AND curso_id IN ($ids_string)";
        $total_concluidas_geral = (int) $wpdb->get_var($wpdb->prepare($sql_concluidas, $user_id));

        // 4. Contar total de aulas existentes nesses cursos
        // Precisamos iterar para garantir a query correta de meta (igual ou LIKE para arrays serializados),
        // mantendo consistência com listar-aulas.php
        $total_aulas_geral = 0;

        foreach ($curso_ids as $c_id) {
            $c_id = (int) $c_id;

            $args_aula = [
                'post_type' => 'aula',
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => 'ids',
                'meta_query' => [
                    'relation' => 'OR',
                    [
                        'key' => 'curso',
                        'value' => $c_id,
                        'compare' => '=',
                    ],
                    [
                        'key' => 'curso',
                        'value' => '"' . $c_id . '"',
                        'compare' => 'LIKE',
                    ],
                ],
                'no_found_rows' => true, // Performance
                'update_post_meta_cache' => false, // Performance
                'update_post_term_cache' => false, // Performance
            ];

            $query_aulas = new WP_Query($args_aula);
            $total_aulas_geral += $query_aulas->post_count;
        }

        // Evitar divisão por zero
        if ($total_aulas_geral > 0) {
            $porcentagem_geral = ($total_concluidas_geral / $total_aulas_geral) * 100;
        } else {
            $porcentagem_geral = 0;
            // Se não tem aulas, talvez considerar 100% se o usuário tem o curso? 
            // Mas logicamente 0/0 aulas é indefinido, visualmente 100% parece melhor se "concluiu tudo que tinha".
            // Mas padrão é 0.
        }
    }

    // Arredondar e limitar
    $porcentagem_geral = round($porcentagem_geral);
    if ($porcentagem_geral > 100)
        $porcentagem_geral = 100;
    if ($porcentagem_geral < 0)
        $porcentagem_geral = 0;

    // 5. Renderizar
    ob_start();
    ?>
    <style>
        .barra-progresso-geral-wrapper {
            --bg-bar: rgba(255, 255, 255, 0.1);
            --bg-fill: #FDC110;
            --text-color: #fff;
            --height: 8px;
            --radius: 4px;
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            margin-bottom: 20px;
        }

        .barra-progresso-geral-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            color: var(--text-color);
            font-size: 0.9rem;
            font-weight: 600;
            gap: 10px;
        }

        .barra-progresso-geral-bar {
            width: 100%;
            height: var(--height);
            background: var(--bg-bar);
            border-radius: var(--radius);
            overflow: hidden;
        }

        .barra-progresso-geral-fill {
            height: 100%;
            background: var(--bg-fill);
            border-radius: var(--radius);
            transition: width 0.4s ease;
        }
    </style>

    <div class="barra-progresso-geral-wrapper">
        <div class="barra-progresso-geral-header">
            <span>Seu Progresso</span>
            <span><?php echo $porcentagem_geral; ?>%</span>
        </div>
        <div class="barra-progresso-geral-bar">
            <div class="barra-progresso-geral-fill" style="width: <?php echo $porcentagem_geral; ?>%"></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
