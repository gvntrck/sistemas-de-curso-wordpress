<?php
/**
 * Plugin Name: Sistema de Certificados
 * Description: Gera certificados de conclusão de curso.
 * Version: 1.0.0
 * Author: Antigravity
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. ADMINISTRAÇÃO
 * Adiciona menu e página de configurações
 */
add_action('admin_menu', 'certificado_admin_menu');
function certificado_admin_menu()
{
    add_menu_page(
        'Configurar Certificados',
        'Certificados',
        'manage_options',
        'config-certificado',
        'certificado_render_admin_page',
        'dashicons-awards',
        52 // Posição menu
    );
}

/**
 * Enqueue Media Uploader scripts no admin
 */
add_action('admin_enqueue_scripts', 'certificado_admin_scripts');
function certificado_admin_scripts($hook)
{
    if ($hook === 'toplevel_page_config-certificado') {
        wp_enqueue_media();
    }
}

/**
 * Renderiza página de admin
 */
function certificado_render_admin_page()
{
    // Salvar configurações
    if (isset($_POST['certificado_submit']) && check_admin_referer('certificado_save_options')) {
        update_option('certificado_bg_url', sanitize_url($_POST['certificado_bg_url']));

        // Posições (Top/Left em % ou px)
        update_option('certificado_nome_top', sanitize_text_field($_POST['certificado_nome_top']));
        update_option('certificado_nome_left', sanitize_text_field($_POST['certificado_nome_left']));

        update_option('certificado_curso_top', sanitize_text_field($_POST['certificado_curso_top']));
        update_option('certificado_curso_left', sanitize_text_field($_POST['certificado_curso_left']));

        update_option('certificado_data_top', sanitize_text_field($_POST['certificado_data_top']));
        update_option('certificado_data_left', sanitize_text_field($_POST['certificado_data_left']));

        // Estilos
        update_option('certificado_color', sanitize_hex_color($_POST['certificado_color']));
        update_option('certificado_font_size', sanitize_text_field($_POST['certificado_font_size']));

        echo '<div class="notice notice-success is-dismissible"><p>Configurações salvas com sucesso!</p></div>';
    }

    // Carregar valores
    $bg_url = get_option('certificado_bg_url', '');
    $nome_top = get_option('certificado_nome_top', '40%');
    $nome_left = get_option('certificado_nome_left', '50%');
    $curso_top = get_option('certificado_curso_top', '55%');
    $curso_left = get_option('certificado_curso_left', '50%');
    $data_top = get_option('certificado_data_top', '70%');
    $data_left = get_option('certificado_data_left', '50%');
    $color = get_option('certificado_color', '#000000');
    $font_size = get_option('certificado_font_size', '24px');
    ?>
    <div class="wrap">
        <h1>Configuração de Certificado</h1>
        <p>Defina a imagem de fundo e a posição dos elementos no certificado.</p>

        <form method="post" action="">
            <?php wp_nonce_field('certificado_save_options'); ?>

            <table class="form-table">
                <!-- Imagem de Fundo -->
                <tr>
                    <th scope="row">Imagem de Fundo</th>
                    <td>
                        <input type="text" name="certificado_bg_url" id="certificado_bg_url"
                            value="<?php echo esc_attr($bg_url); ?>" class="regular-text">
                        <button type="button" class="button" id="btn_upload_bg">Selecionar Imagem</button>
                        <p class="description">Recomendado: Formato A4 Paisagem (ex: 3508x2480 px ou proporcional).</p>
                        <?php if ($bg_url): ?>
                            <div style="margin-top: 10px;">
                                <img src="<?php echo esc_url($bg_url); ?>" style="max-width: 300px; border: 1px solid #ccc;">
                            </div>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Cor e Fonte -->
                <tr>
                    <th scope="row">Estilo do Texto</th>
                    <td>
                        <label>Cor: <input type="color" name="certificado_color"
                                value="<?php echo esc_attr($color); ?>"></label><br><br>
                        <label>Tamanho Base da Fonte: <input type="text" name="certificado_font_size"
                                value="<?php echo esc_attr($font_size); ?>" class="small-text"> (ex: 24px, 2rem)</label>
                    </td>
                </tr>

                <!-- Posições -->
                <tr>
                    <th scope="row">Posição do Nome do Aluno</th>
                    <td>
                        Top: <input type="text" name="certificado_nome_top" value="<?php echo esc_attr($nome_top); ?>"
                            class="small-text">
                        Left: <input type="text" name="certificado_nome_left" value="<?php echo esc_attr($nome_left); ?>"
                            class="small-text">
                        <p class="description">Use % para melhor responsividade ou px para fixo.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Posição do Nome do Curso</th>
                    <td>
                        Top: <input type="text" name="certificado_curso_top" value="<?php echo esc_attr($curso_top); ?>"
                            class="small-text">
                        Left: <input type="text" name="certificado_curso_left" value="<?php echo esc_attr($curso_left); ?>"
                            class="small-text">
                    </td>
                </tr>
                <tr>
                    <th scope="row">Posição da Data de Conclusão</th>
                    <td>
                        Top: <input type="text" name="certificado_data_top" value="<?php echo esc_attr($data_top); ?>"
                            class="small-text">
                        Left: <input type="text" name="certificado_data_left" value="<?php echo esc_attr($data_left); ?>"
                            class="small-text">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="certificado_submit" id="submit" class="button button-primary"
                    value="Salvar Alterações">
            </p>
        </form>
    </div>

    <!-- Script Media Uploader -->
    <script>
        jQuery(document).ready(function ($) {
            var mediaUploader;
            $('#btn_upload_bg').click(function (e) {
                e.preventDefault();
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }
                mediaUploader = wp.media.frames.file_frame = wp.media({
                    title: 'Escolher Imagem do Certificado',
                    button: { text: 'Usar Imagem' },
                    multiple: false
                });
                mediaUploader.on('select', function () {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#certificado_bg_url').val(attachment.url);
                });
                mediaUploader.open();
            });
        });
    </script>
    <?php
}

/**
 * 2. SHORTCODE E FRONTEND
 * Shortcode [certificado]
 */
add_shortcode('certificado', 'certificado_shortcode');
function certificado_shortcode($atts)
{
    if (!defined('ABSPATH'))
        return;

    // Recupera configurações
    $bg_url = get_option('certificado_bg_url', '');
    $nome_top = get_option('certificado_nome_top', '40%');
    $nome_left = get_option('certificado_nome_left', '50%');
    $curso_top = get_option('certificado_curso_top', '55%');
    $curso_left = get_option('certificado_curso_left', '50%');
    $data_top = get_option('certificado_data_top', '70%');
    $data_left = get_option('certificado_data_left', '50%');
    $color = get_option('certificado_color', '#000000');
    $font_size = get_option('certificado_font_size', '24px');

    // Recupera parâmetros
    $atts = shortcode_atts([], $atts, 'certificado');
    $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

    // Verifica Login
    if (!is_user_logged_in()) {
        return '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">
            Você precisa estar logado para acessar seu certificado. <a href="' . wp_login_url(get_permalink()) . '" style="color: inherit; text-decoration: underline;">Fazer login</a>
        </div>';
    }

    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);

    // Nome do aluno
    $nome_aluno = $user_data->first_name . ' ' . $user_data->last_name;
    if (trim($nome_aluno) === '') {
        $nome_aluno = $user_data->display_name;
    }

    // Verifica Curso
    if ($curso_id <= 0) {
        return '<div class="mc-alert" style="color:red; text-align:center;">Curso não especificado.</div>';
    }

    // Verifica Conclusão
    // Baseado na lógica de listar-aulas.php
    $progresso = (int) get_user_meta($user_id, "progresso_curso_{$curso_id}", true);

    // Se for admin, permite visualizar mesmo sem completar
    if ($progresso < 100 && !current_user_can('manage_options')) {
        return '<div class="mc-container" style="text-align: center; padding: 40px;">
            <h3 style="color: var(--text-heading, #fff);">Curso em andamento</h3>
            <p style="color: var(--text-muted, #888);">Conclua todas as aulas para desbloquear seu certificado. Progresso atual: ' . $progresso . '%</p>
            <div style="margin-top:20px;">
                <a href="' . get_permalink($curso_id) . '" class="mc-btn-save">Voltar ao Curso</a>
            </div>
        </div>';
    }

    $curso_titulo = get_the_title($curso_id);
    // Data de "hoje" ou data de conclusão real se tivesse salvando timestamp de conclusão do curso.
    // Como listar-aulas.php não parece salvar a data exata de conclusão do *curso* (apenas aulas), usaremos a data atual na emissão.
    $data_conclusao = date_i18n(get_option('date_format'), current_time('timestamp'));

    ob_start();
    ?>
    <style>
        /* CSS Específico para o Certificado em Tela */
        .cert-wrapper {
            background: #121212;
            /* Dark theme wrapper */
            padding: 40px;
            text-align: center;
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            border-radius: 12px;
        }

        .cert-container {
            position: relative;
            max-width: 1000px;
            /* Largura base para visualização */
            margin: 0 auto 30px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            background: #fff;
            color: <?php echo $color; ?>;
            overflow: hidden;
            /* Mantém aspect ratio A4 aproximado */
            aspect-ratio: 297/210;
            font-size: <?php echo $font_size; ?>;
            user-select: none;
        }

        .cert-bg {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .cert-element {
            position: absolute;
            transform: translate(-50%, -50%);
            /* Centraliza no ponto X,Y */
            white-space: nowrap;
            text-align: center;
            font-weight: bold;
        }

        .cert-print-btn {
            background-color: #FDC110;
            color: #000;
            border: none;
            padding: 12px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 6px;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: inline-block;
        }

        .cert-print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(253, 193, 16, 0.4);
        }

        /* ESTILOS DE IMPRESSÃO */
        @media print {
            @page {
                size: landscape;
                margin: 0;
            }

            body * {
                visibility: hidden;
            }

            .cert-container,
            .cert-container * {
                visibility: visible;
            }

            .cert-container {
                position: fixed;
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                margin: 0;
                box-shadow: none;
                border: none;
                z-index: 9999;
                max-width: none;
                aspect-ratio: auto;
            }

            /* Esconde botões na impressão */
            .cert-actions,
            .site-header,
            .site-footer,
            #wpadminbar {
                display: none !important;
            }
        }
    </style>

    <div class="cert-wrapper">
        <h2 style="margin-bottom: 20px; color: #fff;">Parabéns,
            <?php echo esc_html($user_data->first_name); ?>!
        </h2>
        <p style="margin-bottom: 30px; color: #aaa;">Aqui está o seu certificado de conclusão do curso <strong>
                <?php echo esc_html($curso_titulo); ?>
            </strong>.</p>

        <div class="cert-container" id="printable-cert">
            <?php if ($bg_url): ?>
                <img src="<?php echo esc_url($bg_url); ?>" class="cert-bg" alt="Fundo Certificado">
            <?php else: ?>
                <div
                    style="width:100%; height:100%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#333;">
                    Configure a imagem de fundo no admin
                </div>
            <?php endif; ?>

            <div class="cert-element"
                style="top: <?php echo esc_attr($nome_top); ?>; left: <?php echo esc_attr($nome_left); ?>;">
                <?php echo esc_html($nome_aluno); ?>
            </div>

            <div class="cert-element"
                style="top: <?php echo esc_attr($curso_top); ?>; left: <?php echo esc_attr($curso_left); ?>;">
                <?php echo esc_html($curso_titulo); ?>
            </div>

            <div class="cert-element"
                style="top: <?php echo esc_attr($data_top); ?>; left: <?php echo esc_attr($data_left); ?>;">
                <?php echo esc_html($data_conclusao); ?>
            </div>
        </div>

        <div class="cert-actions">
            <button onclick="window.print();" class="cert-print-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 10px;">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimir / Salvar PDF
            </button>
            <br><br>
            <a href="<?php echo get_permalink($curso_id); ?>" style="color: #888; text-decoration: none; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 5px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
                Voltar ao curso
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
