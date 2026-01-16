<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Single_Trilha
{
    /**
     * class-shortcode-single-trilha.php
     *
     * Shortcode [single-trilha]
     * Exibe a página principal de uma trilha (quando acessada individualmente).
     * Lista todos os cursos que pertencem a essa trilha, permitindo navegação para os cursos.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('single-trilha', [$this, 'render_shortcode']);
    }

    public function render_shortcode()
    {
        $trilha_id = get_the_ID();
        if (!$trilha_id) {
            return '';
        }

        $args = [
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'meta_query' => [
                [
                    'key' => 'trilha',
                    'value' => (string) $trilha_id,
                    'compare' => '='
                ]
            ]
        ];

        $the_query = new WP_Query($args);

        ob_start();
        ?>
        <div class="mc-container">
            <div class="mc-header">
                <h3>Cursos desta Trilha</h3>
            </div>
            <div class="mc-body">
                <div class="cursos-da-trilha">
                    <?php if ($the_query->have_posts()): ?>
                        <?php while ($the_query->have_posts()):
                            $the_query->the_post();
                            $capa_vertical = get_post_meta(get_the_ID(), 'capa_vertical', true);
                            $thumb_url = '';

                            if (is_array($capa_vertical) && isset($capa_vertical['url'])) {
                                $thumb_url = $capa_vertical['url'];
                            } elseif (is_numeric($capa_vertical) && $capa_vertical > 0) {
                                $thumb_url = wp_get_attachment_url($capa_vertical);
                            } elseif (is_string($capa_vertical) && !empty($capa_vertical)) {
                                $thumb_url = $capa_vertical;
                            }

                            if (empty($thumb_url)) {
                                $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                            }

                            if (empty($thumb_url)) {
                                $thumb_url = 'https://via.placeholder.com/220x300/333/FDC110?text=Sem+Imagem';
                            }
                            ?>
                            <div class="curso-item type-curso">
                                <a href="<?php the_permalink(); ?>" class="curso-link">
                                    <img src="<?php echo esc_url($thumb_url); ?>" alt="<?php the_title_attribute(); ?>">
                                    <span class="curso-title">
                                        <?php the_title(); ?>
                                    </span>
                                </a>
                            </div>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="resultado-vazio">
                            <p>Nenhum curso associado a esta trilha no momento.</p>
                        </div>
                    <?php endif; ?>
                    <?php wp_reset_postdata(); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
