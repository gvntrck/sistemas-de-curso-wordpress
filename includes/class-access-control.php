<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Access_Control
{
    /**
     * class-access-control.php
     *
     * Gerencia o controle de acesso dos alunos aos cursos.
     * Cria a tabela personalizada no banco de dados, verifica permissões, concede, revoga e suspende acessos, além de gerenciar a interface administrativa de alunos.
     *
     * @package SistemaCursos
     * @version 1.0.9
     */
    public function __construct()
    {
        add_action('init', [$this, 'create_table']);
        add_action('admin_menu', [$this, 'admin_menu'], 20);
        add_action('admin_init', [$this, 'admin_process']);
        add_action('wp_login', [$this, 'track_user_login'], 10, 2);
        add_action('wp_ajax_sc_admin_get_course_lessons_progress', [$this, 'ajax_get_course_lessons_progress']);
        add_action('wp_ajax_sc_admin_update_course_lesson_progress', [$this, 'ajax_update_course_lesson_progress']);
        add_action('admin_post_update_access_date', [$this, 'handle_update_access_date']);
    }

    /**
     * Rastreia o login do usuário para fins de segurança (Anti-Pirataria)
     */
    public function track_user_login($user_login, $user)
    {
        if (!$user) {
            return;
        }

        // Sanitizar e validar IP antes de salvar
        $raw_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Unknown';
        $ip = filter_var($raw_ip, FILTER_VALIDATE_IP) ? $raw_ip : 'Invalid IP';

        $login_data = [
            'time' => current_time('timestamp'), // Timestamp Unix: evita conflito de timezone
            'ip' => $ip,
            'ua' => isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])), 0, 500) : 'Unknown',
        ];

        // Obter histórico existente
        $history = get_user_meta($user->ID, '_login_history', true);
        if (!is_array($history)) {
            $history = [];
        }

        // Adicionar novo login no início
        array_unshift($history, $login_data);

        // Manter apenas os últimos 50 registros
        $history = array_slice($history, 0, 50);

        // Salvar
        update_user_meta($user->ID, '_login_history', $history);

        // Atualizar também o meta simples de último login para compatibilidade
        update_user_meta($user->ID, 'last_login', current_time('mysql'));
    }

    // =============================================================================
    // TABELA NO BANCO DE DADOS
    // =============================================================================

    public function create_table()
    {
        $db_schema_version = get_option('sistema_cursos_db_schema', '0');
        $current_schema = defined('SISTEMA_CURSOS_VERSION') ? SISTEMA_CURSOS_VERSION : '1.0.0';

        if ($db_schema_version === $current_schema) {
            return;
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'ativo',
            data_fim datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL,
            created_by bigint(20) unsigned DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_curso (user_id, curso_id),
            KEY user_id (user_id),
            KEY curso_id (curso_id),
            KEY status (status),
            KEY data_fim (data_fim)
        ) $charset_collate;";

        $table_log = $wpdb->prefix . 'acesso_cursos_log';
        $sql_log = "CREATE TABLE IF NOT EXISTS $table_log (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            curso_id bigint(20) unsigned NOT NULL,
            action varchar(50) NOT NULL,
            actor_id bigint(20) unsigned DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            details text DEFAULT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY curso_id (curso_id),
            KEY action (action),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        dbDelta($sql_log);

        update_option('sistema_cursos_db_schema', $current_schema);
    }

    // =============================================================================
    // HELPER DE LOG
    // =============================================================================

    /**
     * Registra ações de acesso no histórico
     */
    private static function log_access_action($user_id, $curso_id, $action, $actor_id = null, $details = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos_log';

        if ($actor_id === null) {
            $actor_id = get_current_user_id();
        }

        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'curso_id' => $curso_id,
                'action' => $action,
                'actor_id' => $actor_id,
                'created_at' => current_time('mysql'),
                'details' => $details ? json_encode($details) : null
            ],
            ['%d', '%d', '%s', '%d', '%s', '%s']
        );
    }

    /**
     * Método público para registrar mudanças em grupos
     * 
     * @param int $user_id ID do aluno
     * @param int $group_id ID do grupo
     * @param string $action Ação (grupo_entrou, grupo_saiu)
     * @param int $actor_id ID de quem realizou a ação
     */
    public static function log_group_change($user_id, $group_id, $action, $actor_id = 0, $extra_details = [])
    {
        $group_title = get_the_title($group_id);

        $details = [
            'group_id' => $group_id,
            'group_name' => $group_title ? $group_title : "Grupo ID $group_id"
        ];

        if (!empty($extra_details)) {
            $details = array_merge($details, $extra_details);
        }

        self::log_access_action($user_id, 0, $action, $actor_id, $details);
    }

    /**
     * Obtém a URL base da página do painel ([lms-painel]).
     * Mantém fallback para a home.
     */
    private static function get_certificate_page_url()
    {
        static $cached_url = null;

        if ($cached_url !== null) {
            return $cached_url;
        }

        $cached_url = home_url('/');

        // Prioriza página do painel SPA.
        $painel_page_query = new WP_Query([
            's' => '[lms-painel]',
            'post_type' => 'page',
            'posts_per_page' => 1,
            'fields' => 'ids'
        ]);

        if (!empty($painel_page_query->posts)) {
            $cached_url = get_permalink((int) $painel_page_query->posts[0]);
        } else {
            // Compatibilidade: se não existir painel, tenta página dedicada de certificado.
            $cert_page_query = new WP_Query([
                's' => '[certificado]',
                'post_type' => 'page',
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);

            if (!empty($cert_page_query->posts)) {
                $cached_url = get_permalink((int) $cert_page_query->posts[0]);
            }
        }

        wp_reset_postdata();

        return $cached_url;
    }

    /**
     * Monta link administrativo para emissão de certificado de um aluno em um curso.
     */
    private static function get_admin_certificate_link($user_id, $curso_id)
    {
        return add_query_arg(
            [
                'lms_view' => 'certificado-view',
                'curso_id' => (int) $curso_id,
                'aluno_id' => (int) $user_id,
                'forcar_emissao' => 1
            ],
            self::get_certificate_page_url()
        );
    }

    // =============================================================================
    // FUNÇÕES DE ACESSO
    // =============================================================================



    /**
     * Utilitarios para fluxo de redefinicao de senha no admin.
     */
    /**
     * Monta o link padrao do WordPress para redefinicao de senha.
     */
    private static function get_password_reset_link($user)
    {
        $key = get_password_reset_key($user);
        if (is_wp_error($key)) {
            return $key;
        }

        return network_site_url(
            'wp-login.php?action=rp&key=' . rawurlencode($key) . '&login=' . rawurlencode($user->user_login),
            'login'
        );
    }

    /**
     * Gera o template HTML (com CSS inline) para e-mail de redefinicao.
     */
    private static function get_password_reset_email_html($user, $reset_url)
    {
        $nome = trim($user->first_name);
        if ($nome === '') {
            $nome = $user->display_name ?: $user->user_login;
        }

        $site_name = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
        $support_email = get_option('admin_email');
        $support_email_link = is_email($support_email) ? 'mailto:' . sanitize_email($support_email) : '';
        $home_url = home_url('/');
        $reset_url_escaped = esc_url($reset_url);
        $nome_escaped = esc_html($nome);
        $site_name_escaped = esc_html($site_name);

        $support_html = $support_email_link !== ''
            ? '<a href="' . esc_url($support_email_link) . '" style="color:#6b7280; text-decoration:underline;">' . esc_html($support_email) . '</a>'
            : '<span style="color:#6b7280;">nosso suporte</span>';

        return '
<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="margin:0; padding:0; background-color:#e8ecf1; font-family:Arial,Helvetica,sans-serif;">
  <tr>
    <td align="center" style="padding:28px 12px;">
      <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="max-width:640px; margin:0 auto;">
        <tr>
          <td style="background:#ffffff; border:1px solid #d9dde3; border-radius:8px; padding:32px;">
            <p style="margin:0 0 16px; font-size:38px; line-height:1.2; color:#111111;">Oi <strong>' . $nome_escaped . '</strong>,</p>
            <p style="margin:0 0 24px; font-size:18px; line-height:1.55; color:#374151;">
              Precisa redefinir sua senha? Sem problema. Clique no botao abaixo para criar uma nova senha.
            </p>
            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px;">
              <tr>
                <td align="center" style="border-radius:4px; background:#111111;">
                  <a href="' . $reset_url_escaped . '" style="display:inline-block; padding:14px 28px; font-size:16px; line-height:1; font-weight:700; color:#ffffff; text-decoration:none; text-transform:uppercase; letter-spacing:0.4px;">
                    Redefinir senha
                  </a>
                </td>
              </tr>
            </table>
            <p style="margin:0 0 18px; font-size:16px; line-height:1.6; color:#6b7280;">
              Se voce nao solicitou esta redefinicao, ignore este e-mail ou entre em contato com ' . $support_html . '.
            </p>
            <p style="margin:0; font-size:16px; line-height:1.6; color:#6b7280;">
              Obrigado,<br>' . $site_name_escaped . '
            </p>
          </td>
        </tr>
        <tr>
          <td align="center" style="padding-top:20px; color:#6b7280; font-size:13px; line-height:1.5;">
            <p style="margin:0 0 8px;">&copy; ' . $site_name_escaped . '</p>
            <p style="margin:0;">
              <a href="' . esc_url($home_url) . '" style="color:#6b7280; text-decoration:underline;">Acessar site</a>
            </p>
          </td>
        </tr>
      </table>
    </td>
  </tr>
</table>';
    }

    /**
     * Dispara o e-mail de redefinicao para um aluno especifico.
     */
    private static function send_student_password_reset_email($user_id)
    {
        $user = get_user_by('id', (int) $user_id);
        if (!$user || !is_a($user, 'WP_User')) {
            return new WP_Error('invalid_user', 'Usuario invalido.');
        }

        if (!is_email($user->user_email)) {
            return new WP_Error('invalid_email', 'Usuario sem e-mail valido.');
        }

        $reset_url = self::get_password_reset_link($user);
        if (is_wp_error($reset_url)) {
            return $reset_url;
        }

        $subject = sprintf(
            '[%s] Redefinicao de senha',
            wp_specialchars_decode(get_option('blogname'), ENT_QUOTES)
        );

        $headers = [
            'Content-Type: text/html; charset=UTF-8'
        ];

        $message = self::get_password_reset_email_html($user, $reset_url);
        $sent = wp_mail($user->user_email, $subject, $message, $headers);

        if (!$sent) {
            return new WP_Error('mail_failed', 'Falha ao enviar e-mail.');
        }

        return true;
    }

    /**
     * Verifica se o aluno possui acesso ao curso.
     */
    public static function has_access($user_id, $curso_id)
    {
        $source = self::get_access_source($user_id, $curso_id);
        return $source !== false;
    }

    /**
     * Retorna a fonte do acesso (Direto, Grupo ou Trilha)
     * Retorna array com detalhes ou false se não tiver acesso.
     * 
     * @version 1.0.9 - Adicionada validação para grupos inexistentes (bug grupo fantasma)
     */
    public static function get_access_source($user_id, $curso_id)
    {
        // 1. Acesso Direto (Banco de Dados)
        if (self::check_direct_access($user_id, $curso_id)) {
            return ['type' => 'direct', 'label' => 'Matrícula Direta'];
        }

        // 2. Verificar Grupos do Usuário
        $user_grupos = get_user_meta($user_id, '_aluno_grupos', true);
        if (empty($user_grupos) || !is_array($user_grupos)) {
            return false;
        }

        // Filtrar grupos do usuário que ainda existem (remover grupos deletados)
        $user_grupos_validos = array_filter($user_grupos, function ($grupo_id) {
            $status = get_post_status($grupo_id);
            return $status !== false && $status !== 'trash';
        });

        // Se não sobrou nenhum grupo válido, retorna false
        if (empty($user_grupos_validos)) {
            return false;
        }

        // 2a. Grupos no Curso
        $curso_grupos = get_post_meta($curso_id, '_grupos_permitidos', true);
        if (is_array($curso_grupos) && !empty($curso_grupos)) {
            // Filtrar grupos do curso que ainda existem
            $curso_grupos_validos = array_filter($curso_grupos, function ($grupo_id) {
                $status = get_post_status($grupo_id);
                return $status !== false && $status !== 'trash';
            });

            if (!empty($curso_grupos_validos)) {
                $intersect = array_intersect($user_grupos_validos, $curso_grupos_validos);
                if (!empty($intersect)) {
                    $g_id = reset($intersect); // Pega o primeiro grupo encontrado
                    $grupo_titulo = get_the_title($g_id);

                    // Verificar se o grupo realmente existe e tem título
                    if (!empty($grupo_titulo)) {
                        return ['type' => 'group', 'label' => 'Grupo: ' . $grupo_titulo, 'group_id' => $g_id];
                    }
                }
            }
        }

        // 2b. Grupos na Trilha (Pai) - Opcional, mantido para compatibilidade se usar Trilhas
        $trilha_id = get_post_meta($curso_id, 'trilha', true);
        if ($trilha_id) {
            $trilha_grupos = get_post_meta($trilha_id, '_grupos_permitidos', true);
            if (is_array($trilha_grupos) && !empty($trilha_grupos)) {
                // Filtrar grupos da trilha que ainda existem
                $trilha_grupos_validos = array_filter($trilha_grupos, function ($grupo_id) {
                    $status = get_post_status($grupo_id);
                    return $status !== false && $status !== 'trash';
                });

                if (!empty($trilha_grupos_validos)) {
                    $intersect = array_intersect($user_grupos_validos, $trilha_grupos_validos);
                    if (!empty($intersect)) {
                        $g_id = reset($intersect);
                        $grupo_titulo = get_the_title($g_id);

                        // Verificar se o grupo realmente existe e tem título
                        if (!empty($grupo_titulo)) {
                            return ['type' => 'group_trilha', 'label' => 'Trilha/Grupo: ' . $grupo_titulo, 'group_id' => $g_id];
                        }
                    }
                }
            }
        }

        return false;
    }

    private static function check_direct_access($user_id, $curso_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        if (!$result) {
            return false;
        }

        if ($result->status !== 'ativo') {
            return false;
        }

        if ($result->data_fim !== null && strtotime($result->data_fim) < time()) {
            return false;
        }

        return true;
    }

    // =============================================================================
    // LIMPEZA DE GRUPOS ÓRFÃOS
    // =============================================================================

    /**
     * Limpa referências a grupos que foram deletados mas ainda estão
     * associados a cursos, trilhas ou usuários.
     * 
     * @return array Estatísticas de limpeza com totais por tipo
     * @since 1.0.9
     */
    public static function cleanup_orphaned_group_references()
    {
        $cleaned = [
            'cursos' => 0,
            'trilhas' => 0,
            'usuarios' => 0
        ];

        // 1. Limpar grupos inexistentes de cursos
        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any'
        ]);

        foreach ($cursos as $curso_id) {
            $grupos = get_post_meta($curso_id, '_grupos_permitidos', true);
            if (is_array($grupos) && !empty($grupos)) {
                $grupos_validos = array_filter($grupos, function ($g_id) {
                    $status = get_post_status($g_id);
                    return $status !== false && $status !== 'trash';
                });

                if (count($grupos_validos) !== count($grupos)) {
                    update_post_meta($curso_id, '_grupos_permitidos', array_values($grupos_validos));
                    $cleaned['cursos']++;
                }
            }
        }

        // 2. Limpar grupos inexistentes de trilhas
        $trilhas = get_posts([
            'post_type' => 'trilha',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'post_status' => 'any'
        ]);

        foreach ($trilhas as $trilha_id) {
            $grupos = get_post_meta($trilha_id, '_grupos_permitidos', true);
            if (is_array($grupos) && !empty($grupos)) {
                $grupos_validos = array_filter($grupos, function ($g_id) {
                    $status = get_post_status($g_id);
                    return $status !== false && $status !== 'trash';
                });

                if (count($grupos_validos) !== count($grupos)) {
                    update_post_meta($trilha_id, '_grupos_permitidos', array_values($grupos_validos));
                    $cleaned['trilhas']++;
                }
            }
        }

        // 3. Limpar grupos inexistentes de usuários
        $users = get_users(['fields' => 'ID']);
        foreach ($users as $user_id) {
            if (self::cleanup_user_orphaned_groups($user_id)) {
                $cleaned['usuarios']++;
            }
        }

        // Log para auditoria
        if (array_sum($cleaned) > 0) {
            error_log(sprintf(
                '[LMS SuporteRapido] Limpeza de grupos órfãos: %d cursos, %d trilhas, %d usuários atualizados',
                $cleaned['cursos'],
                $cleaned['trilhas'],
                $cleaned['usuarios']
            ));
        }

        return $cleaned;
    }

    /**
     * Limpa grupos órfãos de um usuário específico.
     * 
     * @param int $user_id ID do usuário
     * @return bool True se houve alteração, false caso contrário
     * @since 1.0.9
     */
    public static function cleanup_user_orphaned_groups($user_id)
    {
        $grupos = get_user_meta($user_id, '_aluno_grupos', true);

        if (!is_array($grupos) || empty($grupos)) {
            return false;
        }

        $grupos_validos = array_filter($grupos, function ($g_id) {
            $status = get_post_status($g_id);
            return $status !== false && $status !== 'trash';
        });

        if (count($grupos_validos) !== count($grupos)) {
            update_user_meta($user_id, '_aluno_grupos', array_values($grupos_validos));
            return true;
        }

        return false;
    }

    public static function grant_access($user_id, $curso_id, $data_fim = null, $created_by = null, $custom_log_action = null, $custom_log_details = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        if ($created_by === null) {
            $created_by = get_current_user_id();
        }

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        if ($existing) {
            $updated = $wpdb->update(
                $table_name,
                [
                    'status' => 'ativo',
                    'data_fim' => $data_fim,
                    'updated_at' => current_time('mysql')
                ],
                ['id' => $existing],
                ['%s', '%s', '%s'],
                ['%d']
            );

            if ($updated !== false) {
                $action = $custom_log_action ? $custom_log_action : 'reativado';
                $details = $custom_log_details ? $custom_log_details : null;
                self::log_access_action($user_id, $curso_id, $action, $created_by, $details);
            }

            return $updated;
        }

        $result = $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'curso_id' => $curso_id,
                'status' => 'ativo',
                'data_fim' => $data_fim,
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
                'created_by' => $created_by
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
        );

        if ($result) {
            $action = $custom_log_action ? $custom_log_action : 'concedido';
            $details = $custom_log_details ? $custom_log_details : ['data_fim' => $data_fim];
            self::log_access_action($user_id, $curso_id, $action, $created_by, $details);
        }

        return $result;
    }

    public static function revoke_access($user_id, $curso_id, $actor_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $result = $wpdb->update(
            $table_name,
            ['status' => 'revogado', 'updated_at' => current_time('mysql')],
            ['user_id' => $user_id, 'curso_id' => $curso_id],
            ['%s', '%s'],
            ['%d', '%d']
        );

        if ($result !== false) {
            self::log_access_action($user_id, $curso_id, 'revogado', $actor_id);
        }

        return $result;
    }

    public static function suspend_access($user_id, $curso_id, $actor_id = null)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $result = $wpdb->update(
            $table_name,
            ['status' => 'suspenso', 'updated_at' => current_time('mysql')],
            ['user_id' => $user_id, 'curso_id' => $curso_id],
            ['%s', '%s'],
            ['%d', '%d']
        );

        if ($result !== false) {
            self::log_access_action($user_id, $curso_id, 'suspenso', $actor_id);
        }

        return $result;
    }

    public static function delete_access($id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        return $wpdb->delete($table_name, ['id' => $id], ['%d']);
    }

    public static function list_accesses($args = [])
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $defaults = [
            'user_id' => null,
            'curso_id' => null,
            'status' => null,
            'expirados' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        ];

        $args = wp_parse_args($args, $defaults);

        $where = ['1=1'];
        $prepare_values = [];

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }

        if ($args['curso_id']) {
            $where[] = 'curso_id = %d';
            $prepare_values[] = $args['curso_id'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }

        if ($args['expirados'] === true) {
            $where[] = '(data_fim IS NOT NULL AND data_fim < NOW())';
        } elseif ($args['expirados'] === false) {
            $where[] = '(data_fim IS NULL OR data_fim >= NOW())';
        }

        $where_sql = implode(' AND ', $where);
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']) ?: 'created_at DESC';

        $sql = "SELECT * FROM $table_name WHERE $where_sql ORDER BY $orderby LIMIT %d OFFSET %d";
        $prepare_values[] = $args['limit'];
        $prepare_values[] = $args['offset'];

        if (count($prepare_values) > 2) {
            return $wpdb->get_results($wpdb->prepare($sql, $prepare_values));
        }

        return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
    }

    public static function count_accesses($args = [])
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $where = ['1=1'];
        $prepare_values = [];

        if (!empty($args['user_id'])) {
            $where[] = 'user_id = %d';
            $prepare_values[] = $args['user_id'];
        }

        if (!empty($args['curso_id'])) {
            $where[] = 'curso_id = %d';
            $prepare_values[] = $args['curso_id'];
        }

        if (!empty($args['status'])) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }

        $where_sql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) FROM $table_name WHERE $where_sql";

        if (!empty($prepare_values)) {
            return (int) $wpdb->get_var($wpdb->prepare($sql, $prepare_values));
        }

        return (int) $wpdb->get_var($sql);
    }

    public static function get_user_courses($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        // 1. Acesso Direto (Banco de Dados)
        $direct_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT curso_id FROM $table_name 
             WHERE user_id = %d 
             AND status = 'ativo' 
             AND (data_fim IS NULL OR data_fim >= NOW())",
            $user_id
        ));

        // 2. Acesso via Grupos (Cursos diretos e Trilhas)
        $group_course_ids = [];
        $user_grupos = get_user_meta($user_id, '_aluno_grupos', true);

        if (!empty($user_grupos) && is_array($user_grupos)) {
            $conteudos_ids = [];
            foreach ($user_grupos as $g_id) {
                // Conteúdos salvos no grupo (Curso ID ou Trilha ID)
                $c = get_post_meta($g_id, '_grupo_conteudos', true);
                if (is_array($c)) {
                    $conteudos_ids = array_merge($conteudos_ids, $c);
                }
            }
            $conteudos_ids = array_unique($conteudos_ids);

            if (!empty($conteudos_ids)) {
                // Verificar tipos para separar Cursos de Trilhas
                $conteudos_objects = get_posts([
                    'post_type' => ['curso', 'trilha'],
                    'post__in' => $conteudos_ids,
                    'posts_per_page' => -1,
                ]);

                $trilha_ids = [];
                foreach ($conteudos_objects as $obj) {
                    if ($obj->post_type === 'curso') {
                        $group_course_ids[] = $obj->ID;
                    } elseif ($obj->post_type === 'trilha') {
                        $trilha_ids[] = $obj->ID;
                    }
                }

                // Buscar cursos das trilhas encontradas (Recursividade Trilha -> Cursos)
                if (!empty($trilha_ids)) {
                    $child_courses = get_posts([
                        'post_type' => 'curso',
                        'posts_per_page' => -1,
                        'meta_query' => [
                            [
                                'key' => 'trilha',
                                'value' => $trilha_ids,
                                'compare' => 'IN'
                            ]
                        ],
                        'fields' => 'ids'
                    ]);
                    if (!empty($child_courses)) {
                        $group_course_ids = array_merge($group_course_ids, $child_courses);
                    }
                }
            }
        }

        // Combinar e remover duplicatas
        $all_ids = array_merge($direct_ids, $group_course_ids);
        return array_map('intval', array_unique($all_ids));
    }

    public static function get_detailed_progress($user_id, $curso_id)
    {
        if (class_exists('System_Cursos_Progress') && method_exists('System_Cursos_Progress', 'get_course_progress_details')) {
            return System_Cursos_Progress::get_course_progress_details($user_id, $curso_id);
        }

        // Fallback or empty return if class not available yet
        return [
            'total' => 0,
            'concluidas' => 0,
            'percent' => 0,
            'last_date' => null
        ];
    }

    private static function get_course_lessons($curso_id)
    {
        $curso_id = (int) $curso_id;
        if ($curso_id <= 0) {
            return [];
        }

        return get_posts([
            'post_type' => 'aula',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'meta_query' => [
                'relation' => 'OR',
                [
                    'key' => 'curso',
                    'value' => $curso_id,
                    'compare' => '=',
                ],
                [
                    'key' => 'curso',
                    'value' => '"' . $curso_id . '"',
                    'compare' => 'LIKE',
                ],
            ],
        ]);
    }

    private static function lesson_belongs_to_course($aula_id, $curso_id)
    {
        $aula_id = (int) $aula_id;
        $curso_id = (int) $curso_id;

        if ($aula_id <= 0 || $curso_id <= 0) {
            return false;
        }

        $lesson = get_post($aula_id);
        if (!$lesson || $lesson->post_type !== 'aula') {
            return false;
        }

        $curso_meta = get_post_meta($aula_id, 'curso', true);

        if (is_array($curso_meta)) {
            $curso_meta = array_map('intval', $curso_meta);
            return in_array($curso_id, $curso_meta, true);
        }

        if (is_numeric($curso_meta)) {
            return (int) $curso_meta === $curso_id;
        }

        if (is_string($curso_meta)) {
            if ((int) $curso_meta === $curso_id) {
                return true;
            }

            return strpos($curso_meta, '"' . (string) $curso_id . '"') !== false;
        }

        return false;
    }

    private static function get_manual_completion_score($aula_id)
    {
        $aula_id = (int) $aula_id;
        if ($aula_id <= 0) {
            return 0;
        }

        if (!class_exists('System_Cursos_Quiz_Process') || !method_exists('System_Cursos_Quiz_Process', 'get_quiz_data')) {
            return 0;
        }

        $quiz_data = System_Cursos_Quiz_Process::get_quiz_data($aula_id);
        if (!is_array($quiz_data)) {
            return 0;
        }

        $questions = isset($quiz_data['questions']) && is_array($quiz_data['questions']) ? $quiz_data['questions'] : [];
        if (empty($questions)) {
            return 0;
        }

        return max(0, min(100, (int) ($quiz_data['passing_score'] ?? 0)));
    }

    private static function set_manual_lesson_completion($user_id, $curso_id, $aula_id, $completed)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        $user_id = (int) $user_id;
        $curso_id = (int) $curso_id;
        $aula_id = (int) $aula_id;

        if ($user_id <= 0 || $curso_id <= 0 || $aula_id <= 0) {
            return false;
        }

        if ($completed) {
            $pontuacao = self::get_manual_completion_score($aula_id);
            $data_conclusao = current_time('mysql');

            $existing_id = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE user_id = %d AND aula_id = %d",
                $user_id,
                $aula_id
            ));

            if ($existing_id > 0) {
                $updated = $wpdb->update(
                    $table_name,
                    [
                        'curso_id' => $curso_id,
                        'pontuacao' => $pontuacao,
                        'tentativas' => 1,
                        'data_conclusao' => $data_conclusao
                    ],
                    ['id' => $existing_id],
                    ['%d', '%d', '%d', '%s'],
                    ['%d']
                );

                if ($updated === false) {
                    return false;
                }
            } else {
                $inserted = $wpdb->insert(
                    $table_name,
                    [
                        'user_id' => $user_id,
                        'aula_id' => $aula_id,
                        'curso_id' => $curso_id,
                        'pontuacao' => $pontuacao,
                        'tentativas' => 1,
                        'data_conclusao' => $data_conclusao
                    ],
                    ['%d', '%d', '%d', '%d', '%d', '%s']
                );

                if (!$inserted) {
                    return false;
                }
            }
        } else {
            $deleted = $wpdb->delete(
                $table_name,
                ['user_id' => $user_id, 'aula_id' => $aula_id],
                ['%d', '%d']
            );

            if ($deleted === false) {
                return false;
            }
        }

        if (class_exists('System_Cursos_Progress') && method_exists('System_Cursos_Progress', 'update_user_progress')) {
            System_Cursos_Progress::update_user_progress($user_id, $curso_id);
        }

        return true;
    }

    public function ajax_get_course_lessons_progress()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sem permissao para esta acao.'], 403);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'sc_admin_lesson_progress')) {
            wp_send_json_error(['message' => 'Falha de seguranca. Recarregue a pagina e tente novamente.'], 403);
        }

        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;

        if ($user_id <= 0 || $curso_id <= 0) {
            wp_send_json_error(['message' => 'Usuario ou curso invalido.']);
        }

        $user = get_user_by('ID', $user_id);
        $curso = get_post($curso_id);
        if (!$user || !$curso || $curso->post_type !== 'curso') {
            wp_send_json_error(['message' => 'Nao foi possivel localizar o aluno ou o curso.']);
        }

        if (!self::has_access($user_id, $curso_id)) {
            wp_send_json_error(['message' => 'O aluno nao possui acesso ativo a este curso.']);
        }

        $aulas = self::get_course_lessons($curso_id);
        $aulas_concluidas = [];
        if (class_exists('System_Cursos_Progress') && method_exists('System_Cursos_Progress', 'get_completed_lessons')) {
            $aulas_concluidas = System_Cursos_Progress::get_completed_lessons($user_id, $curso_id);
        }

        $concluidas_lookup = array_fill_keys(array_map('intval', $aulas_concluidas), true);
        $lessons_payload = [];

        foreach ($aulas as $index => $aula) {
            $aula_id = (int) $aula->ID;
            $lessons_payload[] = [
                'id' => $aula_id,
                'title' => $aula->post_title,
                'order' => $index + 1,
                'completed' => isset($concluidas_lookup[$aula_id]),
                'passing_score' => self::get_manual_completion_score($aula_id),
            ];
        }

        wp_send_json_success([
            'course_title' => get_the_title($curso_id),
            'lessons' => $lessons_payload,
            'progress' => self::get_detailed_progress($user_id, $curso_id),
        ]);
    }

    public function ajax_update_course_lesson_progress()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sem permissao para esta acao.'], 403);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
        if (!$nonce || !wp_verify_nonce($nonce, 'sc_admin_lesson_progress')) {
            wp_send_json_error(['message' => 'Falha de seguranca. Recarregue a pagina e tente novamente.'], 403);
        }

        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;
        $aula_id = isset($_POST['aula_id']) ? (int) $_POST['aula_id'] : 0;
        $completed = isset($_POST['completed']) && (int) $_POST['completed'] === 1;

        if ($user_id <= 0 || $curso_id <= 0 || $aula_id <= 0) {
            wp_send_json_error(['message' => 'Dados invalidos para atualizar a aula.']);
        }

        if (!self::has_access($user_id, $curso_id)) {
            wp_send_json_error(['message' => 'O aluno nao possui acesso ativo a este curso.']);
        }

        if (!self::lesson_belongs_to_course($aula_id, $curso_id)) {
            wp_send_json_error(['message' => 'A aula informada nao pertence a este curso.']);
        }

        $updated = self::set_manual_lesson_completion($user_id, $curso_id, $aula_id, $completed);
        if (!$updated) {
            wp_send_json_error(['message' => 'Nao foi possivel salvar a alteracao da aula.']);
        }

        $is_completed = false;
        if (class_exists('System_Cursos_Progress') && method_exists('System_Cursos_Progress', 'is_lesson_completed')) {
            $is_completed = System_Cursos_Progress::is_lesson_completed($user_id, $aula_id);
        }

        wp_send_json_success([
            'completed' => (bool) $is_completed,
            'progress' => self::get_detailed_progress($user_id, $curso_id),
            'message' => $is_completed ? 'Aula marcada como concluida.' : 'Aula desmarcada como concluida.',
        ]);
    }

    /**
     * Retorna dados de engajamento para o grafico (Aulas concluidas por dia nos ultimos 30 dias)
     */
    public static function get_engagement_data($user_id)
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'progresso_aluno';

        // Inicializar últimos 30 dias com 0
        $data = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $data[$date] = 0;
        }

        // Buscar contagem agrupada por dia
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(data_conclusao) as date, COUNT(*) as count 
             FROM $table_name 
             WHERE user_id = %d AND data_conclusao >= DATE_SUB(NOW(), INTERVAL 30 DAY)
             GROUP BY DATE(data_conclusao)",
            $user_id
        ));

        // Mesclar resultados
        foreach ($results as $row) {
            if (isset($data[$row->date])) {
                $data[$row->date] = (int) $row->count;
            }
        }

        return $data;
    }

    // =============================================================================
    // PAINEL ADMINISTRATIVO
    // =============================================================================

    public function admin_menu()
    {
        add_submenu_page(
            'lms-suporte-rapido',
            'Lista de Alunos',
            'Lista de Alunos',
            'manage_options',
            'acesso-cursos-alunos',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Processa a atualização da data de expiração via admin-post.php
     */
    public function handle_update_access_date()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Acesso negado.');
        }

        // Verificar Nonce (nome e ação devem corresponder ao formulário)
        if (!isset($_POST['update_access_date_nonce']) || !wp_verify_nonce($_POST['update_access_date_nonce'], 'update_access_date_action')) {
            wp_die('Falha na verificação de segurança (Nonce). Tente recarregar a página.');
        }

        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $curso_id = isset($_POST['curso_id']) ? intval($_POST['curso_id']) : 0;
        $data_fim = isset($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) : '';

        if (!$user_id || !$curso_id) {
            wp_die('Dados inválidos: Usuário ou Curso não informados.');
        }

        // Se data_fim estiver vazia, passamos null para ser vitalício.
        // Se preenchida, adicionamos o horário final do dia.
        $data_fim_sql = !empty($data_fim) ? $data_fim . ' 23:59:59' : null;

        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        // Buscar data antiga para log
        $antiga_data_fim = $wpdb->get_var($wpdb->prepare(
            "SELECT data_fim FROM $table_name WHERE user_id = %d AND curso_id = %d",
            $user_id,
            $curso_id
        ));

        // Reutilizamos grant_access para atualizar a data e logar a alteração
        self::grant_access(
            $user_id,
            $curso_id,
            $data_fim_sql,
            get_current_user_id(),
            'data_alterada',
            [
                'antiga_data_fim' => $antiga_data_fim,
                'nova_data_fim' => $data_fim_sql
            ]
        );

        // Redirecionar de volta para a página de detalhes com mensagem de sucesso
        wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=data_atualizada'));
        exit;
    }

    public function admin_process()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Ações rápidas na página de detalhes do aluno
        if (isset($_POST['acao_rapida']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_acesso_rapido')) {
            $user_id = (int) $_POST['user_id'];
            $acao_parts = explode('_', $_POST['acao_rapida'], 2);
            $acao = $acao_parts[0];
            $curso_id = isset($acao_parts[1]) ? (int) $acao_parts[1] : 0;

            if ($user_id > 0 && $curso_id > 0) {
                switch ($acao) {
                    case 'ativar':
                    case 'reativar':
                        self::grant_access($user_id, $curso_id, null, get_current_user_id());
                        break;
                    case 'suspender':
                        self::suspend_access($user_id, $curso_id, get_current_user_id());
                        break;
                    case 'revogar':
                        self::revoke_access($user_id, $curso_id, get_current_user_id());
                        break;
                }

                wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=acesso_atualizado'));
                exit;
            }
        }


        // Conceder acesso com data de expiração
        if (isset($_POST['conceder_acesso']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_conceder_acesso')) {
            $user_id = (int) $_POST['user_id'];
            $curso_id = (int) $_POST['curso_id'];
            $data_fim = !empty($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) . ' 23:59:59' : null;

            if ($user_id > 0 && $curso_id > 0) {
                self::grant_access($user_id, $curso_id, $data_fim, get_current_user_id());

                wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=acesso_concedido'));
                exit;
            }
        }

        // Matrícula em Lote Múltipla (Cursos e Trilhas)
        if (isset($_POST['matricular_lote']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_matricula_lote')) {
            $user_ids = isset($_POST['user_ids']) ? (array) $_POST['user_ids'] : [];
            $lote_cursos = isset($_POST['lote_cursos']) ? (array) $_POST['lote_cursos'] : [];
            $lote_trilhas = isset($_POST['lote_trilhas']) ? (array) $_POST['lote_trilhas'] : [];
            $lote_acao = isset($_POST['lote_acao']) ? sanitize_text_field($_POST['lote_acao']) : 'add';
            $data_fim = !empty($_POST['lote_data_fim']) ? sanitize_text_field($_POST['lote_data_fim']) . ' 23:59:59' : null;

            if (!empty($user_ids) && (!empty($lote_cursos) || !empty($lote_trilhas))) {
                $user_ids = array_map('intval', $user_ids);
                $manager_id = get_current_user_id();
                $cursos_para_processar = $lote_cursos; // Inicia com cursos individuais marcados

                // Acumula cursos oriundos das trilhas selecionadas
                foreach ($lote_trilhas as $trilha_id) {
                    if ($trilha_id > 0) {
                        $cursos_trilha = get_posts([
                            'post_type' => 'curso',
                            'posts_per_page' => -1,
                            'meta_query' => [
                                [
                                    'key' => 'trilha',
                                    'value' => (string) $trilha_id,
                                    'compare' => '='
                                ]
                            ],
                            'fields' => 'ids'
                        ]);
                        if (!empty($cursos_trilha)) {
                            $cursos_para_processar = array_merge($cursos_para_processar, $cursos_trilha);
                        }
                    }
                }

                // Remove possíveis IDs duplicados na junção
                $cursos_para_processar = array_unique(array_filter(array_map('intval', $cursos_para_processar)));

                if (!empty($cursos_para_processar)) {
                    foreach ($user_ids as $uid) {
                        foreach ($cursos_para_processar as $cid) {
                            if ($lote_acao === 'remove') {
                                self::revoke_access($uid, $cid, $manager_id);
                            } else {
                                self::grant_access($uid, $cid, $data_fim, $manager_id);
                            }
                        }
                    }
                }

                wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&msg=matricula_lote_success'));
                exit;
            }
        }

        // Matrícula em Massa por Trilha
        if (isset($_POST['matricular_trilha']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_matricular_trilha')) {
            $user_id = (int) $_POST['user_id'];
            $trilha_id = (int) $_POST['trilha_id'];
            $data_fim = !empty($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) . ' 23:59:59' : null;

            if ($user_id > 0 && $trilha_id > 0) {
                $cursos = get_posts([
                    'post_type' => 'curso',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        [
                            'key' => 'trilha',
                            'value' => (string) $trilha_id,
                            'compare' => '=',
                        ]
                    ],
                    'fields' => 'ids'
                ]);

                if (!empty($cursos)) {
                    foreach ($cursos as $curso_id) {
                        self::grant_access($user_id, $curso_id, $data_fim, get_current_user_id());
                    }
                    wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=trilha_matriculada'));
                } else {
                    wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=trilha_vazia'));
                }
                exit;
            }
        }

        // Salvar Grupos do Aluno (Detalhes)
        if (isset($_POST['save_student_groups']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_save_groups')) {
            $user_id = (int) $_POST['user_id'];
            $grupos = isset($_POST['aluno_grupos']) ? (array) $_POST['aluno_grupos'] : [];
            $grupos = array_map('intval', $grupos);

            if ($user_id > 0) {
                $current_groups = get_user_meta($user_id, '_aluno_grupos', true);
                if (!is_array($current_groups)) {
                    $current_groups = [];
                }

                // Calculate diffs
                $added = array_diff($grupos, $current_groups);
                $removed = array_diff($current_groups, $grupos);

                update_user_meta($user_id, '_aluno_grupos', $grupos);

                // Log Actions
                $current_user_id = get_current_user_id();

                foreach ($added as $group_id) {
                    $group_title = get_the_title($group_id);
                    self::log_access_action($user_id, 0, 'grupo_entrou', $current_user_id, [
                        'group_id' => $group_id,
                        'group_name' => $group_title
                    ]);
                }

                foreach ($removed as $group_id) {
                    $group_title = get_the_title($group_id);
                    self::log_access_action($user_id, 0, 'grupo_saiu', $current_user_id, [
                        'group_id' => $group_id,
                        'group_name' => $group_title
                    ]);
                }

                wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=grupos_atualizados'));
                exit;
            }
        }

        // Limpeza de grupos órfãos
        if (isset($_POST['cleanup_orphaned_groups']) && wp_verify_nonce($_POST['_wpnonce'], 'cleanup_orphaned_groups')) {
            $result = self::cleanup_orphaned_group_references();

            $stats = base64_encode(json_encode($result));
            wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&msg=cleanup_success&stats=' . $stats));
            exit;
        }

        // Atualizar Dados do Aluno (Detalhes)
        if (isset($_POST['update_student_data']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_update_data')) {
            $user_id = (int) $_POST['user_id'];

            if ($user_id > 0 && current_user_can('edit_user', $user_id)) {
                // Atualizar Senha (se fornecida)
                if (!empty($_POST['new_password'])) {
                    wp_update_user([
                        'ID' => $user_id,
                        'user_pass' => $_POST['new_password']
                    ]);
                }

                // Campos para atualizar
                $fields = [
                    'billing_phone',
                    'phone',
                    'instagram',
                    'cpf',
                    'aniversario',
                    'cep',
                    'rua',
                    'numero',
                    'complemento',
                    'bairro',
                    'cidade',
                    'estado'
                ];

                foreach ($fields as $field) {
                    if (isset($_POST[$field])) {
                        update_user_meta($user_id, $field, sanitize_text_field($_POST[$field]));
                    }
                }

                // Tratamento especial para Billing Phone -> Phone syncing
                if (isset($_POST['billing_phone'])) {
                    update_user_meta($user_id, 'phone', sanitize_text_field($_POST['billing_phone']));
                }

                // Salvar Notas do CRM (Apenas Admin)
                if (isset($_POST['crm_notes'])) {
                    // Sem sanitização excessiva para permitir quebras de linha, mas protegendo contra XSS básico se necessário.
                    // O wp_kses_post permitiria HTML básico, mas vamos usar sanitize_textarea_field para texto puro.
                    update_user_meta($user_id, '_crm_private_notes', sanitize_textarea_field($_POST['crm_notes']));
                }

                wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=dados_atualizados'));
                exit;
            }
        }

        // Solicitar redefinicao de senha para aluno
        if (isset($_POST['request_student_password_reset']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_request_password_reset')) {
            $user_id = (int) $_POST['user_id'];

            if ($user_id > 0 && current_user_can('edit_user', $user_id)) {
                $result = self::send_student_password_reset_email($user_id);
                $msg = is_wp_error($result) ? 'reset_senha_erro' : 'reset_senha_enviado';
            } else {
                $msg = 'reset_senha_erro';
            }

            wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=' . $msg));
            exit;
        }

        // Resetar tentativas de Quiz
        if (isset($_POST['reset_quiz_attempts']) && wp_verify_nonce($_POST['_wpnonce'], 'aluno_reset_quiz_attempts')) {
            $user_id = (int) $_POST['user_id'];
            $aula_id = (int) $_POST['aula_id'];

            if ($user_id > 0 && $aula_id > 0 && current_user_can('edit_user', $user_id)) {
                delete_user_meta($user_id, '_quiz_attempts_' . $aula_id);
                // Opcional: deletar meta de quiz_score se quiser limpar a nota também, mas geralmente resetar tentativas é suficiente para tentar de novo.
                // delete_user_meta($user_id, '_quiz_score_' . $aula_id);
                // Também é bom limpar o status de "concluído" da aula se for requisito
                // Mas o sistema de quiz atual bloqueia baseado em tentativas, então resetar tentativas libera.

                $msg = 'tentativas_resetadas';
            } else {
                $msg = 'erro_reset_tentativas';
            }

            wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=' . $msg));
            exit;
        }
    }

    public function render_admin_page()
    {
        $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';

        switch ($action) {
            case 'view':
                $this->render_details_page();
                break;
            default:
                $this->render_list_page();
                break;
        }
    }

    public function render_list_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        // Filtros
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $filter_curso = isset($_GET['filter_curso']) ? (int) $_GET['filter_curso'] : 0;
        $filter_status_acesso = isset($_GET['filter_status_acesso']) ? sanitize_text_field($_GET['filter_status_acesso']) : '';
        $filter_role = isset($_GET['filter_role']) ? sanitize_text_field($_GET['filter_role']) : '';

        // Paginação
        $per_page = 30;
        $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
        $offset = ($current_page - 1) * $per_page;

        // Query de usuários
        $user_args = [
            'number' => $per_page,
            'offset' => $offset,
            'orderby' => 'registered',
            'order' => 'DESC'
        ];

        if ($search) {
            $user_args['search'] = '*' . $search . '*';
            $user_args['search_columns'] = ['user_login', 'user_email', 'display_name', 'ID'];
        }

        if ($filter_role) {
            $user_args['role'] = $filter_role;
        }

        // Filtrar por curso específico
        if ($filter_curso > 0) {
            $user_ids_with_curso = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT user_id FROM $table_name WHERE curso_id = %d" .
                ($filter_status_acesso ? " AND status = %s" : ""),
                $filter_status_acesso ? [$filter_curso, $filter_status_acesso] : $filter_curso
            ));

            if (!empty($user_ids_with_curso)) {
                $user_args['include'] = $user_ids_with_curso;
            } else {
                $user_args['include'] = [0];
            }
        }

        $users_query = new WP_User_Query($user_args);
        $users = $users_query->get_results();
        $total_users = $users_query->get_total();
        $total_pages = ceil($total_users / $per_page);

        // Buscar cursos para o filtro
        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        // Buscar roles editáveis
        global $wp_roles;
        if (!isset($wp_roles)) {
            $wp_roles = new WP_Roles();
        }
        $editable_roles = apply_filters('editable_roles', $wp_roles->roles);

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Lista de Alunos</h1>

            <?php if (isset($_GET['msg'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['msg']) {
                            case 'updated':
                                echo 'Aluno atualizado com sucesso!';
                                break;
                            case 'data_atualizada':
                                echo 'Data de expiração atualizada com sucesso!';
                                break;
                            case 'matricula_lote_success':
                                echo 'Alunos matriculados com sucesso!';
                                break;
                            case 'cleanup_success':
                                $stats = isset($_GET['stats']) ? json_decode(base64_decode($_GET['stats']), true) : [];
                                echo sprintf(
                                    '🧹 <strong>Limpeza concluída com sucesso!</strong> %d cursos, %d trilhas e %d usuários foram atualizados.',
                                    isset($stats['cursos']) ? (int) $stats['cursos'] : 0,
                                    isset($stats['trilhas']) ? (int) $stats['trilhas'] : 0,
                                    isset($stats['usuarios']) ? (int) $stats['usuarios'] : 0
                                );
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Painel de Manutenção -->
            <details style="margin: 15px 0; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
                <summary style="padding: 12px 15px; cursor: pointer; font-weight: 600; color: #1d2327;">
                    🔧 Manutenção do Sistema
                </summary>
                <div style="padding: 15px; border-top: 1px solid #eee;">
                    <p style="margin-top: 0;">
                        Limpe referências a grupos que foram deletados mas ainda estão associados a cursos, trilhas ou alunos.
                        <br><small style="color: #666;">Isso corrige o bug onde alunos aparecem com "Utilizando Grupo:"
                            vazio.</small>
                    </p>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('cleanup_orphaned_groups'); ?>
                        <button type="submit" name="cleanup_orphaned_groups" value="1" class="button"
                            onclick="return confirm('Deseja realmente limpar as referências órfãs?\n\nIsso removerá grupos deletados de cursos, trilhas e usuários.\nEsta ação não pode ser desfeita.');">
                            🧹 Executar Limpeza de Grupos Órfãos
                        </button>
                    </form>
                </div>
            </details>

            <!-- Filtros -->
            <form method="get" style="margin: 20px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <input type="hidden" name="page" value="acesso-cursos-alunos">

                <input type="search" name="s" placeholder="Buscar por nome, email ou ID"
                    value="<?php echo esc_attr($search); ?>" style="min-width: 250px;">

                <select name="filter_curso">
                    <option value="">Todos os cursos</option>
                    <?php foreach ($cursos as $curso): ?>
                        <option value="<?php echo $curso->ID; ?>" <?php selected($filter_curso, $curso->ID); ?>>
                            <?php echo esc_html($curso->post_title); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_role">
                    <option value="">Todas as roles</option>
                    <?php foreach ($editable_roles as $role_key => $details): ?>
                        <option value="<?php echo esc_attr($role_key); ?>" <?php selected($filter_role, $role_key); ?>>
                            <?php echo translate_user_role($details['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="filter_status_acesso">
                    <option value="">Qualquer status</option>
                    <option value="ativo" <?php selected($filter_status_acesso, 'ativo'); ?>>Com acesso ativo</option>
                    <option value="suspenso" <?php selected($filter_status_acesso, 'suspenso'); ?>>Suspenso</option>
                    <option value="revogado" <?php selected($filter_status_acesso, 'revogado'); ?>>Revogado</option>
                </select>

                <button type="submit" class="button">Filtrar</button>
                <a href="<?php echo admin_url('admin.php?page=acesso-cursos-alunos'); ?>" class="button">Limpar</a>
            </form>

            <p class="description">Total: <strong>
                    <?php echo $total_users; ?>
                </strong> alunos encontrados</p>

            <form method="post" id="form-matricula-lote">
                <?php wp_nonce_field('aluno_matricula_lote'); ?>
                <div class="alignleft actions bulkactions" style="margin-bottom: 10px;">
                    <button type="button" class="button action" onclick="abrirModalMatriculaLote()">Matricular
                        Selecionados</button>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column" style="width: 2.2em; padding: 8px 10px;">
                                <input id="cb-select-all-1" type="checkbox" onchange="toggleAllCheckboxes(this)">
                            </td>
                            <th style="width: 50px;">ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Cadastro</th>
                            <th>Último Login</th>
                            <th>Grupos</th>
                            <th style="width: 120px;">Cursos Ativos</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8">Nenhum aluno encontrado.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user):
                                $cursos_ativos = $wpdb->get_var($wpdb->prepare(
                                    "SELECT COUNT(*) FROM $table_name 
                                 WHERE user_id = %d AND status = 'ativo' 
                                 AND (data_fim IS NULL OR data_fim >= NOW())",
                                    $user->ID
                                ));

                                $last_login = get_user_meta($user->ID, 'last_login', true);
                                if (!$last_login) {
                                    $last_login = get_user_meta($user->ID, '_last_login', true);
                                }
                                ?>
                                <tr>
                                    <th scope="row" class="check-column" style="padding: 8px 10px;">
                                        <input type="checkbox" name="user_ids[]" value="<?php echo $user->ID; ?>"
                                            class="aluno-checkbox">
                                    </th>
                                    <td><strong>#
                                            <?php echo $user->ID; ?>
                                        </strong></td>
                                    <td>
                                        <strong>
                                            <?php echo esc_html($user->display_name); ?>
                                        </strong>
                                        <?php if ($user->first_name || $user->last_name): ?>
                                            <br><small style="color: #666;">
                                                <?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                            <?php echo esc_html($user->user_email); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($user->user_registered)); ?>
                                    </td>
                                    <td>
                                        <?php if ($last_login): ?>
                                            <?php echo date('d/m/Y H:i', strtotime($last_login)); ?>
                                        <?php else: ?>
                                            <em style="color: #999;">—</em>
                                        <?php endif; ?>

                                    </td>
                                    <td>
                                        <?php
                                        $user_grupos = get_user_meta($user->ID, '_aluno_grupos', true);
                                        if (!empty($user_grupos) && is_array($user_grupos)) {
                                            $grupos_titles = [];
                                            foreach ($user_grupos as $g_id) {
                                                $g_title = get_the_title($g_id);
                                                if ($g_title) {
                                                    $grupos_titles[] = $g_title;
                                                }
                                            }
                                            if (!empty($grupos_titles)) {
                                                echo implode(', ', array_map('esc_html', $grupos_titles));
                                            } else {
                                                echo '<span style="color:#999;">—</span>';
                                            }
                                        } else {
                                            echo '<span style="color:#999;">—</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($cursos_ativos > 0): ?>
                                            <span
                                                style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                                <?php echo $cursos_ativos; ?> curso
                                                <?php echo $cursos_ativos > 1 ? 's' : ''; ?>
                                            </span>
                                        <?php else: ?>
                                            <span
                                                style="background: #e5e7eb; color: #666; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                                Nenhum
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a
                                            href="<?php echo admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user->ID); ?>">Ver
                                            Detalhes</a> |
                                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>">Editar Perfil</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                    <div class="tablenav bottom">
                        <div class="tablenav-pages">
                            <?php
                            echo paginate_links([
                                'base' => add_query_arg('paged', '%#%'),
                                'format' => '',
                                'current' => $current_page,
                                'total' => $total_pages
                            ]);
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </form> <!-- Fecha o form da listagem -->

            <!-- Modal de Ações em Lote -->
            <div id="modal-matricula-lote"
                style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; align-items:center; justify-content:center;">
                <div style="background:#fff; padding:20px; border-radius:4px; min-width:450px; max-width:90%;">
                    <h2 style="margin-top:0;">Ações em Lote para Alunos Selecionados</h2>

                    <div style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px;"><strong>Qual Ação Realizar?</strong></label>
                        <div style="display: flex; gap: 15px;">
                            <label><input type="radio" name="lote_acao_modal" value="add" checked
                                    onchange="document.getElementById('div-data-fim').style.display='block'"> Matricular</label>
                            <label><input type="radio" name="lote_acao_modal" value="remove"
                                    onchange="document.getElementById('div-data-fim').style.display='none'">
                                Desmatricular</label>
                        </div>
                    </div>

                    <div style="display: flex; gap: 20px; margin-bottom: 15px;">
                        <div style="flex: 1;">
                            <label style="display:block; margin-bottom:5px;"><strong>Cursos:</strong></label>
                            <div
                                style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; border-radius: 4px; background: #fafafa;">
                                <?php foreach ($cursos as $curso): ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" class="lote-curso-cb" value="<?php echo $curso->ID; ?>">
                                        <?php echo esc_html($curso->post_title); ?>
                                    </label>
                                <?php endforeach; ?>
                                <?php if (empty($cursos)):
                                    echo '<em>Nenhum curso disponível.</em>';
                                endif; ?>
                            </div>
                        </div>

                        <div style="flex: 1;">
                            <label style="display:block; margin-bottom:5px;"><strong>Trilhas:</strong></label>
                            <div
                                style="max-height: 150px; overflow-y: auto; border: 1px solid #ccd0d4; padding: 10px; border-radius: 4px; background: #fafafa;">
                                <?php
                                $trilhas_lote = get_posts(['post_type' => 'trilha', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC']);
                                foreach ($trilhas_lote as $trilha): ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" class="lote-trilha-cb" value="<?php echo $trilha->ID; ?>">
                                        <?php echo esc_html($trilha->post_title); ?>
                                    </label>
                                <?php endforeach; ?>
                                <?php if (empty($trilhas_lote)):
                                    echo '<em>Nenhuma trilha disponível.</em>';
                                endif; ?>
                            </div>
                        </div>
                    </div>

                    <div id="div-data-fim" style="margin-bottom: 15px;">
                        <label style="display:block; margin-bottom:5px;"><strong>Expira em (opcional):</strong></label>
                        <input type="date" id="lote-data-fim" style="width: 100%;">
                        <p class="description">Válido apenas para matrículas. Deixe em branco para acesso vitalício.</p>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" class="button"
                            onclick="document.getElementById('modal-matricula-lote').style.display='none'">Cancelar</button>
                        <button type="button" class="button button-primary" onclick="confirmarAcaoLote()">Confirmar Ação em
                            Lote</button>
                    </div>
                </div>
            </div>

            <script>
                function toggleAllCheckboxes(source) {
                    const checkboxes = document.querySelectorAll('.aluno-checkbox');
                    for (let i = 0; i < checkboxes.length; i++) {
                        checkboxes[i].checked = source.checked;
                    }
                }

                function abrirModalMatriculaLote() {
                    const selecionados = document.querySelectorAll('.aluno-checkbox:checked');
                    if (selecionados.length === 0) {
                        alert('Por favor, selecione pelo menos um aluno.');
                        return;
                    }
                    document.getElementById('modal-matricula-lote').style.display = 'flex';
                }

                function confirmarAcaoLote() {
                    const form = document.getElementById('form-matricula-lote');
                    const acaoRadio = document.querySelector('input[name="lote_acao_modal"]:checked');

                    const cursosCheckbox = document.querySelectorAll('.lote-curso-cb:checked');
                    const trilhasCheckbox = document.querySelectorAll('.lote-trilha-cb:checked');
                    const dataFim = document.getElementById('lote-data-fim').value;

                    if (cursosCheckbox.length === 0 && trilhasCheckbox.length === 0) {
                        alert('Você deve selecionar pelo menos um curso ou uma trilha.');
                        return;
                    }

                    // Adicionar o input base de autorizacao e ação
                    let inputContexto = document.createElement('input');
                    inputContexto.type = 'hidden';
                    inputContexto.name = 'matricular_lote';
                    inputContexto.value = '1';
                    form.appendChild(inputContexto);

                    let inputAcao = document.createElement('input');
                    inputAcao.type = 'hidden';
                    inputAcao.name = 'lote_acao';
                    inputAcao.value = acaoRadio ? acaoRadio.value : 'add';
                    form.appendChild(inputAcao);

                    // Adicionar Cursos multi-seleção
                    cursosCheckbox.forEach(function (cb) {
                        let inputCurso = document.createElement('input');
                        inputCurso.type = 'hidden';
                        inputCurso.name = 'lote_cursos[]';
                        inputCurso.value = cb.value;
                        form.appendChild(inputCurso);
                    });

                    // Adicionar Trilhas multi-seleção
                    trilhasCheckbox.forEach(function (cb) {
                        let inputTrilha = document.createElement('input');
                        inputTrilha.type = 'hidden';
                        inputTrilha.name = 'lote_trilhas[]';
                        inputTrilha.value = cb.value;
                        form.appendChild(inputTrilha);
                    });

                    if (dataFim) {
                        let inputData = document.createElement('input');
                        inputData.type = 'hidden';
                        inputData.name = 'lote_data_fim';
                        inputData.value = dataFim;
                        form.appendChild(inputData);
                    }

                    form.submit();
                }
            </script>

        </div>
        <?php
    }

    public function render_details_page()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'acesso_cursos';

        $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
        $user = get_user_by('ID', $user_id);

        if (!$user) {
            echo '<div class="wrap"><div class="notice notice-error"><p>Aluno não encontrado.</p></div></div>';
            return;
        }

        $acessos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
            $user_id
        ));

        $cursos = get_posts([
            'post_type' => 'curso',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $trilhas = get_posts([
            'post_type' => 'trilha',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $acessos_map = [];
        foreach ($acessos as $acesso) {
            $acessos_map[$acesso->curso_id] = $acesso;
        }

        $last_login = get_user_meta($user_id, 'last_login', true) ?: get_user_meta($user_id, '_last_login', true);
        $phone = get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'phone', true);
        $cpf = get_user_meta($user_id, 'cpf', true);
        $aniversario = get_user_meta($user_id, 'aniversario', true);
        $instagram = get_user_meta($user_id, 'instagram', true);
        $cep = get_user_meta($user_id, 'cep', true);
        $rua = get_user_meta($user_id, 'rua', true);
        $numero = get_user_meta($user_id, 'numero', true);
        $complemento = get_user_meta($user_id, 'complemento', true);
        $bairro = get_user_meta($user_id, 'bairro', true);
        $cidade = get_user_meta($user_id, 'cidade', true);
        $estado = get_user_meta($user_id, 'estado', true);

        // CRM Notes
        $crm_notes = get_user_meta($user_id, '_crm_private_notes', true);

        // Login History for Security
        $login_history = get_user_meta($user_id, '_login_history', true);
        if (!is_array($login_history)) {
            $login_history = [];
        }

        $empty_placeholder = '<em style="color:#999;">Não informado</em>';
        $format_meta = function ($value) use ($empty_placeholder) {
            $value = is_string($value) ? trim($value) : $value;
            return ($value !== '' && $value !== null) ? esc_html((string) $value) : $empty_placeholder;
        };

        // Engagement Data
        $engagement_data = self::get_engagement_data($user_id);


        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <a href="<?php echo admin_url('admin.php?page=acesso-cursos-alunos'); ?>" style="text-decoration: none;">← </a>
                Detalhes do Aluno
            </h1>

            <?php if (isset($_GET['msg'])): ?>
                <div
                    class="notice <?php echo ($_GET['msg'] === 'reset_senha_erro') ? 'notice-error' : 'notice-success'; ?> is-dismissible">
                    <p>
                        <?php
                        switch ($_GET['msg']) {
                            case 'acesso_atualizado':
                                echo 'Acesso atualizado com sucesso!';
                                break;
                            case 'acesso_concedido':
                                echo 'Acesso concedido com sucesso!';
                                break;
                            case 'trilha_matriculada':
                                echo 'Matrícula na trilha realizada com sucesso!';
                                break;
                            case 'trilha_vazia':
                                echo 'A trilha selecionada não possui cursos.';
                                break;
                            case 'grupos_atualizados':
                                echo 'Grupos do aluno atualizados com sucesso!';
                                break;
                            case 'dados_atualizados':
                                echo 'Dados cadastrais e senha atualizados com sucesso!';
                                break;
                            case 'reset_senha_enviado':
                                echo 'E-mail de redefinicao enviado para o aluno.';
                                break;
                            case 'reset_senha_erro':
                                echo 'Nao foi possivel enviar o e-mail de redefinicao.';
                                break;
                            case 'tentativas_resetadas':
                                echo 'Tentativas de quiz resetadas com sucesso!';
                                break;
                            case 'erro_reset_tentativas':
                                echo 'Erro ao resetar tentativas. Verifique permissões.';
                                break;
                        }
                        ?>
                    </p>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
                <div
                    style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1; min-width: 300px;">
                    <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                        <?php echo get_avatar($user->ID, 48, '', '', ['style' => 'vertical-align: middle; margin-right: 10px; border-radius: 50%;']); ?>
                        <?php echo esc_html($user->first_name ? $user->first_name : $user->display_name); ?>
                    </h2>

                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th style="padding: 8px 0; width: 120px;">ID</th>
                            <td style="padding: 8px 0;"><strong>#
                                    <?php echo $user->ID; ?>
                                </strong></td>
                        </tr>
                        <tr>
                            <th style="padding: 8px 0;">Email</th>
                            <td style="padding: 8px 0;">
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                        </tr>
                        <?php if ($user->first_name || $user->last_name): ?>
                            <tr>
                                <th style="padding: 8px 0;">Nome Completo</th>
                                <td style="padding: 8px 0;">
                                    <?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php if ($phone): ?>
                            <tr>
                                <th style="padding: 8px 0;">Telefone</th>
                                <td style="padding: 8px 0;">
                                    <?php echo esc_html($phone); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                        <tr>
                            <th style="padding: 8px 0;">Cadastrado em</th>
                            <td style="padding: 8px 0;">
                                <?php echo date('d/m/Y H:i', strtotime($user->user_registered)); ?>
                            </td>
                        </tr>
                        <tr>
                            <th style="padding: 8px 0;">Último Login</th>
                            <td style="padding: 8px 0;">
                                <?php echo $last_login ? date('d/m/Y H:i', strtotime($last_login)) : '<em style="color:#999;">Não registrado</em>'; ?>
                            </td>
                        </tr>
                    </table>

                    <div
                        style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee; display: flex; flex-direction: column; align-items: flex-start; gap: 10px;">
                        <button type="button" class="button"
                            onclick="document.getElementById('modal-dados-cadastrais').style.display='flex'">Ver Dados
                            Completos</button>
                        <button type="button" class="button"
                            onclick="document.getElementById('modal-alterar-senha').style.display='flex'">Alterar Senha</button>
                        <form method="post" style="margin: 0;">
                            <?php wp_nonce_field('aluno_request_password_reset'); ?>
                            <input type="hidden" name="request_student_password_reset" value="1">
                            <input type="hidden" name="user_id" value="<?php echo (int) $user->ID; ?>">
                            <button type="submit" class="button">Solicitar redefinicao</button>
                        </form>
                        <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button">Editar Perfil
                            Completo</a>
                    </div>
                </div>

                <!-- CRM / Notas Internas -->
                <div
                    style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1; min-width: 300px;">
                    <h3 style="margin-top: 0; display:flex; align-items:center; gap:8px;">
                        📝 Notas Internas (CRM)
                        <span class="dashicons dashicons-lock" style="font-size:16px; width:16px; height:16px; color:#666;"
                            title="Visível apenas para administradores"></span>
                    </h3>
                    <form method="post">
                        <?php wp_nonce_field('aluno_update_data', '_wpnonce'); ?>
                        <input type="hidden" name="update_student_data" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">

                        <textarea name="crm_notes" rows="6"
                            style="width:100%; margin-bottom:10px; font-family:monospace; background:#fafafa; border:1px solid #ddd;"
                            placeholder="Escreva observações internas sobre o aluno aqui... (Ex: Solicitou prorrogação, contato via WhatsApp, etc)"><?php echo esc_textarea($crm_notes); ?></textarea>

                        <button type="submit" class="button button-primary">Salvar Anotações</button>
                    </form>
                </div>

                <!-- Segurança e Histórico de Login -->
                <div
                    style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 300px; max-width: 400px;">
                    <h3 style="margin-top: 0; display:flex; align-items:center; gap:8px;">
                        🛡️ Segurança
                    </h3>

                    <?php
                    // Detecção Básica de Compartilhamento (Múltiplos IPs recentes)
                    $unique_ips_recent = [];
                    // Usa current_time('timestamp') para comparar com o mesmo referencial dos logs
                    $recent_time_limit = current_time('timestamp') - DAY_IN_SECONDS;
                    foreach ($login_history as $log) {
                        // Suporte a registros antigos (string MySQL) e novos (timestamp int)
                        $log_time = is_int($log['time']) ? $log['time'] : strtotime($log['time']);
                        if ($log_time > $recent_time_limit) {
                            $unique_ips_recent[$log['ip']] = true;
                        }
                    }
                    if (count($unique_ips_recent) > 1) {
                        echo '<div class="notice notice-warning inline" style="margin:0 0 15px 0; border-left-color: #f59e0b;"><p><strong>⚠️ Alerta de Segurança:</strong> Este usuário acessou de <strong>' . count($unique_ips_recent) . ' Endereços IP diferentes</strong> nas últimas 24 horas.</p></div>';
                    }
                    ?>

                    <p style="margin-bottom:10px;"><strong>Últimos Acessos:</strong></p>

                    <?php if (empty($login_history)): ?>
                        <p style="color:#999; font-style:italic;">Nenhum registro de login recente.</p>
                    <?php else: ?>
                        <div style="max-height: 200px; overflow-y: auto; border:1px solid #eee; border-radius:4px;">
                            <table class="wp-list-table widefat fixed striped" style="border:none;">
                                <thead>
                                    <tr>
                                        <th>Data/Hora</th>
                                        <th>IP</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($login_history, 0, 10) as $log):
                                        // Suporte a registros antigos (string MySQL) e novos (timestamp int)
                                        $log_ts = is_int($log['time']) ? $log['time'] : strtotime($log['time']);
                                        $time_str = wp_date('d/m/Y H:i', $log_ts); // wp_date respeita o timezone configurado no WordPress
                                        $ip = $log['ip'];
                                        $ua = isset($log['ua']) ? $log['ua'] : '';

                                        // Simple brower detection for tooltip
                                        $browser = 'Desconhecido';
                                        if (strpos($ua, 'Chrome') !== false)
                                            $browser = 'Chrome';
                                        elseif (strpos($ua, 'Firefox') !== false)
                                            $browser = 'Firefox';
                                        elseif (strpos($ua, 'Safari') !== false)
                                            $browser = 'Safari';
                                        elseif (strpos($ua, 'Edge') !== false)
                                            $browser = 'Edge';
                                        ?>
                                        <tr>
                                            <td><?php echo $time_str; ?></td>
                                            <td title="<?php echo esc_attr($ua); ?>">
                                                <?php echo esc_html($ip); ?>
                                                <br><small style="color:#999;"><?php echo $browser; ?></small>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <p class="description" style="margin-top:5px;">Mostrando os últimos 10 acessos.</p>
                    <?php endif; ?>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 250px;">
                    <h3 style="margin-top: 0;">Grupos do Aluno</h3>
                    <form method="post" action="">
                        <?php wp_nonce_field('aluno_save_groups', '_wpnonce'); ?>
                        <input type="hidden" name="save_student_groups" value="1">
                        <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">

                        <?php
                        // Buscar todos os grupos
                        $all_grupos = get_posts([
                            'post_type' => 'grupo',
                            'posts_per_page' => -1,
                            'orderby' => 'title',
                            'order' => 'ASC'
                        ]);
                        $user_grupos = get_user_meta($user->ID, '_aluno_grupos', true);
                        if (!is_array($user_grupos))
                            $user_grupos = [];
                        ?>

                        <div
                            style="max-height: 200px; overflow-y: auto; border: 1px solid #eee; padding: 10px; margin-bottom: 10px;">
                            <?php if (empty($all_grupos)): ?>
                                <p style="color: #999;">Nenhum grupo cadastrado.</p>
                            <?php else: ?>
                                <?php foreach ($all_grupos as $grupo): ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" name="aluno_grupos[]" value="<?php echo $grupo->ID; ?>" <?php checked(in_array($grupo->ID, $user_grupos)); ?>>
                                        <?php echo esc_html($grupo->post_title); ?>
                                    </label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="button button-primary" style="width: 100%;">Salvar Grupos</button>
                    </form>
                </div>

                <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; min-width: 200px;">
                    <h3 style="margin-top: 0;">Resumo de Acessos</h3>
                    <?php
                    $total_ativos = 0;
                    $total_suspensos = 0;
                    $total_revogados = 0;
                    $total_expirados = 0;
                    foreach ($acessos as $a) {
                        if ($a->status === 'ativo') {
                            if ($a->data_fim && strtotime($a->data_fim) < time()) {
                                $total_expirados++;
                            } else {
                                $total_ativos++;
                            }
                        } elseif ($a->status === 'suspenso') {
                            $total_suspensos++;
                        } else {
                            $total_revogados++;
                        }
                    }
                    ?>
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Ativos</span>
                            <span
                                style="background: #22c55e; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                                <?php echo $total_ativos; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Expirados</span>
                            <span
                                style="background: #f59e0b; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                                <?php echo $total_expirados; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Suspensos</span>
                            <span
                                style="background: #6b7280; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                                <?php echo $total_suspensos; ?>
                            </span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Revogados</span>
                            <span
                                style="background: #ef4444; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                                <?php echo $total_revogados; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Gráfico de Engajamento e Certificados -->
            <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">

                <!-- Gráfico -->
                <div
                    style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 2; min-width: 300px;">
                    <h3 style="margin-top: 0;">📊 Engajamento (Últimos 30 dias)</h3>
                    <p class="description">Quantidade de aulas concluídas por dia.</p>

                    <div style="display: flex; align-items: flex-end; height: 150px; gap: 4px; padding-top: 20px;">
                        <?php
                        $max_count = max($engagement_data) ?: 1; // Avoid division by zero
                        foreach ($engagement_data as $date => $count):
                            $height_percent = ($count / $max_count) * 100;
                            $height_percent = max($height_percent, 2); // Min height for visibility
                            $color = $count > 0 ? '#3b82f6' : '#e5e7eb';
                            $title = date('d/m', strtotime($date)) . ': ' . $count . ' aulas';
                            ?>
                            <div style="flex: 1; height: 100%; display: flex; flex-direction: column; justify-content: flex-end; align-items: center;"
                                title="<?php echo esc_attr($title); ?>">
                                <div
                                    style="width: 100%; height: <?php echo $height_percent; ?>%; background: <?php echo $color; ?>; border-radius: 2px 2px 0 0; transition: height 0.3s;">
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 10px; color: #999;">
                        <span><?php echo date('d/m', strtotime('-30 days')); ?></span>
                        <span>Hoje</span>
                    </div>
                </div>

                <!-- Certificados -->
                <div
                    style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1; min-width: 250px;">
                    <h3 style="margin-top: 0;">🏆 Certificados Conquistados</h3>
                    <?php
                    // Encontrar cursos 100% concluídos
                    $certificados_conquistados = [];
                    global $wpdb;
                    $meta_rows = $wpdb->get_results($wpdb->prepare(
                        "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE user_id = %d AND meta_key LIKE 'progresso_curso_%'",
                        $user_id
                    ));

                    foreach ($meta_rows as $row) {
                        if ($row->meta_value >= 100) {
                            $c_id = (int) str_replace('progresso_curso_', '', $row->meta_key);
                            if (get_post_status($c_id) === 'publish') {
                                $certificados_conquistados[] = $c_id;
                            }
                        }
                    }

                    if (empty($certificados_conquistados)):
                        ?>
                        <p style="color: #999;">Nenhum certificado emitido ainda.</p>
                    <?php else: ?>
                        <ul style="list-style: none; margin: 0; padding: 0;">
                            <?php foreach ($certificados_conquistados as $c_id):
                                $c_title = get_the_title($c_id);
                                $progress = self::get_detailed_progress($user_id, $c_id);
                                $data_conclusao = isset($progress['last_date']) ? date('d/m/Y', strtotime($progress['last_date'])) : 'Data desconhecida';

                                // Link de emissao para admin (funciona mesmo com progresso < 100%).
                                $cert_link = self::get_admin_certificate_link($user_id, $c_id);
                                ?>
                                <li
                                    style="margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #f0f0f1; display:flex; align-items:center; justify-content:space-between;">
                                    <div>
                                        <strong><?php echo esc_html($c_title); ?></strong>
                                        <br><small style="color:#666;">Concluído em: <?php echo $data_conclusao; ?></small>
                                    </div>
                                    <a href="<?php echo esc_url($cert_link); ?>" target="_blank" class="button button-small"
                                        title="Visualizar Certificado">
                                        <span class="dashicons dashicons-visibility" style="margin-top:2px;"></span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

            </div>
            <?php
            // Fetch History Logs
            $table_log = $wpdb->prefix . 'acesso_cursos_log';
            $history_logs = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM $table_log WHERE user_id = %d ORDER BY created_at DESC",
                $user_id
            ));

            // Group logs by Course for duration calculation logic if needed, 
            // but flat list is better for a timeline view.
            ?>
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Histórico de Matrículas e Acessos</h3>
                <p class="description">Registro detalhado de quando o acesso foi concedido, revogado ou suspenso.</p>

                <?php if (empty($history_logs)): ?>
                    <p style="color: #666; font-style: italic;">Nenhum histórico registrado para este aluno.</p>
                <?php else: ?>
                    <div style="max-height: 300px; overflow-y: auto;">
                        <table class="wp-list-table widefat fixed striped" style="box-shadow: none; border: 1px solid #e5e7eb;">
                            <thead>
                                <tr>
                                    <th style="width: 15px;"></th> <!-- Status color indicator -->
                                    <th>Curso</th>
                                    <th>Ação</th>
                                    <th>Data</th>
                                    <th>Realizado por</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history_logs as $log):
                                    $curso_title = '';
                                    if ($log->curso_id == 0) {
                                        $details_arr = json_decode($log->details, true);
                                        if (isset($details_arr['group_name'])) {
                                            $curso_title = '<strong>Grupo:</strong> ' . esc_html($details_arr['group_name']);
                                        } else {
                                            $curso_title = 'Sistema / Geral';
                                        }
                                    } else {
                                        $curso_title = get_the_title($log->curso_id) ?: '(Curso Deletado ID: ' . $log->curso_id . ')';
                                        $curso_title = '<a href="' . get_edit_post_link($log->curso_id) . '" target="_blank">' . esc_html(strip_tags($curso_title)) . '</a>';
                                    }
                                    $actor_data = get_userdata($log->actor_id);
                                    $actor_name = $actor_data ? $actor_data->display_name : 'Sistema/Desconhecido';

                                    // Se for sistema e tiver origem nos detalhes, mostrar
                                    $details = $log->details ? json_decode($log->details, true) : [];
                                    if ($log->actor_id == 0 && isset($details['origin'])) {
                                        $actor_name = 'Sistema (' . esc_html($details['origin']) . ')';
                                    }

                                    // Status config
                                    $status_config = [
                                        'concedido' => ['color' => '#22c55e', 'label' => 'Acesso Concedido'],
                                        'revogado' => ['color' => '#ef4444', 'label' => 'Acesso Revogado'],
                                        'suspenso' => ['color' => '#f59e0b', 'label' => 'Acesso Suspenso'],
                                        'reativado' => ['color' => '#3b82f6', 'label' => 'Acesso Reativado'],
                                        'data_alterada' => ['color' => '#8b5cf6', 'label' => 'Data Alterada'],
                                    ];

                                    $config = isset($status_config[$log->action]) ? $status_config[$log->action] : ['color' => '#64748b', 'label' => ucfirst($log->action)];
                                    $details = $log->details ? json_decode($log->details, true) : [];

                                    // Calculate time enrolled if this is a 'revoked' event, find the previous 'granted'
                                    // (This is a simplified "time enrolled" calculation for display)
                                    $time_diff_display = '';
                                    if (in_array($log->action, ['revogado', 'suspenso'])) {
                                        // Find most recent 'concedido' or 'reativado' before this log for this course
                                        // using simple array search as we are iterating
                                        // Note: sophisticated duration calc might require dedicated query, 
                                        // but simple "since" logic serves the purpose here.
                                    }
                                    ?>
                                    <tr>
                                        <td style="padding: 0;">
                                            <div
                                                style="height: 100%; width: 5px; background-color: <?php echo $config['color']; ?>; height: 40px;">
                                            </div>
                                        </td>
                                        <td><strong><?php echo $curso_title; ?></strong></td>
                                        <td>
                                            <span style="color: <?php echo $config['color']; ?>; font-weight: 500;">
                                                <?php echo esc_html($config['label']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($log->created_at)); ?></td>
                                        <td>
                                            <?php echo esc_html($actor_name); ?>
                                            <small style="display:block; color: #999;">ID: <?php echo $log->actor_id; ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            if ($log->action === 'data_alterada' && (isset($details['antiga_data_fim']) || isset($details['nova_data_fim']))) {
                                                $antiga = !empty($details['antiga_data_fim']) ? date('d/m/Y', strtotime($details['antiga_data_fim'])) : 'Vitalício';
                                                $nova = !empty($details['nova_data_fim']) ? date('d/m/Y', strtotime($details['nova_data_fim'])) : 'Vitalício';
                                                echo '<small>De: <strong>' . $antiga . '</strong><br>Para: <strong>' . $nova . '</strong></small>';
                                            } elseif (!empty($details['data_fim'])) {
                                                echo '<small>Expira em: ' . date('d/m/Y', strtotime($details['data_fim'])) . '</small>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <h2 style="margin-top: 30px;">Formação e Progresso</h2>
            <p class="description">Acompanhe o desenvolvimento do aluno em cada curso matriculado.</p>

            <div
                style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;">
                <?php
                $cursos_com_acesso = array_filter($cursos, function ($c) use ($user_id) {
                    return self::has_access($user_id, $c->ID);
                });

                if (empty($cursos_com_acesso)): ?>
                    <div
                        style="grid-column: 1 / -1; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; text-align: center; color: #666;">
                        Nenhum curso ativo para este aluno.
                    </div>
                <?php else:
                    foreach ($cursos_com_acesso as $c):
                        $progresso = self::get_detailed_progress($user_id, $c->ID);
                        ?>
                        <div
                            style="background: #fff; border: 1px solid #ccd0d4; border-radius: 8px; padding: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                <h4 style="margin: 0; font-size: 15px; color: #1d2327;">
                                    <?php echo esc_html($c->post_title); ?>
                                </h4>
                                <span style="font-size: 18px; font-weight: 700; color: #22c55e;">
                                    <?php echo $progresso['percent']; ?>%
                                </span>
                            </div>

                            <div style="height: 8px; background: #f0f0f1; border-radius: 4px; overflow: hidden; margin-bottom: 10px;">
                                <div
                                    style="width: <?php echo $progresso['percent']; ?>%; height: 100%; background: #22c55e; border-radius: 4px; transition: width 0.3s ease;">
                                </div>
                            </div>

                            <div style="display: flex; justify-content: space-between; font-size: 13px; color: #64748b;">
                                <span>
                                    <?php echo $progresso['concluidas']; ?> de
                                    <?php echo $progresso['total']; ?> aulas
                                </span>
                                <span>
                                    <?php if ($progresso['percent'] >= 100): ?>
                                        <span
                                            style="background: #dcfce7; color: #166534; padding: 2px 8px; border-radius: 4px; font-weight: 600; font-size: 11px;">CONCLUÍDO</span>
                                    <?php else: ?>
                                        EM ANDAMENTO
                                    <?php endif; ?>
                                </span>
                            </div>

                            <?php if ($progresso['last_date']): ?>
                                <div
                                    style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #f1f5f9; font-size: 12px; color: #94a3b8;">
                                    🕒 Última aula:
                                    <?php echo date('d/m/Y H:i', strtotime($progresso['last_date'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach;
                endif; ?>
            </div>

            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin: 20px 0;">
                <h3 style="margin-top: 0;">Gestão de Tentativas de Quiz</h3>
                <p class="description">Abaixo estão listadas todas as avaliações disponíveis nos cursos que o aluno tem acesso.
                </p>

                <?php
                // 1. Identificar cursos que o aluno tem acesso
                $cursos_acessiveis_ids = [];
                foreach ($cursos as $curso) {
                    if (self::has_access($user_id, $curso->ID)) {
                        $cursos_acessiveis_ids[] = $curso->ID;
                    }
                }

                if (empty($cursos_acessiveis_ids)) {
                    echo '<p style="color: #666;">O aluno não tem acesso a nenhum curso com avaliações.</p>';
                } else {
                    // 2. Buscar todas as aulas com quiz desses cursos
                    // Usa get_course_lessons() para buscar aulas de cada curso (meta key = 'curso')
                    // e depois filtra via PHP com get_quiz_data() (pois _aula_quiz_data é array serializado)
                    $aulas_com_quiz = [];
                    foreach ($cursos_acessiveis_ids as $cid) {
                        $aulas_do_curso = self::get_course_lessons($cid);
                        foreach ($aulas_do_curso as $aula) {
                            if (class_exists('System_Cursos_Quiz_Process') && System_Cursos_Quiz_Process::get_quiz_data($aula->ID)) {
                                $aula->_curso_id_ref = $cid; // Salvar referência do curso
                                $aulas_com_quiz[] = $aula;
                            }
                        }
                    }

                    if (empty($aulas_com_quiz)) {
                        echo '<p style="color: #666;">Nenhuma avaliação encontrada nos cursos deste aluno.</p>';
                    } else {
                        ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Aula / Curso</th>
                                    <th style="width: 150px;">Tentativas</th>
                                    <th style="width: 120px;">Nota</th>
                                    <th style="width: 180px;">Status</th>
                                    <th style="width: 200px;">Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                foreach ($aulas_com_quiz as $aula) {
                                    $aula_id = $aula->ID;
                                    $curso_id = isset($aula->_curso_id_ref) ? $aula->_curso_id_ref : 0;
                                    $curso_titulo = $curso_id ? get_the_title($curso_id) : 'Curso Desconhecido';

                                    // Configurações do Quiz
                                    $quiz_data = get_post_meta($aula_id, '_aula_quiz_data', true);

                                    // Fallback para meta antigo
                                    $max_tentativas = 1;
                                    $nota_minima = 70;

                                    if (!empty($quiz_data) && is_array($quiz_data)) {
                                        $max_tentativas = isset($quiz_data['max_attempts']) ? (int) $quiz_data['max_attempts'] : 1;
                                        $nota_minima = isset($quiz_data['passing_score']) ? (int) $quiz_data['passing_score'] : 70;
                                    } else {
                                        $max_tentativas = (int) get_post_meta($aula_id, 'quiz_tentativas', true) ?: 1;
                                        $nota_minima = (int) get_post_meta($aula_id, 'quiz_nota_minima', true) ?: 70;
                                    }

                                    // Ajuste para ilimitado
                                    if ($max_tentativas === 0)
                                        $max_tentativas = 9999;

                                    // Buscar Tentativas e Nota Real (Progresso)
                                    global $wpdb;
                                    $table_progresso = $wpdb->prefix . 'progresso_aluno';
                                    $progresso = $wpdb->get_row($wpdb->prepare(
                                        "SELECT pontuacao, tentativas FROM $table_progresso WHERE user_id = %d AND aula_id = %d",
                                        $user_id,
                                        $aula_id
                                    ));

                                    // Tentativas no user_meta (usado para controle de bloqueio)
                                    $tentativas_meta = (int) get_user_meta($user_id, '_quiz_attempts_' . $aula_id, true);

                                    // Consolidar dados
                                    $nota_real = ($progresso) ? (int) $progresso->pontuacao : null;

                                    // Se tem registro no progresso, usa tentativas de lá, senão usa do meta
                                    $tentativas_usadas = ($progresso && $progresso->tentativas) ? (int) $progresso->tentativas : $tentativas_meta;

                                    // Calcular Status
                                    $status_html = '';
                                    $is_passed = ($nota_real !== null && $nota_real >= $nota_minima);

                                    if ($is_passed) {
                                        $status_html = '<span style="color:green; font-weight:bold;">✅ Aprovado</span>';
                                    } elseif ($tentativas_usadas >= $max_tentativas) {
                                        $status_html = '<span style="color:red; font-weight:bold;">🚫 Bloqueado</span>';
                                    } elseif ($tentativas_usadas > 0) {
                                        $remaining = $max_tentativas - $tentativas_usadas;
                                        $status_html = '<span style="color:orange; font-weight:600;">Em andamento</span><br><small>(' . $remaining . ' rest.)</small>';
                                    } else {
                                        $status_html = '<span style="color:#666;">Não iniciado</span>';
                                    }

                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html($aula->post_title); ?></strong>
                                            <br><small><?php echo esc_html($curso_titulo); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo $tentativas_usadas; ?></strong> /
                                            <?php echo ($max_tentativas === 9999) ? 'Ilimitado' : $max_tentativas; ?>
                                        </td>
                                        <td>
                                            <?php echo ($nota_real !== null) ? $nota_real . '%' : '-'; ?>
                                        </td>
                                        <td><?php echo $status_html; ?></td>
                                        <td>
                                            <?php if ($tentativas_usadas > 0): ?>
                                                <form method="post"
                                                    onsubmit="return confirm('Tem certeza que deseja ZERAR as tentativas desta aula para este aluno?');">
                                                    <?php wp_nonce_field('aluno_reset_quiz_attempts', '_wpnonce'); ?>
                                                    <input type="hidden" name="reset_quiz_attempts" value="1">
                                                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">
                                                    <input type="hidden" name="aula_id" value="<?php echo $aula_id; ?>">
                                                    <button type="submit" class="button button-secondary">
                                                        <span class="dashicons dashicons-image-rotate" style="vertical-align: text-top;"></span>
                                                        Resetar
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span style="color:#aaa;">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                ?>
                            </tbody>
                        </table>
                        <?php
                    }
                }
                ?>
            </div>

            <h2>Cursos e Permissões</h2>
            <p class="description">Gerencie os acessos do aluno aos cursos disponíveis.</p>

            <form method="post">
                <?php wp_nonce_field('aluno_acesso_rapido'); ?>
                <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Curso</th>
                            <th style="width: 120px;">Status</th>
                            <th style="width: 150px;">Expiração</th>
                            <th style="width: 130px;">Desde</th>
                            <th style="width: 320px;">Ações Rápidas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cursos as $curso):
                            $acesso = isset($acessos_map[$curso->ID]) ? $acessos_map[$curso->ID] : null;

                            // Check for Group Access if no Direct Access
                            $access_source = self::get_access_source($user->ID, $curso->ID);

                            $tem_acesso = ($access_source !== false);
                            $is_group_access = ($access_source && in_array($access_source['type'], ['group', 'group_trilha']));

                            $expirado = $acesso && $acesso->status === 'ativo' && $acesso->data_fim && strtotime($acesso->data_fim) < time();
                            $cert_link = self::get_admin_certificate_link($user->ID, $curso->ID);
                            ?>
                            <tr>
                                <td><strong>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $curso->ID . '&action=edit')); ?>"
                                            title="Editar curso: <?php echo esc_attr($curso->post_title); ?>"
                                            style="text-decoration: none; color: #2271b1;">
                                            <?php echo esc_html($curso->post_title); ?>
                                        </a>
                                    </strong></td>
                                <td>
                                    <?php if (!$tem_acesso): ?>
                                        <?php if ($expirado): ?>
                                            <span style="color: #f59e0b; font-weight: 600;">Expirado</span>
                                        <?php else: ?>
                                            <span style="color: #9ca3af;">Sem acesso</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php if ($is_group_access): ?>
                                            <span
                                                style="color: #2563eb; font-weight: 600; background: #dbeafe; padding: 2px 8px; border-radius: 4px;">Utilizando
                                                <?php echo esc_html($access_source['label']); ?></span>
                                        <?php elseif ($acesso && $acesso->status === 'ativo'): ?>
                                            <span style="color: #22c55e; font-weight: 600;">Ativo (Manual)</span>
                                        <?php elseif ($acesso && $acesso->status === 'suspenso'): ?>
                                            <span style="color: #6b7280; font-weight: 600;">Suspenso</span>
                                        <?php else: ?>
                                            <span style="color: #ef4444; font-weight: 600;">Revogado</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($is_group_access): ?>
                                        <em>Gerenciado pelo Grupo</em>
                                    <?php elseif ($acesso && $acesso->data_fim): ?>
                                        <?php echo date('d/m/Y', strtotime($acesso->data_fim)); ?>
                                    <?php elseif ($acesso && $acesso->status === 'ativo'): ?>
                                        <em>Vitalício</em>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                    <?php if ($tem_acesso && !$is_group_access && $acesso): ?>
                                        <button type="button" class="button button-small"
                                            onclick="openEditDateModal(<?php echo (int) $curso->ID; ?>, '<?php echo $acesso->data_fim ? date('Y-m-d', strtotime($acesso->data_fim)) : ''; ?>')"
                                            title="Editar data de expiração"
                                            style="margin-left: 5px; padding: 0 4px; min-height: 24px; line-height: 1;">
                                            <span class="dashicons dashicons-edit"
                                                style="font-size: 14px; width: 14px; height: 14px; padding-top: 3px;"></span>
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $acesso ? date('d/m/Y', strtotime($acesso->created_at)) : '—'; ?>
                                </td>
                                <td>
                                    <?php if ($is_group_access): ?>
                                        <small style="color:#666;">Acesso via grupo. Edite o grupo ou remova o aluno dele.</small>
                                    <?php elseif (!$acesso || $acesso->status !== 'ativo' || $expirado): ?>
                                        <button type="submit" name="acao_rapida" value="ativar_<?php echo $curso->ID; ?>"
                                            class="button button-primary button-small">
                                            Conceder Acesso
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="acao_rapida" value="suspender_<?php echo $curso->ID; ?>"
                                            class="button button-small">
                                            Suspender
                                        </button>
                                        <button type="submit" name="acao_rapida" value="revogar_<?php echo $curso->ID; ?>"
                                            class="button button-small" style="color: #dc3232;">
                                            Revogar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($acesso && $acesso->status === 'suspenso' && !$is_group_access): ?>
                                        <button type="submit" name="acao_rapida" value="reativar_<?php echo $curso->ID; ?>"
                                            class="button button-primary button-small">
                                            Reativar
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($tem_acesso): ?>
                                        <div style="margin-top: 8px; display: flex; gap: 6px; flex-wrap: wrap;">
                                            <button type="button" class="button button-small sc-open-lesson-progress-modal"
                                                data-user-id="<?php echo (int) $user->ID; ?>"
                                                data-curso-id="<?php echo (int) $curso->ID; ?>"
                                                data-curso-titulo="<?php echo esc_attr($curso->post_title); ?>">
                                                Gerenciar Aulas
                                            </button>
                                            <a href="<?php echo esc_url($cert_link); ?>" target="_blank" class="button button-small"
                                                title="Emitir certificado para este aluno">
                                                Gerar Certificado
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </form>

            <div
                style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-top: 20px; max-width: 500px;">
                <h3 style="margin-top: 0;">Conceder Acesso com Data de Expiração</h3>
                <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('aluno_conceder_acesso'); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">

                    <label>
                        <strong>Curso:</strong><br>
                        <select name="curso_id" required style="width: 100%;">
                            <option value="">Selecione...</option>
                            <?php foreach ($cursos as $curso): ?>
                                <option value="<?php echo $curso->ID; ?>">
                                    <?php echo esc_html($curso->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <strong>Data de Expiração:</strong><br>
                        <input type="date" name="data_fim" style="width: 100%;">
                        <small style="color: #666;">Deixe vazio para acesso vitalício.</small>
                    </label>

                    <button type="submit" name="conceder_acesso" value="1" class="button button-primary">
                        Conceder Acesso
                    </button>
                </form>
            </div>

            <div
                style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-top: 20px; max-width: 500px;">
                <h3 style="margin-top: 0;">Matrícula em Massa por Trilha</h3>
                <p class="description">Matricula o aluno em todos os cursos desta trilha.</p>
                <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                    <?php wp_nonce_field('aluno_matricular_trilha'); ?>
                    <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">

                    <label>
                        <strong>Trilha:</strong><br>
                        <select name="trilha_id" required style="width: 100%;">
                            <option value="">Selecione a Trilha...</option>
                            <?php foreach ($trilhas as $trilha): ?>
                                <option value="<?php echo $trilha->ID; ?>">
                                    <?php echo esc_html($trilha->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        <strong>Data de Expiração (Opcional):</strong><br>
                        <input type="date" name="data_fim" style="width: 100%;">
                        <small style="color: #666;">Aplica a mesma data para todos os cursos da trilha.</small>
                    </label>

                    <button type="submit" name="matricular_trilha" value="1" class="button button-primary">
                        Matricular na Trilha
                    </button>
                </form>
            </div>
        </div>

        <!-- Modal Alterar Dados Cadastrais -->
        <div id="modal-dados-cadastrais" class="sc-modal-overlay">
            <div class="sc-modal-content">
                <div class="sc-modal-header">
                    <h2>Alterar Dados Cadastrais</h2>
                    <span class="sc-modal-close"
                        onclick="document.getElementById('modal-dados-cadastrais').style.display='none'">&times;</span>
                </div>
                <form method="post">
                    <?php wp_nonce_field('aluno_update_data', '_wpnonce'); ?>
                    <input type="hidden" name="update_student_data" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                    <div style="max-height: 70vh; overflow-y: auto; padding-right: 10px;">
                        <div style="margin-bottom: 20px;">
                            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0;">Contato e Documentos
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th>CPF</th>
                                    <td><input type="text" name="cpf" value="<?php echo esc_attr($cpf); ?>" class="regular-text"
                                            style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Aniversário</th>
                                    <td><input type="date" name="aniversario" value="<?php echo esc_attr($aniversario); ?>"
                                            style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Telefone</th>
                                    <td><input type="text" name="billing_phone" value="<?php echo esc_attr($phone); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Instagram</th>
                                    <td><input type="text" name="instagram" value="<?php echo esc_attr($instagram); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                            </table>
                        </div>

                        <div>
                            <h3 style="border-bottom: 1px solid #eee; padding-bottom: 10px;">Endereço</h3>
                            <table class="form-table">
                                <tr>
                                    <th>CEP</th>
                                    <td><input type="text" name="cep" value="<?php echo esc_attr($cep); ?>" class="regular-text"
                                            style="width: 150px;"></td>
                                </tr>
                                <tr>
                                    <th>Rua</th>
                                    <td><input type="text" name="rua" value="<?php echo esc_attr($rua); ?>" class="regular-text"
                                            style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Número</th>
                                    <td><input type="text" name="numero" value="<?php echo esc_attr($numero); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Complemento</th>
                                    <td><input type="text" name="complemento" value="<?php echo esc_attr($complemento); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Bairro</th>
                                    <td><input type="text" name="bairro" value="<?php echo esc_attr($bairro); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Cidade</th>
                                    <td><input type="text" name="cidade" value="<?php echo esc_attr($cidade); ?>"
                                            class="regular-text" style="width: 100%;"></td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td><input type="text" name="estado" value="<?php echo esc_attr($estado); ?>"
                                            class="regular-text" style="width: 60px;" maxlength="2" placeholder="UF"></td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: right;">
                        <button type="button" class="button"
                            onclick="document.getElementById('modal-dados-cadastrais').style.display='none'">Cancelar</button>
                        <button type="submit" class="button button-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Alterar Senha -->
        <div id="modal-alterar-senha" class="sc-modal-overlay">
            <div class="sc-modal-content" style="max-width: 500px;">
                <div class="sc-modal-header">
                    <h2>Alterar Senha de Acesso</h2>
                    <span class="sc-modal-close"
                        onclick="document.getElementById('modal-alterar-senha').style.display='none'">&times;</span>
                </div>
                <form method="post">
                    <?php wp_nonce_field('aluno_update_data', '_wpnonce'); ?>
                    <input type="hidden" name="update_student_data" value="1">
                    <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                    <p>Digite a nova senha para este usuário. A senha anterior será substituída imediatamente.</p>

                    <label style="display: block; margin: 20px 0;">
                        <strong>Nova Senha</strong>
                        <div style="position: relative;">
                            <input type="password" name="new_password" id="new_password_field" class="regular-text"
                                style="width: 100%; display: block; margin-top: 5px; padding-right: 40px;" required
                                placeholder="••••••••">
                            <span onclick="togglePasswordVisibility()"
                                style="position: absolute; right: 10px; top: 55%; transform: translateY(-50%); cursor: pointer; color: #666;"
                                title="Ver senha">
                                <span class="dashicons dashicons-visibility" id="password-toggle-icon"></span>
                            </span>
                        </div>
                    </label>

                    <script>
                        function togglePasswordVisibility() {
                            var passwordInput = document.getElementById('new_password_field');
                            var toggleIcon = document.getElementById('password-toggle-icon');
                            if (passwordInput.type === 'password') {
                                passwordInput.type = 'text';
                                toggleIcon.classList.remove('dashicons-visibility');
                                toggleIcon.classList.add('dashicons-hidden');
                            } else {
                                passwordInput.type = 'password';
                                toggleIcon.classList.remove('dashicons-hidden');
                                toggleIcon.classList.add('dashicons-visibility');
                            }
                        }
                    </script>

                    <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; text-align: right;">
                        <button type="button" class="button"
                            onclick="document.getElementById('modal-alterar-senha').style.display='none'">Cancelar</button>
                        <button type="submit" class="button button-primary">Alterar Senha</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Gerenciar Progresso das Aulas -->
        <div id="modal-gerenciar-aulas" class="sc-modal-overlay">
            <div class="sc-modal-content sc-modal-content-lessons" role="dialog" aria-modal="true"
                aria-labelledby="sc-lessons-modal-title">
                <div class="sc-modal-header">
                    <h2 id="sc-lessons-modal-title">Gerenciar Conclusao de Aulas</h2>
                    <span class="sc-modal-close" data-close-lessons-modal="1">&times;</span>
                </div>

                <p id="sc-lessons-modal-course" style="margin: 0 0 8px; color: #334155; font-weight: 600;"></p>
                <div id="sc-lessons-modal-progress" class="sc-lessons-modal-progress"></div>
                <div id="sc-lessons-modal-status" class="sc-lessons-modal-status" aria-live="polite"></div>

                <div id="sc-lessons-modal-body" class="sc-lessons-modal-body">
                    <p style="margin: 0; color: #64748b;">Selecione um curso para carregar as aulas.</p>
                </div>

                <div style="margin-top: 16px; text-align: right;">
                    <button type="button" class="button" data-close-lessons-modal="1">Fechar</button>
                </div>
            </div>
        </div>

        <script>
            (function () {
                var modal = document.getElementById('modal-gerenciar-aulas');
                if (!modal) {
                    return;
                }

                var modalBody = document.getElementById('sc-lessons-modal-body');
                var modalStatus = document.getElementById('sc-lessons-modal-status');
                var modalProgress = document.getElementById('sc-lessons-modal-progress');
                var modalCourse = document.getElementById('sc-lessons-modal-course');
                var ajaxUrl = <?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>;
                var nonce = <?php echo wp_json_encode(wp_create_nonce('sc_admin_lesson_progress')); ?>;
                var state = {
                    userId: <?php echo (int) $user_id; ?>,
                    cursoId: 0
                };

                function escapeHtml(value) {
                    var div = document.createElement('div');
                    div.textContent = value == null ? '' : String(value);
                    return div.innerHTML;
                }

                function setStatus(message, type) {
                    modalStatus.className = 'sc-lessons-modal-status';
                    if (!message) {
                        modalStatus.textContent = '';
                        return;
                    }
                    if (type) {
                        modalStatus.classList.add('is-' + type);
                    }
                    modalStatus.textContent = message;
                }

                function renderProgress(progress) {
                    if (!progress || typeof progress !== 'object') {
                        modalProgress.innerHTML = '';
                        return;
                    }

                    var concluidas = parseInt(progress.concluidas, 10) || 0;
                    var total = parseInt(progress.total, 10) || 0;
                    var percent = parseInt(progress.percent, 10) || 0;

                    modalProgress.innerHTML =
                        '<strong>' + concluidas + '</strong> de <strong>' + total + '</strong> aulas concluidas (' + percent + '%)';
                }

                function closeModal() {
                    modal.style.display = 'none';
                    state.cursoId = 0;
                }

                function openModal(button) {
                    state.cursoId = parseInt(button.getAttribute('data-curso-id'), 10) || 0;
                    state.userId = parseInt(button.getAttribute('data-user-id'), 10) || state.userId;

                    if (state.cursoId <= 0 || state.userId <= 0) {
                        return;
                    }

                    var courseTitle = button.getAttribute('data-curso-titulo') || '';
                    modalCourse.textContent = courseTitle ? ('Curso: ' + courseTitle) : '';
                    modalBody.innerHTML = '<p style=\"margin: 0; color: #64748b;\">Carregando aulas...</p>';
                    renderProgress(null);
                    setStatus('Carregando dados do curso...', 'info');
                    modal.style.display = 'flex';
                    loadLessons();
                }

                function ajaxPost(payload) {
                    var formData = new FormData();
                    Object.keys(payload).forEach(function (key) {
                        formData.append(key, payload[key]);
                    });

                    return fetch(ajaxUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: formData
                    }).then(function (response) {
                        return response.json();
                    });
                }

                function renderLessons(lessons) {
                    if (!Array.isArray(lessons) || lessons.length === 0) {
                        modalBody.innerHTML = '<p style=\"margin: 0; color: #64748b;\">Este curso nao possui aulas publicadas.</p>';
                        return;
                    }

                    var html = [
                        '<table class=\"widefat striped sc-lessons-table\">',
                        '<thead><tr><th style=\"width: 70px;\">OK</th><th>Aula</th><th style=\"width: 120px;\">Quiz</th></tr></thead>',
                        '<tbody>'
                    ];

                    lessons.forEach(function (lesson) {
                        var aulaId = parseInt(lesson.id, 10) || 0;
                        var checked = lesson.completed ? ' checked' : '';
                        var rowClass = lesson.completed ? ' class=\"is-completed\"' : '';
                        var title = escapeHtml((lesson.order || '-') + '. ' + (lesson.title || 'Aula sem titulo'));
                        var quizInfo = (parseInt(lesson.passing_score, 10) || 0) > 0
                            ? ('Min. ' + parseInt(lesson.passing_score, 10) + '%')
                            : 'Nao';

                        html.push(
                            '<tr' + rowClass + '>' +
                            '<td><input type=\"checkbox\" class=\"sc-lesson-completed-toggle\" data-aula-id=\"' + aulaId + '\"' + checked + '></td>' +
                            '<td><span>' + title + '</span></td>' +
                            '<td><small>' + escapeHtml(quizInfo) + '</small></td>' +
                            '</tr>'
                        );
                    });

                    html.push('</tbody></table>');
                    modalBody.innerHTML = html.join('');
                }

                function loadLessons() {
                    ajaxPost({
                        action: 'sc_admin_get_course_lessons_progress',
                        nonce: nonce,
                        user_id: state.userId,
                        curso_id: state.cursoId
                    })
                        .then(function (response) {
                            if (!response || !response.success) {
                                throw new Error(response && response.data && response.data.message ? response.data.message : 'Nao foi possivel carregar as aulas.');
                            }

                            renderLessons(response.data.lessons || []);
                            renderProgress(response.data.progress || null);
                            setStatus('Marque ou desmarque as aulas para atualizar o progresso do aluno.', 'info');
                        })
                        .catch(function (error) {
                            modalBody.innerHTML = '<p style=\"margin: 0; color: #b91c1c;\">Falha ao carregar as aulas.</p>';
                            setStatus(error.message || 'Falha ao carregar as aulas.', 'error');
                        });
                }

                function updateLessonProgress(toggle) {
                    var aulaId = parseInt(toggle.getAttribute('data-aula-id'), 10) || 0;
                    if (aulaId <= 0 || state.cursoId <= 0 || state.userId <= 0) {
                        return;
                    }

                    var desiredValue = toggle.checked ? 1 : 0;
                    toggle.disabled = true;
                    setStatus('Salvando alteracao...', 'info');

                    ajaxPost({
                        action: 'sc_admin_update_course_lesson_progress',
                        nonce: nonce,
                        user_id: state.userId,
                        curso_id: state.cursoId,
                        aula_id: aulaId,
                        completed: desiredValue
                    })
                        .then(function (response) {
                            if (!response || !response.success) {
                                throw new Error(response && response.data && response.data.message ? response.data.message : 'Nao foi possivel salvar a alteracao.');
                            }

                            var finalState = !!(response.data && response.data.completed);
                            toggle.checked = finalState;

                            var row = toggle.closest('tr');
                            if (row) {
                                row.classList.toggle('is-completed', finalState);
                            }

                            renderProgress(response.data.progress || null);
                            setStatus(response.data.message || 'Alteracao salva com sucesso.', 'success');
                        })
                        .catch(function (error) {
                            toggle.checked = !toggle.checked;
                            setStatus(error.message || 'Falha ao salvar alteracao.', 'error');
                        })
                        .then(function () {
                            toggle.disabled = false;
                        });
                }

                document.addEventListener('click', function (event) {
                    var openButton = event.target.closest('.sc-open-lesson-progress-modal');
                    if (openButton) {
                        event.preventDefault();
                        openModal(openButton);
                        return;
                    }

                    var closeButton = event.target.closest('[data-close-lessons-modal=\"1\"]');
                    if (closeButton) {
                        closeModal();
                    }
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeModal();
                    }
                });

                modal.addEventListener('change', function (event) {
                    var toggle = event.target.closest('.sc-lesson-completed-toggle');
                    if (!toggle) {
                        return;
                    }

                    updateLessonProgress(toggle);
                });

                document.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && modal.style.display === 'flex') {
                        closeModal();
                    }
                });
            })();
        </script>

        <style>
            .sc-modal-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9999;
                justify-content: center;
                align-items: center;
            }

            .sc-modal-content {
                background: #fff;
                width: 90%;
                max-width: 800px;
                border-radius: 8px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
                padding: 25px;
                position: relative;
                animation: slideDown 0.3s ease-out;
            }

            .sc-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                border-bottom: 1px solid #eee;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }

            .sc-modal-header h2 {
                margin: 0;
                font-size: 1.3em;
            }

            .sc-modal-close {
                font-size: 28px;
                font-weight: bold;
                color: #aaa;
                cursor: pointer;
                line-height: 1;
            }

            .sc-modal-close:hover {
                color: #000;
            }

            .sc-modal-content-lessons {
                max-width: 900px;
            }

            .sc-lessons-modal-progress {
                margin: 0 0 10px;
                font-size: 13px;
                color: #0f172a;
            }

            .sc-lessons-modal-status {
                margin: 0 0 12px;
                font-size: 13px;
                color: #64748b;
            }

            .sc-lessons-modal-status.is-success {
                color: #15803d;
            }

            .sc-lessons-modal-status.is-error {
                color: #b91c1c;
            }

            .sc-lessons-modal-body {
                max-height: 55vh;
                overflow-y: auto;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                padding: 10px;
                background: #f8fafc;
            }

            .sc-lessons-table {
                margin: 0;
                border: none;
            }

            .sc-lessons-table tr.is-completed td {
                background: #ecfdf5;
            }

            .sc-lessons-table td {
                vertical-align: middle;
            }

            @keyframes slideDown {
                from {
                    transform: translateY(-50px);
                    opacity: 0;
                }

                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
        </style>

        <!-- Modal Editar Data -->
        <div id="modal-editar-data" class="sc-modal"
            style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.4);">
            <div class="sc-modal-content"
                style="background-color: #fefefe; margin: 15% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);">
                <span class="sc-modal-close" onclick="closeEditDateModal()"
                    style="color: #aaa; float: right; font-size: 28px; font-weight: bold; cursor: pointer;">&times;</span>
                <h3 style="margin-top: 0; margin-bottom: 20px; font-size: 1.25rem; font-weight: 600;">Editar Data de Expiração
                </h3>
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('update_access_date_action', 'update_access_date_nonce'); ?>
                    <input type="hidden" name="action" value="update_access_date">
                    <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
                    <input type="hidden" name="curso_id" id="edit_date_curso_id" value="">

                    <div style="margin-bottom: 20px;">
                        <label for="edit_data_fim" style="display: block; margin-bottom: 8px; font-weight: 500;">Nova Data de
                            Expiração:</label>
                        <input type="date" name="data_fim" id="edit_data_fim" class="regular-text"
                            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <p style="margin-top: 5px; font-size: 0.875rem; color: #666;">Deixe o campo vazio para conceder acesso
                            <strong>Vitalício</strong>.
                        </p>
                    </div>

                    <div style="text-align: right; margin-top: 20px; border-top: 1px solid #eee; padding-top: 15px;">
                        <button type="button" class="button button-secondary" onclick="closeEditDateModal()"
                            style="margin-right: 10px;">Cancelar</button>
                        <button type="submit" class="button button-primary">Salvar Alteração</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            function openEditDateModal(cursoId, currentDataFim) {
                document.getElementById('edit_date_curso_id').value = cursoId;
                document.getElementById('edit_data_fim').value = currentDataFim;
                document.getElementById('modal-editar-data').style.display = 'block';
            }

            function closeEditDateModal() {
                document.getElementById('modal-editar-data').style.display = 'none';
            }

            window.addEventListener('click', function (event) {
                var modal = document.getElementById('modal-editar-data');
                if (event.target == modal) {
                    modal.style.display = "none";
                }
            });
        </script>
        <?php
    }
}

// Wrapper Functions for Backwards Compatibility
function acesso_cursos_has($user_id, $curso_id)
{
    return System_Cursos_Access_Control::has_access($user_id, $curso_id);
}

function acesso_cursos_grant($user_id, $curso_id, $data_fim = null, $created_by = null)
{
    return System_Cursos_Access_Control::grant_access($user_id, $curso_id, $data_fim, $created_by);
}

function acesso_cursos_revoke($user_id, $curso_id)
{
    return System_Cursos_Access_Control::revoke_access($user_id, $curso_id);
}

function acesso_cursos_suspend($user_id, $curso_id)
{
    return System_Cursos_Access_Control::suspend_access($user_id, $curso_id);
}

function acesso_cursos_delete($id)
{
    return System_Cursos_Access_Control::delete_access($id);
}

function acesso_cursos_list($args = [])
{
    return System_Cursos_Access_Control::list_accesses($args);
}

function acesso_cursos_count($args = [])
{
    return System_Cursos_Access_Control::count_accesses($args);
}

function acesso_cursos_get_user_cursos($user_id)
{
    return System_Cursos_Access_Control::get_user_courses($user_id);
}

function acesso_cursos_get_progresso_detalhado($user_id, $curso_id)
{
    return System_Cursos_Access_Control::get_detailed_progress($user_id, $curso_id);
}
