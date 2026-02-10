<?php
/**
 * Plugin Name: LMS SuporteRapido
 * Description: Plugin LMS para WordPress - Alternativa ao Learndash
 * Author: Giovani Tureck
 * Text Domain: lms-suporte-rapido
 * Version: 1.3.10
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Definição de constantes
define('SISTEMA_CURSOS_VERSION', '1.3.10');

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
require_once plugin_dir_path(__FILE__) . 'includes/class-assets.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-minha-conta.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-cadastro-usuario.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-access-control.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-user-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-admin-filters.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-course-progress.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-listar-aulas.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-meus-cursos.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-certificates.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-certificado.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-resultado-busca.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-barra-progresso.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-cursos-trilha.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-single-trilha.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes/class-shortcode-redireciona-aula.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-woocommerce-integration.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-quiz-builder.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-quiz-process.php';

// 2. Inicializar Assets Globais
new System_Cursos_CPT_Manager();
new System_Cursos_Assets();
new System_Cursos_Shortcode_Minha_Conta();
new System_Cursos_Shortcode_Cadastro();
new System_Cursos_Access_Control();
new System_Cursos_User_Fields();
new System_Cursos_Admin_Filters();
new System_Cursos_Progress();
new System_Cursos_Shortcode_Listar_Aulas();
new System_Cursos_Shortcode_Meus_Cursos();
new System_Cursos_Certificates();
new System_Cursos_Shortcode_Certificado();
new System_Cursos_Shortcode_Resultado_Busca();
new System_Cursos_Shortcode_Barra_Progresso();
new System_Cursos_Shortcode_Cursos_Trilha();
new System_Cursos_Shortcode_Single_Trilha();
new System_Cursos_Shortcode_Redireciona_Aula();
new System_Cursos_Quiz_Builder();
new System_Cursos_Quiz_Process();

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
    update_option('sistema_cursos_version', '1.2.24');
}

/**
 * Verificação de Versão (Auto-Update)
 * Se a versão do código for maior que o banco, roda flush_rewrite_rules.
 * Útil para atualizações onde mudamos slugs ou CPTs.
 */
add_action('init', 'sistema_cursos_check_version', 99);


function sistema_cursos_check_version()
{
    $current_version = '1.3.10';
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

function sistema_cursos_render_admin_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'ordenacao';
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
            <p>Para o funcionamento correto do sistema, você deve criar as páginas abaixo e inserir os respectivos shortcodes.
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

