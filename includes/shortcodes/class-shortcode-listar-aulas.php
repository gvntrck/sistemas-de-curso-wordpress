<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Listar_Aulas
{
    /**
     * class-shortcode-listar-aulas.php
     *
     * Shortcode [lista-aulas]
     * Renderiza o player de vídeo principal, a lista de navegação entre aulas e materiais de apoio.
     * Gerencia o controle de acesso por curso, marcação de aula concluída e requisições AJAX para troca de conteúdo sem reaload.
     *
     * @package SistemaCursos
     * @version 1.1.2
     */
    public function __construct()
    {
        add_shortcode('lista-aulas', [$this, 'render_shortcode']);
        add_action('wp_ajax_lista_aulas_get_aula', [$this, 'ajax_get_aula']);
        add_action('wp_ajax_nopriv_lista_aulas_get_aula', [$this, 'ajax_get_aula']);
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts([
            'curso_id' => 0,
            'aula_id' => 0,
            'limite' => 200,
        ], $atts, 'lista-aulas');

        $cursoId = (int) $atts['curso_id'];
        $limite = max(1, (int) $atts['limite']);

        // URL Query Params Override
        $aulaFromQuery = isset($_GET['aula']) ? (int) $_GET['aula'] : 0;
        $cursoFromQuery = isset($_GET['curso']) ? (int) $_GET['curso'] : 0;
        $aulaId = $aulaFromQuery ?: (int) $atts['aula_id'];

        if ($cursoId <= 0 && $cursoFromQuery > 0) {
            $cursoId = $cursoFromQuery;
        }

        // Auto-detect course ID from context
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
            return '<div class="mc-alert mc-error">Informe o curso_id no shortcode: <code>[lista-aulas curso_id="123"]</code></div>';
        }

        // Access Check
        $currentUserId = get_current_user_id();
        $isAdmin = current_user_can('manage_options');

        if (!$isAdmin) {
            if ($currentUserId <= 0) {
                return System_Cursos_Config::get_message('access_denied');
            }

            if (class_exists('System_Cursos_Access_Control') && !System_Cursos_Access_Control::has_access($currentUserId, $cursoId)) {
                return System_Cursos_Config::get_message('not_enrolled');
            }
        }

        // Query Lessons
        $RELATION_META_KEY = 'curso';
        $aulasQuery = new WP_Query([
            'post_type' => 'aula',
            'post_status' => 'publish',
            'posts_per_page' => $limite,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => $RELATION_META_KEY,
                    'value' => $cursoId,
                    'compare' => '=',
                ],
                [
                    'key' => $RELATION_META_KEY,
                    'value' => '"' . $cursoId . '"',
                    'compare' => 'LIKE',
                ],
            ],
            'no_found_rows' => true,
        ]);

        $aulas = $aulasQuery->posts;

        if (empty($aulas)) {
            return '<div class="mc-alert mc-info">Este curso ainda não possui aulas cadastradas. <button onclick="history.back()" class="mc-btn mc-btn-secondary" style="margin-left: 10px;">Voltar</button></div>';
        }

        // Validate selected lesson
        $aulaIds = array_map(static fn($p) => (int) $p->ID, $aulas);
        if ($aulaId <= 0 || !in_array($aulaId, $aulaIds, true)) {
            $aulaId = (int) $aulas[0]->ID;
        }

        $aulaAtual = get_post($aulaId);
        if (!$aulaAtual || $aulaAtual->post_type !== 'aula') {
            $aulaAtual = $aulas[0];
            $aulaId = (int) $aulaAtual->ID;
        }

        // Prepare Data
        $embed = get_field('embed_do_vimeo', $aulaId) ?: get_post_meta($aulaId, 'embed_do_vimeo', true);
        $descricao = get_field('descricao', $aulaId) ?: get_post_meta($aulaId, 'descricao', true);

        $titulo = esc_html(get_the_title($aulaId));
        $descricaoHtml = $descricao ? wp_kses_post($descricao) : '';
        $embedHtml = $embed ? $this->kses_embed($embed) : '<div class="lista-aulas__placeholder">Vídeo não disponível.</div>';
        $anexosHtml = $this->get_anexos_html($aulaId);

        // URLs
        $baseUrl = get_permalink($cursoId) ?: (get_permalink() ?: home_url('/'));
        $uid = 'lista-aulas-' . wp_generate_uuid4();

        // Progress Data
        $isLoggedIn = $currentUserId > 0;
        $aulasConcluidas = ($isLoggedIn && class_exists('System_Cursos_Progress'))
            ? System_Cursos_Progress::get_completed_lessons($currentUserId, $cursoId)
            : [];
        $aulaAtualConcluida = in_array($aulaId, $aulasConcluidas, true);

        // Progress Calc
        $totalAulas = count($aulas);
        $qtdConcluidas = 0;
        foreach ($aulas as $a) {
            if (in_array((int) $a->ID, $aulasConcluidas, true)) {
                $qtdConcluidas++;
            }
        }
        $progressoPercent = $totalAulas > 0 ? min(100, round(($qtdConcluidas / $totalAulas) * 100)) : 0;

        ob_start();
        ?>
        <div id="<?php echo esc_attr($uid); ?>" class="lista-aulas"
            data-ajax-url="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
            <div class="lista-aulas__main">
                <div class="lista-aulas__video">
                    <?php echo $embedHtml; ?>
                </div>

                <div class="lista-aulas__header">
                    <h2 class="lista-aulas__titulo">
                        <?php echo $titulo; ?>
                    </h2>
                    <?php if ($isLoggedIn): ?>
                        <button type="button"
                            class="lista-aulas__btn-concluir <?php echo $aulaAtualConcluida ? 'is-concluida' : ''; ?>"
                            data-aula-id="<?php echo $aulaId; ?>" data-curso-id="<?php echo $cursoId; ?>">
                            <svg class="lista-aulas__btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span class="lista-aulas__btn-texto">
                                <?php echo $aulaAtualConcluida ? 'Concluído' : 'Marcar como concluído'; ?>
                            </span>
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($descricaoHtml)): ?>
                    <div class="lista-aulas__descricao">
                        <?php echo $descricaoHtml; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($anexosHtml)): ?>
                    <div class="lista-aulas__anexos-wrapper">
                        <?php echo $anexosHtml; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="lista-aulas__sidebar" aria-label="Lista de aulas">
                <div class="lista-aulas__sidebar-header">
                    <div class="lista-aulas__curso-label">Aulas do curso</div>
                    <div class="lista-aulas__curso-titulo">
                        <?php echo esc_html(get_the_title($cursoId)); ?>
                    </div>

                    <div class="lista-aulas__progresso-wrapper" data-total-aulas="<?php echo $totalAulas; ?>"
                        data-concluidas="<?php echo esc_attr(json_encode(array_values($aulasConcluidas))); ?>">
                        <div class="lista-aulas__progresso-bar">
                            <div class="lista-aulas__progresso-fill" style="width: <?php echo $progressoPercent; ?>%"></div>
                        </div>
                        <div class="lista-aulas__progresso-texto">
                            <?php echo $progressoPercent; ?>%
                        </div>
                    </div>
                </div>

                <nav class="lista-aulas__lista" role="list">
                    <?php foreach ($aulas as $index => $aula):
                        $id = (int) $aula->ID;
                        $isActive = ($id === $aulaId);
                        $url = add_query_arg(['aula' => $id], $baseUrl);
                        ?>
                        <a role="listitem" class="lista-aulas__item <?php echo $isActive ? 'is-active' : ''; ?>"
                            href="<?php echo esc_url($url); ?>" data-aula-id="<?php echo $id; ?>">
                            <span
                                class="lista-aulas__item-index <?php echo in_array($id, $aulasConcluidas, true) ? 'is-concluida' : ''; ?>">
                                <?php if (in_array($id, $aulasConcluidas, true)): ?>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"
                                        stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"></polyline>
                                    </svg>
                                <?php else: ?>
                                    <?php echo (int) ($index + 1); ?>
                                <?php endif; ?>
                            </span>
                            <span class="lista-aulas__item-title">
                                <?php echo esc_html(get_the_title($id)); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
        </div>

        <?php
        // Inline script initialized here, but using localized/data attributes would be cleaner if moved to external file.
        // For now, mirroring legacy inline script logic but adapted to use cleaner selectors.
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.SystemCursos && window.SystemCursos.initListaAulas) {
                    window.SystemCursos.initListaAulas('<?php echo esc_js($uid); ?>');
                }
            });
        </script>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_aula()
    {
        $aulaId = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;

        if ($aulaId <= 0) {
            wp_send_json_error(['message' => 'ID da aula inválido.']);
        }

        $aula = get_post($aulaId);
        if (!$aula || $aula->post_type !== 'aula' || $aula->post_status !== 'publish') {
            wp_send_json_error(['message' => 'Aula não encontrada.']);
        }

        $embed = get_field('embed_do_vimeo', $aulaId) ?: get_post_meta($aulaId, 'embed_do_vimeo', true);
        $descricao = get_field('descricao', $aulaId) ?: get_post_meta($aulaId, 'descricao', true);

        wp_send_json_success([
            'titulo' => esc_html(get_the_title($aulaId)),
            'embed' => $embed ? $this->kses_embed($embed) : '<div class="lista-aulas__placeholder">Vídeo não disponível.</div>',
            'descricao' => $descricao ? wp_kses_post($descricao) : '',
            'anexos' => $this->get_anexos_html($aulaId),
        ]);
    }

    private function get_anexos_html($aulaId)
    {
        $arquivos = get_field('arquivos', $aulaId);

        if (empty($arquivos) || !is_array($arquivos)) {
            return '';
        }

        ob_start();
        ?>
        <div class="lista-aulas__anexos">
            <h3>Materiais de Apoio</h3>
            <ul class="lista-aulas__anexos-lista">
                <?php foreach ($arquivos as $item):
                    $url = $item['anexos'] ?? '';
                    if (!$url)
                        continue;
                    $nome = basename($url);
                    ?>
                    <li>
                        <a href="<?php echo esc_url($url); ?>" target="_blank" download class="lista-aulas__btn-anexo">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            <?php echo esc_html($nome); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }

    private function kses_embed($html)
    {
        $allowed = [
            'iframe' => [
                'src' => true,
                'width' => true,
                'height' => true,
                'frameborder' => true,
                'allow' => true,
                'allowfullscreen' => true,
                'webkitallowfullscreen' => true,
                'mozallowfullscreen' => true,
                'title' => true,
                'class' => true,
                'id' => true,
                'style' => true,
                'loading' => true,
            ],
            'div' => ['class' => true, 'style' => true],
            'script' => ['src' => true, 'async' => true],
        ];
        return wp_kses($html, $allowed);
    }
}
