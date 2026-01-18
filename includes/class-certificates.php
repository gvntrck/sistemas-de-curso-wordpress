<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Certificates
{
    /**
     * class-certificates.php
     *
     * Registra o Custom Post Type 'Certificado' e gerencia suas funcionalidades.
     * Adiciona metaboxes para configuração visual dos certificados (imagem de fundo, posições, cores) e permite vincular um modelo de certificado a um curso específico.
     *
     * @package SistemaCursos
     * @version 1.1.8
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_cpt']);
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
        add_action('save_post', [$this, 'save_meta']);
        add_action('admin_print_footer_scripts', [$this, 'print_media_script']);
    }

    public function register_cpt()
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
            'all_items' => 'Certificados',
            'search_items' => 'Buscar Modelos',
            'not_found' => 'Nenhum modelo encontrado',
            'not_found_in_trash' => 'Nenhum modelo na lixeira'
        ];

        $args = [
            'labels' => $labels,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lms-suporte-rapido',
            'menu_icon' => 'dashicons-awards',
            'supports' => ['title'],
            'capability_type' => 'post',
            'map_meta_cap' => true,
        ];

        register_post_type('certificado', $args);
    }

    public function add_metaboxes()
    {
        add_meta_box(
            'certificado_config',
            'Configurações do Layout',
            [$this, 'render_metabox_config'],
            'certificado',
            'normal',
            'high'
        );

        // Metabox para Curso
        add_meta_box(
            'curso_certificado_select',
            'Certificado de Conclusão',
            [$this, 'render_metabox_selection'],
            'curso',
            'side',
            'default'
        );

        // Metabox para Grupo de Alunos
        add_meta_box(
            'grupo_certificado_select',
            'Certificado Específico da Turma',
            [$this, 'render_metabox_selection'],
            'grupo',
            'side',
            'default'
        );
    }

    public function admin_scripts($hook)
    {
        global $post_type;
        if ('certificado' === $post_type) {
            wp_enqueue_media();
        }
    }

    public function print_media_script()
    {
        global $post_type;
        if ('certificado' !== $post_type) {
            return;
        }
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
    }

    public function render_metabox_config($post)
    {
        wp_nonce_field('certificado_save_config', 'certificado_nonce');

        $bg_url = get_post_meta($post->ID, '_cert_bg_url', true);
        $nome_top = get_post_meta($post->ID, '_cert_nome_top', true) ?: '40%';
        $nome_left = get_post_meta($post->ID, '_cert_nome_left', true) ?: '50%';
        $curso_top = get_post_meta($post->ID, '_cert_curso_top', true) ?: '55%';
        $curso_left = get_post_meta($post->ID, '_cert_curso_left', true) ?: '50%';
        $data_top = get_post_meta($post->ID, '_cert_data_top', true) ?: '70%';
        $data_left = get_post_meta($post->ID, '_cert_data_left', true) ?: '50%';
        $color = get_post_meta($post->ID, '_cert_color', true) ?: '#000000';
        $font_size = get_post_meta($post->ID, '_cert_font_size', true) ?: '24px';
        $font_family = get_post_meta($post->ID, '_cert_font_family', true) ?: 'Roboto';
        $show_curso = get_post_meta($post->ID, '_cert_show_curso', true);
        $show_data = get_post_meta($post->ID, '_cert_show_data', true);

        // Lista de fontes do Google Fonts populares para certificados
        $google_fonts = [
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
            'Playfair Display' => 'Playfair Display',
            'Merriweather' => 'Merriweather',
            'Dancing Script' => 'Dancing Script',
            'Great Vibes' => 'Great Vibes',
            'Pacifico' => 'Pacifico',
            'Lobster' => 'Lobster',
            'Cinzel' => 'Cinzel',
            'Cormorant Garamond' => 'Cormorant Garamond',
            'Libre Baskerville' => 'Libre Baskerville',
            'Crimson Text' => 'Crimson Text',
            'Josefin Sans' => 'Josefin Sans',
            'Raleway' => 'Raleway',
            'Poppins' => 'Poppins',
            'Nunito' => 'Nunito',
            'Quicksand' => 'Quicksand',
            'Sacramento' => 'Sacramento',
        ];
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
            <div class="cert-inputs" style="margin-top: 10px;">
                <label>Fonte (Google Fonts):
                    <select name="cert_font_family" id="cert_font_family" style="min-width: 200px;">
                        <?php foreach ($google_fonts as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($font_family, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <span id="font_preview" style="margin-left: 15px; font-size: 18px;">Prévia da Fonte</span>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                function loadFontPreview(fontName) {
                    var fontUrl = 'https://fonts.googleapis.com/css2?family=' + fontName.replace(/ /g, '+') + '&display=swap';
                    $('head').append('<link rel="stylesheet" href="' + fontUrl + '" type="text/css">');
                    $('#font_preview').css('font-family', '"' + fontName + '", sans-serif');
                }

                // Carregar fonte inicial
                loadFontPreview($('#cert_font_family').val());

                // Atualizar ao mudar
                $('#cert_font_family').on('change', function () {
                    loadFontPreview($(this).val());
                });
            });
        </script>

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
                    <input type="checkbox" name="cert_show_curso" value="1" <?php checked($show_curso, '1'); ?>> Exibir Nome do
                    Curso?
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
                    <input type="checkbox" name="cert_show_data" value="1" <?php checked($show_data, '1'); ?>> Exibir Data de
                    Conclusão?
                </label>
            </div>
            <div class="cert-inputs">
                <label>Top: <input type="text" name="cert_data_top" value="<?php echo esc_attr($data_top); ?>"
                        class="tiny-text"></label>
                <label>Left: <input type="text" name="cert_data_left" value="<?php echo esc_attr($data_left); ?>"
                        class="tiny-text"></label>
            </div>
        </div>

        <p class="description">Utilize valores em porcentagem (%) para posicionamento relativo ao tamanho da imagem.</p>
        <?php
    }

    public function render_metabox_selection($post)
    {
        wp_nonce_field('sistema_cursos_cert_selection', 'cert_selection_nonce');

        // Define a meta key baseada no post type
        $meta_key = ($post->post_type === 'grupo') ? '_grupo_certificado_id' : '_curso_certificado_id';
        $selected = get_post_meta($post->ID, $meta_key, true);

        $certificados = get_posts([
            'post_type' => 'certificado',
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);

        if (empty($certificados)) {
            echo '<p>Nenhum modelo de certificado encontrado. <a href="' . admin_url('post-new.php?post_type=certificado') . '">Crie um modelo primeiro</a>.</p>';
            return;
        }

        echo '<label for="certificado_id_select" style="display:block; margin-bottom:5px;">Selecione o modelo:</label>';
        echo '<select name="certificado_id_select" id="certificado_id_select" style="width:100%;">';
        echo '<option value="">-- ' . ($post->post_type === 'grupo' ? 'Padrão do Curso' : 'Nenhum') . ' --</option>';

        foreach ($certificados as $cert) {
            $is_selected = ($selected == $cert->ID) ? 'selected' : '';
            echo '<option value="' . $cert->ID . '" ' . $is_selected . '>' . esc_html($cert->post_title) . '</option>';
        }

        echo '</select>';

        if ($post->post_type === 'grupo') {
            echo '<p class="description">Se selecionado, este modelo substituirá o modelo padrão do curso para os alunos desta turma.</p>';
        } else {
            echo '<p class="description">Este modelo será usado para gerar o certificado deste curso.</p>';
        }
    }

    public function save_meta($post_id)
    {
        // 1. Salvar Config do Certificado (Post Type: Certificado)
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
                '_cert_font_size',
                '_cert_font_family'
            ];

            foreach ($fields as $field) {
                $key = substr($field, 1);
                if (isset($_POST[$key])) {
                    update_post_meta($post_id, $field, sanitize_text_field($_POST[$key]));
                }
            }

            $checkboxes = ['_cert_show_curso', '_cert_show_data'];
            foreach ($checkboxes as $cb) {
                $key = substr($cb, 1);
                if (isset($_POST[$key])) {
                    update_post_meta($post_id, $cb, '1');
                } else {
                    delete_post_meta($post_id, $cb);
                }
            }
        }

        // 2. Salvar Seleção de Certificado (Post Types: Curso e Grupo)
        if (isset($_POST['cert_selection_nonce']) && wp_verify_nonce($_POST['cert_selection_nonce'], 'sistema_cursos_cert_selection')) {
            $post_type = get_post_type($post_id);
            $meta_key = ($post_type === 'grupo') ? '_grupo_certificado_id' : '_curso_certificado_id';

            if (isset($_POST['certificado_id_select'])) {
                $val = sanitize_text_field($_POST['certificado_id_select']);
                if (!empty($val)) {
                    update_post_meta($post_id, $meta_key, $val);
                } else {
                    delete_post_meta($post_id, $meta_key);
                }
            }
        }
    }
}
