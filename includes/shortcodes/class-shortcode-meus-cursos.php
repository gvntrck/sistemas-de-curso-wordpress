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
            'orderby' => 'post__in' // Mantém ordem retornada pelo Access Control? Ou orderby title? Access Control usually date desc.
        ]);

        $todos_cursos = $cursos_query->posts;

        // 5. Agrupar Cursos por Trilha
        $cursos_por_trilha = [];
        $cursos_sem_trilha = [];

        foreach ($todos_cursos as $curso) {
            $trilha_id = get_post_meta($curso->ID, 'trilha', true);

            if ($trilha_id && get_post_status($trilha_id) === 'publish') {
                $cursos_por_trilha[$trilha_id][] = $curso;
            } else {
                $cursos_sem_trilha[] = $curso;
            }
        }

        // 6. Preparar dados das Trilhas para ordenação (opcional: ordenar trilhas por nome)
        // Aqui vamos iterar o array $cursos_por_trilha que é indexado por ID.

        // 7. Renderizar
        ob_start();
        ?>
        <div class="meus-cursos-wrapper">

            <?php
            // A. Trilhas
            if (!empty($cursos_por_trilha)):
                foreach ($cursos_por_trilha as $t_id => $cursos_da_trilha):
                    $trilha_obj = get_post($t_id);
                    $nome_trilha = $trilha_obj->post_title;
                    $desc_trilha = get_post_meta($t_id, 'descricao_curta', true);
                    ?>
                    <div class="mc-container" style="margin-bottom: 30px; max-width: 100%; margin-left: 0; margin-right: 0;">
                        <div class="mc-header" style="text-align: left; padding: 25px;">
                            <h3 style="margin: 0; font-size: 1.5rem; color: var(--text-heading, #fff);">
                                <span style="color: var(--accent-color, #FDC110); margin-right: 8px;">◈</span>
                                <?php echo esc_html($nome_trilha); ?>
                            </h3>
                            <?php if ($desc_trilha): ?>
                                <p style="margin: 5px 0 0 0; color: var(--text-muted, #888); font-size: 0.95rem;">
                                    <?php echo esc_html($desc_trilha); ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="mc-body" style="padding: 25px;">
                            <div class="cursos-da-trilha" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: stretch;">
                                <?php foreach ($cursos_da_trilha as $curso):
                                    echo $this->render_curso_card($curso, $user_id);
                                endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach;
            endif; // End Trilhas Wrapper
            ?>

            <?php
            // B. Cursos Avulsos (Sem Trilha)
            if (!empty($cursos_sem_trilha)): ?>
                <div class="mc-container" style="max-width: 100%; margin-left: 0; margin-right: 0;">
                    <div class="mc-header" style="text-align: left; padding: 25px;">
                        <h3 style="margin: 0; font-size: 1.5rem; color: var(--text-heading, #fff);">
                            Outros Cursos
                        </h3>
                    </div>
                    <div class="mc-body" style="padding: 25px;">
                        <div class="cursos-da-trilha" style="display: flex; flex-wrap: wrap; gap: 20px; align-items: stretch;">
                            <?php foreach ($cursos_sem_trilha as $curso):
                                echo $this->render_curso_card($curso, $user_id);
                            endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <style>
            /* Inline styles fallback or enhancement if theme CSS differs */
            .curso-item {
                flex: 0 0 auto;
                background: #1a1a1a;
                padding: 12px;
                border-radius: 8px;
                border: 1px solid #333;
                width: 200px;
                /* Largura fixa para os cards verticalizados */
                transition: transform 0.2s, border-color 0.2s;
                display: flex;
                flex-direction: column;
            }

            .curso-item:hover {
                transform: translateY(-3px);
                border-color: var(--accent-color, #FDC110);
            }

            .curso-link {
                text-decoration: none;
                color: inherit;
                display: flex;
                flex-direction: column;
                height: 100%;
            }

            .curso-thumb-wrapper {
                position: relative;
                margin-bottom: 10px;
                border-radius: 4px;
                overflow: hidden;
                width: 100%;
                /* 9/16 aspect ratio roughly or fixed height */
                height: 280px;
                background: #222;
            }

            .curso-thumb-wrapper img {
                width: 100%;
                height: 100%;
                object-fit: cover;
                display: block;
            }

            .curso-title {
                font-weight: 600;
                font-size: 0.95rem;
                line-height: 1.3;
                color: #fff;
                margin-bottom: 8px;
                text-align: center;
            }

            /* Responsive adjustments */
            @media (max-width: 600px) {
                .cursos-da-trilha {
                    justify-content: center;
                }

                .curso-item {
                    width: 100%;
                    max-width: 250px;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Helper para renderizar card individual
     */
    private function render_curso_card($curso, $user_id)
    {
        $id = $curso->ID;
        $titulo = get_the_title($id);
        $link = get_permalink($id);

        // Imagem Capa Vertical
        $thumb_url = '';
        $capa = get_post_meta($id, 'capa_vertical', true);
        if (is_numeric($capa) && $capa > 0) {
            $thumb_url = wp_get_attachment_image_url($capa, 'large');
        } elseif (is_array($capa) && !empty($capa['url'])) {
            $thumb_url = $capa['url'];
        } elseif (is_string($capa) && !empty($capa)) {
            $thumb_url = $capa;
        }

        // Fallback p/ imagem destacada padrão se não tiver vertical
        if (empty($thumb_url)) {
            $thumb_url = get_the_post_thumbnail_url($id, 'large');
        }

        // Final fallback placeholder
        if (empty($thumb_url)) {
            $thumb_url = 'https://via.placeholder.com/400x600/333/FDC110?text=' . urlencode($titulo);
        }

        // Progresso
        $porcentagem = (int) get_user_meta($user_id, "progresso_curso_{$id}", true);
        if ($porcentagem > 100)
            $porcentagem = 100;
        if ($porcentagem < 0)
            $porcentagem = 0;

        ob_start();
        ?>
        <div class="curso-item">
            <a href="<?php echo esc_url($link); ?>" class="curso-link">
                <div class="curso-thumb-wrapper">
                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php echo esc_attr($titulo); ?>">

                    <!-- Overlay de Progresso (Opcional, se quiser mostrar "Concluído" visualmente) -->
                    <?php if ($porcentagem >= 100): ?>
                        <div
                            style="position:absolute; top: 10px; right: 10px; background: #2ed573; color: #fff; font-size: 10px; font-weight: bold; padding: 2px 6px; border-radius: 4px;">
                            ✔</div>
                    <?php endif; ?>

                    <div style="position: absolute; bottom: 0; left: 0; right: 0; height: 4px; background: rgba(0,0,0,0.5);">
                        <div
                            style="height: 100%; background: var(--accent-color, #FDC110); width: <?php echo $porcentagem; ?>%;">
                        </div>
                    </div>
                </div>

                <span class="curso-title">
                    <?php echo esc_html($titulo); ?>
                </span>

                <span style="font-size: 0.8rem; color: #888; text-align: center; margin-top: auto;">
                    <?php echo $porcentagem; ?>% concluído
                </span>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}
