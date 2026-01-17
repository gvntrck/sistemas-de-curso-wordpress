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
            'supports' => ['title', 'thumbnail'],
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
            'supports' => ['title', 'thumbnail'],
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

        // Permissões de Grupo (Curso)
        add_meta_box(
            'curso_group_permissions',
            'Restrições de Acesso (Grupos)',
            [$this, 'render_group_permissions_metabox'],
            'curso',
            'side',
            'default'
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

        // Permissões de Grupo (Trilha)
        add_meta_box(
            'trilha_group_permissions',
            'Restrições de Acesso (Grupos)',
            [$this, 'render_group_permissions_metabox'],
            'trilha',
            'side',
            'default'
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
                ['jquery'],
                '1.0.2', // Bump version again
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
     * Renderiza Metabox do Curso
     */
    public function render_curso_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        // Field: trilha (Relationship)
        $trilha_id = get_post_meta($post->ID, 'trilha', true);

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
        </div>
        <?php
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

        // Salvar Campos

        // 1. Trilha (Post Object ID)
        if (isset($_POST['trilha'])) {
            update_post_meta($post_id, 'trilha', sanitize_text_field($_POST['trilha']));
        }

        // 2. Capa Vertical (ID)
        if (isset($_POST['capa_vertical'])) {
            update_post_meta($post_id, 'capa_vertical', sanitize_text_field($_POST['capa_vertical']));
        }

        // 3. Curso (Relacionamento da Aula)
        if (isset($_POST['curso'])) {
            update_post_meta($post_id, 'curso', sanitize_text_field($_POST['curso']));
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

        // 8. Grupos Permitidos (Curso e Trilha)
        $post_type = get_post_type($post_id);
        if (in_array($post_type, ['curso', 'trilha'])) {
            $grupos = isset($_POST['grupos_permitidos']) ? (array) $_POST['grupos_permitidos'] : [];
            $grupos = array_map('intval', $grupos); // Sanitizar IDs
            update_post_meta($post_id, '_grupos_permitidos', $grupos);
        }

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
        }
    }

    /**
     * Renderiza Metabox de Permissões de Grupo (Curso e Trilha)
     */
    public function render_group_permissions_metabox($post)
    {
        wp_nonce_field('sistema_cursos_save_meta', 'sistema_cursos_nonce');

        $grupos_selecionados = get_post_meta($post->ID, '_grupos_permitidos', true);
        if (!is_array($grupos_selecionados)) {
            $grupos_selecionados = [];
        }

        $grupos = get_posts([
            'post_type' => 'grupo',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        if (empty($grupos)) {
            echo '<p class="description">Nenhum grupo de alunos cadastrado. Crie grupos no menu "Grupos de Alunos".</p>';
            return;
        }

        echo '<p class="description">Selecione os grupos que terão acesso automático a este conteúdo.</p>';
        echo '<div style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; background: #fff;">';
        foreach ($grupos as $grupo) {
            $checked = in_array($grupo->ID, $grupos_selecionados) ? 'checked' : '';
            echo '<label style="display:block; margin-bottom: 5px;">';
            echo '<input type="checkbox" name="grupos_permitidos[]" value="' . esc_attr($grupo->ID) . '" ' . $checked . '> ';
            echo esc_html($grupo->post_title);
            echo '</label>';
        }
        echo '</div>';
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
}
