<?php
/**
 * Shortcode: [lista-aulas]
 * Versão: 1.3.2
 *
 * Renderiza uma tela de aulas:
 * - Centro: vídeo + título + descrição da aula selecionada
 * - Direita: lista de aulas com rolagem independente
 *
 * Pré-requisitos:
 * - CPT curso:  curso
 * - CPT aula:   aula
 * - ACF field (aula): embed_do_vimeo (campo de texto) e descricao (WYSIWYG/textarea)
 *
 * Uso:
 * [lista-aulas] (dentro do single do curso)
 * [lista-aulas curso_id="123"]
 *
 * Opcional:
 * - curso_id: ID do curso (post do tipo "curso")
 * - aula_id:  ID de uma aula para abrir por padrão
 * - limite:   limite de aulas listadas (padrão: 200)
 *
 * Observação:
 * - As aulas são relacionadas ao curso via meta "curso" (post object / relationship retornando ID).
 *   Se o seu ACF usa outro nome de campo para a relação, altere RELATION_META_KEY abaixo.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Estilos para mensagens de acesso negado (carregados via wp_head)
 */
add_action('wp_head', 'lista_aulas_estilos_acesso_negado');
function lista_aulas_estilos_acesso_negado() {
    ?>
    <style>
        .lista-aulas__acesso-negado {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,.1);
            color: #fff;
            border-radius: 12px;
            padding: 40px;
            text-align: center;
            max-width: 500px;
            margin: 40px auto;
        }
        .lista-aulas__acesso-negado h3 {
            margin: 0 0 12px;
            font-size: 22px;
            color: #fff;
        }
        .lista-aulas__acesso-negado p {
            margin: 0 0 20px;
            opacity: .8;
            font-size: 15px;
        }
        .lista-aulas__btn-login {
            display: inline-block;
            padding: 12px 24px;
            background: #22c55e;
            color: #fff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background .2s;
        }
        .lista-aulas__btn-login:hover {
            background: #16a34a;
        }
        .lista-aulas__btn-voltar {
            display: inline-block;
            padding: 12px 24px;
            background: #6b7280;
            color: #fff !important;
            border: none;
            border-radius: 8px;
            font-weight: 500;
            transition: background .2s;
            cursor: pointer;
        }
        .lista-aulas__btn-voltar:hover {
            background: #4b5563;
        }
    </style>
    <?php
}

/**
 * Cria a tabela de progresso do aluno no banco de dados
 */
function lista_aulas_criar_tabela_progresso() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'progresso_aluno';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        user_id bigint(20) unsigned NOT NULL,
        aula_id bigint(20) unsigned NOT NULL,
        curso_id bigint(20) unsigned NOT NULL,
        data_conclusao datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY user_aula (user_id, aula_id),
        KEY user_id (user_id),
        KEY aula_id (aula_id),
        KEY curso_id (curso_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'lista_aulas_criar_tabela_progresso');

/**
 * Verifica se uma aula está concluída para um usuário
 */
function lista_aulas_aula_concluida($user_id, $aula_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'progresso_aluno';
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM $table_name WHERE user_id = %d AND aula_id = %d",
        $user_id,
        $aula_id
    ));
    
    return !empty($result);
}

/**
 * Retorna lista de aulas concluídas de um curso para um usuário
 */
function lista_aulas_get_concluidas($user_id, $curso_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'progresso_aluno';
    
    $results = $wpdb->get_col($wpdb->prepare(
        "SELECT aula_id FROM $table_name WHERE user_id = %d AND curso_id = %d",
        $user_id,
        $curso_id
    ));
    
    return array_map('intval', $results);
}

/**
 * AJAX: Marcar/desmarcar aula como concluída
 */
add_action('wp_ajax_lista_aulas_toggle_concluida', 'lista_aulas_ajax_toggle_concluida');
function lista_aulas_ajax_toggle_concluida() {
    $user_id = get_current_user_id();
    
    if ($user_id <= 0) {
        wp_send_json_error(['message' => 'Você precisa estar logado para marcar aulas como concluídas.']);
    }
    
    $aula_id = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;
    $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;
    
    if ($aula_id <= 0) {
        wp_send_json_error(['message' => 'ID da aula inválido.']);
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'progresso_aluno';
    
    $ja_concluida = lista_aulas_aula_concluida($user_id, $aula_id);
    
    if ($ja_concluida) {
        $wpdb->delete(
            $table_name,
            ['user_id' => $user_id, 'aula_id' => $aula_id],
            ['%d', '%d']
        );
        wp_send_json_success(['concluida' => false, 'message' => 'Aula desmarcada.']);
    } else {
        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'aula_id' => $aula_id,
                'curso_id' => $curso_id,
                'data_conclusao' => current_time('mysql')
            ],
            ['%d', '%d', '%d', '%s']
        );
        wp_send_json_success(['concluida' => true, 'message' => 'Aula concluída!']);
    }
}

function lista_aulas_kses_embed($html) {
    $allowed = [
        'iframe' => [
            'src'             => true,
            'width'           => true,
            'height'          => true,
            'frameborder'     => true,
            'allow'           => true,
            'allowfullscreen' => true,
            'webkitallowfullscreen' => true,
            'mozallowfullscreen' => true,
            'title'           => true,
            'class'           => true,
            'id'              => true,
            'style'           => true,
            'loading'         => true,
        ],
        'div'    => [
            'class' => true,
            'style' => true,
        ],
        'script' => [
            'src'   => true,
            'async' => true,
        ],
    ];
    return wp_kses($html, $allowed);
}

add_action('wp_ajax_lista_aulas_get_aula', 'lista_aulas_ajax_get_aula');
add_action('wp_ajax_nopriv_lista_aulas_get_aula', 'lista_aulas_ajax_get_aula');

function lista_aulas_ajax_get_aula() {
    $aulaId = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;

    if ($aulaId <= 0) {
        wp_send_json_error(['message' => 'ID da aula inválido.']);
    }

    $aula = get_post($aulaId);
    if (!$aula || $aula->post_type !== 'aula' || $aula->post_status !== 'publish') {
        wp_send_json_error(['message' => 'Aula não encontrada.']);
    }

    $embed = get_field('embed_do_vimeo', $aulaId);
    if (!$embed) {
        $embed = get_post_meta($aulaId, 'embed_do_vimeo', true);
    }
    $descricao = get_field('descricao', $aulaId);
    if (!$descricao) {
        $descricao = get_post_meta($aulaId, 'descricao', true);
    }

    wp_send_json_success([
        'titulo'    => esc_html(get_the_title($aulaId)),
        'embed'     => $embed ? lista_aulas_kses_embed($embed) : '<div class="lista-aulas__placeholder">Vídeo não disponível.</div>',
        'descricao' => $descricao ? wp_kses_post($descricao) : '',
    ]);
}

add_shortcode('lista-aulas', function ($atts) {
    $atts = shortcode_atts([
        'curso_id' => 0,
        'aula_id'  => 0,
        'limite'   => 200,
    ], $atts, 'lista-aulas');

    $cursoId = (int) $atts['curso_id'];
    $limite  = max(1, (int) $atts['limite']);

    // Permite selecionar aula via URL: ?aula=123
    $aulaFromQuery = isset($_GET['aula']) ? (int) $_GET['aula'] : 0;
    // Permite selecionar curso via URL: ?curso=123
    $cursoFromQuery = isset($_GET['curso']) ? (int) $_GET['curso'] : 0;
    $aulaId = $aulaFromQuery ?: (int) $atts['aula_id'];

    if ($cursoId <= 0 && $cursoFromQuery > 0) {
        $cursoId = $cursoFromQuery;
    }

    // Se curso_id não veio no shortcode, tenta obter do contexto (ex.: dentro do single curso)
    if ($cursoId <= 0) {
        $maybeCurso = get_queried_object_id();
        $post = $maybeCurso ? get_post($maybeCurso) : null;

        if (!$post || $post->post_type !== 'curso') {
            $maybeCurso = get_the_ID();
            $post = $maybeCurso ? get_post($maybeCurso) : null;
        }

        if ($post && $post->post_type === 'curso') {
            $cursoId = (int) $post->ID;
        }
    }

    if ($cursoId <= 0) {
        return '<div class="lista-aulas__erro">Informe o curso_id no shortcode: <code>[lista-aulas curso_id="123"]</code></div>';
    }

    // Verificação de acesso ao curso
    $currentUserId = get_current_user_id();
    $isAdmin = current_user_can('manage_options');
    
    if (!$isAdmin) {
        if ($currentUserId <= 0) {
            return '<div class="lista-aulas__acesso-negado">
                <h3>Acesso restrito</h3>
                <p>Você precisa estar logado para acessar este curso.</p>
                <a href="' . esc_url(wp_login_url(get_permalink())) . '" class="lista-aulas__btn-login">Fazer login</a>
            </div>';
        }
        
        if (function_exists('acesso_cursos_has') && !acesso_cursos_has($currentUserId, $cursoId)) {
            return '<div class="lista-aulas__acesso-negado">
                <h3>Acesso não autorizado</h3>
                <p>Você não possui acesso a este curso.</p>
            </div>';
        }
    }

    // Ajuste aqui caso o campo ACF de relação (aula -> curso) tenha outro nome.
    $RELATION_META_KEY = 'curso';

    $aulasQuery = new WP_Query([
        'post_type'      => 'aula',
        'post_status'    => 'publish',
        'posts_per_page' => $limite,
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
        'meta_query'     => [
            'relation' => 'OR',
            [
                'key'     => $RELATION_META_KEY,
                'value'   => $cursoId,
                'compare' => '=',
            ],
            [
                'key'     => $RELATION_META_KEY,
                'value'   => '"' . $cursoId . '"',
                'compare' => 'LIKE',
            ],
        ],
        'no_found_rows'  => true,
    ]);

    $aulas = $aulasQuery->posts;

    if (empty($aulas)) {
        return '<div class="lista-aulas__acesso-negado">
            <h3>Nenhuma aula encontrada</h3>
            <p>Este curso ainda não possui aulas cadastradas.</p>
            <button onclick="history.back()" class="lista-aulas__btn-voltar">Voltar</button>
        </div>';
    }

    // Valida aula selecionada: precisa pertencer ao conjunto retornado
    $aulaIds = array_map(static fn($p) => (int) $p->ID, $aulas);
    if ($aulaId <= 0 || !in_array($aulaId, $aulaIds, true)) {
        $aulaId = (int) $aulas[0]->ID;
    }

    $aulaAtual = get_post($aulaId);
    if (!$aulaAtual || $aulaAtual->post_type !== 'aula') {
        $aulaAtual = $aulas[0];
        $aulaId = (int) $aulaAtual->ID;
    }

    // ACF fields (fallback para meta comum)
    $embed = get_field('embed_do_vimeo', $aulaId);
    if (!$embed) {
        $embed = get_post_meta($aulaId, 'embed_do_vimeo', true);
    }
    $descricao = get_field('descricao', $aulaId);
    if (!$descricao) {
        $descricao = get_post_meta($aulaId, 'descricao', true);
    }

    // Segurança/saída
    $titulo = esc_html(get_the_title($aulaId));
    $descricaoHtml = $descricao ? wp_kses_post($descricao) : '';
    $embedHtml = $embed ? lista_aulas_kses_embed($embed) : '<div class="lista-aulas__placeholder">Vídeo não disponível.</div>';

    // URL base preservando querystring do contexto, mas controlando a aula via ?aula=
    $baseUrl = get_permalink($cursoId);
    if (!$baseUrl) {
        $baseUrl = get_permalink();
    }
    $baseUrl = $baseUrl ?: home_url('/');

    $uid = 'lista-aulas-' . wp_generate_uuid4();

    // Verifica usuário logado e aulas concluídas
    $userId = get_current_user_id();
    $isLoggedIn = $userId > 0;
    $aulasConcluidas = $isLoggedIn ? lista_aulas_get_concluidas($userId, $cursoId) : [];
    $aulaAtualConcluida = in_array($aulaId, $aulasConcluidas, true);

    ob_start();
    ?>
    <div id="<?php echo esc_attr($uid); ?>" class="lista-aulas">
        <div class="lista-aulas__main">
            <div class="lista-aulas__video">
                <?php echo $embedHtml; ?>
            </div>

            <div class="lista-aulas__header">
                <h2 class="lista-aulas__titulo"><?php echo $titulo; ?></h2>
                <?php if ($isLoggedIn) : ?>
                    <button 
                        type="button" 
                        class="lista-aulas__btn-concluir <?php echo $aulaAtualConcluida ? 'is-concluida' : ''; ?>"
                        data-aula-id="<?php echo $aulaId; ?>"
                        data-curso-id="<?php echo $cursoId; ?>"
                    >
                        <svg class="lista-aulas__btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                        <span class="lista-aulas__btn-texto"><?php echo $aulaAtualConcluida ? 'Concluído' : 'Marcar como concluído'; ?></span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (!empty($descricaoHtml)) : ?>
                <div class="lista-aulas__descricao">
                    <?php echo $descricaoHtml; ?>
                </div>
            <?php endif; ?>
        </div>

        <aside class="lista-aulas__sidebar" aria-label="Lista de aulas">
            <div class="lista-aulas__sidebar-header">
                <div class="lista-aulas__curso-label">Aulas do curso</div>
                <div class="lista-aulas__curso-titulo"><?php echo esc_html(get_the_title($cursoId)); ?></div>
            </div>

            <nav class="lista-aulas__lista" role="list">
                <?php foreach ($aulas as $index => $aula) :
                    $id = (int) $aula->ID;
                    $isActive = ($id === $aulaId);
                    $url = add_query_arg(['aula' => $id], $baseUrl);
                    ?>
                    <a
                        role="listitem"
                        class="lista-aulas__item <?php echo $isActive ? 'is-active' : ''; ?>"
                        href="<?php echo esc_url($url); ?>"
                        data-aula-id="<?php echo $id; ?>"
                    >
                        <span class="lista-aulas__item-index <?php echo in_array($id, $aulasConcluidas, true) ? 'is-concluida' : ''; ?>">
                            <?php if (in_array($id, $aulasConcluidas, true)) : ?>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <?php else : ?>
                                <?php echo (int) ($index + 1); ?>
                            <?php endif; ?>
                        </span>
                        <span class="lista-aulas__item-title"><?php echo esc_html(get_the_title($id)); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
    </div>

    <style>
        /* Escopo por ID para não vazar estilo */
        #<?php echo esc_attr($uid); ?>.lista-aulas {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 16px;
            width: 100%;
            min-height: 70vh;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__main {
            min-width: 0;
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,.1);
            color: #fff;
            border-radius: 12px;
            padding: 16px;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__video {
            width: 100%;
            border-radius: 12px;
            overflow: hidden;
            background: #000;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__video iframe,
        #<?php echo esc_attr($uid); ?> .lista-aulas__video video,
        #<?php echo esc_attr($uid); ?> .lista-aulas__video embed,
        #<?php echo esc_attr($uid); ?> .lista-aulas__video object {
            width: 100%;
            aspect-ratio: 16 / 9;
            display: block;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__placeholder {
            color: #fff;
            padding: 24px;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            margin: 16px 0 8px;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__titulo {
            margin: 0;
            font-size: 20px;
            line-height: 1.25;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__descricao {
            margin-top: 8px;
            font-size: 15px;
            line-height: 1.6;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__sidebar {
            background: #0a0a0a;
            border: 1px solid rgba(255,255,255,.1);
            color: #fff;
            border-radius: 12px;
            overflow: hidden;
            display: grid;
            grid-template-rows: auto 1fr;
            min-height: 70vh;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__sidebar-header {
            padding: 14px 14px 10px;
            border-bottom: 1px solid rgba(255,255,255,.1);
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__curso-label {
            font-size: 12px;
            letter-spacing: .02em;
            opacity: .7;
            margin-bottom: 4px;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__curso-titulo {
            font-size: 15px;
            font-weight: 600;
            line-height: 1.2;
        }

        /* Rolagem independente */
        #<?php echo esc_attr($uid); ?> .lista-aulas__lista {
            overflow: auto;
            padding: 8px;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item {
            display: grid;
            grid-template-columns: 32px 1fr;
            gap: 10px;
            align-items: center;
            text-decoration: none;
            padding: 10px;
            border-radius: 10px;
            color: inherit;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item:hover {
            background: rgba(255,255,255,.08);
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item.is-active {
            background: rgba(255,255,255,.12);
            outline: 2px solid rgba(255,255,255,.2);
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item-index {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            display: inline-grid;
            place-items: center;
            font-size: 12px;
            background: rgba(255,255,255,.1);
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item-title {
            font-size: 14px;
            line-height: 1.25;
        }

        /* Botão de marcar como concluído */
        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            padding: 10px 18px;
            border: 2px solid rgba(255,255,255,.2);
            border-radius: 8px;
            background: transparent;
            color: #fff;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all .2s ease;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir:hover {
            border-color: rgba(255,255,255,.4);
            background: rgba(255,255,255,.05);
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir.is-concluida {
            background: #22c55e;
            border-color: #22c55e;
            color: #fff;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir.is-concluida:hover {
            background: #16a34a;
            border-color: #16a34a;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-icon {
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir:disabled {
            opacity: .6;
            cursor: not-allowed;
        }

        /* Indicador de aula concluída na lista */
        #<?php echo esc_attr($uid); ?> .lista-aulas__item-index.is-concluida {
            background: #22c55e;
            color: #fff;
        }

        #<?php echo esc_attr($uid); ?> .lista-aulas__item-index.is-concluida svg {
            width: 16px;
            height: 16px;
        }

        /* Responsivo */
        @media (max-width: 980px) {
            #<?php echo esc_attr($uid); ?>.lista-aulas {
                grid-template-columns: 1fr;
            }

            #<?php echo esc_attr($uid); ?> .lista-aulas__header {
                flex-direction: column;
                align-items: flex-start;
            }

            #<?php echo esc_attr($uid); ?> .lista-aulas__btn-concluir {
                align-self: flex-end;
            }

            #<?php echo esc_attr($uid); ?> .lista-aulas__sidebar {
                min-height: 40vh;
            }
        }
    </style>

    <script>
    (function() {
        var container = document.getElementById('<?php echo esc_js($uid); ?>');
        if (!container) return;

        var items = container.querySelectorAll('.lista-aulas__item[data-aula-id]');
        var videoContainer = container.querySelector('.lista-aulas__video');
        var tituloEl = container.querySelector('.lista-aulas__titulo');
        var descricaoEl = container.querySelector('.lista-aulas__descricao');
        var mainEl = container.querySelector('.lista-aulas__main');
        var btnConcluir = container.querySelector('.lista-aulas__btn-concluir');
        var ajaxUrl = '<?php echo esc_js(admin_url('admin-ajax.php')); ?>';

        // Mapa de aulas concluídas (carregado do servidor)
        var aulasConcluidas = <?php echo json_encode(array_values($aulasConcluidas)); ?>;

        // Função para verificar se aula está concluída
        function isAulaConcluida(aulaId) {
            return aulasConcluidas.indexOf(parseInt(aulaId)) !== -1;
        }

        // Função para atualizar visual do botão
        function atualizarBotaoConcluir(aulaId) {
            if (!btnConcluir) return;
            btnConcluir.setAttribute('data-aula-id', aulaId);
            var concluida = isAulaConcluida(aulaId);
            if (concluida) {
                btnConcluir.classList.add('is-concluida');
                btnConcluir.querySelector('.lista-aulas__btn-texto').textContent = 'Concluído';
            } else {
                btnConcluir.classList.remove('is-concluida');
                btnConcluir.querySelector('.lista-aulas__btn-texto').textContent = 'Marcar como concluído';
            }
        }

        // Função para atualizar visual do item na lista
        function atualizarItemLista(aulaId, concluida, index) {
            var item = container.querySelector('.lista-aulas__item[data-aula-id="' + aulaId + '"]');
            if (!item) return;
            var indexEl = item.querySelector('.lista-aulas__item-index');
            if (!indexEl) return;
            
            if (concluida) {
                indexEl.classList.add('is-concluida');
                indexEl.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
            } else {
                indexEl.classList.remove('is-concluida');
                // Recupera índice original
                var allItems = container.querySelectorAll('.lista-aulas__item[data-aula-id]');
                var idx = Array.prototype.indexOf.call(allItems, item) + 1;
                indexEl.textContent = idx;
            }
        }

        // Handler do botão de concluir
        if (btnConcluir) {
            btnConcluir.addEventListener('click', function() {
                var aulaId = this.getAttribute('data-aula-id');
                var cursoId = this.getAttribute('data-curso-id');
                if (!aulaId) return;

                this.disabled = true;

                var formData = new FormData();
                formData.append('action', 'lista_aulas_toggle_concluida');
                formData.append('aula_id', aulaId);
                formData.append('curso_id', cursoId);

                var btn = this;

                fetch(ajaxUrl, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    btn.disabled = false;
                    if (data.success) {
                        var concluida = data.data.concluida;
                        if (concluida) {
                            aulasConcluidas.push(parseInt(aulaId));
                            btn.classList.add('is-concluida');
                            btn.querySelector('.lista-aulas__btn-texto').textContent = 'Concluído';
                        } else {
                            var idx = aulasConcluidas.indexOf(parseInt(aulaId));
                            if (idx > -1) aulasConcluidas.splice(idx, 1);
                            btn.classList.remove('is-concluida');
                            btn.querySelector('.lista-aulas__btn-texto').textContent = 'Marcar como concluído';
                        }
                        atualizarItemLista(aulaId, concluida);
                    } else {
                        alert(data.data.message || 'Erro ao atualizar.');
                    }
                })
                .catch(function(err) {
                    btn.disabled = false;
                    console.error('Erro:', err);
                });
            });
        }

        items.forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();

                var aulaId = this.getAttribute('data-aula-id');
                if (!aulaId) return;

                // Atualiza estado ativo
                items.forEach(function(el) { el.classList.remove('is-active'); });
                this.classList.add('is-active');

                // Atualiza botão de concluir para nova aula
                atualizarBotaoConcluir(aulaId);

                // Atualiza URL sem reload
                var newUrl = this.getAttribute('href');
                if (window.history && window.history.pushState) {
                    window.history.pushState({ aula: aulaId }, '', newUrl);
                }

                // Fetch via AJAX
                var formData = new FormData();
                formData.append('action', 'lista_aulas_get_aula');
                formData.append('aula_id', aulaId);

                fetch('<?php echo esc_js(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) { return response.json(); })
                .then(function(data) {
                    if (data.success && data.data) {
                        // Atualiza vídeo
                        if (videoContainer) {
                            videoContainer.innerHTML = data.data.embed;
                        }
                        // Atualiza título
                        if (tituloEl) {
                            tituloEl.textContent = data.data.titulo;
                        }
                        // Atualiza descrição
                        if (data.data.descricao) {
                            if (descricaoEl) {
                                descricaoEl.innerHTML = data.data.descricao;
                                descricaoEl.style.display = '';
                            } else {
                                var newDesc = document.createElement('div');
                                newDesc.className = 'lista-aulas__descricao';
                                newDesc.innerHTML = data.data.descricao;
                                tituloEl.insertAdjacentElement('afterend', newDesc);
                            }
                        } else {
                            if (descricaoEl) {
                                descricaoEl.style.display = 'none';
                            }
                        }
                        // Scroll para o topo do vídeo
                        mainEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                })
                .catch(function(err) {
                    console.error('Erro ao carregar aula:', err);
                });
            });
        });

        // Suporte ao botão voltar do navegador
        window.addEventListener('popstate', function(e) {
            if (e.state && e.state.aula) {
                var targetItem = container.querySelector('.lista-aulas__item[data-aula-id="' + e.state.aula + '"]');
                if (targetItem) {
                    targetItem.click();
                }
            }
        });
    })();
    </script>
    <?php

    return ob_get_clean();
});
