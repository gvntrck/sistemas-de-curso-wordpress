<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Redireciona_Aula
{
    /**
     * class-shortcode-redireciona-aula.php
     *
     * Shortcode [redireciona-aula]
     * Utilitário para redirecionar acessos diretos a posts do tipo 'aula'.
     * Envia o usuário para a página do curso correspondente com o parâmetro 'aula' na URL, garantindo que a aula seja assistida no player principal.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('redireciona-aula', [$this, 'render_shortcode']);
    }

    public function render_shortcode()
    {
        $aula_id = get_the_ID();
        $post_type = get_post_type($aula_id);

        if ('aula' !== $post_type) {
            return '';
        }

        $curso_id = get_post_meta($aula_id, 'curso', true);
        $redirect_url = home_url();

        if ($curso_id) {
            $curso_permalink = get_permalink($curso_id);
            if ($curso_permalink) {
                $redirect_url = add_query_arg('target_aula', $aula_id, $curso_permalink);
            }
        }

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
}
