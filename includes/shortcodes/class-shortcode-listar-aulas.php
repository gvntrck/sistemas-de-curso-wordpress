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
     * Renderiza o player principal e a lista lateral de aulas.
     *
     * @package SistemaCursos
     * @version 1.2.0
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

        // URL query params override.
        $aulaFromQuery = isset($_GET['target_aula']) ? (int) $_GET['target_aula'] : 0;
        $cursoFromQuery = isset($_GET['curso']) ? (int) $_GET['curso'] : 0;
        $aulaId = $aulaFromQuery ?: (int) $atts['aula_id'];

        if ($cursoId <= 0 && $cursoFromQuery > 0) {
            $cursoId = $cursoFromQuery;
        }

        // Auto-detect course ID from context.
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

        // Access check.
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

        // Query lessons.
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
            return '<div class="mc-alert mc-info">Este curso ainda nao possui aulas cadastradas. <button onclick="history.back()" class="mc-btn mc-btn-secondary" style="margin-left: 10px;">Voltar</button></div>';
        }

        // Validate selected lesson.
        $aulaIds = array_map(static fn($p) => (int) $p->ID, $aulas);
        if ($aulaId <= 0 || !in_array($aulaId, $aulaIds, true)) {
            $aulaId = (int) $aulas[0]->ID;
        }

        $aulaAtual = get_post($aulaId);
        if (!$aulaAtual || $aulaAtual->post_type !== 'aula') {
            $aulaAtual = $aulas[0];
            $aulaId = (int) $aulaAtual->ID;
        }

        $aulasLockState = [];
        foreach ($aulas as $aula) {
            $lessonId = (int) $aula->ID;
            $aulasLockState[$lessonId] = $this->get_lesson_lock_state($lessonId, $currentUserId);
        }

        $aulaAtualLockState = $aulasLockState[$aulaId] ?? $this->get_lesson_lock_state($aulaId, $currentUserId);
        $isAulaAtualLocked = !empty($aulaAtualLockState['is_locked']);

        // Prepare data.
        $embed = get_post_meta($aulaId, 'embed_do_vimeo', true);
        $descricao = get_post_meta($aulaId, 'descricao', true);

        $titulo = esc_html($this->get_lesson_display_title($aulaId));
        $descricaoHtml = $descricao ? do_shortcode(wpautop(wp_kses_post($descricao))) : '';
        $embedHtml = $embed ? $this->kses_embed($embed) : '<div class="lista-aulas__placeholder">Video nao disponivel.</div>';
        $anexosHtml = $this->get_anexos_html($aulaId);

        if ($isAulaAtualLocked) {
            $embedHtml = $this->get_locked_embed_html($aulaAtualLockState['message']);
            $descricaoHtml = '';
            $anexosHtml = '';
        }

        // URLs.
        $baseUrl = get_permalink($cursoId) ?: (get_permalink() ?: home_url('/'));
        $uid = 'lista-aulas-' . wp_generate_uuid4();

        // Progress data.
        $isLoggedIn = $currentUserId > 0;
        $aulasConcluidas = ($isLoggedIn && class_exists('System_Cursos_Progress'))
            ? System_Cursos_Progress::get_completed_lessons($currentUserId, $cursoId)
            : [];
        $aulaAtualConcluida = in_array($aulaId, $aulasConcluidas, true);

        // Quiz and comments.
        $quizHtml = '';
        $commentsHtml = '';
        if (!$isAulaAtualLocked) {
            if (class_exists('System_Cursos_Quiz_Process')) {
                $quizHtml = System_Cursos_Quiz_Process::render_quiz($aulaId);
            }

            if (class_exists('System_Cursos_Aula_Comments')) {
                $commentsHtml = System_Cursos_Aula_Comments::render_comments_section($aulaId);
            }
        }

        $esconderBotaoManual = $isAulaAtualLocked || (!empty($quizHtml) && !$aulaAtualConcluida);

        // Progress calc.
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
                <?php if (!empty($GLOBALS['lms_painel_mode'])): ?>
                    <a href="#" class="lms-voltar-cursos lista-aulas__voltar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                        Voltar para Meus Cursos
                    </a>
                <?php endif; ?>

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
                            data-aula-id="<?php echo $aulaId; ?>" data-curso-id="<?php echo $cursoId; ?>"
                            <?php if ($esconderBotaoManual): ?>style="display:none;"<?php endif; ?>>
                            <svg class="lista-aulas__btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                            <span class="lista-aulas__btn-texto">
                                <?php echo $aulaAtualConcluida ? 'Concluido' : 'Marcar como concluido'; ?>
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

                <?php if (!empty($quizHtml)): ?>
                    <div class="lista-aulas__quiz-wrapper">
                        <?php echo $quizHtml; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($commentsHtml)): ?>
                    <div class="lista-aulas__comentarios-wrapper">
                        <?php echo $commentsHtml; ?>
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
                        $url = add_query_arg(['target_aula' => $id], $baseUrl);
                        $lockState = $aulasLockState[$id] ?? $this->get_lesson_lock_state($id, $currentUserId);
                        $isLocked = !empty($lockState['is_locked']);
                        $itemClasses = 'lista-aulas__item';
                        if ($isActive) {
                            $itemClasses .= ' is-active';
                        }
                        if ($isLocked) {
                            $itemClasses .= ' is-locked';
                        }
                        ?>
                        <a role="listitem" class="<?php echo esc_attr($itemClasses); ?>"
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
                            <span class="lista-aulas__item-main">
                                <span class="lista-aulas__item-title">
                                    <?php echo esc_html($this->get_lesson_display_title($id)); ?>
                                </span>
                                <?php if ($isLocked): ?>
                                    <span class="lista-aulas__item-meta">
                                        <?php
                                        $releaseLabel = trim((string) ($lockState['release_label'] ?? ''));
                                        echo esc_html($releaseLabel !== '' ? 'Libera em ' . $releaseLabel : 'Aguardando liberacao');
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </aside>
        </div>

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
            wp_send_json_error(['message' => 'ID da aula invalido.']);
        }

        $aula = get_post($aulaId);
        if (!$aula || $aula->post_type !== 'aula' || $aula->post_status !== 'publish') {
            wp_send_json_error(['message' => 'Aula nao encontrada.']);
        }

        $userId = get_current_user_id();
        $isAdmin = current_user_can('manage_options');
        $cursoId = $this->get_lesson_course_id($aulaId);

        if (!$isAdmin) {
            if ($userId <= 0) {
                wp_send_json_error(['message' => 'Voce precisa estar logado para acessar esta aula.']);
            }

            if ($cursoId > 0 && class_exists('System_Cursos_Access_Control') && !System_Cursos_Access_Control::has_access($userId, $cursoId)) {
                wp_send_json_error(['message' => 'Voce nao tem acesso a esta aula.']);
            }
        }

        $lockState = $this->get_lesson_lock_state($aulaId, $userId);
        if (!empty($lockState['is_locked'])) {
            wp_send_json_success([
                'titulo' => $this->get_lesson_display_title($aulaId),
                'embed' => $this->get_locked_embed_html($lockState['message']),
                'descricao' => '',
                'anexos' => '',
                'quiz' => '',
                'comentarios' => '',
                'esconder_botao_manual' => true,
                'bloqueada' => true,
                'mensagem_bloqueio' => $lockState['message'],
            ]);
        }

        $embed = get_post_meta($aulaId, 'embed_do_vimeo', true);
        $descricao = get_post_meta($aulaId, 'descricao', true);

        $quizHtml = '';
        if (class_exists('System_Cursos_Quiz_Process')) {
            $quizHtml = System_Cursos_Quiz_Process::render_quiz($aulaId);
        }

        $commentsHtml = '';
        if (class_exists('System_Cursos_Aula_Comments')) {
            $commentsHtml = System_Cursos_Aula_Comments::render_comments_section($aulaId);
        }

        $isCompleted = false;
        if ($userId > 0 && class_exists('System_Cursos_Progress')) {
            $isCompleted = System_Cursos_Progress::is_lesson_completed($userId, $aulaId);
        }

        $esconderBotaoManual = !empty($quizHtml) && !$isCompleted;

        wp_send_json_success([
            'titulo' => $this->get_lesson_display_title($aulaId),
            'embed' => $embed ? $this->kses_embed($embed) : '<div class="lista-aulas__placeholder">Video nao disponivel.</div>',
            'descricao' => $descricao ? do_shortcode(wpautop(wp_kses_post($descricao))) : '',
            'anexos' => $this->get_anexos_html($aulaId),
            'quiz' => $quizHtml,
            'comentarios' => $commentsHtml,
            'esconder_botao_manual' => $esconderBotaoManual,
            'bloqueada' => false,
            'mensagem_bloqueio' => '',
        ]);
    }

    private function get_anexos_html($aulaId)
    {
        $arquivos = get_post_meta($aulaId, 'arquivos', true);

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
                    if (!$url) {
                        continue;
                    }
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

    private function get_lesson_display_title($aulaId)
    {
        $post = get_post((int) $aulaId);
        $title = ($post && isset($post->post_title)) ? (string) $post->post_title : '';

        if ($title === '') {
            $title = (string) get_the_title($aulaId);
        }

        $title = preg_replace('/&(ndash|mdash|minus|#8210|#8211|#8212|#8213|#8722|#x2010|#x2011|#x2012|#x2013|#x2014|#x2015|#x2212);?/i', '-', $title);
        $title = $this->decode_html_entities_recursively($title);
        $title = wp_check_invalid_utf8($title, true);

        if ($title === '') {
            return '';
        }

        $title = preg_replace('/[\x{00AD}\x{058A}\x{05BE}\x{1400}\x{1806}\x{2010}-\x{2015}\x{2043}\x{2212}\x{2E17}\x{2E1A}\x{2E3A}\x{2E3B}\x{2E40}\x{301C}\x{3030}\x{30A0}\x{FE31}\x{FE32}\x{FE58}\x{FE63}\x{FF0D}\x{FFFD}\x{25A1}\x{25AB}\x{25AD}\x{25FB}-\x{25FE}]+/u', '-', $title);
        $title = preg_replace('/([\p{L}\p{N}])\s*[^\p{L}\p{N}\s\(\)\[\]\{\}\.,:;!\?@#%&\*\+\/\\\\\'"_-]+\s*(?=[\p{L}\p{N}])/u', '$1 - ', $title);
        $title = preg_replace('/\s+/', ' ', $title);

        return trim((string) $title);
    }

    private function decode_html_entities_recursively($value)
    {
        $decoded = (string) $value;

        for ($i = 0; $i < 3; $i++) {
            $next = html_entity_decode($decoded, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($next === $decoded) {
                break;
            }

            $decoded = $next;
        }

        return $decoded;
    }

    private function get_lesson_course_id($aulaId)
    {
        $cursoMeta = get_post_meta((int) $aulaId, 'curso', true);

        if (is_array($cursoMeta)) {
            foreach ($cursoMeta as $item) {
                $candidate = (int) $item;
                if ($candidate > 0) {
                    return $candidate;
                }
            }
            return 0;
        }

        return (int) $cursoMeta;
    }

    private function get_lesson_lock_state($aulaId, $userId = 0)
    {
        $state = [
            'is_locked' => false,
            'message' => '',
            'release_label' => '',
        ];

        if (!class_exists('System_Cursos_Lesson_Schedule')) {
            return $state;
        }

        $state['is_locked'] = System_Cursos_Lesson_Schedule::is_locked_for_user($aulaId, $userId);
        $state['release_label'] = System_Cursos_Lesson_Schedule::get_release_label($aulaId);

        if ($state['is_locked']) {
            $state['message'] = System_Cursos_Lesson_Schedule::get_lock_message($aulaId);
        }

        return $state;
    }

    private function get_locked_embed_html($message)
    {
        $safeMessage = esc_html($message ?: 'Esta aula ainda nao foi liberada.');
        return '<div class="lista-aulas__placeholder lista-aulas__placeholder--locked"><div class="mc-alert lista-aulas__alerta-bloqueio">' . $safeMessage . '</div></div>';
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
