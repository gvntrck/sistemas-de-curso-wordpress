<?php
/**
 * Plugin Name: LMS SuporteRapido
 * Description: Plugin LMS para WordPress - Alternativa ao Learndash
 * Author: Giovani Tureck
 * Text Domain: lms-suporte-rapido
 * Version: 1.6.7
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Definição de constantes
define('SISTEMA_CURSOS_VERSION', '1.6.7');

/**
 * sistema-cursos-plugin.php
 *
 * Arquivo principal do plugin Sistema de Cursos Personalizado.
 * Responsável por inicializar todas as classes, shortcodes e funcionalidades do sistema.
 * Carrega dependências, define hooks de ativação e configura o menu de documentação no admin.
 *
 * @package SistemaCursos
 * @version 1.3.10
 */

// 1. Carregar Classes do Core
require_once plugin_dir_path(__FILE__) . 'includes/class-cpt-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/role-aluno.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-config.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-lesson-schedule.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-minha-conta.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-cadastro-usuario.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-access-control.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-user-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-course-progress.php';
// require_once plugin_dir_path(__FILE__) . 'includes/admin/class-admin-quiz-manager.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-listar-aulas.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-meus-cursos.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-certificates.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-certificado.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-resultado-busca.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-barra-progresso.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-cursos-trilha.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-single-trilha.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-barra-lateral.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-redireciona-aula.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-painel.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-woocommerce-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-quiz-builder.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-quiz-process.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-aula-comments.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-customizer.php';

// 2. Inicializar Assets Globais
new System_Cursos_CPT_Manager();
new System_Cursos_Assets();
new System_Cursos_Shortcode_Minha_Conta();
new System_Cursos_Shortcode_Cadastro();
new System_Cursos_Access_Control();
new System_Cursos_User_Fields();
new System_Cursos_Admin_Filters();
new System_Cursos_Progress();
// new System_Cursos_Admin_Quiz_Manager();
new System_Cursos_Shortcode_Listar_Aulas();
new System_Cursos_Shortcode_Meus_Cursos();
new System_Cursos_Certificates();
new System_Cursos_Shortcode_Certificado();
new System_Cursos_Shortcode_Resultado_Busca();
new System_Cursos_Shortcode_Barra_Progresso();
new System_Cursos_Shortcode_Cursos_Trilha();
new System_Cursos_Shortcode_Single_Trilha();
new System_Cursos_Shortcode_Barra_Lateral();
new System_Cursos_Shortcode_Redireciona_Aula();
new System_Cursos_Shortcode_Painel();
new System_Cursos_Quiz_Builder();
new System_Cursos_Quiz_Process();
new System_Cursos_Aula_Comments();
new System_Cursos_Customizer();

add_action('plugins_loaded', 'sistema_cursos_init_woocommerce_integration');

function sistema_cursos_init_woocommerce_integration()
{
    if (class_exists('WooCommerce')) {
        new System_Cursos_WooCommerce();
    }
}

/**
 * Ativação do Plugin
 * Garante que as regras de reescrita sejam lavadas (flush) ao ativar.
 */
register_activation_hook(__FILE__, 'sistema_cursos_activate');

function sistema_cursos_activate()
{
    // Garante que os CPTs estejam registrados antes do flush
    $cpt_manager = new System_Cursos_CPT_Manager();
    $cpt_manager->register_cpts();

    flush_rewrite_rules();
    update_option('sistema_cursos_version', '1.6.2');
}

/**
 * Verificação de Versão (Auto-Update)
 * Se a versão do código for maior que o banco, roda flush_rewrite_rules.
 * Útil para atualizações onde mudamos slugs ou CPTs.
 */
add_action('init', 'sistema_cursos_check_version', 99);


function sistema_cursos_check_version()
{
    $current_version = '1.6.2';
    $db_version = get_option('sistema_cursos_version');

    if ($db_version !== $current_version) {
        // Garante registro
        $cpt_manager = new System_Cursos_CPT_Manager();
        $cpt_manager->register_cpts();

        flush_rewrite_rules();
        update_option('sistema_cursos_version', $current_version);
    }
}




/**
 * Adiciona página de Documentação no Menu Admin
 */
add_action('admin_menu', 'sistema_cursos_add_admin_menu');

function sistema_cursos_add_admin_menu()
{
    add_menu_page(
        'LMS SuporteRapido',      // Page Title
        'LMS SuporteRapido',      // Menu Title
        'manage_options',         // Capability
        'lms-suporte-rapido',     // Menu Slug
        'sistema_cursos_render_admin_page', // Callback function
        'dashicons-welcome-learn-more', // Icon
        2                        // Position
    );

    // Rename the first submenu item to "Configuração"
    add_submenu_page(
        'lms-suporte-rapido',    // Parent Slug
        'Configuração',           // Page Title
        'Configuração',           // Menu Title
        'manage_options',         // Capability
        'lms-suporte-rapido',     // Menu Slug (Same as parent to override default)
        'sistema_cursos_render_admin_page' // Callback
    );
}

/**
 * Retorna as configuracoes do banner da pagina inicial.
 *
 * @return array{autoplay_seconds:int,slides:array<int,array{image_id:int,link:string}>}
 */
function sistema_cursos_get_banner_settings()
{
    $defaults = [
        'autoplay_seconds' => 5,
        'slides' => [],
    ];

    $stored = get_option('lms_sr_banner_settings', []);
    if (!is_array($stored)) {
        return $defaults;
    }

    return sistema_cursos_sanitize_banner_settings($stored);
}

/**
 * Sanitiza configuracoes do banner antes de salvar/usar.
 *
 * @param mixed $settings
 * @return array{autoplay_seconds:int,slides:array<int,array{image_id:int,link:string}>}
 */
function sistema_cursos_sanitize_banner_settings($settings)
{
    $autoplay_seconds = 5;
    if (is_array($settings) && isset($settings['autoplay_seconds'])) {
        $autoplay_seconds = (int) $settings['autoplay_seconds'];
    }
    if ($autoplay_seconds < 2) {
        $autoplay_seconds = 2;
    } elseif ($autoplay_seconds > 30) {
        $autoplay_seconds = 30;
    }

    $slides = [];
    if (is_array($settings) && isset($settings['slides']) && is_array($settings['slides'])) {
        foreach ($settings['slides'] as $slide) {
            if (!is_array($slide)) {
                continue;
            }

            $image_id = isset($slide['image_id']) ? absint($slide['image_id']) : 0;
            $link = isset($slide['link']) ? esc_url_raw(trim((string) $slide['link'])) : '';

            if ($image_id <= 0 || !wp_attachment_is_image($image_id)) {
                continue;
            }

            $slides[] = [
                'image_id' => $image_id,
                'link' => $link,
            ];
        }
    }

    return [
        'autoplay_seconds' => $autoplay_seconds,
        'slides' => $slides,
    ];
}

function sistema_cursos_render_admin_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'ordenacao';
    $settings_notice = '';

    if (isset($_POST['lms_sr_save_acesso_settings'])) {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão para salvar esta configuração.');
        }

        check_admin_referer('lms_sr_save_acesso_settings', 'lms_sr_acesso_nonce');

        $redirect_page_id = isset($_POST['lms_sr_aluno_redirect_page_id']) ? absint(wp_unslash($_POST['lms_sr_aluno_redirect_page_id'])) : 0;

        if ($redirect_page_id > 0 && get_post_type($redirect_page_id) !== 'page') {
            $redirect_page_id = 0;
        }

        update_option('lms_sr_aluno_redirect_page_id', $redirect_page_id);
        $settings_notice = '<div class="notice notice-success is-dismissible"><p>Configuração de redirecionamento salva com sucesso.</p></div>';
    }

    if (isset($_POST['lms_sr_save_banner_settings'])) {
        if (!current_user_can('manage_options')) {
            wp_die('Sem permissão para salvar esta configuração.');
        }

        check_admin_referer('lms_sr_save_banner_settings', 'lms_sr_banner_nonce');

        $raw_banner = isset($_POST['lms_sr_banner']) ? wp_unslash($_POST['lms_sr_banner']) : [];
        if (!is_array($raw_banner)) {
            $raw_banner = [];
        }

        $banner_settings = sistema_cursos_sanitize_banner_settings($raw_banner);
        update_option('lms_sr_banner_settings', $banner_settings);
        $settings_notice = '<div class="notice notice-success is-dismissible"><p>Configurações do banner salvas com sucesso.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>LMS SuporteRapido - Configuração do Sistema</h1>
        <p>Bem-vindo ao painel de configuração dos shortcodes e funcionalidades do <strong>Sistema de Cursos
                Personalizado</strong>.</p>

        <nav class="nav-tab-wrapper">
            <a href="?page=lms-suporte-rapido&tab=ordenacao"
                class="nav-tab <?php echo $active_tab == 'ordenacao' ? 'nav-tab-active' : ''; ?>">📋 Ordenação</a>
            <a href="?page=lms-suporte-rapido&tab=shortcodes"
                class="nav-tab <?php echo $active_tab == 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
            <a href="?page=lms-suporte-rapido&tab=cpts"
                class="nav-tab <?php echo $active_tab == 'cpts' ? 'nav-tab-active' : ''; ?>">Estrutura de Dados (CPTs)</a>
            <a href="?page=lms-suporte-rapido&tab=instrucoes"
                class="nav-tab <?php echo $active_tab == 'instrucoes' ? 'nav-tab-active' : ''; ?>">Instruções de Uso</a>
            <a href="?page=lms-suporte-rapido&tab=acesso"
                class="nav-tab <?php echo $active_tab == 'acesso' ? 'nav-tab-active' : ''; ?>">Acesso</a>
            <a href="?page=lms-suporte-rapido&tab=banner"
                class="nav-tab <?php echo $active_tab == 'banner' ? 'nav-tab-active' : ''; ?>">Banner Home</a>
            <a href="?page=lms-suporte-rapido&tab=personalizar"
                class="nav-tab <?php echo $active_tab == 'personalizar' ? 'nav-tab-active' : ''; ?>">🎨 Personalizar</a>
        </nav>

        <style>
            .sc-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: #fff;
                border: 1px solid #c3c4c7;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
            }

            .sc-table th,
            .sc-table td {
                padding: 12px 15px;
                text-align: left;
                border-bottom: 1px solid #c3c4c7;
                vertical-align: top;
            }

            .sc-table th {
                background: #f0f0f1;
                font-weight: 600;
                color: #1d2327;
                border-bottom: 2px solid #c3c4c7;
            }

            .sc-table tr:last-child td {
                border-bottom: none;
            }

            .sc-table tr:nth-child(even) {
                background-color: #f6f7f7;
            }

            /* Globais */
            .sc-tag {
                background: #f0f0f1;
                color: #2271b1;
                padding: 4px 8px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 1.1em;
                font-weight: bold;
                border: 1px solid #c3c4c7;
                display: inline-block;
            }

            .sc-desc-text {
                font-size: 14px;
                color: #50575e;
                margin-bottom: 10px;
            }

            .sc-params {
                background: #fff;
                border: 1px solid #dcdcde;
                padding: 10px;
                border-radius: 4px;
                font-size: 13px;
                margin-top: 8px;
            }

            .sc-params strong {
                display: block;
                margin-bottom: 4px;
                color: #1d2327;
            }

            .sc-params code {
                background: #f6f7f7;
                padding: 2px 4px;
                margin: 0 2px;
            }

            .sc-params ul {
                margin: 5px 0 5px 20px;
                list-style-type: disc;
            }

            .sc-params li {
                margin-bottom: 3px;
            }

            /* CPTs Specific */
            .cpt-section-title {
                margin-top: 30px;
                padding-bottom: 10px;
                border-bottom: 2px solid #2271b1;
                color: #1d2327;
            }

            .field-type {
                font-size: 0.85em;
                color: #fff;
                background: #646970;
                padding: 2px 6px;
                border-radius: 3px;
                margin-left: 5px;
                text-transform: uppercase;
            }

            .req-plugin {
                color: #d63638;
                font-weight: bold;
            }

            /* Ordenação Styles */
            .sc-order-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                padding: 20px;
                margin-bottom: 20px;
            }

            .sc-order-section h3 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 2px solid #2271b1;
                color: #1d2327;
            }

            .sortable-list {
                list-style: none;
                padding: 0;
                margin: 15px 0;
            }

            .sortable-list li {
                background: #f6f7f7;
                border: 1px solid #c3c4c7;
                padding: 12px 15px;
                margin-bottom: 8px;
                border-radius: 4px;
                cursor: move;
                display: flex;
                align-items: center;
                gap: 10px;
                transition: all 0.2s;
            }

            .sortable-list li:hover {
                background: #e8f4fc;
                border-color: #2271b1;
            }

            .sortable-list li.ui-sortable-helper {
                background: #fff;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }

            .sortable-list li.ui-sortable-placeholder {
                background: #e1f0fa;
                border: 2px dashed #2271b1;
                visibility: visible !important;
            }

            .sortable-list .drag-handle {
                color: #888;
                font-size: 1.2em;
            }

            .sortable-list .item-title {
                flex: 1;
                font-weight: 500;
            }

            .sortable-list .item-order {
                color: #666;
                font-size: 0.85em;
                background: #e0e0e0;
                padding: 2px 8px;
                border-radius: 10px;
            }

            .sc-order-actions {
                margin-top: 15px;
                display: flex;
                gap: 10px;
                align-items: center;
            }

            .sc-order-notice {
                padding: 10px 15px;
                border-radius: 4px;
                display: none;
            }

            .sc-order-notice.success {
                background: #d1e7dd;
                color: #0f5132;
                border: 1px solid #badbcc;
                display: block;
            }

            .sc-order-notice.error {
                background: #f8d7da;
                color: #842029;
                border: 1px solid #f5c2c7;
                display: block;
            }

            .sc-trilha-selector {
                margin-bottom: 15px;
            }

            .sc-trilha-selector select {
                min-width: 300px;
                padding: 8px;
            }

            .sc-loading {
                display: none;
                color: #666;
                font-style: italic;
            }
        </style>

        <?php if ($active_tab == 'ordenacao'): ?>

            <h2>Ordenação de Conteúdos</h2>
            <p>Arraste e solte os itens para definir a ordem de exibição no shortcode <code>[meus-cursos]</code>.</p>

            <!-- Ordenar Trilhas -->
            <div class="sc-order-section">
                <h3>📌 Ordenar Trilhas</h3>
                <p class="description">A ordem definida aqui será usada para exibir as trilhas no frontend.</p>

                <ul id="sortable-trilhas" class="sortable-list">
                    <?php
                    $trilhas = get_posts([
                        'post_type' => 'trilha',
                        'posts_per_page' => -1,
                        'orderby' => 'menu_order',
                        'order' => 'ASC',
                        'post_status' => 'publish'
                    ]);
                    foreach ($trilhas as $index => $trilha):
                        ?>
                        <li data-id="<?php echo $trilha->ID; ?>">
                            <span class="drag-handle">⋮⋮</span>
                            <span class="item-title">
                                <?php echo esc_html($trilha->post_title); ?>
                            </span>
                            <span class="item-order">#
                                <?php echo $index + 1; ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <?php if (empty($trilhas)): ?>
                    <p style="color: #666; font-style: italic;">Nenhuma trilha encontrada.</p>
                <?php else: ?>
                    <div class="sc-order-actions">
                        <button type="button" class="button button-primary" id="salvar-ordem-trilhas">
                            💾 Salvar Ordem das Trilhas
                        </button>
                        <span id="trilhas-notice" class="sc-order-notice"></span>
                        <span id="trilhas-loading" class="sc-loading">Salvando...</span>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Ordenar Cursos por Trilha -->
            <div class="sc-order-section">
                <h3>📚 Ordenar Cursos por Trilha</h3>
                <p class="description">Selecione uma trilha para ordenar os cursos dentro dela.</p>

                <div class="sc-trilha-selector">
                    <select id="select-trilha-cursos">
                        <option value="">-- Selecione uma Trilha --</option>
                        <?php foreach ($trilhas as $trilha): ?>
                            <option value="<?php echo $trilha->ID; ?>">
                                <?php echo esc_html($trilha->post_title); ?>
                            </option>
                        <?php endforeach; ?>
                        <option value="0">📦 Cursos sem Trilha</option>
                    </select>
                    <span id="cursos-loading" class="sc-loading">Carregando cursos...</span>
                </div>

                <ul id="sortable-cursos" class="sortable-list">
                    <li style="background: #f0f0f1; color: #666; cursor: default; border-style: dashed;">
                        Selecione uma trilha acima para ver os cursos
                    </li>
                </ul>

                <div class="sc-order-actions" id="cursos-actions" style="display: none;">
                    <button type="button" class="button button-primary" id="salvar-ordem-cursos">
                        💾 Salvar Ordem dos Cursos
                    </button>
                    <span id="cursos-notice" class="sc-order-notice"></span>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    // Inicializar Sortable para Trilhas
                    $('#sortable-trilhas').sortable({
                        placeholder: 'ui-sortable-placeholder',
                        handle: '.drag-handle',
                        update: function (event, ui) {
                            // Atualizar números de ordem
                            $('#sortable-trilhas li').each(function (i) {
                                $(this).find('.item-order').text('#' + (i + 1));
                            });
                        }
                    });

                    // Salvar ordem das trilhas
                    $('#salvar-ordem-trilhas').on('click', function () {
                        var $btn = $(this);
                        var $notice = $('#trilhas-notice');
                        var $loading = $('#trilhas-loading');

                        var trilhaIds = [];
                        $('#sortable-trilhas li').each(function () {
                            trilhaIds.push($(this).data('id'));
                        });

                        $btn.prop('disabled', true);
                        $loading.show();
                        $notice.removeClass('success error').hide();

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'salvar_ordem_trilhas',
                                trilha_ids: trilhaIds,
                                nonce: '<?php echo wp_create_nonce('ordenar_trilhas_nonce'); ?>'
                            },
                            success: function (response) {
                                $loading.hide();
                                $btn.prop('disabled', false);

                                if (response.success) {
                                    $notice.text('✅ Ordem das trilhas salva com sucesso!').addClass('success').show();
                                    setTimeout(function () { $notice.fadeOut(); }, 3000);
                                } else {
                                    $notice.text('❌ Erro ao salvar: ' + response.data).addClass('error').show();
                                }
                            },
                            error: function () {
                                $loading.hide();
                                $btn.prop('disabled', false);
                                $notice.text('❌ Erro de conexão').addClass('error').show();
                            }
                        });
                    });

                    // Carregar cursos da trilha selecionada
                    $('#select-trilha-cursos').on('change', function () {
                        var trilhaId = $(this).val();
                        var $list = $('#sortable-cursos');
                        var $loading = $('#cursos-loading');
                        var $actions = $('#cursos-actions');

                        if (!trilhaId && trilhaId !== '0') {
                            $list.html('<li style="background: #f0f0f1; color: #666; cursor: default; border-style: dashed;">Selecione uma trilha acima para ver os cursos</li>');
                            $actions.hide();
                            return;
                        }

                        $loading.show();
                        $list.html('<li style="color: #666;">Carregando...</li>');

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'get_cursos_trilha',
                                trilha_id: trilhaId,
                                nonce: '<?php echo wp_create_nonce('ordenar_cursos_nonce'); ?>'
                            },
                            success: function (response) {
                                $loading.hide();

                                if (response.success && response.data.length > 0) {
                                    var html = '';
                                    $.each(response.data, function (i, curso) {
                                        html += '<li data-id="' + curso.id + '">';
                                        html += '<span class="drag-handle">⋮⋮</span>';
                                        html += '<span class="item-title">' + curso.title + '</span>';
                                        html += '<span class="item-order">#' + (i + 1) + '</span>';
                                        html += '</li>';
                                    });
                                    $list.html(html);
                                    $actions.show();

                                    // Reinicializar sortable
                                    $list.sortable({
                                        placeholder: 'ui-sortable-placeholder',
                                        handle: '.drag-handle',
                                        update: function (event, ui) {
                                            $list.find('li').each(function (i) {
                                                $(this).find('.item-order').text('#' + (i + 1));
                                            });
                                        }
                                    });
                                } else {
                                    $list.html('<li style="background: #f0f0f1; color: #666; cursor: default; border-style: dashed;">Nenhum curso encontrado nesta trilha</li>');
                                    $actions.hide();
                                }
                            },
                            error: function () {
                                $loading.hide();
                                $list.html('<li style="color: #d63638;">Erro ao carregar cursos</li>');
                            }
                        });
                    });

                    // Salvar ordem dos cursos
                    $('#salvar-ordem-cursos').on('click', function () {
                        var $btn = $(this);
                        var $notice = $('#cursos-notice');
                        var trilhaId = $('#select-trilha-cursos').val();

                        var cursoIds = [];
                        $('#sortable-cursos li[data-id]').each(function () {
                            cursoIds.push($(this).data('id'));
                        });

                        $btn.prop('disabled', true).text('Salvando...');
                        $notice.removeClass('success error').hide();

                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'salvar_ordem_cursos_trilha',
                                trilha_id: trilhaId,
                                curso_ids: cursoIds,
                                nonce: '<?php echo wp_create_nonce('ordenar_cursos_nonce'); ?>'
                            },
                            success: function (response) {
                                $btn.prop('disabled', false).text('💾 Salvar Ordem dos Cursos');

                                if (response.success) {
                                    $notice.text('✅ Ordem dos cursos salva com sucesso!').addClass('success').show();
                                    setTimeout(function () { $notice.fadeOut(); }, 3000);
                                } else {
                                    $notice.text('❌ Erro ao salvar: ' + response.data).addClass('error').show();
                                }
                            },
                            error: function () {
                                $btn.prop('disabled', false).text('💾 Salvar Ordem dos Cursos');
                                $notice.text('❌ Erro de conexão').addClass('error').show();
                            }
                        });
                    });
                });
            </script>

        <?php elseif ($active_tab == 'shortcodes'): ?>

            <h2>Shortcodes Disponíveis</h2>
            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Shortcode</th>
                        <th>Descrição e Uso</th>
                    </tr>
                </thead>
                <tbody>

                    <!-- [lms-painel] ★ NOVO -->
                    <tr style="background: linear-gradient(135deg, rgba(252, 196, 25, 0.08), rgba(252, 196, 25, 0.02));">
                        <td><span class="sc-tag" style="border-color: #fcc419; color: #b8860b;">⭐ [lms-painel]</span></td>
                        <td>
                            <div class="sc-desc-text"><strong>Shortcode Universal (Recomendado).</strong> Renderiza um painel
                                completo estilo SPA com sidebar permanente e carregamento dinâmico de seções via AJAX.
                                Substitui a necessidade de múltiplas páginas e shortcodes individuais.</div>
                            <div class="sc-params">
                                <strong>Views integradas:</strong>
                                <ul>
                                    <li><strong>Início</strong> — Meus Cursos (padrão)</li>
                                    <li><strong>Minha Conta</strong> — Perfil do usuário</li>
                                    <li><strong>Meus Cursos</strong> — Cursos matriculados</li>
                                    <li><strong>Todos os Cursos</strong> — Catálogo completo</li>
                                    <li><strong>Curso</strong> — Player de aulas (carregado ao clicar num curso, com botão
                                        voltar)</li>
                                    <li><strong>Certificados</strong> — Certificados do aluno</li>
                                    <li><strong>Admin</strong> — Cadastro de alunos (somente administradores)</li>
                                </ul>
                                <strong>Uso:</strong>
                                <p>Basta criar <strong>UMA</strong> página e inserir:</p>
                                <pre
                                    style="background: #f0f0f1; padding: 15px; border-radius: 4px; border-left: 4px solid #fcc419; overflow-x: auto;"><code>[lms-painel]</code></pre>
                            </div>
                        </td>
                    </tr>

                    <!-- [barra-lateral-aluno] -->
                    <tr>
                        <td><span class="sc-tag">[barra-lateral-aluno]</span></td>
                        <td>
                            <div class="sc-desc-text">Renderiza uma barra lateral de navegação completa com avatar do aluno,
                                nome, progresso geral e links personalizados.</div>
                            <div class="sc-params">
                                <strong>Configuração de Links:</strong>
                                <ul>
                                    <li><code>link_inicio</code> Home / Dashboard</li>
                                    <li><code>link_minha_conta</code> Perfil do Aluno</li>
                                    <li><code>link_meus_cursos</code> Listagem de Matrículas</li>
                                    <li><code>link_todos_cursos</code> Catálogo Completo</li>
                                    <li><code>link_certificados</code> Conquistas</li>
                                    <li><code>link_admin</code> Painel WordPress (apenas Administradores)</li>
                                </ul>
                                <strong>Uso Recomendado:</strong>
                                <p>Copie o exemplo abaixo, cole na sua página e ajuste as URLs conforme necessário:</p>
                                <pre
                                    style="background: #f0f0f1; padding: 15px; border-radius: 4px; border-left: 4px solid #2271b1; overflow-x: auto;"><code>[barra-lateral-aluno 
                                                                                            link_inicio="/inicio" 
                                                                                            link_minha_conta="/perfil"
                                                                                            link_meus_cursos="/meus-cursos" 
                                                                                            link_todos_cursos="/loja"
                                                                                            link_certificados="/certificados"
                                                                                            link_admin="/wp-admin"
                                                                                        ]</code></pre>
                            </div>
                        </td>
                    </tr>

                    <!-- [barra-progresso-geral] -->
                    <tr>
                        <td><span class="sc-tag">[barra-progresso-geral]</span></td>
                        <td>
                            <div class="sc-desc-text">Exibe uma barra de progresso visual mostrando a porcentagem geral de
                                conclusão de todos os cursos que o aluno tem acesso.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[barra-progresso-geral]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [cadastro-usuario] -->
                    <tr>
                        <td><span class="sc-tag">[cadastro-usuario]</span></td>
                        <td>
                            <div class="sc-desc-text">Renderiza o formulário de cadastro de novos alunos. Inclui abas para
                                cadastro manual e importação via CSV.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[cadastro-usuario]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [certificado] -->
                    <tr>
                        <td><span class="sc-tag">[certificado]</span></td>
                        <td>
                            <div class="sc-desc-text">Gerencia a exibição e geração de certificados.</div>
                            <div class="sc-params">
                                <strong>Funcionalidade:</strong> Sem parâmetros, lista os certificados disponíveis. No contexto
                                de conclusão, exibe o certificado.
                                <br><br>
                                <strong>Parâmetros Opcionais:</strong>
                                <ul>
                                    <li><code>curso_id</code>: ID do curso para forçar a exibição (ex:
                                        <code>[certificado curso_id="123"]</code>).
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>

                    <!-- [lista-aulas] -->
                    <tr>
                        <td><span class="sc-tag">[lista-aulas]</span></td>
                        <td>
                            <div class="sc-desc-text">Exibe o player de vídeo, descrição e lista de aulas lateral (sidebar). É o
                                coração da experiência de assistir aulas.</div>
                            <div class="sc-params">
                                <strong>Parâmetros:</strong>
                                <ul>
                                    <li><code>curso_id</code>: (Obrigatório se fora do loop) ID do curso.</li>
                                    <li><code>aula_id</code>: (Opcional) ID da aula inicial.</li>
                                    <li><code>limite</code>: (Opcional) Padrão 200.</li>
                                </ul>
                                <strong>Exemplo:</strong> <code>[lista-aulas curso_id="10"]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [meus-cursos] -->
                    <tr>
                        <td><span class="sc-tag">[meus-cursos]</span></td>
                        <td>
                            <div class="sc-desc-text">Lista todos os cursos em que o usuário logado está matriculado, mostrando
                                uma barra de progresso individual para cada um.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[meus-cursos]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [minha-conta] -->
                    <tr>
                        <td><span class="sc-tag">[minha-conta]</span></td>
                        <td>
                            <div class="sc-desc-text">Exibe um painel para o usuário editar seus dados pessoais (Nome, CPF, Data
                                de Nascimento, Endereço, etc).</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[minha-conta]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [redireciona-aula] -->
                    <tr>
                        <td><span class="sc-tag">[redireciona-aula]</span></td>
                        <td>
                            <div class="sc-desc-text">Utilitário para usar no modelo <em>Single Aula</em>. Redireciona o acesso
                                direto à aula para a visualização dentro do player do curso.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[redireciona-aula]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [resultado-busca] -->
                    <tr>
                        <td><span class="sc-tag">[resultado-busca]</span></td>
                        <td>
                            <div class="sc-desc-text">Exibe os resultados da pesquisa do site utilizando o design system do
                                projeto.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[resultado-busca]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [single-trilha] -->
                    <tr>
                        <td><span class="sc-tag">[single-trilha]</span></td>
                        <td>
                            <div class="sc-desc-text">Usado na página de uma Trilha. Lista todos os cursos que pertencem a essa
                                trilha visualmente.</div>
                            <div class="sc-params">
                                <strong>Uso:</strong> <code>[single-trilha]</code>
                            </div>
                        </td>
                    </tr>

                    <!-- [cursos_da_trilha] -->
                    <tr>
                        <td><span class="sc-tag">[cursos_da_trilha]</span></td>
                        <td>
                            <div class="sc-desc-text">Similar ao <code>[single-trilha]</code>, mas com mais opções de controle.
                                Lista cursos associados ao ID da trilha atual.</div>
                            <div class="sc-params">
                                <strong>Parâmetros:</strong>
                                <ul>
                                    <li><code>orderby</code>: Padrão 'title'.</li>
                                    <li><code>order</code>: 'ASC' ou 'DESC'.</li>
                                    <li><code>limit</code>: Padrão -1 (todos).</li>
                                    <li><code>image_width</code>: Padrão 220.</li>
                                </ul>
                                <strong>Exemplo:</strong> <code>[cursos_da_trilha limit="4" order="DESC"]</code>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>

        <?php elseif ($active_tab == 'instrucoes'): ?>

            <h2>Instruções de Configuração Inicial</h2>

            <div
                style="background: linear-gradient(135deg, rgba(252, 196, 25, 0.12), rgba(252, 196, 25, 0.03)); border: 1px solid rgba(252, 196, 25, 0.3); border-radius: 8px; padding: 20px; margin-bottom: 25px;">
                <h3 style="margin-top: 0; color: #b8860b;">⭐ Modo Simplificado (Recomendado)</h3>
                <p>Crie <strong>apenas 1 página</strong> e insira o shortcode abaixo. Todas as seções (cursos, conta,
                    certificados, admin) ficam acessíveis via sidebar automática:</p>
                <pre
                    style="background: #fff; padding: 12px; border-radius: 4px; border-left: 4px solid #fcc419; font-size: 14px;"><code>[lms-painel]</code></pre>
                <p style="margin-bottom: 0; font-size: 13px; color: #666;">💡 Se preferir mais controle, use o <strong>modo
                        avançado</strong> abaixo com múltiplas páginas e shortcodes individuais.</p>
            </div>

            <h3>Modo Avançado — Páginas e Shortcodes Individuais</h3>
            <p>Para o funcionamento correto do sistema usando páginas separadas, você deve criar as páginas abaixo e inserir os
                respectivos shortcodes.
            </p>

            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Página Sugerida</th>
                        <th>Shortcode Obrigatório</th>
                        <th>Descrição da Funcionalidade</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Cadastro / Login</strong></td>
                        <td><code>[cadastro-usuario]</code></td>
                        <td>
                            Permite o cadastro de novos alunos e login no sistema.
                            <br><small>Redireciona automaticamente se já logado.</small>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Minha Conta</strong></td>
                        <td><code>[minha-conta]</code></td>
                        <td>
                            Painel onde o aluno edita seus dados pessoais (nome, senha, foto).
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Meus Certificados</strong></td>
                        <td><code>[certificado]</code></td>
                        <td>
                            Lista os certificados conquistados pelo aluno.
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Meus Cursos (Dashboard)</strong></td>
                        <td><code>[meus-cursos]</code></td>
                        <td>
                            Lista todos os cursos em que o aluno está matriculado, com barra de progresso.
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Busca de Cursos</strong></td>
                        <td><code>[resultado-busca]</code></td>
                        <td>
                            Página de resultados da pesquisa (o formulário de busca deve apontar para esta página).
                        </td>
                    </tr>
                </tbody>
                </tbody>
            </table>

            <br>
            <h3 style="border-top: 1px solid #ccc; padding-top: 20px; margin-top: 20px;">Modelos de Single (Elementor Theme
                Builder)</h3>
            <p>Além das páginas acima, você deve criar modelos de <strong>Single Post</strong> no Elementor (Theme Builder) para
                os seguintes tipos de post:</p>

            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 250px;">Tipo de Post (CPT)</th>
                        <th>Shortcode Recomendado</th>
                        <th>O que faz?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Aulas</strong> (<code>aula</code>)</td>
                        <td><code>[lista-aulas]</code></td>
                        <td>
                            Exibe o player de vídeo principal, navegação lateral e anexos.
                            <br><em>Essencial para a experiência de aprendizado.</em>
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Trilhas</strong> (<code>trilha</code>)</td>
                        <td><code>[single-trilha]</code></td>
                        <td>
                            Exibe a lista de cursos que pertencem à trilha atual.
                            <br>(Alternativa: use o widget nativo de Posts do Elementor filtrando por meta 'trilha').
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Cursos</strong> (<code>curso</code>)</td>
                        <td><em>Nenhum / Opcional</em></td>
                        <td>
                            Use widgets nativos do Elementor (Título, Imagem Destacada, Conteúdo) para criar uma Landing Page de
                            venda.
                            <br><strong>Ou</strong> use <code>[lista-aulas]</code> se quiser que o curso abra direto no player
                            (modo "estilo Netflix").
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3>💡 Dicas Adicionais</h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Crie uma página para cada item acima.</li>
                <li>No editor da página, cole apenas o shortcode correspondente.</li>
                <li>Adicione essas páginas ao menu principal do site para facilitar a navegação.</li>
            </ul>

        <?php elseif ($active_tab == 'cpts'): ?>

            <h2>Estrutura de Dados e Campos Personalizados</h2>
            <p>Para que o sistema funcione corretamente, os seguintes Custom Post Types (CPTs) e Campos Personalizados (ACF)
                devem existir.</p>

            <h3 class="cpt-section-title">1. Post Type: Curso (<code>curso</code>)</h3>
            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Campo (Meta Key)</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>trilha</code></td>
                        <td><span class="field-type">Relationship / Post Object</span></td>
                        <td>Deve retornar o ID da Trilha associada a este curso.</td>
                    </tr>
                    <tr>
                        <td><code>capa_vertical</code></td>
                        <td><span class="field-type">Image</span></td>
                        <td>Imagem vertical usada nos cards de listagem (ex: Meus Cursos, Busca). Retorna Array ou URL.</td>
                    </tr>
                    <tr>
                        <td><code>_curso_certificado_id</code></td>
                        <td><span class="field-type">Post Meta</span></td>
                        <td>ID do certificado associado a este curso. Gerenciado automaticamente pela metabox do plugin.</td>
                    </tr>
                </tbody>
            </table>

            <h3 class="cpt-section-title">2. Post Type: Aula (<code>aula</code>)</h3>
            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Campo (Meta Key)</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>curso</code></td>
                        <td><span class="field-type">Relationship / Post Object</span></td>
                        <td>Define a qual Curso esta aula pertence. <span class="req-plugin">Crucial para a navegação.</span>
                        </td>
                    </tr>
                    <tr>
                        <td><code>embed_do_vimeo</code></td>
                        <td><span class="field-type">Text / Oembed</span></td>
                        <td>URL do vídeo (Vimeo/YouTube) ou código de embed.</td>
                    </tr>
                    <tr>
                        <td><code>descricao</code></td>
                        <td><span class="field-type">Wysiwyg / Textarea</span></td>
                        <td>Descrição completa do conteúdo da aula que aparece abaixo do vídeo.</td>
                    </tr>
                    <tr>
                        <td><code>arquivos</code></td>
                        <td><span class="field-type">Repeater</span></td>
                        <td>Lista de materiais de apoio. Sub-campo: <code>anexos</code> (File/URL).</td>
                    </tr>
                </tbody>
            </table>

            <h3 class="cpt-section-title">3. Post Type: Trilha (<code>trilha</code>)</h3>
            <table class="sc-table">
                <thead>
                    <tr>
                        <th style="width: 200px;">Campo (Meta Key)</th>
                        <th>Tipo</th>
                        <th>Descrição</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>descricao_curta</code></td>
                        <td><span class="field-type">Textarea</span></td>
                        <td>Uma breve descrição da trilha exibida nos cards.</td>
                    </tr>
                    <tr>
                        <td>Este Post Type serve primariamente como agrupador. Os cursos são ligados à trilha através do campo
                            <code>trilha</code> no CPT Curso.
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3 class="cpt-section-title">4. Post Type: Certificado (<code>certificado</code>)</h3>
            <table class="sc-table">
                <tbody>
                    <tr>
                        <td>Post Type registrado internamente por este plugin (arquivo <code>certificado.php</code>). Armazena
                            os templates de certificado.</td>
                    </tr>
                </tbody>
            </table>

        <?php elseif ($active_tab == 'acesso'): ?>

            <?php
            $aluno_redirect_page_id = (int) get_option('lms_sr_aluno_redirect_page_id', 0);
            $aluno_redirect_page_url = $aluno_redirect_page_id > 0 ? get_permalink($aluno_redirect_page_id) : '';
            ?>

            <?php if (!empty($settings_notice)): ?>
                <?php echo wp_kses_post($settings_notice); ?>
            <?php endif; ?>

            <h2>Configuração de Acesso</h2>
            <p>Escolha para qual página usuários com role <code>aluno</code> devem ser redirecionados depois do login.</p>

            <form method="post" action="">
                <?php wp_nonce_field('lms_sr_save_acesso_settings', 'lms_sr_acesso_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="lms_sr_aluno_redirect_page_id">Página de redirecionamento do aluno</label>
                        </th>
                        <td>
                            <?php
                            wp_dropdown_pages([
                                'name' => 'lms_sr_aluno_redirect_page_id',
                                'id' => 'lms_sr_aluno_redirect_page_id',
                                'selected' => $aluno_redirect_page_id,
                                'show_option_none' => '-- Selecione uma página --',
                                'option_none_value' => '0',
                            ]);
                            ?>
                            <p class="description">Se nenhuma página for selecionada, o plugin vai redirecionar para a home.</p>
                            <?php if (!empty($aluno_redirect_page_url)): ?>
                                <p class="description">URL atual: <code><?php echo esc_url($aluno_redirect_page_url); ?></code></p>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" name="lms_sr_save_acesso_settings" class="button button-primary">Salvar
                        configuração</button>
                </p>
            </form>

        <?php elseif ($active_tab == 'banner'): ?>

            <?php
            $banner_settings = sistema_cursos_get_banner_settings();
            $banner_slides = isset($banner_settings['slides']) && is_array($banner_settings['slides']) ? $banner_settings['slides'] : [];
            wp_enqueue_media();
            ?>

            <?php if (!empty($settings_notice)): ?>
                <?php echo wp_kses_post($settings_notice); ?>
            <?php endif; ?>

            <h2>Banner da Página Inicial</h2>
            <p>Configure o carrossel exibido apenas na view <code>Inicio</code> do painel, acima da lista de trilhas.</p>
            <p class="description">Tamanho recomendado para melhor resultado visual: <strong>1340 x 365 px</strong>.</p>

            <form method="post" action="">
                <?php wp_nonce_field('lms_sr_save_banner_settings', 'lms_sr_banner_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="lms_sr_banner_autoplay">Tempo entre banners (segundos)</label>
                        </th>
                        <td>
                            <input type="number" id="lms_sr_banner_autoplay" name="lms_sr_banner[autoplay_seconds]"
                                value="<?php echo esc_attr((string) $banner_settings['autoplay_seconds']); ?>" min="2" max="30"
                                step="1">
                            <p class="description">Intervalo automático do carrossel (entre 2 e 30 segundos).</p>
                        </td>
                    </tr>
                </table>

                <div class="lms-banner-admin-wrap">
                    <h3>Imagens do Carrossel</h3>
                    <p class="description">Cada imagem pode ter um link opcional (ex.: página de curso, trilha, campanha etc.).
                    </p>

                    <div id="lms-banner-slides" class="lms-banner-slides">
                        <?php foreach ($banner_slides as $index => $slide): ?>
                            <?php
                            $image_id = isset($slide['image_id']) ? absint($slide['image_id']) : 0;
                            $image_url = $image_id > 0 ? wp_get_attachment_image_url($image_id, 'medium') : '';
                            $slide_link = isset($slide['link']) ? (string) $slide['link'] : '';
                            ?>
                            <div class="lms-banner-row" data-index="<?php echo esc_attr((string) $index); ?>">
                                <div class="lms-banner-thumb">
                                    <img src="<?php echo esc_url($image_url ?: ''); ?>" alt="" <?php echo empty($image_url) ? 'style="display:none;"' : ''; ?>>
                                </div>
                                <div class="lms-banner-fields">
                                    <input type="hidden" class="lms-banner-image-id"
                                        name="lms_sr_banner[slides][<?php echo esc_attr((string) $index); ?>][image_id]"
                                        value="<?php echo esc_attr((string) $image_id); ?>">
                                    <p>
                                        <button type="button"
                                            class="button lms-banner-select-image"><?php echo $image_id > 0 ? 'Trocar imagem' : 'Selecionar imagem'; ?></button>
                                        <button type="button" class="button-link-delete lms-banner-remove">Remover</button>
                                    </p>
                                    <label>Link (opcional)</label>
                                    <input type="url" class="regular-text lms-banner-link-input"
                                        name="lms_sr_banner[slides][<?php echo esc_attr((string) $index); ?>][link]"
                                        value="<?php echo esc_attr($slide_link); ?>" placeholder="https://exemplo.com/pagina">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <p>
                        <button type="button" class="button" id="lms-banner-add">+ Adicionar imagem</button>
                    </p>
                </div>

                <p class="submit">
                    <button type="submit" name="lms_sr_save_banner_settings" class="button button-primary">Salvar
                        banner</button>
                </p>
            </form>

            <style>
                .lms-banner-admin-wrap {
                    margin-top: 20px;
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 6px;
                    padding: 20px;
                    max-width: 1000px;
                }

                .lms-banner-slides {
                    display: grid;
                    gap: 14px;
                    margin-top: 12px;
                }

                .lms-banner-row {
                    display: grid;
                    grid-template-columns: 220px 1fr;
                    gap: 16px;
                    padding: 14px;
                    border: 1px solid #dcdcde;
                    border-radius: 6px;
                    background: #f8f9fa;
                }

                .lms-banner-thumb {
                    width: 100%;
                    height: 100px;
                    border-radius: 4px;
                    border: 1px dashed #c3c4c7;
                    background: #fff;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    overflow: hidden;
                }

                .lms-banner-thumb img {
                    width: 100%;
                    height: 100%;
                    object-fit: cover;
                }

                .lms-banner-fields label {
                    display: block;
                    margin-bottom: 4px;
                    font-weight: 600;
                }

                .lms-banner-fields p {
                    margin: 0 0 8px;
                }

                @media (max-width: 782px) {
                    .lms-banner-row {
                        grid-template-columns: 1fr;
                    }

                    .lms-banner-thumb {
                        height: 130px;
                    }
                }
            </style>

            <script>
                jQuery(function ($) {
                    var $slides = $('#lms-banner-slides');

                    function createRow() {
                        return $(
                            '<div class="lms-banner-row" data-index="0">' +
                            '<div class="lms-banner-thumb"><img src="" alt="" style="display:none;"></div>' +
                            '<div class="lms-banner-fields">' +
                            '<input type="hidden" class="lms-banner-image-id" value="">' +
                            '<p>' +
                            '<button type="button" class="button lms-banner-select-image">Selecionar imagem</button> ' +
                            '<button type="button" class="button-link-delete lms-banner-remove">Remover</button>' +
                            '</p>' +
                            '<label>Link (opcional)</label>' +
                            '<input type="url" class="regular-text lms-banner-link-input" value="" placeholder="https://exemplo.com/pagina">' +
                            '</div>' +
                            '</div>'
                        );
                    }

                    function reindexRows() {
                        $slides.find('.lms-banner-row').each(function (index) {
                            $(this).attr('data-index', index);
                            $(this).find('.lms-banner-image-id').attr('name', 'lms_sr_banner[slides][' + index + '][image_id]');
                            $(this).find('.lms-banner-link-input').attr('name', 'lms_sr_banner[slides][' + index + '][link]');
                        });
                    }

                    function openMediaFrame($row) {
                        var frame = wp.media({
                            title: 'Selecionar imagem do banner',
                            button: { text: 'Usar imagem' },
                            library: { type: 'image' },
                            multiple: false
                        });

                        frame.on('select', function () {
                            var attachment = frame.state().get('selection').first().toJSON();
                            if (!attachment || !attachment.id) return;

                            $row.find('.lms-banner-image-id').val(attachment.id);
                            $row.find('.lms-banner-select-image').text('Trocar imagem');

                            var preview = attachment.sizes && attachment.sizes.medium
                                ? attachment.sizes.medium.url
                                : attachment.url;
                            $row.find('.lms-banner-thumb img').attr('src', preview).show();
                        });

                        frame.open();
                    }

                    $('#lms-banner-add').on('click', function (event) {
                        event.preventDefault();
                        var $row = createRow();
                        $slides.append($row);
                        reindexRows();
                    });

                    $slides.on('click', '.lms-banner-remove', function (event) {
                        event.preventDefault();
                        $(this).closest('.lms-banner-row').remove();
                        reindexRows();
                    });

                    $slides.on('click', '.lms-banner-select-image', function (event) {
                        event.preventDefault();
                        openMediaFrame($(this).closest('.lms-banner-row'));
                    });

                    reindexRows();
                });
            </script>

        <?php elseif ($active_tab == 'personalizar'): ?>

            <?php
            $cust_settings = System_Cursos_Customizer::get_settings();
            $cust_defaults = System_Cursos_Customizer::get_defaults();
            $font_options = System_Cursos_Customizer::get_font_options();
            $presets = System_Cursos_Customizer::get_presets();
            ?>

            <style>
                .lms-cust-wrap {
                    max-width: 900px;
                }

                .lms-cust-section {
                    background: #fff;
                    border: 1px solid #c3c4c7;
                    border-radius: 6px;
                    padding: 24px;
                    margin-bottom: 20px;
                }

                .lms-cust-section h3 {
                    margin: 0 0 16px;
                    padding-bottom: 10px;
                    border-bottom: 2px solid #2271b1;
                    color: #1d2327;
                    font-size: 15px;
                }

                .lms-cust-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
                    gap: 16px;
                }

                .lms-cust-field {
                    display: flex;
                    flex-direction: column;
                    gap: 6px;
                }

                .lms-cust-field label {
                    font-size: 12px;
                    font-weight: 600;
                    color: #1d2327;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                }

                .lms-cust-field .description {
                    font-size: 11px;
                    color: #666;
                    margin: 0;
                }

                .lms-cust-color-wrap {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }

                .lms-cust-color-wrap input[type="color"] {
                    width: 40px;
                    height: 34px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    padding: 2px;
                    cursor: pointer;
                }

                .lms-cust-color-wrap input[type="text"] {
                    width: 90px;
                    font-family: monospace;
                    font-size: 13px;
                    padding: 4px 8px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                }

                .lms-cust-field select,
                .lms-cust-field input[type="number"] {
                    padding: 6px 10px;
                    border: 1px solid #c3c4c7;
                    border-radius: 4px;
                    font-size: 13px;
                }

                .lms-cust-field select {
                    max-width: 300px;
                }

                .lms-cust-field input[type="number"] {
                    width: 80px;
                }

                .lms-cust-actions {
                    display: flex;
                    gap: 10px;
                    align-items: center;
                    margin-top: 20px;
                }

                .lms-cust-notice {
                    padding: 8px 14px;
                    border-radius: 4px;
                    display: none;
                    font-size: 13px;
                }

                .lms-cust-notice.success {
                    background: #d1e7dd;
                    color: #0f5132;
                    border: 1px solid #badbcc;
                    display: block;
                }

                .lms-cust-notice.error {
                    background: #f8d7da;
                    color: #842029;
                    border: 1px solid #f5c2c7;
                    display: block;
                }

                .lms-cust-preview {
                    border: 1px solid #c3c4c7;
                    border-radius: 8px;
                    overflow: hidden;
                    margin-top: 16px;
                }

                .lms-cust-preview-inner {
                    padding: 24px;
                    transition: all 0.3s;
                }

                .lms-cust-preview-card {
                    padding: 16px;
                    border-radius: 8px;
                    border: 1px solid;
                    margin-bottom: 12px;
                }

                .lms-cust-preview-btn {
                    display: inline-block;
                    padding: 8px 20px;
                    border-radius: 6px;
                    font-weight: 600;
                    font-size: 14px;
                    border: none;
                    cursor: default;
                    color: #fff;
                }

                .lms-cust-preview-progress {
                    height: 6px;
                    border-radius: 3px;
                    overflow: hidden;
                    margin-top: 8px;
                }

                .lms-cust-preview-progress-fill {
                    height: 100%;
                    width: 65%;
                    border-radius: 3px;
                }

                .lms-presets-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
                    gap: 14px;
                }

                .lms-preset-card {
                    border: 2px solid #dcdcde;
                    border-radius: 10px;
                    padding: 0;
                    cursor: pointer;
                    transition: all 0.2s ease;
                    overflow: hidden;
                    background: #fff;
                }

                .lms-preset-card:hover {
                    border-color: #2271b1;
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                }

                .lms-preset-card.is-active {
                    border-color: #2271b1;
                    box-shadow: 0 0 0 1px #2271b1, 0 4px 12px rgba(34, 113, 177, 0.15);
                }

                .lms-preset-swatch {
                    height: 80px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    gap: 6px;
                    padding: 12px;
                }

                .lms-preset-swatch-dot {
                    width: 22px;
                    height: 22px;
                    border-radius: 50%;
                    border: 2px solid rgba(255, 255, 255, 0.25);
                    flex-shrink: 0;
                }

                .lms-preset-swatch-accent {
                    width: 30px;
                    height: 30px;
                    border-radius: 50%;
                    border: 3px solid rgba(255, 255, 255, 0.3);
                    flex-shrink: 0;
                }

                .lms-preset-info {
                    padding: 10px 12px;
                    border-top: 1px solid #eee;
                }

                .lms-preset-name {
                    font-weight: 600;
                    font-size: 13px;
                    color: #1d2327;
                    margin: 0 0 2px;
                    display: flex;
                    align-items: center;
                    gap: 5px;
                }

                .lms-preset-desc {
                    font-size: 11px;
                    color: #666;
                    margin: 0;
                    line-height: 1.3;
                }

                .lms-preset-badge {
                    display: inline-block;
                    font-size: 10px;
                    padding: 1px 6px;
                    border-radius: 3px;
                    background: #f0f0f1;
                    color: #50575e;
                    font-weight: 500;
                    margin-left: auto;
                }

                .lms-preset-card.is-active .lms-preset-badge {
                    background: #2271b1;
                    color: #fff;
                }
            </style>

            <div class="lms-cust-wrap">
                <h2>Personalizar Aparência do LMS</h2>
                <p>Configure cores, fontes e bordas do sistema. Todas as alterações são <strong>isoladas do tema
                        WordPress</strong> e afetam exclusivamente os componentes do LMS.</p>

                <!-- Preview -->
                <div class="lms-cust-section">
                    <h3>👁️ Preview em Tempo Real</h3>
                    <div class="lms-cust-preview">
                        <div class="lms-cust-preview-inner" id="lms-preview">
                            <h3 style="margin:0 0 8px;" class="lms-prev-heading">Título de Exemplo</h3>
                            <p style="margin:0 0 12px;" class="lms-prev-text">Texto de exemplo do conteúdo do LMS.</p>
                            <p style="margin:0 0 16px; font-size:12px;" class="lms-prev-muted">Texto secundário / muted</p>
                            <div class="lms-cust-preview-card" id="lms-prev-card">
                                <strong class="lms-prev-heading" style="display:block; margin-bottom:6px;">Card de
                                    Curso</strong>
                                <span class="lms-prev-muted" style="font-size:12px;">Descrição do curso aqui</span>
                                <div class="lms-cust-preview-progress" id="lms-prev-progress-bg">
                                    <div class="lms-cust-preview-progress-fill" id="lms-prev-progress-fill"></div>
                                </div>
                            </div>
                            <span class="lms-cust-preview-btn" id="lms-prev-btn">Botão Primário</span>
                            <span class="lms-cust-preview-btn" id="lms-prev-btn-success"
                                style="margin-left:8px;">Concluído</span>
                        </div>
                    </div>
                </div>

                <!-- Presets -->
                <div class="lms-cust-section">
                    <h3>🎨 Presets de Cores</h3>
                    <p style="margin:0 0 14px; font-size:13px; color:#666;">Selecione um preset para aplicar uma combinação
                        harmoniosa de cores. Você pode personalizar individualmente após aplicar.</p>
                    <div class="lms-presets-grid">
                        <?php foreach ($presets as $key => $preset): ?>
                            <div class="lms-preset-card" data-preset="<?php echo esc_attr($key); ?>">
                                <div class="lms-preset-swatch"
                                    style="background: linear-gradient(135deg, <?php echo esc_attr($preset['colors']['color_bg_primary']); ?> 0%, <?php echo esc_attr($preset['colors']['color_bg_secondary']); ?> 100%);">
                                    <span class="lms-preset-swatch-dot"
                                        style="background:<?php echo esc_attr($preset['colors']['color_text_heading']); ?>;"></span>
                                    <span class="lms-preset-swatch-accent"
                                        style="background:<?php echo esc_attr($preset['colors']['color_accent']); ?>;"></span>
                                    <span class="lms-preset-swatch-dot"
                                        style="background:<?php echo esc_attr($preset['colors']['color_success']); ?>;"></span>
                                </div>
                                <div class="lms-preset-info">
                                    <p class="lms-preset-name"><?php echo $preset['emoji']; ?>
                                        <?php echo esc_html($preset['name']); ?><span class="lms-preset-badge">Aplicar</span>
                                    </p>
                                    <p class="lms-preset-desc"><?php echo esc_html($preset['desc']); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cores de Fundo -->
                <div class="lms-cust-section">
                    <h3>🎨 Cores de Fundo</h3>
                    <div class="lms-cust-grid">
                        <?php
                        $bg_fields = [
                            'color_bg_primary' => 'Fundo Principal',
                            'color_bg_secondary' => 'Fundo Secundário',
                            'color_bg_tertiary' => 'Fundo Terciário',
                            'color_bg_header_start' => 'Header (Início)',
                            'color_bg_header_end' => 'Header (Fim)',
                            'color_bg_footer' => 'Footer',
                        ];
                        foreach ($bg_fields as $key => $label):
                            ?>
                            <div class="lms-cust-field">
                                <label><?php echo esc_html($label); ?></label>
                                <div class="lms-cust-color-wrap">
                                    <input type="color" id="<?php echo $key; ?>_picker"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" data-target="<?php echo $key; ?>">
                                    <input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" maxlength="7">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cores de Texto -->
                <div class="lms-cust-section">
                    <h3>✏️ Cores de Texto</h3>
                    <div class="lms-cust-grid">
                        <?php
                        $text_fields = [
                            'color_text_primary' => 'Texto Principal',
                            'color_text_heading' => 'Títulos',
                            'color_text_muted' => 'Texto Secundário',
                            'color_text_label' => 'Labels',
                        ];
                        foreach ($text_fields as $key => $label):
                            ?>
                            <div class="lms-cust-field">
                                <label><?php echo esc_html($label); ?></label>
                                <div class="lms-cust-color-wrap">
                                    <input type="color" id="<?php echo $key; ?>_picker"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" data-target="<?php echo $key; ?>">
                                    <input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" maxlength="7">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Cores de Destaque e Estado -->
                <div class="lms-cust-section">
                    <h3>⚡ Cores de Destaque e Estado</h3>
                    <div class="lms-cust-grid">
                        <?php
                        $accent_fields = [
                            'color_accent' => 'Cor de Destaque (Accent)',
                            'color_accent_hover' => 'Accent Hover',
                            'color_success' => 'Sucesso (Verde)',
                            'color_error' => 'Erro (Vermelho)',
                            'color_border' => 'Borda Principal',
                            'color_border_input' => 'Borda dos Inputs',
                        ];
                        foreach ($accent_fields as $key => $label):
                            ?>
                            <div class="lms-cust-field">
                                <label><?php echo esc_html($label); ?></label>
                                <div class="lms-cust-color-wrap">
                                    <input type="color" id="<?php echo $key; ?>_picker"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" data-target="<?php echo $key; ?>">
                                    <input type="text" id="<?php echo $key; ?>" name="<?php echo $key; ?>"
                                        value="<?php echo esc_attr($cust_settings[$key]); ?>" maxlength="7">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tipografia -->
                <div class="lms-cust-section">
                    <h3>🔤 Tipografia</h3>
                    <div class="lms-cust-grid">
                        <div class="lms-cust-field">
                            <label>Família da Fonte</label>
                            <select id="font_family" name="font_family">
                                <?php foreach ($font_options as $value => $label): ?>
                                    <option value="<?php echo esc_attr($value); ?>" <?php selected($cust_settings['font_family'], $value); ?>>
                                        <?php echo esc_html($label); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Fontes Google são carregadas automaticamente.</p>
                        </div>
                        <div class="lms-cust-field">
                            <label>Tamanho Base (px)</label>
                            <input type="number" id="font_size_base" name="font_size_base"
                                value="<?php echo esc_attr($cust_settings['font_size_base']); ?>" min="12" max="22" step="1">
                            <p class="description">Padrão: 16px</p>
                        </div>
                    </div>
                </div>

                <!-- Bordas -->
                <div class="lms-cust-section">
                    <h3>📐 Bordas e Arredondamento</h3>
                    <div class="lms-cust-grid">
                        <div class="lms-cust-field">
                            <label>Raio Base (px)</label>
                            <input type="number" id="radius_base" name="radius_base"
                                value="<?php echo esc_attr($cust_settings['radius_base']); ?>" min="0" max="20" step="1">
                            <p class="description">Padrão: 6px. Afeta botões e inputs.</p>
                        </div>
                        <div class="lms-cust-field">
                            <label>Raio dos Cards (px)</label>
                            <input type="number" id="radius_card" name="radius_card"
                                value="<?php echo esc_attr($cust_settings['radius_card']); ?>" min="0" max="30" step="1">
                            <p class="description">Padrão: 12px. Afeta cards e containers.</p>
                        </div>
                    </div>
                </div>

                <!-- Ações -->
                <div class="lms-cust-actions">
                    <button type="button" class="button button-primary button-hero" id="lms-cust-save">💾 Salvar
                        Personalização</button>
                    <button type="button" class="button" id="lms-cust-reset">Restaurar Padrão</button>
                    <span id="lms-cust-notice" class="lms-cust-notice"></span>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    // Sincronizar color picker com input text
                    $('input[type="color"]').on('input', function () {
                        var target = $(this).data('target');
                        $('#' + target).val($(this).val());
                        updatePreview();
                    });

                    $('input[type="text"][maxlength="7"]').on('input', function () {
                        var val = $(this).val();
                        if (/^#[0-9a-fA-F]{6}$/.test(val)) {
                            var id = $(this).attr('id');
                            $('#' + id + '_picker').val(val);
                            updatePreview();
                        }
                    });

                    $('select[name="font_family"], input[type="number"]').on('change', function () {
                        updatePreview();
                    });

                    function updatePreview() {
                        var preview = $('#lms-preview');
                        var bgPrimary = $('#color_bg_primary').val();
                        var bgSecondary = $('#color_bg_secondary').val();
                        var textPrimary = $('#color_text_primary').val();
                        var textHeading = $('#color_text_heading').val();
                        var textMuted = $('#color_text_muted').val();
                        var accent = $('#color_accent').val();
                        var success = $('#color_success').val();
                        var border = $('#color_border_input').val();
                        var fontFamily = $('#font_family').val();
                        var fontSize = $('#font_size_base').val() + 'px';
                        var radiusCard = $('#radius_card').val() + 'px';
                        var radiusBase = $('#radius_base').val() + 'px';

                        preview.css({
                            'background-color': bgPrimary,
                            'font-family': fontFamily,
                            'font-size': fontSize,
                            'color': textPrimary,
                            'border-radius': radiusCard
                        });
                        preview.find('.lms-prev-heading').css('color', textHeading);
                        preview.find('.lms-prev-text').css('color', textPrimary);
                        preview.find('.lms-prev-muted').css('color', textMuted);
                        $('#lms-prev-card').css({
                            'background-color': bgSecondary,
                            'border-color': border,
                            'border-radius': radiusCard
                        });
                        $('#lms-prev-btn').css({ 'background-color': accent, 'border-radius': radiusBase });
                        $('#lms-prev-btn-success').css({ 'background-color': success, 'border-radius': radiusBase });
                        $('#lms-prev-progress-bg').css({ 'background': 'rgba(255,255,255,0.1)', 'border-radius': '3px' });
                        $('#lms-prev-progress-fill').css({ 'background-color': accent, 'border-radius': '3px' });
                    }

                    // Presets de cores
                    var lmsPresets = <?php echo wp_json_encode(array_map(function ($p) {
                        return $p['colors'];
                    }, $presets)); ?>;

                    $('.lms-preset-card').on('click', function () {
                        var presetKey = $(this).data('preset');
                        var colors = lmsPresets[presetKey];
                        if (!colors) return;

                        // Aplicar cada cor nos campos
                        $.each(colors, function (field, value) {
                            $('#' + field).val(value);
                            $('#' + field + '_picker').val(value);
                        });

                        // Marcar card ativo
                        $('.lms-preset-card').removeClass('is-active');
                        $(this).addClass('is-active');

                        // Atualizar preview
                        updatePreview();
                    });

                    // Inicializar preview
                    updatePreview();

                    // Salvar
                    $('#lms-cust-save').on('click', function () {
                        var $btn = $(this);
                        var $notice = $('#lms-cust-notice');
                        $btn.prop('disabled', true).text('Salvando...');
                        $notice.removeClass('success error').hide();

                        var data = {
                            action: 'lms_sr_save_customizer',
                            nonce: '<?php echo wp_create_nonce('lms_sr_customizer_nonce'); ?>'
                        };

                        // Coletar todos os campos
                        <?php foreach (array_keys($cust_defaults) as $key): ?>
                            data['<?php echo $key; ?>'] = $('#<?php echo $key; ?>').val();
                        <?php endforeach; ?>

                        $.post(ajaxurl, data, function (response) {
                            $btn.prop('disabled', false).text('💾 Salvar Personalização');
                            if (response.success) {
                                $notice.text('✅ ' + response.data).addClass('success').show();
                                setTimeout(function () { $notice.fadeOut(); }, 4000);
                            } else {
                                $notice.text('❌ Erro: ' + response.data).addClass('error').show();
                            }
                        }).fail(function () {
                            $btn.prop('disabled', false).text('💾 Salvar Personalização');
                            $notice.text('❌ Erro de conexão').addClass('error').show();
                        });
                    });

                    // Restaurar Padrão
                    $('#lms-cust-reset').on('click', function () {
                        if (!confirm('Tem certeza que deseja restaurar todas as configurações para o padrão?')) return;

                        var $notice = $('#lms-cust-notice');
                        $notice.removeClass('success error').hide();

                        $.post(ajaxurl, {
                            action: 'lms_sr_reset_customizer',
                            nonce: '<?php echo wp_create_nonce('lms_sr_customizer_nonce'); ?>'
                        }, function (response) {
                            if (response.success) {
                                $notice.text('✅ ' + response.data).addClass('success').show();
                                setTimeout(function () { location.reload(); }, 1000);
                            } else {
                                $notice.text('❌ Erro: ' + response.data).addClass('error').show();
                            }
                        });
                    });
                });
            </script>

        <?php endif; ?>

    </div>
    <?php
}

/**
 * FIM DA SEÇÃO DE ABAS
 * AJAX HANDLERS ABAIXO
 */

/**
 * AJAX: Salvar ordem das trilhas
 */
add_action('wp_ajax_salvar_ordem_trilhas', 'sistema_cursos_salvar_ordem_trilhas_handler');
function sistema_cursos_salvar_ordem_trilhas_handler()
{
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ordenar_trilhas_nonce')) {
        wp_send_json_error('Nonce inválido');
    }

    // Verificar permissão
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão');
    }

    $trilha_ids = isset($_POST['trilha_ids']) ? (array) $_POST['trilha_ids'] : [];
    $trilha_ids = array_map('intval', $trilha_ids);

    foreach ($trilha_ids as $ordem => $trilha_id) {
        wp_update_post([
            'ID' => $trilha_id,
            'menu_order' => $ordem
        ]);
    }

    wp_send_json_success('Ordem salva');
}

/**
 * AJAX: Obter cursos de uma trilha
 */
add_action('wp_ajax_get_cursos_trilha', 'sistema_cursos_get_cursos_trilha_handler');
function sistema_cursos_get_cursos_trilha_handler()
{
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ordenar_cursos_nonce')) {
        wp_send_json_error('Nonce inválido');
    }

    $trilha_id = isset($_POST['trilha_id']) ? intval($_POST['trilha_id']) : 0;

    // Buscar cursos da trilha
    if ($trilha_id === 0) {
        // Cursos sem trilha
        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'trilha',
                    'compare' => 'NOT EXISTS'
                ],
                [
                    'key' => 'trilha',
                    'value' => '',
                    'compare' => '='
                ]
            ]
        ]);
    } else {
        // Cursos da trilha específica
        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'post_status' => 'publish',
            'meta_key' => 'trilha',
            'meta_value' => $trilha_id
        ]);
    }

    $result = [];
    foreach ($cursos as $curso) {
        $result[] = [
            'id' => $curso->ID,
            'title' => $curso->post_title,
            'order' => $curso->menu_order
        ];
    }

    wp_send_json_success($result);
}

/**
 * AJAX: Salvar ordem dos cursos de uma trilha
 */
add_action('wp_ajax_salvar_ordem_cursos_trilha', 'sistema_cursos_salvar_ordem_cursos_trilha_handler');
function sistema_cursos_salvar_ordem_cursos_trilha_handler()
{
    // Verificar nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'ordenar_cursos_nonce')) {
        wp_send_json_error('Nonce inválido');
    }

    // Verificar permissão
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Sem permissão');
    }

    $curso_ids = isset($_POST['curso_ids']) ? (array) $_POST['curso_ids'] : [];
    $curso_ids = array_map('intval', $curso_ids);

    foreach ($curso_ids as $ordem => $curso_id) {
        wp_update_post([
            'ID' => $curso_id,
            'menu_order' => $ordem
        ]);
    }

    wp_send_json_success('Ordem salva');
}

/**
 * Carregar jQuery UI Sortable na página de ordenação
 */
add_action('admin_enqueue_scripts', 'sistema_cursos_enqueue_sortable_handler');
function sistema_cursos_enqueue_sortable_handler($hook)
{
    if ($hook === 'toplevel_page_lms-suporte-rapido' && isset($_GET['tab']) && $_GET['tab'] === 'ordenacao') {
        wp_enqueue_script('jquery-ui-sortable');
    }
}

