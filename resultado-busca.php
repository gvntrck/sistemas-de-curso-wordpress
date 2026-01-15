<?php
/*
 * Plugin Name: Shortcode Resultado Busca
 * Description: Shortcode [resultado-busca] para exibir resultados de busca seguindo o modelo visual.
 * Version: 1.1.0
 */

function shortcode_resultado_busca_func() {
    // Obtém o termo da busca
    $search_term = get_search_query();

    // Fallback se get_search_query retornar vazio
    if ( empty( $search_term ) && isset( $_GET['s'] ) ) {
        $search_term = sanitize_text_field( $_GET['s'] );
    }

    // Argumentos da query
    $args = array(
        's'              => $search_term,
        'post_type'      => 'any',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
    );

    $the_query = new WP_Query( $args );

    ob_start();
    ?>
    <style>
        /* =========================================
           Variaveis Globais
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
            justify-content: center; /* Centralizar os cards */
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

        /* Estilo para Outros (Lista/Links) - Trilhas, Aulas, etc. */
        .curso-item.type-link {
            flex: 1 1 100%; /* Ocupa 100% da largura, quebrando linha */
            background: #1a1a1a;
            padding: 15px 20px;
            border-radius: 6px;
            border: 1px solid #333;
            box-sizing: border-box;
            transition: background-color 0.2s ease;
            display: flex;
            align-items: center;
        }

        .curso-item.type-link:hover {
            background-color: #252525;
            border-color: #444;
        }

        .curso-item.type-link .curso-link {
            display: flex;
            align-items: center;
            width: 100%;
            text-decoration: none;
            color: #fff;
            justify-content: space-between;
        }

        .curso-item.type-link .curso-icon {
            margin-right: 15px;
            font-size: 1.2rem;
            color: var(--accent-color);
        }

        .curso-item.type-link .curso-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .curso-item.type-link .curso-title {
            font-weight: 600;
            font-size: 1rem;
            color: #fff;
        }

        .curso-item.type-link .post-type-label {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .arrow-icon {
            color: #666;
            margin-left: 10px;
        }
        
        .curso-item.type-link:hover .arrow-icon {
            color: var(--accent-color);
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
            <h3>
                <?php 
                if ( ! empty( $search_term ) ) {
                    echo 'Resultados para: "' . esc_html( $search_term ) . '"';
                } else {
                    echo 'Resultados da Busca';
                }
                ?>
            </h3>
        </div>
        <div class="mc-body">
            <div class="cursos-da-trilha">
                <?php if ( $the_query->have_posts() ) : ?>
                    <?php while ( $the_query->have_posts() ) : $the_query->the_post(); 
                        $post_type = get_post_type();
                        $is_curso = ( 'curso' === $post_type );
                    ?>
                        <?php if ( $is_curso ) : 
                            // Lógica para Curso: Card com Imagem (Capa Vertical)
                            $capa_vertical = get_post_meta( get_the_ID(), 'capa_vertical', true );
                            
                            // Se for ID, pega a URL
                            if ( is_numeric( $capa_vertical ) && $capa_vertical > 0 ) {
                                $thumb_url = wp_get_attachment_url( $capa_vertical );
                            } else {
                                $thumb_url = $capa_vertical;
                            }

                            // Fallback para imagem destacada se capa_vertical estiver vazia
                            if ( empty( $thumb_url ) ) {
                                $thumb_url = get_the_post_thumbnail_url( get_the_ID(), 'medium_large' );
                            }

                            // Placeholder final
                            if ( empty( $thumb_url ) ) {
                                $thumb_url = 'https://via.placeholder.com/220x300/333/FDC110?text=Sem+Imagem';
                            }
                        ?>
                            <div class="curso-item type-curso">
                                <a href="<?php the_permalink(); ?>" class="curso-link">
                                    <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php the_title_attribute(); ?>">
                                    <span class="curso-title"><?php the_title(); ?></span>
                                </a>
                            </div>

                        <?php else : 
                            // Lógica para Trilha, Aula e outros: Apenas Link formatado
                            // Ícones baseados no tipo
                            $icon = '📄'; // Default
                            if ( 'trilha' === $post_type ) $icon = '🎓';
                            if ( 'aula' === $post_type ) $icon = '▶️';
                        ?>
                            <div class="curso-item type-link">
                                <a href="<?php the_permalink(); ?>" class="curso-link">
                                    <div style="display:flex; align-items:center;">
                                        <span class="curso-icon"><?php echo $icon; ?></span>
                                        <div class="curso-content">
                                            <span class="curso-title"><?php the_title(); ?></span>
                                            <span class="post-type-label"><?php echo ucfirst( $post_type ); ?></span>
                                        </div>
                                    </div>
                                    <span class="arrow-icon">➔</span>
                                </a>
                            </div>
                        <?php endif; ?>

                    <?php endwhile; ?>
                <?php else : ?>
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
add_shortcode( 'resultado-busca', 'shortcode_resultado_busca_func' );
