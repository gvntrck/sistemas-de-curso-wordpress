<?php
/**
 * Plugin Name: Sistema de Cursos Personalizado
 * Description: Plugin que unifica todos os snippets do sistema de cursos (Cadastro, Certificados, Aulas, Trilhas, Controle de Acesso, etc) em um único local.
 * Version: 1.0.1
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
            .sc-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-left: 4px solid #2271b1;
                box-shadow: 0 1px 1px rgba(0, 0, 0, .04);
                margin-bottom: 20px;
                padding: 15px 20px;
                max-width: 900px;
            }

            .sc-card h3 {
                margin-top: 0;
                color: #1d2327;
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .sc-tag {
                background: #f0f0f1;
                color: #2271b1;
                padding: 2px 8px;
                border-radius: 4px;
                font-family: monospace;
                font-size: 1.1em;
                font-weight: bold;
                border: 1px solid #c3c4c7;
            }

            .sc-desc {
                font-size: 14px;
                line-height: 1.5;
                color: #50575e;
                margin-bottom: 15px;
            }

            .sc-params {
                background: #f6f7f7;
                padding: 10px;
                border-radius: 4px;
                font-size: 13px;
            }

            .sc-params strong {
                display: block;
                margin-bottom: 5px;
            }

            .sc-params code {
                background: #fff;
            }
        </style>

        <h2>Shortcodes Disponíveis</h2>

        <!-- [barra-progresso-geral] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[barra-progresso-geral]</span></h3>
            <p class="sc-desc">Exibe uma barra de progresso visual mostrando a porcentagem geral de conclusão de todos os
                cursos que o aluno tem acesso.</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[barra-progresso-geral]</code>
            </div>
        </div>

        <!-- [cadastro-usuario] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[cadastro-usuario]</span></h3>
            <p class="sc-desc">Renderiza o formulário de cadastro de novos alunos. Inclui abas para cadastro manual e
                importação via CSV.</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[cadastro-usuario]</code>
            </div>
        </div>

        <!-- [certificado] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[certificado]</span></h3>
            <p class="sc-desc">Gerencia a exibição e geração de certificados. Se usado sem parâmetros, lista os certificados
                disponíveis para o aluno. Se usado com parametros ou no contexto de conclusão, exibe o certificado
                específico.</p>
            <div class="sc-params">
                <strong>Parâmetros Opcionais:</strong>
                <ul>
                    <li><code>curso_id</code>: ID do curso para forçar a exibição de um certificado específico (ex:
                        <code>[certificado curso_id="123"]</code>).</li>
                </ul>
            </div>
        </div>

        <!-- [lista-aulas] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[lista-aulas]</span></h3>
            <p class="sc-desc">Exibe o player de vídeo, descrição e lista de aulas lateral (sidebar). É o coração da
                experiência de assistir aulas.</p>
            <div class="sc-params">
                <strong>Parâmetros:</strong>
                <ul>
                    <li><code>curso_id</code>: (Obrigatório se fora do loop) ID do curso.</li>
                    <li><code>aula_id</code>: (Opcional) ID da aula inicial.</li>
                    <li><code>limite</code>: (Opcional) Quantidade máxima de aulas. Padrão 200.</li>
                </ul>
                <strong>Exemplo:</strong> <code>[lista-aulas curso_id="10"]</code>
            </div>
        </div>

        <!-- [meus-cursos] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[meus-cursos]</span></h3>
            <p class="sc-desc">Lista todos os cursos em que o usuário logado está matriculado, mostrando uma barra de
                progresso individual para cada um.</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[meus-cursos]</code>
            </div>
        </div>

        <!-- [minha-conta] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[minha-conta]</span></h3>
            <p class="sc-desc">Exibe um painel para o usuário editar seus dados pessoais (Nome, CPF, Data de Nascimento,
                Endereço, etc).</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[minha-conta]</code>
            </div>
        </div>

        <!-- [redireciona-aula] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[redireciona-aula]</span></h3>
            <p class="sc-desc">Utilitário para usar no modelo <em>Single Aula</em>. Ele redireciona automaticamente o acesso
                direto à aula para a visualização dentro do player do curso.</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[redireciona-aula]</code>
            </div>
        </div>

        <!-- [resultado-busca] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[resultado-busca]</span></h3>
            <p class="sc-desc">Exibe os resultados da pesquisa do site utilizando o design system do projeto (Cards para
                cursos, Lista para outros tipos).</p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[resultado-busca]</code>
            </div>
        </div>

        <!-- [single-trilha] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[single-trilha]</span></h3>
            <p class="sc-desc">Usado na página de uma Trilha. Lista todos os cursos que pertencem a essa trilha visualmente.
            </p>
            <div class="sc-params">
                <strong>Uso:</strong>
                <code>[single-trilha]</code>
            </div>
        </div>

        <!-- [cursos_da_trilha] -->
        <div class="sc-card">
            <h3><span class="sc-tag">[cursos_da_trilha]</span></h3>
            <p class="sc-desc">Similar ao <code>[single-trilha]</code>, mas com mais opções de controle de layout e
                ordenação. Focado em listar cursos associados ao ID da trilha atual.</p>
            <div class="sc-params">
                <strong>Parâmetros:</strong>
                <ul>
                    <li><code>orderby</code>: Campo de ordenação (ex: title). Padrão 'title'.</li>
                    <li><code>order</code>: 'ASC' ou 'DESC'. Padrão 'ASC'.</li>
                    <li><code>limit</code>: Limite de itens. Padrão -1 (todos).</li>
                    <li><code>image_width</code>: Largura da imagem em px. Padrão 220.</li>
                </ul>
                <strong>Exemplo:</strong> <code>[cursos_da_trilha limit="4" order="DESC"]</code>
            </div>
        </div>

    </div>
    <?php
}
