<?php
/**
 * Snippet para Sistema de Certificados Avançado
 * Description: Gera certificados personalizados por curso via CPT.
 * 
 *  */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * 1. REGISTRO DO CPT 'CERTIFICADO'
 */
add_action('init', 'certificado_register_cpt');
function certificado_register_cpt()
{
    $labels = [
        'name' => 'Certificados',
        'singular_name' => 'Certificado',
        'menu_name' => 'Certificados',
        'add_new' => 'Novo Modelo',
        'add_new_item' => 'Adicionar Novo Modelo',
        'edit_item' => 'Editar Modelo',
        'new_item' => 'Novo Modelo',
        'view_item' => 'Ver Modelo',
        'all_items' => 'Todos os Modelos',
        'search_items' => 'Buscar Modelos',
        'not_found' => 'Nenhum modelo encontrado',
        'not_found_in_trash' => 'Nenhum modelo na lixeira'
    ];

    $args = [
        'labels' => $labels,
        'public' => false, // Não precisa de URL frontend
        'show_ui' => true,  // Mostra no admin
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-awards',
        'supports' => ['title'], // Apenas título, o resto é meta
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ];

    register_post_type('certificado', $args);
}

/**
 * 2. META BOXES PARA CONFIGURAÇÃO DO CERTIFICADO
 */
add_action('add_meta_boxes', 'certificado_add_metaboxes');
function certificado_add_metaboxes()
{
    // Metabox no CPT 'certificado' para configurar layout
    add_meta_box(
        'certificado_config',
        'Configurações do Layout',
        'certificado_render_metabox_config',
        'certificado',
        'normal',
        'high'
    );

    // Metabox no CPT 'curso' para selecionar o certificado
    add_meta_box(
        'curso_certificado_select',
        'Certificado de Conclusão',
        'certificado_render_metabox_curso',
        'curso',
        'side',
        'default'
    );
}

/**
 * Scripts para Upload de Mídia no Admin
 */
add_action('admin_enqueue_scripts', 'certificado_admin_scripts');
function certificado_admin_scripts($hook)
{
    global $post_type;

    // Carrega apenas na edição do CPT certificado
    if ('certificado' !== $post_type) {
        return;
    }

    wp_enqueue_media();

    // Injeta o script diretamente no footer para garantir funcionamento
    add_action('admin_print_footer_scripts', function () {
        ?>
        <script>
            jQuery(document).ready(function ($) {
                var mediaUploader;
                $('#btn_upload_bg').on('click', function (e) {
                    e.preventDefault();
                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }
                    mediaUploader = wp.media.frames.file_frame = wp.media({
                        title: 'Escolher Imagem de Fundo',
                        button: { text: 'Usar esta imagem' },
                        multiple: false
                    });
                    mediaUploader.on('select', function () {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#certificado_bg_url').val(attachment.url);
                        $('#preview_bg').attr('src', attachment.url).show();
                    });
                    mediaUploader.open();
                });
            });
        </script>
        <?php
    });
}

/**
 * Renderiza Metabox de Configuração (CPT Certificado)
 */
function certificado_render_metabox_config($post)
{
    wp_nonce_field('certificado_save_config', 'certificado_nonce');

    // Recuperar valores salvos
    $bg_url = get_post_meta($post->ID, '_cert_bg_url', true);

    $nome_top = get_post_meta($post->ID, '_cert_nome_top', true) ?: '40%';
    $nome_left = get_post_meta($post->ID, '_cert_nome_left', true) ?: '50%';

    $curso_top = get_post_meta($post->ID, '_cert_curso_top', true) ?: '55%';
    $curso_left = get_post_meta($post->ID, '_cert_curso_left', true) ?: '50%';

    $data_top = get_post_meta($post->ID, '_cert_data_top', true) ?: '70%';
    $data_left = get_post_meta($post->ID, '_cert_data_left', true) ?: '50%';

    $color = get_post_meta($post->ID, '_cert_color', true) ?: '#000000';
    $font_size = get_post_meta($post->ID, '_cert_font_size', true) ?: '24px';

    // Novos campos de visibilidade (Padrão: desligado/vazio se não salvo, mas queremos explícito)
    $show_curso = get_post_meta($post->ID, '_cert_show_curso', true);
    $show_data = get_post_meta($post->ID, '_cert_show_data', true);

    ?>
    <style>
        .cert-row {
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .cert-label {
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
        }

        .cert-inputs {
            display: flex;
            gap: 10px;
            align-items: center;
        }
    </style>

    <div class="cert-row">
        <span class="cert-label">Imagem de Fundo</span>
        <div style="display: flex; gap: 10px; align-items: center;">
            <input type="text" name="cert_bg_url" id="certificado_bg_url" value="<?php echo esc_attr($bg_url); ?>"
                class="large-text">
            <button type="button" class="button" id="btn_upload_bg">Selecionar Imagem</button>
        </div>
        <div style="margin-top: 10px;">
            <img id="preview_bg" src="<?php echo esc_url($bg_url); ?>"
                style="max-width: 100%; height: auto; border: 1px solid #ccc; <?php echo $bg_url ? '' : 'display:none;'; ?>">
        </div>
    </div>

    <div class="cert-row">
        <span class="cert-label">Estilos Globais</span>
        <div class="cert-inputs">
            <label>Cor do Texto: <input type="color" name="cert_color" value="<?php echo esc_attr($color); ?>"></label>
            <label>Tamanho da Fonte: <input type="text" name="cert_font_size" value="<?php echo esc_attr($font_size); ?>"
                    class="tiny-text"> (ex: 24px)</label>
        </div>
    </div>

    <div class="cert-row">
        <span class="cert-label">Posição: Nome do Aluno</span>
        <div class="cert-inputs">
            <label>Top: <input type="text" name="cert_nome_top" value="<?php echo esc_attr($nome_top); ?>"
                    class="tiny-text"></label>
            <label>Left: <input type="text" name="cert_nome_left" value="<?php echo esc_attr($nome_left); ?>"
                    class="tiny-text"></label>
        </div>
    </div>

    <div class="cert-row">
        <div class="cert-inputs" style="margin-bottom: 10px;">
            <label style="font-weight: bold;">
                <input type="checkbox" name="cert_show_curso" value="1" <?php checked($show_curso, '1'); ?>>
                Exibir Nome do Curso?
            </label>
        </div>
        <div class="cert-inputs">
            <label>Top: <input type="text" name="cert_curso_top" value="<?php echo esc_attr($curso_top); ?>"
                    class="tiny-text"></label>
            <label>Left: <input type="text" name="cert_curso_left" value="<?php echo esc_attr($curso_left); ?>"
                    class="tiny-text"></label>
        </div>
    </div>

    <div class="cert-row">
        <div class="cert-inputs" style="margin-bottom: 10px;">
            <label style="font-weight: bold;">
                <input type="checkbox" name="cert_show_data" value="1" <?php checked($show_data, '1'); ?>>
                Exibir Data de Conclusão?
            </label>
        </div>
        <div class="cert-inputs">
            <label>Top: <input type="text" name="cert_data_top" value="<?php echo esc_attr($data_top); ?>"
                    class="tiny-text"></label>
            <label>Left: <input type="text" name="cert_data_left" value="<?php echo esc_attr($data_left); ?>"
                    class="tiny-text"></label>
        </div>
    </div>

    <p class="description">Utilize valores em porcentagem (%) para posicionamento relativo ao tamanho da imagem. Padrão:
        Nome do Curso e Data desligados.</p>
    <?php
}

/**
 * Renderiza Metabox de Seleção (CPT Curso)
 */
function certificado_render_metabox_curso($post)
{
    wp_nonce_field('curso_certificado_save', 'curso_certificado_nonce');

    $selected = get_post_meta($post->ID, '_curso_certificado_id', true);

    // Buscar todos os modelos de certificado
    $certificados = get_posts([
        'post_type' => 'certificado',
        'numberposts' => -1,
        'post_status' => 'publish'
    ]);

    if (empty($certificados)) {
        echo '<p>Nenhum modelo de certificado encontrado. <a href="' . admin_url('post-new.php?post_type=certificado') . '">Crie um modelo primeiro</a>.</p>';
        return;
    }

    echo '<label for="curso_certificado_id" style="display:block; margin-bottom:5px;">Selecione o modelo:</label>';
    echo '<select name="curso_certificado_id" id="curso_certificado_id" style="width:100%;">';
    echo '<option value="">-- Nenhum --</option>';

    foreach ($certificados as $cert) {
        $is_selected = ($selected == $cert->ID) ? 'selected' : '';
        echo '<option value="' . $cert->ID . '" ' . $is_selected . '>' . esc_html($cert->post_title) . '</option>';
    }

    echo '</select>';
    echo '<p class="description">Este modelo será usado para gerar o certificado deste curso.</p>';
}

/**
 * Salvar Meta Data
 */
add_action('save_post', 'certificado_save_meta');
function certificado_save_meta($post_id)
{
    // 1. Salvar Config do Certificado
    if (isset($_POST['certificado_nonce']) && wp_verify_nonce($_POST['certificado_nonce'], 'certificado_save_config')) {
        $fields = [
            '_cert_bg_url',
            '_cert_nome_top',
            '_cert_nome_left',
            '_cert_curso_top',
            '_cert_curso_left',
            '_cert_data_top',
            '_cert_data_left',
            '_cert_color',
            '_cert_font_size'
        ];

        foreach ($fields as $field) {
            $key = substr($field, 1); // remove _
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$key]));
            }
        }

        // Checkboxes (se não vier no POST, é false/delete)
        $checkboxes = ['_cert_show_curso', '_cert_show_data'];
        foreach ($checkboxes as $cb) {
            $key = substr($cb, 1);
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $cb, '1');
            } else {
                delete_post_meta($post_id, $cb); // Ou update para '0'
            }
        }
    }

    // 2. Salvar Seleção no Curso
    if (isset($_POST['curso_certificado_nonce']) && wp_verify_nonce($_POST['curso_certificado_nonce'], 'curso_certificado_save')) {
        if (isset($_POST['curso_certificado_id'])) {
            update_post_meta($post_id, '_curso_certificado_id', sanitize_text_field($_POST['curso_certificado_id']));
        }
    }
}


/**
 * 3. SHORTCODE ATUALIZADO
 */
add_shortcode('certificado', 'certificado_shortcode');
function certificado_shortcode($atts)
{
    if (!defined('ABSPATH'))
        return;

    $atts = shortcode_atts([], $atts, 'certificado');

    // Verifica Login
    if (!is_user_logged_in()) {
        return '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">
            Você precisa estar logado para acessar seus certificados. <a href="' . wp_login_url(get_permalink()) . '" style="color: inherit; text-decoration: underline;">Fazer login</a>
        </div>';
    }

    $user_id = get_current_user_id();
    $curso_id = isset($_GET['curso_id']) ? intval($_GET['curso_id']) : 0;

    // --- MODO LISTAGEM: Se não tem curso_id, listar disponíveis ---
    if ($curso_id <= 0) {
        // Busca direta no banco para garantir acesso vitalício (independente de matrícula ativa)
        global $wpdb;
        $tabela_usermeta = $wpdb->usermeta;

        // Query para encontrar todos os metais de progresso = 100 deste usuário
        // O meta_key é 'progresso_curso_ID'
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
                // Extrair ID do curso da string 'progresso_curso_123'
                $id_curso = str_replace('progresso_curso_', '', $meta_key);

                // Validar se é um curso existente e publicado
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

        // Renderizar Grid
        ob_start();
        ?>
        <style>
            .cert-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .cert-card {
                background: #1a1a1a;
                border: 1px solid #333;
                border-radius: 12px;
                padding: 20px;
                text-decoration: none;
                color: #fff;
                display: flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
                transition: transform 0.2s, border-color 0.2s;
            }

            .cert-card:hover {
                transform: translateY(-5px);
                border-color: #FDC110;
                color: #FDC110;
            }

            .cert-card h4 {
                margin: 15px 0 10px;
                font-size: 1.1rem;
            }

            .cert-icon {
                color: #FDC110;
                width: 48px;
                height: 48px;
            }

            .cert-btn-mini {
                margin-top: auto;
                background: #2a2a2a;
                color: #fff;
                padding: 8px 16px;
                border-radius: 4px;
                font-size: 0.9rem;
            }

            .cert-card:hover .cert-btn-mini {
                background: #FDC110;
                color: #000;
            }
        </style>
        <div class="cert-grid">
            <?php foreach ($certificados_disponiveis as $id):
                $titulo = get_the_title($id);
                $link = add_query_arg('curso_id', $id, get_permalink());
                ?>
                <a href="<?php echo esc_url($link); ?>" class="cert-card">
                    <svg class="cert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M12 15l-2 5l-7-4.14l0 -6.86l9 -5l9 5l0 6.86l-7 4.14z" />
                        <circle cx="12" cy="9" r="3" />
                    </svg>
                    <h4><?php echo esc_html($titulo); ?></h4>
                    <span class="cert-btn-mini">Ver Certificado</span>
                </a>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // --- MODO VISUALIZAÇÃO: Se tem curso_id, exibir certificado ---

    // Busca o modelo de certificado associado ao curso
    $cert_id = get_post_meta($curso_id, '_curso_certificado_id', true);
    if (!$cert_id) {
        // Fallback: tentar pegar o primeiro ou exibir erro
        return '<div class="mc-alert" style="color:red; text-align:center;">Certificado não configurado para este curso. Contate o suporte.</div>';
    }

    // Carrega configurações do modelo $cert_id
    $bg_url = get_post_meta($cert_id, '_cert_bg_url', true);
    $nome_top = get_post_meta($cert_id, '_cert_nome_top', true) ?: '40%';
    $nome_left = get_post_meta($cert_id, '_cert_nome_left', true) ?: '50%';
    $curso_top = get_post_meta($cert_id, '_cert_curso_top', true) ?: '55%';
    $curso_left = get_post_meta($cert_id, '_cert_curso_left', true) ?: '50%';
    $data_top = get_post_meta($cert_id, '_cert_data_top', true) ?: '70%';
    $data_left = get_post_meta($cert_id, '_cert_data_left', true) ?: '50%';
    $color = get_post_meta($cert_id, '_cert_color', true) ?: '#000000';
    $font_size = get_post_meta($cert_id, '_cert_font_size', true) ?: '24px';

    // Visibilidade
    $show_curso = get_post_meta($cert_id, '_cert_show_curso', true);
    $show_data = get_post_meta($cert_id, '_cert_show_data', true);

    $user_id = get_current_user_id();
    $user_data = get_userdata($user_id);

    // Nome do aluno
    $nome_aluno = $user_data->first_name . ' ' . $user_data->last_name;
    if (trim($nome_aluno) === '') {
        $nome_aluno = $user_data->display_name;
    }

    // Verifica Conclusão
    $progresso = (int) get_user_meta($user_id, "progresso_curso_{$curso_id}", true);

    // Verifica se admin (preview)
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
    <style>
        .cert-wrapper {
            background: #121212;
            padding: 40px;
            text-align: center;
            font-family: 'Segoe UI', sans-serif;
            color: #fff;
            border-radius: 12px;
        }

        .cert-container {
            position: relative;
            max-width: 1000px;
            margin: 0 auto 30px auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            background: #fff;
            color:
                <?php echo esc_attr($color); ?>
            ;
            overflow: hidden;
            aspect-ratio: 297/210;
            font-size:
                <?php echo esc_attr($font_size); ?>
            ;
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
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .cert-print-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(253, 193, 16, 0.4);
        }

        @media print {
            @page {
                size: landscape;
                margin: 0;
            }

            html,
            body {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden;
                /* Evita paginas extras */
            }

            /* Esconde tudo */
            body * {
                visibility: hidden;
            }

            /* Garante que o container do certificado e seus filhos sejam visíveis */
            .cert-container,
            .cert-container * {
                visibility: visible;
            }

            /* Oculta áreas específicas para garantir que não ocupem espaço */
            .site-header,
            .site-footer,
            #wpadminbar,
            .cert-actions,
            .cert-wrapper>h2,
            .cert-wrapper>p {
                display: none !important;
            }

            .cert-container {
                position: fixed;
                /* Fixed garante que fique na tela de impressão independente da posição no DOM */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                box-shadow: none;
                border: none;
                z-index: 99999;
                max-width: none;
                aspect-ratio: auto;
                background: white;
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                display: block !important;
            }
        }
    </style>

    <div class="cert-wrapper">
        <h2 style="margin-bottom: 20px; color: #fff;">Parabéns, <?php echo esc_html($user_data->first_name); ?>!</h2>
        <p style="margin-bottom: 30px; color: #aaa;">Aqui está o seu certificado de conclusão do curso
            <strong><?php echo esc_html($curso_titulo); ?></strong>.
        </p>

        <div class="cert-container" id="printable-cert">
            <?php if ($bg_url): ?>
                <img src="<?php echo esc_url($bg_url); ?>" class="cert-bg" alt="Fundo Certificado">
            <?php else: ?>
                <div
                    style="width:100%; height:100%; background:#f0f0f0; display:flex; align-items:center; justify-content:center; color:#333;">
                    Fundo não configurado.
                </div>
            <?php endif; ?>

            <!-- Nome do Aluno (Sempre visível) -->
            <div class="cert-element"
                style="top: <?php echo esc_attr($nome_top); ?>; left: <?php echo esc_attr($nome_left); ?>;">
                <?php echo esc_html($nome_aluno); ?>
            </div>

            <?php if ($show_curso === '1'): ?>
                <div class="cert-element"
                    style="top: <?php echo esc_attr($curso_top); ?>; left: <?php echo esc_attr($curso_left); ?>;">
                    <?php echo esc_html($curso_titulo); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_data === '1'): ?>
                <div class="cert-element"
                    style="top: <?php echo esc_attr($data_top); ?>; left: <?php echo esc_attr($data_left); ?>;">
                    <?php echo esc_html($data_conclusao); ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="cert-actions">
            <button onclick="window.print();" class="cert-print-btn">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"></polyline>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                    <rect x="6" y="14" width="12" height="8"></rect>
                </svg>
                Imprimir / Salvar PDF
            </button>
            </a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
