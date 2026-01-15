<?php
/**
 * Shortcode: [meus-cursos]
 * Versão: 1.0.0
 *
 * Exibe uma lista de cursos nos quais o usuário está matriculado,
 * calculando o progresso do aluno.
 *
 * Dependências:
 * - controle-acesso.php (para saber quais cursos o aluno tem acesso)
 * - listar-aulas.php (para lógica de cálculo de progresso salva no meta)
 *
 * Estilos:
 * Baseados no 'modelo.html' (Visual "Trilha" + Card Style)
 */

if (!defined('ABSPATH')) {
    exit;
}

add_shortcode('meus-cursos', function ($atts) {
    // 1. Verificar login
    if (!is_user_logged_in()) {
        return '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">
            Você precisa estar logado para ver seus cursos. <a href="' . wp_login_url(get_permalink()) . '" style="color: inherit; text-decoration: underline;">Fazer login</a>
        </div>';
    }

    $user_id = get_current_user_id();

    // 2. Obter cursos do usuário (array de IDs)
    // Função do arquivo controle-acesso.php
    $curso_ids = [];
    if (function_exists('acesso_cursos_get_user_cursos')) {
        $curso_ids = acesso_cursos_get_user_cursos($user_id);
    }

    // 3. Se não tiver cursos
    if (empty($curso_ids)) {
        return '<div class="mc-container" style="text-align: center; padding: 40px;">
            <h3 style="color: var(--text-heading, #fff);">Você ainda não possui cursos.</h3>
            <p style="color: var(--text-muted, #888);">Explore nosso catálogo e comece a aprender hoje mesmo!</p>
        </div>';
    }

    // 4. Buscar objetos dos cursos
    $cursos_query = new WP_Query([
        'post_type' => 'curso',
        'post_status' => 'publish',
        'post__in' => $curso_ids,
        'posts_per_page' => -1,
        'orderby' => 'post__in' // Manter ordem retornada se necessário, ou 'title'
    ]);

    $cursos = $cursos_query->posts;

    // 5. Renderizar
    ob_start();
    ?>
    <style>
        /* Scoped styles baseados no modelo.html para garantir consistência */
        .meus-cursos-wrapper {
            --bg-card: #121212;
            --text-heading: #fff;
            --text-muted: #888;
            --accent-color: #FDC110;
            --border-color: #2a2a2a;
            --font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --radius-card: 12px;
        }

        .meus-cursos-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 24px;
        }

        .meus-cursos-card {
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 12px;
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            text-decoration: none;
            color: inherit;
        }

        .meus-cursos-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            border-color: var(--accent-color);
        }

        .meus-cursos-thumb {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
            background: #333;
        }

        .meus-cursos-body {
            padding: 16px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .meus-cursos-title {
            font-family: var(--font-family);
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-heading);
            margin: 0 0 8px 0;
            line-height: 1.4;
        }

        .meus-cursos-progress {
            margin-top: auto;
        }

        .meus-cursos-progress-bar {
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .meus-cursos-progress-fill {
            height: 100%;
            background: var(--accent-color);
            border-radius: 3px;
        }

        .meus-cursos-meta {
            display: flex;
            justify-content: space-between;
            font-size: 0.85rem;
            color: var(--text-muted);
        }
    </style>

    <div class="meus-cursos-wrapper">
        <h2
            style="color: #fff; margin-bottom: 24px; font-size: 1.8rem; border-bottom: 1px solid #333; padding-bottom: 16px;">
            Meus Cursos
        </h2>

        <div class="meus-cursos-grid">
            <?php foreach ($cursos as $curso):
                $id = $curso->ID;
                $titulo = get_the_title($id);
                $link = get_permalink($id); // Link para o single do curso
        
                // Tenta pegar imagem destacada, se não, capa vertical, se não placeholder
                $thumb_url = get_the_post_thumbnail_url($id, 'large');
                if (!$thumb_url) {
                    $capa = get_field('capa_vertical', $id);
                    if (is_array($capa) && !empty($capa['url'])) {
                        $thumb_url = $capa['url'];
                    } elseif (is_string($capa) && $capa) {
                        $thumb_url = $capa;
                    } else {
                        $thumb_url = 'https://via.placeholder.com/600x338/333/FDC110?text=' . urlencode($titulo);
                    }
                }

                // Calcular progresso
                // O listar-aulas.php salva em: "progresso_curso_{$curso_id}"
                $porcentagem = (int) get_user_meta($user_id, "progresso_curso_{$id}", true);
                if ($porcentagem > 100)
                    $porcentagem = 100;
                if ($porcentagem < 0)
                    $porcentagem = 0;

                ?>
                <a href="<?php echo esc_url($link); ?>" class="meus-cursos-card">
                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($titulo); ?>"
                        class="meus-cursos-thumb">
                    <div class="meus-cursos-body">
                        <h3 class="meus-cursos-title">
                            <?php echo esc_html($titulo); ?>
                        </h3>

                        <div class="meus-cursos-progress">
                            <div class="meus-cursos-progress-bar">
                                <div class="meus-cursos-progress-fill" style="width: <?php echo $porcentagem; ?>%"></div>
                            </div>
                            <div class="meus-cursos-meta">
                                <span>
                                    <?php echo $porcentagem; ?>% concluído
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
});
