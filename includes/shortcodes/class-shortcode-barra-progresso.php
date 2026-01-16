<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Barra_Progresso
{
    /**
     * class-shortcode-barra-progresso.php
     *
     * Shortcode [barra-progresso-geral]
     * Exibe uma barra de progresso visual mostrando a porcentagem geral de conclusão de todos os cursos que o aluno tem acesso.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_shortcode('barra-progresso-geral', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        if (!is_user_logged_in()) {
            return '';
        }

        $user_id = get_current_user_id();
        $porcentagem_geral = 0;

        if (class_exists('System_Cursos_Progress') && method_exists('System_Cursos_Progress', 'get_overall_progress')) {
            $porcentagem_geral = System_Cursos_Progress::get_overall_progress($user_id);
        }

        ob_start();
        ?>
        <div class="barra-progresso-geral-wrapper">
            <div class="barra-progresso-geral-header">
                <span>Seu Progresso</span>
                <span>
                    <?php echo $porcentagem_geral; ?>%
                </span>
            </div>
            <div class="barra-progresso-geral-bar">
                <div class="barra-progresso-geral-fill" style="width: <?php echo $porcentagem_geral; ?>%"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
