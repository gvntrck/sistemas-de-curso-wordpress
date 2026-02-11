<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Aula_Comments
{
    const COURSE_META_KEY = '_sistema_cursos_comments_course_enabled';
    const LESSON_OVERRIDE_META_KEY = '_sistema_cursos_comments_lesson_override';
    const NONCE_ACTION = 'sistema_cursos_aula_comment_action';

    public function __construct()
    {
        add_action('wp_ajax_sistema_cursos_add_comment', [$this, 'ajax_add_comment']);
        add_action('wp_ajax_sistema_cursos_delete_comment', [$this, 'ajax_delete_comment']);
    }

    private static function is_truthy($value)
    {
        return in_array((string) $value, ['1', 'true', 'yes', 'on'], true);
    }

    public static function is_comments_enabled_for_lesson($aula_id)
    {
        $aula_id = (int) $aula_id;
        if ($aula_id <= 0) {
            return false;
        }

        $curso_id = (int) get_post_meta($aula_id, 'curso', true);
        $course_enabled = false;

        if ($curso_id > 0) {
            $course_enabled = self::is_truthy(get_post_meta($curso_id, self::COURSE_META_KEY, true));
        }

        $lesson_override = self::is_truthy(get_post_meta($aula_id, self::LESSON_OVERRIDE_META_KEY, true));

        // Sobrescrita da aula inverte a regra herdada do curso.
        if ($lesson_override) {
            return !$course_enabled;
        }

        return $course_enabled;
    }

    private static function can_user_access_lesson($user_id, $aula_id)
    {
        $user_id = (int) $user_id;
        $aula_id = (int) $aula_id;

        if ($user_id <= 0 || $aula_id <= 0) {
            return false;
        }

        $post = get_post($aula_id);
        if (!$post || $post->post_type !== 'aula') {
            return false;
        }

        if (current_user_can('manage_options')) {
            return true;
        }

        if ($post->post_status !== 'publish') {
            return false;
        }

        $curso_id = (int) get_post_meta($aula_id, 'curso', true);
        if ($curso_id > 0 && class_exists('System_Cursos_Access_Control')) {
            return (bool) System_Cursos_Access_Control::has_access($user_id, $curso_id);
        }

        return true;
    }

    private static function format_comment_content($content)
    {
        $safe_content = trim((string) $content);
        if ($safe_content === '') {
            return '';
        }

        return nl2br(esc_html($safe_content));
    }

    public static function render_comments_section($aula_id)
    {
        $aula_id = (int) $aula_id;
        if ($aula_id <= 0 || !self::is_comments_enabled_for_lesson($aula_id)) {
            return '';
        }

        $comments = get_comments([
            'post_id' => $aula_id,
            'status' => 'approve',
            'orderby' => 'comment_date_gmt',
            'order' => 'DESC',
            'type' => 'comment',
        ]);

        $current_user_id = get_current_user_id();
        $nonce = wp_create_nonce(self::NONCE_ACTION);

        ob_start();
        ?>
        <section class="sc-aula-comments" aria-label="Comentarios da aula">
            <h3 class="sc-aula-comments__title">Comentarios da aula</h3>

            <div class="sc-aula-comments__list">
                <?php if (empty($comments)): ?>
                    <p class="sc-aula-comments__empty">Ainda nao ha comentarios nesta aula.</p>
                <?php else: ?>
                    <?php foreach ($comments as $comment):
                        $comment_id = (int) $comment->comment_ID;
                        $author_name = get_comment_author($comment);
                        $comment_date = get_comment_date('d/m/Y H:i', $comment);
                        $can_delete = $current_user_id > 0
                            && ((int) $comment->user_id === $current_user_id || current_user_can('manage_options'));
                        ?>
                        <article class="sc-aula-comments__item" data-comment-id="<?php echo $comment_id; ?>">
                            <div class="sc-aula-comments__avatar">
                                <?php echo wp_kses_post(get_avatar($comment, 44)); ?>
                            </div>
                            <div class="sc-aula-comments__content">
                                <header class="sc-aula-comments__meta">
                                    <strong class="sc-aula-comments__author">
                                        <?php echo esc_html($author_name); ?>
                                    </strong>
                                    <time class="sc-aula-comments__date" datetime="<?php echo esc_attr(get_comment_date('c', $comment)); ?>">
                                        <?php echo esc_html($comment_date); ?>
                                    </time>
                                </header>
                                <div class="sc-aula-comments__text">
                                    <?php echo self::format_comment_content($comment->comment_content); ?>
                                </div>

                                <?php if ($can_delete): ?>
                                    <button
                                        type="button"
                                        class="sc-aula-comments__delete"
                                        data-comment-id="<?php echo $comment_id; ?>"
                                        data-aula-id="<?php echo $aula_id; ?>"
                                        data-nonce="<?php echo esc_attr($nonce); ?>">
                                        Apagar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if ($current_user_id > 0): ?>
                <form class="sc-aula-comments__form" method="post" novalidate>
                    <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                    <input type="hidden" name="aula_id" value="<?php echo $aula_id; ?>">
                    <label class="sc-aula-comments__label" for="sc_comment_textarea_<?php echo $aula_id; ?>">
                        Escreva um comentario:
                    </label>
                    <textarea
                        id="sc_comment_textarea_<?php echo $aula_id; ?>"
                        name="comment_content"
                        class="sc-aula-comments__textarea"
                        rows="4"
                        maxlength="2000"
                        placeholder="Digite seu comentario aqui..."
                        required></textarea>
                    <div class="sc-aula-comments__actions">
                        <span class="sc-aula-comments__form-status" aria-live="polite"></span>
                        <button type="submit" class="sc-aula-comments__submit">Enviar comentario</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="sc-aula-comments__login-warning">Voce precisa estar logado para comentar.</p>
            <?php endif; ?>
        </section>
        <?php
        return ob_get_clean();
    }

    public function ajax_add_comment()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Voce precisa estar logado para comentar.']);
        }

        $aula_id = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;
        $content_raw = isset($_POST['content']) ? wp_unslash($_POST['content']) : '';
        $content = trim(sanitize_textarea_field($content_raw));

        if ($aula_id <= 0) {
            wp_send_json_error(['message' => 'Aula invalida.']);
        }

        if (!self::is_comments_enabled_for_lesson($aula_id)) {
            wp_send_json_error(['message' => 'Comentarios desativados para esta aula.']);
        }

        if (!self::can_user_access_lesson($user_id, $aula_id)) {
            wp_send_json_error(['message' => 'Voce nao tem permissao para comentar nesta aula.']);
        }

        if ($content === '') {
            wp_send_json_error(['message' => 'Escreva um comentario antes de enviar.']);
        }

        $content_length = function_exists('mb_strlen') ? mb_strlen($content) : strlen($content);
        if ($content_length > 2000) {
            wp_send_json_error(['message' => 'Comentario muito longo. Limite de 2000 caracteres.']);
        }

        $user = wp_get_current_user();

        $comment_id = wp_insert_comment([
            'comment_post_ID' => $aula_id,
            'comment_content' => $content,
            'user_id' => $user_id,
            'comment_author' => $user ? $user->display_name : '',
            'comment_author_email' => $user ? $user->user_email : '',
            'comment_type' => 'comment',
            'comment_approved' => 1,
            'comment_date' => current_time('mysql'),
            'comment_date_gmt' => current_time('mysql', true),
        ]);

        if (!$comment_id) {
            wp_send_json_error(['message' => 'Nao foi possivel salvar o comentario.']);
        }

        wp_send_json_success([
            'section_html' => self::render_comments_section($aula_id),
        ]);
    }

    public function ajax_delete_comment()
    {
        check_ajax_referer(self::NONCE_ACTION, 'nonce');

        $user_id = get_current_user_id();
        if ($user_id <= 0) {
            wp_send_json_error(['message' => 'Voce precisa estar logado para apagar comentarios.']);
        }

        $comment_id = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
        if ($comment_id <= 0) {
            wp_send_json_error(['message' => 'Comentario invalido.']);
        }

        $comment = get_comment($comment_id);
        if (!$comment) {
            wp_send_json_error(['message' => 'Comentario nao encontrado.']);
        }

        $aula_id = (int) $comment->comment_post_ID;
        if (!self::can_user_access_lesson($user_id, $aula_id)) {
            wp_send_json_error(['message' => 'Voce nao tem permissao para esta aula.']);
        }

        $is_owner = ((int) $comment->user_id === $user_id);
        $can_manage = current_user_can('manage_options');
        if (!$is_owner && !$can_manage) {
            wp_send_json_error(['message' => 'Voce so pode apagar seus proprios comentarios.']);
        }

        $deleted = wp_delete_comment($comment_id, true);
        if (!$deleted) {
            wp_send_json_error(['message' => 'Nao foi possivel apagar o comentario.']);
        }

        wp_send_json_success([
            'section_html' => self::render_comments_section($aula_id),
        ]);
    }
}
