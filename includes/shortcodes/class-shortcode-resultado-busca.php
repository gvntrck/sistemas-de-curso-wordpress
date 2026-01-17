<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Resultado_Busca
{
    /**
     * class-shortcode-resultado-busca.php
     *
     * Shortcode [resultado-busca]
     * Exibe os resultados de uma pesquisa realizada no site.
     * Filtra os resultados para exibir apenas Cursos, Trilhas e Aulas.
     * Renderiza cards diferenciados para Cursos (com capa), Trilhas e Aulas (lista simples),
     * mantendo a consistência visual com o restante do tema.
     *
     * @package SistemaCursos
     * @version 1.0.9
     */
    public function __construct()
    {
        add_shortcode('resultado-busca', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        // Obtém o termo da busca
        $search_term = get_search_query();

        // Fallback se get_search_query retornar vazio
        if (empty($search_term) && isset($_GET['s'])) {
            $search_term = sanitize_text_field($_GET['s']);
        }

        // Argumentos da query
        $args = array(
            's' => $search_term,
            'post_type' => array('trilha', 'curso', 'aula'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
        );

        $the_query = new WP_Query($args);

        ob_start();
        ?>
        <div class="mc-container">
            <div class="mc-header">
                <h3>
                    <?php
                    if (!empty($search_term)) {
                        echo 'Resultados para: "' . esc_html($search_term) . '"';
                    } else {
                        echo 'Resultados da Busca';
                    }
                    ?>
                </h3>
            </div>
            <div class="mc-body">
                <div class="cursos-da-trilha">
                    <?php if ($the_query->have_posts()): ?>
                        <?php while ($the_query->have_posts()):
                            $the_query->the_post();
                            $post_type = get_post_type();
                            $is_curso = ('curso' === $post_type);
                            ?>
                            <?php if ($is_curso):
                                // Lógica para Curso: Card com Imagem (Capa Vertical)
                                $capa_vertical = get_post_meta(get_the_ID(), 'capa_vertical', true);

                                // Robust Logic for Image (ACF Array, Native ID, or URL String)
                                if (is_array($capa_vertical) && isset($capa_vertical['url'])) {
                                    $thumb_url = $capa_vertical['url'];
                                } elseif (is_numeric($capa_vertical) && $capa_vertical > 0) {
                                    $thumb_url = wp_get_attachment_image_url($capa_vertical, 'large');
                                } elseif (is_string($capa_vertical) && !empty($capa_vertical)) {
                                    $thumb_url = $capa_vertical;
                                }

                                // Fallback para imagem destacada se capa_vertical estiver vazia
                                if (empty($thumb_url)) {
                                    $thumb_url = get_the_post_thumbnail_url(get_the_ID(), 'medium_large');
                                }

                                // Placeholder final
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

                            <?php else:
                                // Lógica para Trilha, Aula e outros: Apenas Link formatado
                                $icon = '📄'; // Default
                                if ('trilha' === $post_type)
                                    $icon = '🎓';
                                if ('aula' === $post_type)
                                    $icon = '▶️';
                                ?>
                                <div class="curso-item type-link">
                                    <a href="<?php the_permalink(); ?>" class="curso-link">
                                        <div style="display:flex; align-items:center;">
                                            <span class="curso-icon">
                                                <?php echo $icon; ?>
                                            </span>
                                            <div class="curso-content">
                                                <span class="curso-title">
                                                    <?php the_title(); ?>
                                                </span>
                                                <span class="post-type-label">
                                                    <?php echo ucfirst($post_type); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <span class="arrow-icon">➔</span>
                                    </a>
                                </div>
                            <?php endif; ?>

                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="resultado-vazio">
                            <p>Nenhum resultado encontrado para sua busca.</p>
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
