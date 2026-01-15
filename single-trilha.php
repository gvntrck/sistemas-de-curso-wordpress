<?php
/*
 * Description: Shortcode [single-trilha] para exibir cursos de uma trilha específica no modelo visual.
 */

function shortcode_single_trilha_func()
{
    // Obtém o ID da trilha atual
    $trilha_id = get_the_ID();

    // Se não estivermos em uma trilha ou não tivermos ID, retorna vazio
    if (!$trilha_id) {
        return '';
    }

    // Argumentos da query: Buscar cursos que tenham o meta 'trilha' igual ao ID atual
    $args = array(
        'post_type' => 'curso',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'trilha',
                'value' => $trilha_id,
                'compare' => '=' // ou 'LIKE' dependendo de como é salvo, mas '=' é mais seguro para ID único
            )
        )
    );

    $the_query = new WP_Query($args);

    ob_start();
    ?>
    <style>
        /* =========================================
                   Variaveis Globais (Baseadas em modelo.html)
                   ========================================= */
        :root {
            --bg-color: #121212;
            --bg-card: #121212;
            --bg-header: linear-gradient(180deg, #1f1f1f 0%, #161616 100%);
            --text-heading: #fff;
            --text-muted: #888;
            --border-color: #2a2a2a;
            --accent-color: #FDC110;
            --font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            --radius-card: 12px;
        }

        /* Container Card */
        .mc-container {
            max-width: 900px;
            margin: 0 auto 40px;
            background-color: var(--bg-card);
            color: #e0e0e0;
            border-radius: var(--radius-card);
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.5);
            overflow: hidden;
            border: 1px solid var(--border-color);
            font-family: var(--font-family);
        }

        .mc-header {
            background: var(--bg-header);
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
        }

        .mc-header h3 {
            margin: 0;
            color: var(--text-heading);
            font-size: 1.5rem;
        }

        .mc-body {
            padding: 40px;
        }

        /* Lista de Resultados */
        .cursos-da-trilha {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            align-items: stretch;
            justify-content: center;
            /* Centralizar os cards */
        }

        /* Estilo para Cursos (Cards) */
        .curso-item.type-curso {
            flex: 0 0 auto;
            background: #1a1a1a;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #333;
            width: 242px;
            box-sizing: border-box;
            transition: transform 0.2s ease, border-color 0.2s ease;
            position: relative;
        }

        .curso-item.type-curso:hover {
            transform: translateY(-5px);
            border-color: var(--accent-color);
        }

        .curso-item.type-curso .curso-link {
            display: block;
            text-decoration: none;
            color: #fff;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .curso-item.type-curso img {
            display: block;
            width: 100%;
            height: 300px;
            object-fit: cover;
            background: #333;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .curso-item.type-curso .curso-title {
            display: block;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-heading);
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .resultado-vazio {
            text-align: center;
            width: 100%;
            color: var(--text-muted);
            font-size: 1.1rem;
            padding: 20px;
        }
    </style>

    <div class="mc-container">
        <div class="mc-header">
            <h3>Cursos desta Trilha</h3>
        </div>
        <div class="mc-body">
            <div class="cursos-da-trilha">
                <?php if ($the_query->have_posts()): ?>
                    <?php while ($the_query->have_posts()):
                        $the_query->the_post();
                        // Lógica para Curso: Card com Imagem (Capa Vertical)
                        $capa_vertical = get_post_meta(get_the_ID(), 'capa_vertical', true);
                        $thumb_url = '';

                        // Tratamento robusto para capa_vertical (Array, ID ou URL)
                        if (is_array($capa_vertical) && isset($capa_vertical['url'])) {
                            $thumb_url = $capa_vertical['url'];
                        } elseif (is_numeric($capa_vertical) && $capa_vertical > 0) {
                            $thumb_url = wp_get_attachment_url($capa_vertical);
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
add_shortcode('single-trilha', 'shortcode_single_trilha_func');
