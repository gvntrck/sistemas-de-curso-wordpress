<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_CPT_Manager
{
    /**
     * class-cpt-manager.php
     *
     * Gerencia o registro de Custom Post Types (Curso, Aula, Trilha) e seus metaboxes nativos,
     * removendo a dependência do plugin ACF.
     *
     * @package SistemaCursos
     * @version 1.0.0
     */
    public function __construct()
    {
        add_action('init', [$this, 'register_cpts']);
        add_action('add_meta_boxes', [$this, 'add_metaboxes']);
        add_action('save_post', [$this, 'save_metaboxes']);
        add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
    }

    /**
     * Registra os CPTs: Trilha, Curso, Aula
     */
    public function register_cpts()
    {
        // 1. Trilha
        $labels_trilha = [
            'name' => 'Trilhas',
            'singular_name' => 'Trilha',
            'menu_name' => 'Trilhas',
            'add_new' => 'Nova Trilha',
            'add_new_item' => 'Adicionar Nova Trilha',
            'edit_item' => 'Editar Trilha',
            'all_items' => 'Trilhas',
        ];
        register_post_type('trilha', [
            'labels' => $labels_trilha,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'lms-suporte-rapido',
            'menu_icon' => 'dashicons-randomize',
            'supports' => ['title', 'thumbnail', 'page-attributes'],
            'rewrite' => ['slug' => 'trilha'],
            'has_archive' => true,
        ]);

        // 2. Curso
        $labels_curso = [
            'name' => 'Cursos',
            'singular_name' => 'Curso',
            'menu_name' => 'Cursos',
            'add_new' => 'Novo Curso',
            'add_new_item' => 'Adicionar Novo Curso',
            'edit_item' => 'Editar Curso',
            'all_items' => 'Cursos',
        ];
        register_post_type('curso', [
            'labels' => $labels_curso,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'lms-suporte-rapido',
            'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title', 'thumbnail', 'page-attributes'],
            'rewrite' => ['slug' => 'curso'],
            'has_archive' => true,
        ]);

        // 3. Aula
        $labels_aula = [
            'name' => 'Aulas',
            'singular_name' => 'Aula',
            'menu_name' => 'Aulas',
            'add_new' => 'Nova Aula',
            'add_new_item' => 'Adicionar Nova Aula',
            'edit_item' => 'Editar Aula',
            'all_items' => 'Aulas',
        ];
        register_post_type('aula', [
            'labels' => $labels_aula,
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => 'lms-suporte-rapido',
            'menu_icon' => 'dashicons-video-alt3',
            'supports' => ['title', 'page-attributes'], // page-attributes para 'menu_order'
            'rewrite' => ['slug' => 'aula'],
            'has_archive' => false,
        ]);

        // 4. Grupo de Alunos
        $labels_grupo = [
            'name' => 'Grupos de Alunos',
            'singular_name' => 'Grupo de Alunos',
            'menu_name' => 'Grupos de Alunos',
            'add_new' => 'Novo Grupo',
            'add_new_item' => 'Adicionar Novo Grupo',
            'edit_item' => 'Editar Grupo',
            'all_items' => 'Grupos',
        ];
        register_post_type('grupo', [
            'labels' => $labels_grupo,
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'lms-suporte-rapido',
            'menu_icon' => 'dashicons-groups',
            'supports' => ['title'],
            'rewrite' => false,
            'has_archive' => false,
        ]);
    }

    /**
     * Adiciona Metaboxes para os campos personalizados
     */
    public function add_metaboxes()
    {
        // Campos do Curso
        add_meta_box(
            'curso_custom_fields',
            'Configurações do Curso',
            [$this, 'render_curso_metabox'],
            'curso',
            'normal',
            'high'
        );



        // Campos da Aula
        add_meta_box(
            'aula_custom_fields',
            'Configurações da Aula',
            [$this, 'render_aula_metabox'],
            'aula',
            'normal',
            'high'
        );

        // Relationship da Aula (Side)
        add_meta_box(
            'aula_relationship',
            'Vincular ao Curso',
            [$this, 'render_aula_relationship'],
            'aula',
            'side',
            'default'
        );

        // Campos da Trilha
        add_meta_box(
            'trilha_custom_fields',
            'Configurações da Trilha',
            [$this, 'render_trilha_metabox'],
            'trilha',
            'normal',
            'high'
        );



        // Gerenciamento de Alunos (Grupo)
        add_meta_box(
            'grupo_alunos_manager',
            'Gerenciar Alunos do Grupo',
            [$this, 'render_grupo_alunos_metabox'],
            'grupo',
            'normal',
            'high'
        );

        // Gerenciamento de Conteúdos (Grupo) - LearnDash Style
        add_meta_box(
            'grupo_conteudos_manager',
            'Conteúdos do Grupo (Cursos e Trilhas)',
            [$this, 'render_grupo_conteudos_metabox'],
            'grupo',
            'normal',
            'default'
        );

        // Gerenciamento de Cursos na Trilha
        add_meta_box(
            'trilha_cursos_manager',
            'Gerenciar Cursos da Trilha',
            [$this, 'render_trilha_cursos_metabox'],
            'trilha',
            'normal',
            'default'
        );

        // Gerenciamento de Aulas no Curso
        add_meta_box(
            'curso_aulas_manager',
            'Gerenciar Aulas do Curso',
            [$this, 'render_curso_aulas_metabox'],
            'curso',
            'normal',
            'default'
        );

        // Criacao rapida de aulas (estilo LearnDash)
        add_meta_box(
            'curso_quick_add_aulas',
            'Adicionar Novas Aulas',
            [$this, 'render_curso_quick_add_aulas_metabox'],
            'curso',
            'normal',
            'high'
        );
    }

    public function admin_scripts($hook)
    {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Verifica se estamos na tela de edição dos CPTs 'curso' ou 'aula'
        if (in_array($screen->post_type, ['curso', 'aula'])) {
            wp_enqueue_media();
            wp_enqueue_script(
                'sistema-cursos-admin',
                plugins_url('assets/js/admin-metaboxes.js', dirname(__DIR__) . '/sistema-cursos-plugin.php'),
                ['jquery', 'jquery-ui-sortable'],
                '1.0.4',
                true
            );

            // Passar dados para o JS se necessario
            wp_localize_script('sistema-cursos-admin', 'sysCursosAdmin', [
                'placeholder_img' => 'https://via.placeholder.com/150'
            ]);
        }
    }

    /**
     * Renderiza Metabox da Trilha
     */
    public function render_trilha_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        $descricao_curta = get_post_meta($post->ID, 'descricao_curta', true);
        ?>
        <p>
            <label for="descricao_curta" style="font-weight:bold; display:block; margin-bottom:5px;">Descrição Curta:</label>
            <textarea name="descricao_curta" id="descricao_curta" rows="3" class="widefat"
                placeholder="Uma breve descrição da trilha..."><?php echo esc_textarea($descricao_curta); ?></textarea>
        <p class="description">Esta descrição será exibida nos cards de listagem de trilhas.</p>
        </p>
        <?php
    }

    /**
     * Renderiza Metabox de Cursos na Trilha
     */
    public function render_trilha_cursos_metabox($post)
    {
        // Buscar todos os cursos
        $cursos = get_posts([
            'post_type' => 'curso',
            'numberposts' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
            'post_status' => 'publish'
        ]);

        echo '<div style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; background: #fff;">';

        if (empty($cursos)) {
            echo '<p>Nenhum curso encontrado.</p>';
        } else {
            foreach ($cursos as $curso) {
                // Verifica a qual trilha o curso pertence atualmente
                $current_trilha_id = get_post_meta($curso->ID, 'trilha', true);

                // Checkbox marcado se pertencer a ESTA trilha
                $checked = ($current_trilha_id == $post->ID) ? 'checked' : '';

                // Texto extra se pertencer a OUTRA trilha
                $extra_info = '';
                if ($current_trilha_id && $current_trilha_id != $post->ID) {
                    $other_trilha = get_post($current_trilha_id);
                    $trilha_name = $other_trilha ? $other_trilha->post_title : 'Outra Trilha (ID: ' . $current_trilha_id . ')';
                    $extra_info = ' <span style="color: #d63638; font-size: 0.9em;">(Atualmente na trilha: <strong>' . esc_html($trilha_name) . '</strong>)</span>';
                }

                echo '<div style="margin-bottom: 5px;">';
                echo '<label>';
                echo '<input type="checkbox" name="trilha_cursos[]" value="' . esc_attr($curso->ID) . '" ' . $checked . '> ';
                echo '<strong>' . esc_html($curso->post_title) . '</strong>';
                echo $extra_info;
                echo '</label>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '<p class="description">Selecione os cursos que pertencem a esta trilha. Atenção: Se um curso já estiver em outra trilha, ele será movido para esta.</p>';
    }

    /**
     * Renderiza Metabox do Curso
     */
    public function render_curso_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        // Field: trilha (Relationship)
        $trilha_id = get_post_meta($post->ID, 'trilha', true);
        $comments_course_enabled = get_post_meta($post->ID, '_sistema_cursos_comments_course_enabled', true);

        // Buscar todas as trilhas
        $trilhas = get_posts(['post_type' => 'trilha', 'numberposts' => -1, 'post_status' => 'publish']);

        // Field: capa_vertical (Image)
        $capa_vertical_id = get_post_meta($post->ID, 'capa_vertical', true);
        $capa_preview = '';
        if ($capa_vertical_id) {
            $capa_preview = wp_get_attachment_image_url($capa_vertical_id, 'medium');
        }
        ?>
        <div class="system-cursors-metabox">
            <p>
                <label for="trilha_id" style="font-weight:bold; display:block; margin-bottom:5px;">Trilha Pertencente:</label>
                <select name="trilha" id="trilha_id" class="widefat">
                    <option value="">-- Selecione uma Trilha --</option>
                    <?php foreach ($trilhas as $t): ?>
                        <option value="<?php echo $t->ID; ?>" <?php selected($trilha_id, $t->ID); ?>>
                            <?php echo esc_html($t->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <hr>

            <p>
                <label style="font-weight:bold; display:block; margin-bottom:5px;">Capa Vertical (Usada nas listagens):</label>
            <div class="image-preview-wrapper" style="margin-bottom: 10px;">
                <img id="capa_vertical_preview" src="<?php echo esc_url($capa_preview); ?>"
                    style="max-width: 150px; display: <?php echo $capa_preview ? 'block' : 'none'; ?>; border: 1px solid #ccc; padding: 2px;">
            </div>
            <input type="hidden" name="capa_vertical" id="capa_vertical_input"
                value="<?php echo esc_attr($capa_vertical_id); ?>">
            <button type="button" class="button" id="btn_upload_capa">Selecionar Imagem</button>
            <button type="button" class="button button-link-delete" id="btn_remove_capa"
                style="display: <?php echo $capa_preview ? 'inline-block' : 'none'; ?>;">Remover</button>
            </p>

            <hr>

            <p>
                <input type="hidden" name="sistema_cursos_comments_course_enabled" value="0">
                <label for="sistema_cursos_comments_course_enabled" style="display:block; font-weight:600;">
                    <input
                        type="checkbox"
                        id="sistema_cursos_comments_course_enabled"
                        name="sistema_cursos_comments_course_enabled"
                        value="1"
                        <?php checked($comments_course_enabled, '1'); ?>>
                    Ativar comentarios no curso (habilita comentarios nas aulas por padrao)
                </label>
                <span class="description" style="display:block; margin-top:4px;">
                    Padrao: desativado. Ao ativar, todas as aulas do curso passam a aceitar comentarios.
                </span>
            </p>
        </div>
        <?php
    }

    /**
     * Renderiza Metabox de Aulas no Curso
     */
    private function lesson_belongs_to_course($aula_id, $curso_id)
    {
        $aula_id = (int) $aula_id;
        $curso_id = (int) $curso_id;

        if ($aula_id <= 0 || $curso_id <= 0) {
            return false;
        }

        $curso_meta = get_post_meta($aula_id, 'curso', true);

        if (is_array($curso_meta)) {
            $curso_meta = array_map('intval', $curso_meta);
            return in_array($curso_id, $curso_meta, true);
        }

        if (is_numeric($curso_meta)) {
            return ((int) $curso_meta) === $curso_id;
        }

        if (is_string($curso_meta)) {
            if (((int) $curso_meta) === $curso_id) {
                return true;
            }

            return strpos($curso_meta, '"' . (string) $curso_id . '"') !== false;
        }

        return false;
    }

    public function render_curso_aulas_metabox($post)
    {
        // Buscar todas as aulas
        $aulas = get_posts([
            'post_type' => 'aula',
            'numberposts' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => ['publish', 'draft', 'pending', 'future', 'private']
        ]);

        echo '<div style="margin-bottom: 10px;">';
        echo '<label style="margin-right: 16px;">';
        echo '<input type="radio" name="curso_aulas_filter_view" value="course" checked> Somente aulas deste curso';
        echo '</label>';
        echo '<label>';
        echo '<input type="radio" name="curso_aulas_filter_view" value="all"> Todas as aulas do sistema';
        echo '</label>';
        echo '</div>';
        echo '<p id="curso_aulas_manager_empty_filter" class="description" style="display:none; margin-top: 6px;">Nenhuma aula encontrada para este filtro.</p>';

        echo '<div id="curso_aulas_manager_list" style="max-height: 300px; overflow-y: auto; padding: 10px; border: 1px solid #ddd; background: #fff;">';

        if (empty($aulas)) {
            echo '<p>Nenhuma aula encontrada.</p>';
        } else {
            foreach ($aulas as $aula) {
                $belongs_to_this_course = $this->lesson_belongs_to_course($aula->ID, $post->ID);
                $current_curso_meta = get_post_meta($aula->ID, 'curso', true);
                $current_curso_id = is_numeric($current_curso_meta) ? absint($current_curso_meta) : 0;

                // Checkbox marcado se pertencer a ESTE curso
                $checked = $belongs_to_this_course ? 'checked' : '';

                // Texto extra se pertencer a OUTRO curso
                $extra_info = '';
                if ($current_curso_id && $current_curso_id != $post->ID) {
                    $other_curso = get_post($current_curso_id);
                    $curso_name = $other_curso ? $other_curso->post_title : 'Outro Curso (ID: ' . $current_curso_id . ')';
                    $extra_info = ' <span style="color: #d63638; font-size: 0.9em;">(Atualmente no curso: <strong>' . esc_html($curso_name) . '</strong>)</span>';
                }

                $status_label = '';
                if ($aula->post_status !== 'publish') {
                    $status_object = get_post_status_object($aula->post_status);
                    $status_text = $status_object ? $status_object->label : $aula->post_status;
                    $status_label = ' <span style="color:#646970; font-size:0.9em;">[' . esc_html($status_text) . ']</span>';
                }

                echo '<div class="sc-aula-item" data-in-course="' . ($belongs_to_this_course ? '1' : '0') . '" style="margin-bottom: 5px;">';
                echo '<span class="sc-aula-item-handle" style="display:inline-block; cursor:move; color:#646970; margin-right:8px;" title="Arraste para reordenar">&#9776;</span>';
                echo '<label style="display:inline-block; margin-right:6px;">';
                echo '<input type="checkbox" name="curso_aulas[]" value="' . esc_attr($aula->ID) . '" ' . $checked . '>';
                echo '</label>';
                echo '<strong>' . esc_html($aula->post_title) . '</strong>';
                echo $status_label;
                echo $extra_info;
                echo '</div>';
            }
        }

        echo '</div>';
        echo '<input type="hidden" id="curso_aulas_order" name="curso_aulas_order" value="">';
        echo '<p class="description">Arraste as aulas para definir a ordem exibida para o aluno. A listagem do aluno continua usando a ordenacao padrao por <code>menu_order</code>.</p>';
        echo '<p class="description">Selecione as aulas que pertencem a este curso. Atenção: Se uma aula já estiver em outro curso, ela será movida para este.</p>';
    }

    /**
     * Renderiza metabox de criacao rapida de aulas no curso
     */
    public function render_curso_quick_add_aulas_metabox($post)
    {
        echo '<style>
            #curso_novas_aulas_list .sc-nova-aula-row {
                display: flex;
                gap: 8px;
                margin-bottom: 8px;
                align-items: center;
            }
            #curso_novas_aulas_list .sc-nova-aula-row input[type="text"] {
                flex: 1;
            }
        </style>';

        echo '<p class="description">Clique em + para adicionar novas aulas rapidamente. As novas aulas serao criadas em rascunho e vinculadas a este curso quando voce salvar.</p>';
        echo '<div id="curso_novas_aulas_list">';
        echo '<div class="sc-nova-aula-row">';
        echo '<input type="text" name="curso_novas_aulas[]" value="" class="widefat" placeholder="Nome da nova aula">';
        echo '<button type="button" class="button button-link-delete btn-remove-nova-aula-row">Remover</button>';
        echo '</div>';
        echo '</div>';
        echo '<p style="margin-top:10px;">';
        echo '<button type="button" class="button" id="btn_add_nova_aula_row">+ Nova aula</button>';
        echo '</p>';
        echo '<p class="description">Depois de salvar, voce pode abrir cada aula para completar conteudo, video e materiais.</p>';

        echo '<script type="text/template" id="tmpl-curso-nova-aula-row">';
        echo '<div class="sc-nova-aula-row">';
        echo '<input type="text" name="curso_novas_aulas[]" value="" class="widefat" placeholder="Nome da nova aula">';
        echo '<button type="button" class="button button-link-delete btn-remove-nova-aula-row">Remover</button>';
        echo '</div>';
        echo '</script>';
    }

    /**
     * Renderiza Metabox de Relacionamento da Aula
     */
    public function render_aula_relationship($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        $curso_id = get_post_meta($post->ID, 'curso', true);
        $cursos = get_posts(['post_type' => 'curso', 'numberposts' => -1, 'post_status' => 'publish']);
        ?>
        <label for="curso_id" style="font-weight:bold; display:block; margin-bottom:5px;">Vincular ao Curso:</label>
        <select name="curso" id="curso_id" class="widefat">
            <option value="">-- Selecione um Curso --</option>
            <?php foreach ($cursos as $c): ?>
                <option value="<?php echo $c->ID; ?>" <?php selected($curso_id, $c->ID); ?>>
                    <?php echo esc_html($c->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">Defina a qual curso esta aula pertence.</p>
        <?php
    }

    /**
     * Renderiza Metabox Principal da Aula
     */
    public function render_aula_metabox($post)
    {
        // Field: embed_do_vimeo
        $embed = get_post_meta($post->ID, 'embed_do_vimeo', true);

        // Field: descricao
        $descricao = get_post_meta($post->ID, 'descricao', true);
        $comments_lesson_override = get_post_meta($post->ID, '_sistema_cursos_comments_lesson_override', true);
        $release_datetime = class_exists('System_Cursos_Lesson_Schedule')
            ? System_Cursos_Lesson_Schedule::get_release_datetime($post->ID)
            : '';
        $release_datetime_input = '';

        if ($release_datetime !== '') {
            $release_date_obj = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $release_datetime, wp_timezone());
            if ($release_date_obj instanceof DateTimeImmutable) {
                $release_datetime_input = $release_date_obj->format('Y-m-d\TH:i');
            }
        }

        $site_timezone = wp_timezone_string();
        if ($site_timezone === '') {
            $site_timezone = 'UTC';
        }

        // Field: arquivos (Repeater)
        // O formato salvo pelo ACF é um array serializado contendo array('anexos' => URL/ID)
        // Vamos manter um formato compatível para não quebrar frontend, mas simplificando para JSON ou Serializado.
        $arquivos = get_post_meta($post->ID, 'arquivos', true);
        if (!is_array($arquivos)) {
            $arquivos = [];
        }
        ?>
        <style>
            .repeater-item {
                border: 1px solid #ddd;
                padding: 10px;
                margin-bottom: 10px;
                background: #f9f9f9;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .repeater-item .handle {
                cursor: move;
                color: #ccc;
            }
        </style>

        <p>
            <label for="embed_do_vimeo" style="font-weight:bold; display:block; margin-bottom:5px;">Embed do Vídeo
                (Vimeo/Youtube):</label>
            <textarea name="embed_do_vimeo" id="embed_do_vimeo" rows="3"
                class="widefat"><?php echo esc_textarea($embed); ?></textarea>
        </p>

        <p>
            <label for="descricao_aula" style="font-weight:bold; display:block; margin-bottom:5px;">Descrição da Aula:</label>
            <?php
            wp_editor($descricao, 'descricao_aula', [
                'textarea_name' => 'descricao',
                'textarea_rows' => 10,
                'media_buttons' => true
            ]);
            ?>
        </p>

        <p>
            <label for="sistema_cursos_lesson_release_at" style="font-weight:bold; display:block; margin-bottom:5px;">
                Data e hora de liberacao (opcional):
            </label>
            <input
                type="datetime-local"
                id="sistema_cursos_lesson_release_at"
                name="sistema_cursos_lesson_release_at"
                value="<?php echo esc_attr($release_datetime_input); ?>"
                class="widefat">
            <span class="description" style="display:block; margin-top:4px;">
                Alunos so podem assistir esta aula a partir da data e hora informadas. Fuso horario do site:
                <strong><?php echo esc_html($site_timezone); ?></strong>.
            </span>
        </p>

        <hr>

        <p>
            <input type="hidden" name="sistema_cursos_comments_lesson_override" value="0">
            <label for="sistema_cursos_comments_lesson_override" style="display:block; font-weight:600;">
                <input
                    type="checkbox"
                    id="sistema_cursos_comments_lesson_override"
                    name="sistema_cursos_comments_lesson_override"
                    value="1"
                    <?php checked($comments_lesson_override, '1'); ?>>
                Sobrescrever regra de comentarios do curso nesta aula
            </label>
            <span class="description" style="display:block; margin-top:4px;">
                Quando marcado: se o curso estiver com comentarios ativos, esta aula fica desativada. Se o curso estiver
                desativado, esta aula fica ativa.
            </span>
        </p>

        <hr>

        <label style="font-weight:bold; display:block; margin-bottom:5px;">Materiais de Apoio (Arquivos):</label>
        <div id="arquivos_repeater_list">
            <?php foreach ($arquivos as $index => $arq):
                $file_url = isset($arq['anexos']) ? $arq['anexos'] : '';
                // Se for array (padrão ACF image object), pegar URL
                if (is_array($file_url) && isset($file_url['url'])) {
                    $file_url = $file_url['url'];
                }
                ?>
                <div class="repeater-item">
                    <input type="text" name="arquivos[<?php echo $index; ?>][anexos]" value="<?php echo esc_attr($file_url); ?>"
                        class="widefat file-url-input" placeholder="URL do Arquivo">
                    <button type="button" class="button btn-upload-file">Upload</button>
                    <button type="button" class="button button-link-delete btn-remove-row">X</button>
                </div>
            <?php endforeach; ?>
        </div>

        <button type="button" class="button button-primary" id="btn_add_arquivo_row" style="margin-top:10px;">Adicionar
            Material</button>

        <!-- Template Hidden -->
        <script type="text/template" id="tmpl-arquivo-row">
                                                                                                                    <div class="repeater-item">
                                                                                                                        <input type="text" name="arquivos[INDEX][anexos]" value="" class="widefat file-url-input" placeholder="URL do Arquivo">
                                                                                                                        <button type="button" class="button btn-upload-file">Upload</button>
                                                                                                                        <button type="button" class="button button-link-delete btn-remove-row">X</button>
                                                                                                                    </div>
                                                                                                                </script>

        <?php
    }

    /**
     * Salva os dados dos Metaboxes
     */
    public function save_metaboxes($post_id)
    {
        // Verifica Nonce, Autosave e Permissões
        if (!isset($_POST['sistema_cursos_nonce']) || !wp_verify_nonce($_POST['sistema_cursos_nonce'], 'sistema_cursos_save_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post_type = get_post_type($post_id);

        // Salvar Campos

        // 1. Trilha (Post Object ID)
        if (isset($_POST['trilha'])) {
            update_post_meta($post_id, 'trilha', sanitize_text_field($_POST['trilha']));
        }

        // --- NOVO: Salvar Cursos da Trilha (Bidirecional) ---
        if ($post_type === 'trilha') {
            // Cursos selecionados no checkbox
            $cursos_selecionados = isset($_POST['trilha_cursos']) ? (array) $_POST['trilha_cursos'] : [];
            $cursos_selecionados = array_map('intval', $cursos_selecionados);

            // 1. Atualizar cursos selecionados para apontarem para esta trilha
            foreach ($cursos_selecionados as $curso_id) {
                update_post_meta($curso_id, 'trilha', $post_id);
            }

            // 2. Encontrar cursos que estavam nesta trilha mas foram desmarcados
            // Busca todos os cursos que tem 'trilha' = $post_id
            $cursos_na_trilha = get_posts([
                'post_type' => 'curso',
                'numberposts' => -1,
                'meta_key' => 'trilha',
                'meta_value' => $post_id,
                'fields' => 'ids'
            ]);

            foreach ($cursos_na_trilha as $cid) {
                if (!in_array($cid, $cursos_selecionados)) {
                    // Remover a trilha deste curso (orphan)
                    delete_post_meta($cid, 'trilha');
                }
            }
        }

        // 2. Capa Vertical (ID)
        if (isset($_POST['capa_vertical'])) {
            update_post_meta($post_id, 'capa_vertical', sanitize_text_field($_POST['capa_vertical']));
        }

        // 3. Curso (Relacionamento da Aula)
        if (isset($_POST['curso'])) {
            update_post_meta($post_id, 'curso', sanitize_text_field($_POST['curso']));
        }

        // 3.1 Comentarios no Curso (controle global)
        if ($post_type === 'curso' && isset($_POST['sistema_cursos_comments_course_enabled'])) {
            $course_comments_enabled = ($_POST['sistema_cursos_comments_course_enabled'] === '1') ? '1' : '0';
            update_post_meta($post_id, '_sistema_cursos_comments_course_enabled', $course_comments_enabled);
        }

        // 3.2 Comentarios na Aula (sobrescrita da regra do curso)
        if ($post_type === 'aula' && isset($_POST['sistema_cursos_comments_lesson_override'])) {
            $lesson_comments_override = ($_POST['sistema_cursos_comments_lesson_override'] === '1') ? '1' : '0';
            update_post_meta($post_id, '_sistema_cursos_comments_lesson_override', $lesson_comments_override);
        }

        // 3.3 Data/hora de liberacao da aula
        if ($post_type === 'aula' && isset($_POST['sistema_cursos_lesson_release_at'])) {
            $release_raw = $_POST['sistema_cursos_lesson_release_at'];
            $release_datetime = class_exists('System_Cursos_Lesson_Schedule')
                ? System_Cursos_Lesson_Schedule::normalize_datetime_local_input($release_raw)
                : '';

            if ($release_datetime !== '') {
                update_post_meta($post_id, '_sistema_cursos_lesson_release_at', $release_datetime);
            } else {
                delete_post_meta($post_id, '_sistema_cursos_lesson_release_at');
            }
        }

        // --- NOVO: Salvar Aulas do Curso (Bidirecional) ---
        if ($post_type === 'curso') {
            // Aulas selecionadas no checkbox
            $aulas_selecionadas = isset($_POST['curso_aulas']) ? (array) $_POST['curso_aulas'] : [];
            $aulas_selecionadas = array_map('intval', $aulas_selecionadas);

            // 1. Atualizar aulas selecionadas para apontarem para este curso
            foreach ($aulas_selecionadas as $aula_id) {
                update_post_meta($aula_id, 'curso', $post_id);
            }

            // 2. Encontrar aulas que estavam neste curso mas foram desmarcadas
            $aulas_no_curso = get_posts([
                'post_type' => 'aula',
                'numberposts' => -1,
                'meta_key' => 'curso',
                'meta_value' => $post_id,
                'fields' => 'ids'
            ]);

            foreach ($aulas_no_curso as $aid) {
                if (!in_array($aid, $aulas_selecionadas)) {
                    // Remover o curso desta aula
                    delete_post_meta($aid, 'curso');
                }
            }

            // 2.1 Persistir ordem das aulas selecionadas para refletir no player do aluno.
            $aulas_ordenadas_raw = isset($_POST['curso_aulas_order']) ? (string) wp_unslash($_POST['curso_aulas_order']) : '';
            $aulas_ordenadas = [];

            if ($aulas_ordenadas_raw !== '') {
                $aulas_ordenadas = array_filter(array_map('intval', explode(',', $aulas_ordenadas_raw)), static function ($id) {
                    return $id > 0;
                });
                $aulas_ordenadas = array_values(array_unique($aulas_ordenadas));
            }

            // Fallback sem JS: respeita a ordem de submissao dos checkboxes.
            if (empty($aulas_ordenadas)) {
                $aulas_ordenadas = $aulas_selecionadas;
            }

            // Mantem apenas aulas selecionadas neste curso.
            $aulas_ordenadas = array_values(array_filter($aulas_ordenadas, static function ($id) use ($aulas_selecionadas) {
                return in_array($id, $aulas_selecionadas, true);
            }));

            // Garante inclusao de aulas selecionadas ausentes no payload ordenado.
            foreach ($aulas_selecionadas as $aula_id) {
                if (!in_array($aula_id, $aulas_ordenadas, true)) {
                    $aulas_ordenadas[] = $aula_id;
                }
            }

            // Evita recursao do save_post da propria classe ao ajustar menu_order das aulas.
            remove_action('save_post', [$this, 'save_metaboxes']);

            $menu_order = 0;
            foreach ($aulas_ordenadas as $aula_id) {
                $aula_post = get_post($aula_id);
                if (!$aula_post || $aula_post->post_type !== 'aula') {
                    continue;
                }

                if ((int) $aula_post->menu_order !== $menu_order) {
                    wp_update_post([
                        'ID' => $aula_id,
                        'menu_order' => $menu_order,
                    ]);
                }

                $menu_order++;
            }

            add_action('save_post', [$this, 'save_metaboxes']);

            // 3. Criacao rapida de novas aulas dentro da tela do curso
            $novas_aulas = isset($_POST['curso_novas_aulas']) ? (array) $_POST['curso_novas_aulas'] : [];
            $novas_aulas = array_map('sanitize_text_field', $novas_aulas);
            $novas_aulas = array_filter(array_map('trim', $novas_aulas), static function ($titulo) {
                return $titulo !== '';
            });

            if (!empty($novas_aulas)) {
                $capability_to_create = 'edit_posts';
                $aula_post_type = get_post_type_object('aula');
                if ($aula_post_type && isset($aula_post_type->cap->create_posts)) {
                    $capability_to_create = $aula_post_type->cap->create_posts;
                }

                if (current_user_can($capability_to_create)) {
                    foreach ($novas_aulas as $titulo_aula) {
                        $new_lesson_id = wp_insert_post([
                            'post_type' => 'aula',
                            'post_status' => 'draft',
                            'post_title' => $titulo_aula,
                            'post_author' => get_current_user_id(),
                        ], true);

                        if (!is_wp_error($new_lesson_id) && $new_lesson_id > 0) {
                            update_post_meta($new_lesson_id, 'curso', $post_id);
                        }
                    }
                }
            }
        }

        // 4. Embed Vimeo
        if (isset($_POST['embed_do_vimeo'])) {
            // Permitir iframe e HTML seguro
            update_post_meta($post_id, 'embed_do_vimeo', $_POST['embed_do_vimeo']); // Cuidado: sanitização branda necessária para iframes
        }

        // 5. Descrição
        if (isset($_POST['descricao'])) {
            update_post_meta($post_id, 'descricao', wp_kses_post($_POST['descricao']));
        }

        // 6. Arquivos (Repeater)
        if (isset($_POST['arquivos']) && is_array($_POST['arquivos'])) {
            $arquivos_clean = [];
            foreach ($_POST['arquivos'] as $item) {
                if (!empty($item['anexos'])) {
                    $arquivos_clean[] = [
                        'anexos' => esc_url_raw($item['anexos'])
                    ];
                }
            }
            // Reindexar array
            update_post_meta($post_id, 'arquivos', array_values($arquivos_clean));
        } else {
            // Se o campo de arquivos estiver vazio ou não enviado (mas estamos salvando a aula), limpar
            // Só limpar se o post type for aula, para não limpar acidentalmente em outros saves
            if (get_post_type($post_id) === 'aula') {
                delete_post_meta($post_id, 'arquivos'); // Ou update para empty array
            }
        }

        // 7. Descrição Curta (Trilha)
        if (isset($_POST['descricao_curta'])) {
            update_post_meta($post_id, 'descricao_curta', sanitize_textarea_field($_POST['descricao_curta']));
        }

        // 8. Grupos Permitidos (Curso e Trilha) - REMOVIDO: Gestão centralizada no post type 'grupo'
        // A lógica de "push" é feita quando salvamos o GRUPO.
        // Mantemos apenas a limpeza se necessário, mas como o campo não existe mais no form, 
        // não devemos tocar no meta aqui para não apagar dados véios acidentalmente se o POST vier vazio.


        // 9. Sincronização de Alunos (Post Type: Grupo)
        if ($post_type === 'grupo') {
            // Obter todos os usuários para verificar mudanças
            // Isso pode ser pesado se houver milhares de usuários. 
            // O ideal seria pegar apenas os users que foram marcados ou desmarcados, mas via POST recebemos o estado final.

            // Vamos pegar os IDs enviados no POST
            $alunos_selecionados = isset($_POST['alunos_grupo']) ? (array) $_POST['alunos_grupo'] : [];
            $alunos_selecionados = array_map('intval', $alunos_selecionados);

            // Vamos buscar usuários que JÁ possuíam este grupo para ver se alguém foi removido
            // Query reversa eficiente: buscar usuários onde _aluno_grupos contém este ID
            $users_in_group_query = new WP_User_Query([
                'meta_query' => [
                    [
                        'key' => '_aluno_grupos',
                        'value' => sprintf('"%s"', $post_id), // Serialized check parcial (pode falhar dependendo do formato, mas array serializado é texto)
                        'compare' => 'LIKE'
                    ]
                ],
                'fields' => 'ID'
            ]);
            // A query LIKE em serialized pode dar falso positivo (ex: id 1 e 10), então vamos iterar o ALL users é mais seguro para consistência se a base não for gigante
            // OU melhor: Iterar apenas sobre a união dos (enviados + os que já tinham).

            // Abordagem Segura e Otimizada:
            // 1. Pega todos os usuários que tinham esse grupo (via get_users com callback filter se necessário, ou assumindo que WP_User_Query LIKE funciona bem para array serializado se usado corretamente, mas serialize é tricky)
            // Para simplificar e garantir precisão: vamos iterar sobre TODOS os usuários listados no metabox (que são todos os alunos).
            // Se houver muuitos alunos, isso deve ser feito via AJAX. Assumindo < 500 alunos para este MVP.

            $all_users = get_users(['fields' => 'ID']); // Pega IDs de todos

            foreach ($all_users as $user_id) {
                $user_grupos = get_user_meta($user_id, '_aluno_grupos', true);
                if (!is_array($user_grupos))
                    $user_grupos = [];

                $should_be_in = in_array($user_id, $alunos_selecionados);
                $is_in = in_array($post_id, $user_grupos);

                if ($should_be_in && !$is_in) {
                    // Adicionar
                    $user_grupos[] = $post_id;
                    update_user_meta($user_id, '_aluno_grupos', array_unique($user_grupos));
                } elseif (!$should_be_in && $is_in) {
                    // Remover
                    $user_grupos = array_diff($user_grupos, [$post_id]);
                    update_user_meta($user_id, '_aluno_grupos', array_values($user_grupos));
                }
            }
            // Salvar Gestão de Conteúdos do Grupo (Sincronização Bidirecional)
            $conteudos_novos = isset($_POST['grupo_conteudos']) ? (array) $_POST['grupo_conteudos'] : [];
            $conteudos_novos = array_map('intval', $conteudos_novos);

            // Obter conteúdos antigos
            $conteudos_antigos = get_post_meta($post_id, '_grupo_conteudos', true);
            if (!is_array($conteudos_antigos))
                $conteudos_antigos = [];

            // Atualizar Meta do Grupo
            update_post_meta($post_id, '_grupo_conteudos', $conteudos_novos);

            // 1. Adicionar Grupo aos Conteúdos Novos
            foreach ($conteudos_novos as $c_id) {
                if (!in_array($c_id, $conteudos_antigos)) {
                    $grupos_permitidos = get_post_meta($c_id, '_grupos_permitidos', true);
                    if (!is_array($grupos_permitidos))
                        $grupos_permitidos = [];

                    if (!in_array($post_id, $grupos_permitidos)) {
                        $grupos_permitidos[] = $post_id;
                        update_post_meta($c_id, '_grupos_permitidos', $grupos_permitidos);
                    }
                }
            }

            // 2. Remover Grupo dos Conteúdos Removidos
            $conteudos_removidos = array_diff($conteudos_antigos, $conteudos_novos);
            foreach ($conteudos_removidos as $c_id) {
                $grupos_permitidos = get_post_meta($c_id, '_grupos_permitidos', true);
                if (is_array($grupos_permitidos)) {
                    $grupos_permitidos = array_diff($grupos_permitidos, [$post_id]);
                    update_post_meta($c_id, '_grupos_permitidos', array_values($grupos_permitidos));
                }
            }
        }
    }



    /**
     * Renderiza Metabox de Gestão de Alunos (CPT Grupo)
     */
    public function render_grupo_alunos_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        // Buscar todos os usuários
        // TODO: Em produção com muitos usuários, isso deve ser via AJAX.
        $users = get_users([
            'orderby' => 'display_name',
            'order' => 'ASC',
            'fields' => ['ID', 'display_name', 'user_email']
        ]);

        echo '<div class="access-manager">';

        // Campo de Busca
        echo '<p>';
        echo '<input type="text" id="sistema_cursos_search_users" class="widefat" placeholder="Buscar aluno por nome ou email..." style="padding: 8px;">';
        echo '</p>';

        echo '<div id="sistema_cursos_users_list" style="max-height: 400px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; background: #fff;">';

        if (empty($users)) {
            echo '<p>Nenhum usuário cadastrado.</p>';
        } else {
            foreach ($users as $user) {
                // Verificar se user tem este grupo
                $user_grupos = get_user_meta($user->ID, '_aluno_grupos', true);
                if (!is_array($user_grupos))
                    $user_grupos = [];

                $checked = in_array($post->ID, $user_grupos) ? 'checked' : '';

                // Data attributes para busca JS
                $search_string = strtolower($user->display_name . ' ' . $user->user_email);

                echo '<div class="user-option" data-search="' . esc_attr($search_string) . '" style="padding: 5px; border-bottom: 1px solid #f0f0f0; display: flex; align-items: center;">';
                echo '<label style="cursor: pointer; width: 100%; display: flex; align-items: center;">';
                echo '<input type="checkbox" name="alunos_grupo[]" value="' . $user->ID . '" ' . $checked . ' style="margin-right: 10px;">';
                echo '<span><strong>' . esc_html($user->display_name) . '</strong> <span style="color:#888; font-size: 0.9em;">(' . esc_html($user->user_email) . ')</span></span>';
                echo '</label>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '<p class="description" style="margin-top: 5px;">Selecione os alunos que farão parte deste grupo. Use a busca acima para filtrar.</p>';
        echo '</div>';

        // Script Inline Simples para Busca
        ?>
        <script>
            jQuery(document).ready(function ($) {
                $('#sistema_cursos_search_users').on('keyup', function () {
                    var term = $(this).val().toLowerCase();
                    $('#sistema_cursos_users_list .user-option').each(function () {
                        var searchData = $(this).data('search');
                        if (searchData.indexOf(term) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });
            });
        </script>
        <?php
    }

    /**
     * Renderiza Metabox de Conteúdos do Grupo (Cursos e Trilhas)
     */
    public function render_grupo_conteudos_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        $conteudos_selecionados = get_post_meta($post->ID, '_grupo_conteudos', true);
        if (!is_array($conteudos_selecionados)) {
            $conteudos_selecionados = [];
        }

        // Buscar Cursos
        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        // Buscar Trilhas
        $trilhas = get_posts([
            'post_type' => 'trilha',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        echo '<div class="grupo-conteudos-list" style="max-height: 300px; overflow-y: auto;">';

        if (!empty($cursos)) {
            echo '<p><strong>Cursos:</strong></p>';
            foreach ($cursos as $curso) {
                $checked = in_array($curso->ID, $conteudos_selecionados) ? 'checked' : '';
                echo '<label style="display:block; margin-bottom: 5px;">';
                echo '<input type="checkbox" name="grupo_conteudos[]" value="' . esc_attr($curso->ID) . '" ' . $checked . '> ';
                echo esc_html($curso->post_title);
                echo '</label>';
            }
        }

        echo '<hr>';

        if (!empty($trilhas)) {
            echo '<p><strong>Trilhas:</strong></p>';
            foreach ($trilhas as $trilha) {
                $checked = in_array($trilha->ID, $conteudos_selecionados) ? 'checked' : '';
                echo '<label style="display:block; margin-bottom: 5px;">';
                echo '<input type="checkbox" name="grupo_conteudos[]" value="' . esc_attr($trilha->ID) . '" ' . $checked . '> ';
                echo esc_html($trilha->post_title);
                echo '</label>';
            }
        }

        if (empty($cursos) && empty($trilhas)) {
            echo '<p class="description">Nenhum curso ou trilha disponível.</p>';
        }

        echo '</div>';
        echo '<p class="description">Selecione os conteúdos aos quais este grupo terá acesso automático.</p>';
    }
}
