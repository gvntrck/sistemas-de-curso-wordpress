<?php
/**
 * Shortcode: [cursos_da_trilha]
 * Versao: 1.0.1
 *
 * O que faz:
 * - Lista cursos relacionados a trilha atual (post em contexto).
 * - Exibe capa vertical (ACF) ou titulo do curso.
 * nao tem controle de acesso, lista tudo para todos os usuarios
 *
 * Como funciona:
 * - deve ficar dentro do loop de uma trilha- assim listando os cursos daquela trilha
 * - Usa WP_Query em "curso" filtrando meta "trilha" igual ao ID da trilha atual.
 * - Renderiza HTML em flexbox e aplica estilos inline por largura configuravel.
 * - Opcionalmente envolve a capa/titulo com link para o curso.
 *
 * Pre-requisitos:
 * - CPT curso.
 * - ACF field no curso: trilha (relacionamento/ID) e capa_vertical (array, ID ou URL).
 *
 * Uso:
 * [cursos_da_trilha orderby="title" order="ASC" limit="-1" link="1" image_width="220"]
 *
 * Parametros:
 * - orderby: campo de ordenacao (padrao: title)
 * - order: ASC ou DESC (padrao: ASC)
 * - limit: limite de cursos (padrao: -1)
 * - link: 1 para linkar, 0 para apenas texto/imagem (padrao: 1)
 * - image_width: largura da imagem em px (padrao: 220)
 *
 * Observacao:
 * - Deve ser usado dentro do contexto da trilha (get_the_ID()).
 */
if (!defined('ABSPATH'))
    exit;

add_shortcode('cursos_da_trilha', function ($atts) {
    $atts = shortcode_atts([
        'orderby' => 'title',
        'order' => 'ASC',
        'limit' => -1,
        'link' => 1,
        'image_width' => 220,
    ], $atts, 'cursos_da_trilha');

    $trilha_id = get_the_ID();
    if (!$trilha_id)
        return '';

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

    if (!$q->have_posts())
        return '';

    $use_link = (int) $atts['link'] === 1;
    $image_width = max(1, (int) $atts['image_width']);

    ob_start();
    echo '<style>'
        . '.cursos-da-trilha{display:flex;flex-wrap:wrap;gap:16px;align-items:flex-start;}'
        . '.cursos-da-trilha .curso-item{flex:0 0 auto;}'
        . '.cursos-da-trilha .curso-link,.cursos-da-trilha .curso-title{display:block;}'
        . '.cursos-da-trilha img{display:block;width:' . (int) $image_width . 'px;height:auto;max-width:100%;}'
        . '@media(max-width:768px){.cursos-da-trilha{justify-content:center;}}'
        . '</style>';
    echo '<div class="cursos-da-trilha">';
    while ($q->have_posts()) {
        $q->the_post();
        $title = esc_html(get_the_title());
        $url = esc_url(get_permalink());
        $cover = get_field('capa_vertical');
        $cover_html = '';

        if (is_array($cover) && !empty($cover['id'])) {
            $cover_html = wp_get_attachment_image(
                (int) $cover['id'],
                'full',
                false,
                ['alt' => $title, 'class' => 'curso-capa-vertical']
            );
        } elseif (is_numeric($cover)) {
            $cover_html = wp_get_attachment_image(
                (int) $cover,
                'full',
                false,
                ['alt' => $title, 'class' => 'curso-capa-vertical']
            );
        } elseif (is_string($cover) && $cover !== '') {
            $cover_html = sprintf(
                '<img class="curso-capa-vertical" src="%s" alt="%s" />',
                esc_url($cover),
                $title
            );
        }

        echo '<div class="curso-item">';
        if ($use_link) {
            echo '<a class="curso-link" href="' . $url . '">' . ($cover_html ?: $title) . '</a>';
        } else {
            echo '<span class="curso-title">' . ($cover_html ?: $title) . '</span>';
        }
        echo '</div>';
    }
    echo '</div>';

    wp_reset_postdata();
    return ob_get_clean();
});
