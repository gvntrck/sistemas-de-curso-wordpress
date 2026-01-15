<?php
/*
 * Description: Shortcode [redireciona-aula] para redirecionar o single da aula para o contexto do curso.
 */

function shortcode_redireciona_aula_func()
{
    // Apenas roda se estiver no single de aula (ou se forçado o uso)
    // Mas como é um shortcode, assumimos que o usuário colocou onde quer que rode.

    $aula_id = get_the_ID();
    $post_type = get_post_type($aula_id);

    // Verificação de segurança: se por acaso colocar em outro lugar que não seja aula
    if ('aula' !== $post_type) {
        return '';
    }

    // Tenta obter o ID do curso associado (meta key 'curso')
    $curso_id = get_post_meta($aula_id, 'curso', true);

    $redirect_url = home_url(); // Fallback para home

    if ($curso_id) {
        $curso_permalink = get_permalink($curso_id);
        if ($curso_permalink) {
            $redirect_url = add_query_arg('aula', $aula_id, $curso_permalink);
        }
    }

    // Como shortcodes rodam no meio do conteúdo, headers já foram enviados.
    // Usamos Javascript para o redirecionamento imediato.
    ob_start();
    ?>
    <script type="text/javascript">
        window.location.href = "<?php echo esc_url_raw($redirect_url); ?>";
    </script>
    <div style="padding: 20px; text-align: center; color: #fff;">
        <p>Redirecionando para a aula no contexto do curso...</p>
        <p>Se não for redirecionado, <a href="<?php echo esc_url($redirect_url); ?>">clique aqui</a>.</p>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('redireciona-aula', 'shortcode_redireciona_aula_func');
