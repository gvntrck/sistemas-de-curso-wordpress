<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Cursos_Trilha
{
    /**
     * class-shortcode-cursos-trilha.php
     *
     * Shortcode [cursos_da_trilha]
     * Lista os cursos associados a uma trilha específica.
     * Projetado para ser utilizado dentro do modelo Single (post type 'trilha'), identificando automaticamente o ID da trilha atual.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('cursos_da_trilha', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts([
            'orderby' => 'title',
            'order' => 'ASC',
            'limit' => -1,
            'link' => 1,
            'image_width' => 220, // Mantido para compatibilidade, mas estilo global pode prevalecer
        ], $atts, 'cursos_da_trilha');

        $trilha_id = get_the_ID();
        if (!$trilha_id) {
            return '';
        }

        $q = new WP_Query([
            'post_type' => 'curso',
            'post_status' => 'publish',
            'posts_per_page' => (int) $atts['limit'],
            'orderby' => sanitize_key($atts['orderby']),
            'order' => ($atts['order'] === 'DESC') ? 'DESC' : 'ASC',
            'meta_query' => [
                [
                    'key' => 'trilha',   // ACF field name no Curso
                    'value' => (string) $trilha_id,
                    'compare' => '=',
                ]
            ],
            'no_found_rows' => true,
        ]);

        if (!$q->have_posts()) {
            return '';
        }

        ob_start();
        ?>
        <div class="cursos-da-trilha">
            <?php while ($q->have_posts()):
                $q->the_post();

                // Lógica de Imagem (Capa Vertical)
                $capa_vertical = get_post_meta(get_the_ID(), 'capa_vertical', true);
                // Robust Logic for Image (ACF Array, Native ID, or URL String)
                if (is_array($capa_vertical) && isset($capa_vertical['url'])) {
                    $thumb_url = $capa_vertical['url'];
                } elseif (is_numeric($capa_vertical) && $capa_vertical > 0) {
                    $thumb_url = wp_get_attachment_image_url($capa_vertical, 'large');
                } elseif (is_string($capa_vertical) && !empty($capa_vertical)) {
                    $thumb_url = $capa_vertical;
                }

                if (empty($thumb_url)) {
                    $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                }

                if (empty($thumb_url)) {
                    $thumb_url = 'https://via.placeholder.com/220x300/333/FDC110?text=' . urlencode(get_the_title());
                }

                // Renderização Card Padronizado
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
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}
