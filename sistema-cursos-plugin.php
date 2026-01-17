<?php
/**
 * Plugin Name: Sistema de Cursos Personalizado
 * Description: Plugin que unifica todos os snippets do sistema de cursos (Cadastro, Certificados, Aulas, Trilhas, Controle de Acesso, etc) em um único local.
 * Version: 1.1.16
 * Author: Giovani Tureck
 * Text Domain: sistema-cursos
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * sistema-cursos-plugin.php
 *
 * Arquivo principal do plugin Sistema de Cursos Personalizado.
 * Responsável por inicializar todas as classes, shortcodes e funcionalidades do sistema.
 * Carrega dependências, define hooks de ativação e configura o menu de documentação no admin.
 *
 * @package SistemaCursos
 * @version 1.1.14
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
        99                        // Position
    );
}

function sistema_cursos_render_admin_page()
{
    $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'shortcodes';
    ?>
    <div class="wrap">
        <h1>LMS SuporteRapido - Documentação do Sistema</h1>
        <p>Bem-vindo à documentação rápida dos shortcodes e funcionalidades do <strong>Sistema de Cursos
                Personalizado</strong>.</p>

        <nav class="nav-tab-wrapper">
            <a href="?page=lms-suporte-rapido&tab=shortcodes"
                class="nav-tab <?php echo $active_tab == 'shortcodes' ? 'nav-tab-active' : ''; ?>">Shortcodes</a>
            <a href="?page=lms-suporte-rapido&tab=cpts"
                class="nav-tab <?php echo $active_tab == 'cpts' ? 'nav-tab-active' : ''; ?>">Estrutura de Dados (CPTs)</a>
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
        </style>

        <?php if ($active_tab == 'shortcodes'): ?>

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

        <?php else: ?>

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
