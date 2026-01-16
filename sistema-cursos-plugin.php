<?php
/**
 * Plugin Name: Sistema de Cursos Personalizado
 * Description: Plugin que unifica todos os snippets do sistema de cursos (Cadastro, Certificados, Aulas, Trilhas, Controle de Acesso, etc) em um único local.
 * Version: 1.0.3
 * Author: Equipe de Desenvolvimento
 * Text Domain: sistema-cursos
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Array com os nomes dos arquivos (snippets) que compõem o sistema.
// A ordem pode importar se houver dependências entre eles, mas snippets costumam ser independentes ou hook-based.
$arquivos_snippets = [
    'barra-progresso-geral.php',
    'cadastro-usuario.php',
    'campos-usuario.php',
    'certificado.php',
    'controle-acesso.php',
    'cursos-trilha.php',
    'filtro-listagem-aulas.php',
    'listar-aulas.php',
    'meus-cursos.php',
    'minha-conta.php',
    'resultado-busca.php',
    'single-aula.php',
    'single-trilha.php',
];

foreach ($arquivos_snippets as $arquivo) {
    $caminho_arquivo = plugin_dir_path(__FILE__) . $arquivo;

    if (file_exists($caminho_arquivo)) {
        require_once $caminho_arquivo;
    } else {
        // Opcional: Logar erro se arquivo faltar
        error_log("Sistema de Cursos: Arquivo não encontrado - " . $arquivo);
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
        99                        // Position
    );
}

function sistema_cursos_render_admin_page()
{
    ?>
    <div class="wrap">
        <h1>LMS SuporteRapido - Documentação do Sistema</h1>
        <p>Bem-vindo à documentação rápida dos shortcodes e funcionalidades do <strong>Sistema de Cursos
                Personalizado</strong>.</p>

        <hr>

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

            /* Coluna do Shortcode */
            .sc-col-code {
                width: 250px;
                white-space: nowrap;
            }

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

            /* Coluna da Descrição */
            .sc-col-desc {}

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
        </style>

        <h2>Shortcodes Disponíveis</h2>

        <table class="sc-table">
            <thead>
                <tr>
                    <th class="sc-col-code">Shortcode</th>
                    <th class="sc-col-desc">Descrição e Uso</th>
                </tr>
            </thead>
            <tbody>

                <!-- [barra-progresso-geral] -->
                <tr>
                    <td><span class="sc-tag">[barra-progresso-geral]</span></td>
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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
                    <td class="sc-col-desc">
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

    </div>
    <?php
}
