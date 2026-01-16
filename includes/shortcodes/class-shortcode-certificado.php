<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Certificado
{
    /**
     * class-shortcode-certificado.php
     *
     * Shortcode [certificado]
     * Gerencia a exibição de certificados para os alunos.
     * Se não houver ID de curso, lista os certificados disponíveis (cursos 100% concluídos).
     * Se houver ID, renderiza o certificado visualmente usando o modelo configurado.
     *
     * @package SistemaCursos
     * @version 1.1.9
     */
    public function __construct()
    {
        add_shortcode('certificado', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts([], $atts, 'certificado');

        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">%s <a href="%s" style="color: inherit; text-decoration: underline;">%s</a></div>',
                'Você precisa estar logado para acessar seus certificados.',
                wp_login_url(get_permalink()),
                'Fazer login'
            );
        }

        $user_id = get_current_user_id();
        $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

        if ($curso_id <= 0) {
            return $this->render_list($user_id);
        } else {
            return $this->render_certificate($user_id, $curso_id);
        }
    }

    private function render_list($user_id)
    {
        global $wpdb;
        $tabela_usermeta = $wpdb->usermeta;

        $query = $wpdb->prepare(
            "SELECT meta_key FROM $tabela_usermeta 
             WHERE user_id = %d 
             AND meta_key LIKE 'progresso_curso_%%' 
             AND meta_value >= 100",
            $user_id
        );

        $resultados = $wpdb->get_col($query);
        $certificados_disponiveis = [];

        if ($resultados) {
            foreach ($resultados as $meta_key) {
                $id_curso = str_replace('progresso_curso_', '', $meta_key);
                if (get_post_status($id_curso) === 'publish') {
                    $certificados_disponiveis[] = intval($id_curso);
                }
            }
        }

        if (empty($certificados_disponiveis)) {
            return '<div class="mc-container" style="text-align: center; padding: 40px; background: #121212; color: #fff; border-radius: 12px;">
                <h3 style="margin-top:0;">Nenhum certificado disponível</h3>
                <p style="color: #888;">Complete 100% de um curso para desbloquear seu certificado.</p>
             </div>';
        }

        ob_start();
        ?>
        <div class="cert-grid">
            <?php foreach ($certificados_disponiveis as $id):
                $titulo = get_the_title($id);
                $link = add_query_arg('curso_id', $id, get_permalink());
                ?>
                <a href="<?php echo esc_url($link); ?>" class="cert-card">
                    <i class="fas fa-certificate cert-icon"></i>
                    <h4>
                        <?php echo esc_html($titulo); ?>
                    </h4>
                    <span class="mc-btn mc-btn-primary" style="font-size: 0.8rem; padding: 6px 12px; margin-top: auto;">Ver
                        Certificado</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_certificate($user_id, $curso_id)
    {
        $cert_id = get_post_meta($curso_id, '_curso_certificado_id', true);
        if (!$cert_id) {
            return '<div class="mc-alert" style="color:red; text-align:center;">Certificado não configurado para este curso. Contate o suporte.</div>';
        }

        $bg_url = get_post_meta($cert_id, '_cert_bg_url', true);
        $nome_top = get_post_meta($cert_id, '_cert_nome_top', true) ?: '40%';
        $nome_left = get_post_meta($cert_id, '_cert_nome_left', true) ?: '50%';
        $curso_top = get_post_meta($cert_id, '_cert_curso_top', true) ?: '55%';
        $curso_left = get_post_meta($cert_id, '_cert_curso_left', true) ?: '50%';
        $data_top = get_post_meta($cert_id, '_cert_data_top', true) ?: '70%';
        $data_left = get_post_meta($cert_id, '_cert_data_left', true) ?: '50%';
        $color = get_post_meta($cert_id, '_cert_color', true) ?: '#000000';
        $font_size = get_post_meta($cert_id, '_cert_font_size', true) ?: '24px';
        $font_family = get_post_meta($cert_id, '_cert_font_family', true) ?: 'Roboto';
        $show_curso = get_post_meta($cert_id, '_cert_show_curso', true);
        $show_data = get_post_meta($cert_id, '_cert_show_data', true);

        // URL da fonte Google Fonts
        $font_url = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $font_family) . ':wght@400;700&display=swap';

        $user_data = get_userdata($user_id);
        $nome_aluno = $user_data->first_name . ' ' . $user_data->last_name;
        if (trim($nome_aluno) === '') {
            $nome_aluno = $user_data->display_name;
        }

        $progresso = (int) get_user_meta($user_id, "progresso_curso_{$curso_id}", true);
        $is_admin = current_user_can('manage_options');

        if ($progresso < 100 && !$is_admin) {
            return '<div class="mc-container" style="text-align: center; padding: 40px;">
                <h3 style="color: var(--text-heading, #fff);">Curso em andamento</h3>
                <p style="color: var(--text-muted, #888);">Conclua todas as aulas para desbloquear seu certificado. Progresso atual: ' . $progresso . '%</p>
                <div style="margin-top:20px;">
                    <a href="' . get_permalink($curso_id) . '" class="mc-btn-save">Voltar ao Curso</a>
                </div>
            </div>';
        }

        $curso_titulo = get_the_title($curso_id);
        $data_conclusao = date_i18n(get_option('date_format'), current_time('timestamp'));

        ob_start();
        ?>
        <!-- Carrega a fonte do Google Fonts -->
        <link href="<?php echo esc_url($font_url); ?>" rel="stylesheet">

        <div class="cert-wrapper">
            <h2 style="margin-bottom: 20px; color: #fff;">Parabéns,
                <?php echo esc_html($user_data->first_name); ?>!
            </h2>
            <p style="margin-bottom: 30px; color: #aaa;">Aqui está o seu certificado de conclusão do curso <strong>
                    <?php echo esc_html($curso_titulo); ?>
                </strong>.</p>

            <div class="cert-container" id="printable-cert" data-font="<?php echo esc_attr($font_family); ?>"
                data-font-url="<?php echo esc_attr($font_url); ?>"
                data-filename="<?php echo esc_attr($curso_titulo . ' - ' . $nome_aluno); ?>">
                <?php if ($bg_url): ?>
                    <img src="<?php echo esc_url($bg_url); ?>" class="cert-bg" alt="Fundo Certificado">
                <?php else: ?>
                    <div
                        style="width:100%; height:100%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#333;">
                        Fundo não configurado.
                    </div>
                <?php endif; ?>

                <div class="cert-element"
                    style="top: <?php echo esc_attr($nome_top); ?>; left: <?php echo esc_attr($nome_left); ?>; color: <?php echo esc_attr($color); ?>; font-size: <?php echo esc_attr($font_size); ?>; font-family: '<?php echo esc_attr($font_family); ?>', sans-serif;">
                    <?php echo esc_html($nome_aluno); ?>
                </div>

                <?php if ($show_curso === '1'): ?>
                    <div class="cert-element"
                        style="top: <?php echo esc_attr($curso_top); ?>; left: <?php echo esc_attr($curso_left); ?>; color: <?php echo esc_attr($color); ?>; font-size: <?php echo esc_attr($font_size); ?>; font-family: '<?php echo esc_attr($font_family); ?>', sans-serif;">
                        <?php echo esc_html($curso_titulo); ?>
                    </div>
                <?php endif; ?>

                <?php if ($show_data === '1'): ?>
                    <div class="cert-element"
                        style="top: <?php echo esc_attr($data_top); ?>; left: <?php echo esc_attr($data_left); ?>; color: <?php echo esc_attr($color); ?>; font-size: <?php echo esc_attr($font_size); ?>; font-family: '<?php echo esc_attr($font_family); ?>', sans-serif;">
                        <?php echo esc_html($data_conclusao); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cert-actions">
                <button onclick="imprimirCertificado();" class="mc-btn mc-btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="6 9 6 2 18 2 18 9"></polyline>
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                        <rect x="6" y="14" width="12" height="8"></rect>
                    </svg>
                    Imprimir / Salvar PDF
                </button>
            </div>
        </div>

        <script>
            function imprimirCertificado() {
                var certElement = document.getElementById('printable-cert');
                if (!certElement) {
                    alert('Erro: Certificado não encontrado.');
                    return;
                }

                // Obtém a fonte configurada do atributo data
                var fontFamily = certElement.getAttribute('data-font') || 'Roboto';
                var fontUrl = certElement.getAttribute('data-font-url') || '';
                var filename = certElement.getAttribute('data-filename') || 'Certificado';

                // Cria uma nova janela para impressão
                var printWindow = window.open('', '_blank', 'width=1200,height=800');

                // Estilos inline para a janela de impressão (incluindo a fonte)
                var estilos = `
                <link href="${fontUrl}" rel="stylesheet">
                <style>
                    * { margin: 0; padding: 0; box-sizing: border-box; }
                    body { 
                        display: flex; 
                        justify-content: center; 
                        align-items: center; 
                        min-height: 100vh;
                        background: white;
                    }
                    .cert-container {
                        position: relative;
                        width: 100%;
                        max-width: 1000px;
                        aspect-ratio: 1.414 / 1;
                    }
                    .cert-bg {
                        position: absolute;
                        top: 0;
                        left: 0;
                        width: 100%;
                        height: 100%;
                        object-fit: contain;
                    }
                    .cert-element {
                        position: absolute;
                        transform: translateX(-50%);
                        font-weight: bold;
                        text-align: center;
                        white-space: nowrap;
                    }
                    @media print {
                        body { 
                            margin: 0; 
                            padding: 0;
                        }
                        .cert-container {
                            max-width: none;
                            width: 100vw;
                            height: 100vh;
                        }
                        .cert-bg {
                            -webkit-print-color-adjust: exact;
                            print-color-adjust: exact;
                        }
                    }
                    @page {
                        size: landscape;
                        margin: 0;
                    }
                </style>
            `;

        // Monta o HTML da janela de impressão
        printWindow.document.write('<!DOCTYPE html><html><head><title>' + filename + '</title>' + estilos + '</head><body>');
        printWindow.document.write(certElement.outerHTML);
        printWindow.document.write('</body></html>');
        printWindow.document.close();

        // Aguarda a fonte e imagem carregar antes de imprimir
        printWindow.onload = function () {
            setTimeout(function () {
                printWindow.focus();
                printWindow.print();
            }, 800); // Aumentado para dar tempo da fonte carregar
        };
    }
</script>
<?php
                return ob_get_clean();
    }
}
