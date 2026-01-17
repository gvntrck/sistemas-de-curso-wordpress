<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Meus_Cursos
{
    /**
     * class-shortcode-meus-cursos.php
     *
     * Shortcode [meus-cursos]
     * Exibe a grade de cursos do aluno logado.
     * Mostra apenas os cursos liberados para o usuário, incluindo uma barra de progresso visual para cada um.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('meus-cursos', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        // 1. Verificar login
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">%s <a href="%s" style="color: inherit; text-decoration: underline;">%s</a></div>',
                'Você precisa estar logado para ver seus cursos.',
                wp_login_url(get_permalink()),
                'Fazer login'
            );
        }

        $user_id = get_current_user_id();

        // 2. Obter cursos do usuário (array de IDs)
        $curso_ids = [];
        if (class_exists('System_Cursos_Access_Control')) {
            $curso_ids = System_Cursos_Access_Control::get_user_courses($user_id);
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
            'orderby' => 'post__in'
        ]);

        $cursos = $cursos_query->posts;

        // 5. Renderizar
        ob_start();
        ?>
        <div class="meus-cursos-wrapper">
            <h2 class="meus-cursos-heading">Meus Cursos</h2>

            <div class="meus-cursos-grid">
                <?php foreach ($cursos as $curso):
                    $id = $curso->ID;
                    $titulo = get_the_title($id);
                    $link = get_permalink($id);

                    // Tenta pegar imagem destacada, se não, capa vertical, se não placeholder
                    $thumb_url = get_the_post_thumbnail_url($id, 'large');
                    if (!$thumb_url) {
                        // Native Meta Logic replacement for get_field('capa_vertical', $id)
                        $capa = get_post_meta($id, 'capa_vertical', true);

                        // Handle native image ID or legacy URL
                        if (is_numeric($capa) && $capa > 0) {
                            $thumb_url = wp_get_attachment_image_url($capa, 'large'); // or 'full'
                        } elseif (is_array($capa) && !empty($capa['url'])) {
                            // Legacy ACF image object
                            $thumb_url = $capa['url'];
                        } elseif (is_string($capa) && !empty($capa)) {
                            // Legacy URL string
                            $thumb_url = $capa;
                        } else {
                            $thumb_url = 'https://via.placeholder.com/600x338/333/FDC110?text=' . urlencode($titulo);
                        }
                    }

                    // Calcular progresso
                    // System_Cursos_Progress atualiza este meta.
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
    }
}
