<?php
/**
 * Controle de Acesso a Cursos
 * Versão: 1.0.5
 *
 * Sistema de permissões para cursos com:
 * - Tabela própria no banco de dados
 * - Funções de verificação e gerenciamento de acesso
 * - Painel administrativo com CRUD e ações em lote
 *
 * Uso por outros snippets:
 * - Verificar acesso: acesso_cursos_has($user_id, $curso_id)
 * - Conceder acesso: acesso_cursos_grant($user_id, $curso_id, $data_fim = null, $created_by = null)
 * - Revogar acesso: acesso_cursos_revoke($user_id, $curso_id)
 * - Suspender acesso: acesso_cursos_suspend($user_id, $curso_id)
 * - Listar acessos: acesso_cursos_list($args = [])
 * - Contar acessos: acesso_cursos_count($args = [])
 * - Cursos do usuário: acesso_cursos_get_user_cursos($user_id)
 *
 * Hooks registrados:
 * - init: acesso_cursos_criar_tabela
 * - admin_menu: acesso_cursos_admin_menu
 * - admin_init: acesso_cursos_admin_process
 *
 * Observação: este arquivo não aplica bloqueio no front-end por si só.
 * Para restringir conteúdo, chame acesso_cursos_has() em templates/hooks apropriados.
 *
 * Status possíveis: ativo, suspenso, revogado
 * data_fim NULL = acesso vitalício
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// TABELA NO BANCO DE DADOS
// =============================================================================

function acesso_cursos_criar_tabela() {
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

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
add_action('init', 'acesso_cursos_criar_tabela');

// =============================================================================
// FUNÇÕES DE ACESSO
// =============================================================================

/**
 * Verifica se o usuário tem acesso a um curso
 */
function acesso_cursos_has($user_id, $curso_id) {
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

/**
 * Concede acesso a um curso
 */
function acesso_cursos_grant($user_id, $curso_id, $data_fim = null, $created_by = null) {
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
        return $wpdb->update(
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
    }
    
    return $wpdb->insert(
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
}

/**
 * Revoga acesso a um curso
 */
function acesso_cursos_revoke($user_id, $curso_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    return $wpdb->update(
        $table_name,
        ['status' => 'revogado', 'updated_at' => current_time('mysql')],
        ['user_id' => $user_id, 'curso_id' => $curso_id],
        ['%s', '%s'],
        ['%d', '%d']
    );
}

/**
 * Suspende acesso a um curso
 */
function acesso_cursos_suspend($user_id, $curso_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    return $wpdb->update(
        $table_name,
        ['status' => 'suspenso', 'updated_at' => current_time('mysql')],
        ['user_id' => $user_id, 'curso_id' => $curso_id],
        ['%s', '%s'],
        ['%d', '%d']
    );
}

/**
 * Remove completamente um registro de acesso
 */
function acesso_cursos_delete($id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    return $wpdb->delete($table_name, ['id' => $id], ['%d']);
}

/**
 * Lista acessos com filtros
 */
function acesso_cursos_list($args = []) {
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

/**
 * Conta total de registros (para paginação)
 */
function acesso_cursos_count($args = []) {
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

/**
 * Retorna cursos que o usuário tem acesso
 */
function acesso_cursos_get_user_cursos($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    return $wpdb->get_col($wpdb->prepare(
        "SELECT curso_id FROM $table_name 
         WHERE user_id = %d 
         AND status = 'ativo' 
         AND (data_fim IS NULL OR data_fim >= NOW())",
        $user_id
    ));
}

// =============================================================================
// PAINEL ADMINISTRATIVO
// =============================================================================

add_action('admin_menu', 'acesso_cursos_admin_menu');

function acesso_cursos_admin_menu() {
    add_menu_page(
        'Acessos a Cursos',
        'Acessos',
        'manage_options',
        'acesso-cursos',
        'acesso_cursos_admin_page',
        'dashicons-lock',
        30
    );
    
    add_submenu_page(
        'acesso-cursos',
        'Permissões de Acesso',
        'Permissões',
        'manage_options',
        'acesso-cursos',
        'acesso_cursos_admin_page'
    );
    
    add_submenu_page(
        'acesso-cursos',
        'Lista de Alunos',
        'Lista de Alunos',
        'manage_options',
        'acesso-cursos-alunos',
        'acesso_cursos_alunos_page'
    );
}

function acesso_cursos_admin_page() {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    
    switch ($action) {
        case 'add':
        case 'edit':
            acesso_cursos_admin_form();
            break;
        default:
            acesso_cursos_admin_list();
            break;
    }
}

// Processar ações (POST)
add_action('admin_init', 'acesso_cursos_admin_process');

function acesso_cursos_admin_process() {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    // Salvar acesso
    if (isset($_POST['acesso_cursos_save']) && wp_verify_nonce($_POST['_wpnonce'], 'acesso_cursos_save')) {
        $user_id = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
        $curso_ids = [];
        if (isset($_POST['curso_id'])) {
            $curso_ids = is_array($_POST['curso_id'])
                ? array_map('intval', $_POST['curso_id'])
                : [(int) $_POST['curso_id']];
        }
        $curso_ids = array_values(array_unique(array_filter($curso_ids, function ($curso_id) {
            return $curso_id > 0;
        })));
        $status = sanitize_text_field($_POST['status']);
        $data_fim = !empty($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) : null;
        
        if ($user_id > 0 && !empty($curso_ids)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'acesso_cursos';
            
            foreach ($curso_ids as $curso_id) {
                $existing = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_name WHERE user_id = %d AND curso_id = %d",
                    $user_id,
                    $curso_id
                ));
                
                if ($existing) {
                    $wpdb->update(
                        $table_name,
                        [
                            'status' => $status,
                            'data_fim' => $data_fim,
                            'updated_at' => current_time('mysql')
                        ],
                        ['id' => $existing],
                        ['%s', '%s', '%s'],
                        ['%d']
                    );
                } else {
                    $wpdb->insert(
                        $table_name,
                        [
                            'user_id' => $user_id,
                            'curso_id' => $curso_id,
                            'status' => $status,
                            'data_fim' => $data_fim,
                            'created_at' => current_time('mysql'),
                            'updated_at' => current_time('mysql'),
                            'created_by' => get_current_user_id()
                        ],
                        ['%d', '%d', '%s', '%s', '%s', '%s', '%d']
                    );
                }
            }
            
            wp_redirect(admin_url('admin.php?page=acesso-cursos&msg=saved'));
            exit;
        }
    }
    
    // Deletar acesso
    if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'delete_acesso_' . $_GET['id'])) {
            acesso_cursos_delete((int) $_GET['id']);
            wp_redirect(admin_url('admin.php?page=acesso-cursos&msg=deleted'));
            exit;
        }
    }
    
    // Ações em lote
    if (isset($_POST['acesso_cursos_bulk']) && wp_verify_nonce($_POST['_wpnonce'], 'acesso_cursos_bulk')) {
        $action = sanitize_text_field($_POST['bulk_action']);
        $ids = isset($_POST['acesso_ids']) ? array_map('intval', $_POST['acesso_ids']) : [];
        
        if (!empty($ids) && !empty($action)) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'acesso_cursos';
            
            foreach ($ids as $id) {
                switch ($action) {
                    case 'ativar':
                        $wpdb->update($table_name, ['status' => 'ativo'], ['id' => $id]);
                        break;
                    case 'suspender':
                        $wpdb->update($table_name, ['status' => 'suspenso'], ['id' => $id]);
                        break;
                    case 'revogar':
                        $wpdb->update($table_name, ['status' => 'revogado'], ['id' => $id]);
                        break;
                    case 'deletar':
                        acesso_cursos_delete($id);
                        break;
                    case 'estender_30':
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $table_name SET data_fim = DATE_ADD(IFNULL(data_fim, NOW()), INTERVAL 30 DAY) WHERE id = %d",
                            $id
                        ));
                        break;
                    case 'estender_90':
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $table_name SET data_fim = DATE_ADD(IFNULL(data_fim, NOW()), INTERVAL 90 DAY) WHERE id = %d",
                            $id
                        ));
                        break;
                    case 'vitalicio':
                        $wpdb->update($table_name, ['data_fim' => null], ['id' => $id]);
                        break;
                }
            }
            
            wp_redirect(admin_url('admin.php?page=acesso-cursos&msg=bulk_done'));
            exit;
        }
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
                    acesso_cursos_grant($user_id, $curso_id, null, get_current_user_id());
                    break;
                case 'suspender':
                    acesso_cursos_suspend($user_id, $curso_id);
                    break;
                case 'revogar':
                    acesso_cursos_revoke($user_id, $curso_id);
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
            acesso_cursos_grant($user_id, $curso_id, $data_fim, get_current_user_id());
            
            wp_redirect(admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user_id . '&msg=acesso_concedido'));
            exit;
        }
    }
}

function acesso_cursos_admin_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    // Filtros
    $filter_user = isset($_GET['filter_user']) ? (int) $_GET['filter_user'] : 0;
    $filter_curso = isset($_GET['filter_curso']) ? (int) $_GET['filter_curso'] : 0;
    $filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
    
    // Paginação
    $per_page = 30;
    $current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
    $offset = ($current_page - 1) * $per_page;
    
    // Consulta
    $args = [
        'user_id' => $filter_user ?: null,
        'curso_id' => $filter_curso ?: null,
        'status' => $filter_status ?: null,
        'limit' => $per_page,
        'offset' => $offset
    ];
    
    $items = acesso_cursos_list($args);
    $total = acesso_cursos_count($args);
    $total_pages = ceil($total / $per_page);
    
    // Buscar cursos e usuários para os selects
    $users = get_users([
        'fields' => ['ID', 'display_name', 'user_email', 'user_login'],
        'orderby' => 'display_name',
        'order' => 'ASC'
    ]);
    
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Acessos a Cursos</h1>
        <a href="<?php echo admin_url('admin.php?page=acesso-cursos&action=add'); ?>" class="page-title-action">Adicionar Novo</a>
        
        <?php if (isset($_GET['msg'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    switch ($_GET['msg']) {
                        case 'saved': echo 'Acesso salvo com sucesso!'; break;
                        case 'deleted': echo 'Acesso removido!'; break;
                        case 'bulk_done': echo 'Ação em lote concluída!'; break;
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <form method="get" style="margin: 20px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="acesso-cursos">
            
            <select name="filter_curso">
                <option value="">Todos os cursos</option>
                <?php foreach ($cursos as $curso) : ?>
                    <option value="<?php echo $curso->ID; ?>" <?php selected($filter_curso, $curso->ID); ?>>
                        <?php echo esc_html($curso->post_title); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select name="filter_status">
                <option value="">Todos os status</option>
                <option value="ativo" <?php selected($filter_status, 'ativo'); ?>>Ativo</option>
                <option value="suspenso" <?php selected($filter_status, 'suspenso'); ?>>Suspenso</option>
                <option value="revogado" <?php selected($filter_status, 'revogado'); ?>>Revogado</option>
            </select>
            
            <input type="number" name="filter_user" placeholder="ID do usuário" value="<?php echo $filter_user ?: ''; ?>" style="width: 120px;">
            
            <button type="submit" class="button">Filtrar</button>
            <a href="<?php echo admin_url('admin.php?page=acesso-cursos'); ?>" class="button">Limpar</a>
        </form>
        
        <!-- Lista com ações em lote -->
        <form method="post">
            <?php wp_nonce_field('acesso_cursos_bulk'); ?>
            
            <div style="margin-bottom: 10px; display: flex; gap: 10px; align-items: center;">
                <select name="bulk_action">
                    <option value="">Ações em lote</option>
                    <option value="ativar">Ativar</option>
                    <option value="suspender">Suspender</option>
                    <option value="revogar">Revogar</option>
                    <option value="deletar">Deletar</option>
                    <option value="estender_30">Estender +30 dias</option>
                    <option value="estender_90">Estender +90 dias</option>
                    <option value="vitalicio">Tornar vitalício</option>
                </select>
                <button type="submit" name="acesso_cursos_bulk" value="1" class="button" onclick="return confirm('Confirma a ação em lote?');">Aplicar</button>
                <span style="color: #666;">Total: <?php echo $total; ?> registros</span>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td style="width: 30px;"><input type="checkbox" id="select-all"></td>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Curso</th>
                        <th>Status</th>
                        <th>Expiração</th>
                        <th>Criado em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)) : ?>
                        <tr><td colspan="8">Nenhum registro encontrado.</td></tr>
                    <?php else : ?>
                        <?php foreach ($items as $item) :
                            $user = get_user_by('ID', $item->user_id);
                            $curso = get_post($item->curso_id);
                            $expirado = $item->data_fim && strtotime($item->data_fim) < time();
                            ?>
                            <tr>
                                <td><input type="checkbox" name="acesso_ids[]" value="<?php echo $item->id; ?>"></td>
                                <td><?php echo $item->id; ?></td>
                                <td>
                                    <?php if ($user) : ?>
                                        <strong><?php echo esc_html($user->display_name); ?></strong><br>
                                        <small><?php echo esc_html($user->user_email); ?></small>
                                    <?php else : ?>
                                        <em>Usuário #<?php echo $item->user_id; ?> (removido)</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($curso) : ?>
                                        <?php echo esc_html($curso->post_title); ?>
                                    <?php else : ?>
                                        <em>Curso #<?php echo $item->curso_id; ?> (removido)</em>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_colors = [
                                        'ativo' => '#22c55e',
                                        'suspenso' => '#f59e0b',
                                        'revogado' => '#ef4444'
                                    ];
                                    $color = $status_colors[$item->status] ?? '#666';
                                    ?>
                                    <span style="color: <?php echo $color; ?>; font-weight: 600;">
                                        <?php echo ucfirst($item->status); ?>
                                    </span>
                                    <?php if ($expirado && $item->status === 'ativo') : ?>
                                        <br><small style="color: #ef4444;">Expirado</small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($item->data_fim) : ?>
                                        <?php echo date('d/m/Y H:i', strtotime($item->data_fim)); ?>
                                    <?php else : ?>
                                        <em>Vitalício</em>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($item->created_at)); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=acesso-cursos&action=edit&id=' . $item->id); ?>">Editar</a> |
                                    <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=acesso-cursos&action=delete&id=' . $item->id), 'delete_acesso_' . $item->id); ?>" onclick="return confirm('Remover este acesso?');" style="color: #dc3232;">Remover</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
        
        <!-- Paginação -->
        <?php if ($total_pages > 1) : ?>
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
    </div>
    
    <script>
    document.getElementById('select-all').addEventListener('change', function() {
        var checkboxes = document.querySelectorAll('input[name="acesso_ids[]"]');
        checkboxes.forEach(function(cb) { cb.checked = this.checked; }.bind(this));
    });
    </script>
    <?php
}

function acesso_cursos_admin_form() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $item = null;
    
    if ($id > 0) {
        $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }
    
    $users = get_users([
        'fields' => ['ID', 'display_name', 'user_email', 'user_login'],
        'orderby' => 'display_name',
        'order' => 'ASC'
    ]);
    
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    ?>
    <div class="wrap">
        <h1><?php echo $id ? 'Editar Acesso' : 'Adicionar Acesso'; ?></h1>
        
        <form method="post" style="max-width: 600px;">
            <?php wp_nonce_field('acesso_cursos_save'); ?>
            
            <table class="form-table">
                <tr>
                    <th><label for="user_id">Usuário</label></th>
                    <td>
                        <input type="text" id="user_search" class="regular-text" placeholder="Buscar por nome, email ou ID" autocomplete="off" <?php echo $id ? 'disabled' : ''; ?>>
                        <select name="user_id" id="user_id" class="regular-text" required <?php echo $id ? 'disabled' : ''; ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($users as $user_item) : ?>
                                <option value="<?php echo esc_attr($user_item->ID); ?>" 
                                    data-name="<?php echo esc_attr($user_item->display_name); ?>"
                                    data-email="<?php echo esc_attr($user_item->user_email); ?>"
                                    data-id="<?php echo esc_attr($user_item->ID); ?>"
                                    <?php echo $item && $item->user_id == $user_item->ID ? 'selected' : ''; ?>>
                                    <?php echo esc_html(sprintf('%s (%s) - #%d', $user_item->display_name, $user_item->user_email, $user_item->ID)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($id && $item) : ?>
                            <input type="hidden" name="user_id" value="<?php echo esc_attr($item->user_id); ?>">
                            <?php
                            $user = get_user_by('ID', $item->user_id);
                            if ($user) : ?>
                                <p class="description"><?php echo esc_html($user->display_name . ' - ' . $user->user_email); ?></p>
                            <?php endif; ?>
                        <?php else : ?>
                            <p class="description">Digite para filtrar em tempo real.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="curso_id">Curso</label></th>
                    <td>
                        <?php if ($id) : ?>
                            <select name="curso_id" id="curso_id" required disabled>
                                <option value="">Selecione...</option>
                                <?php foreach ($cursos as $curso) : ?>
                                    <option value="<?php echo esc_attr($curso->ID); ?>" <?php echo $item && $item->curso_id == $curso->ID ? 'selected' : ''; ?>>
                                        <?php echo esc_html($curso->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" name="curso_id" value="<?php echo esc_attr($item->curso_id); ?>">
                        <?php else : ?>
                            <select name="curso_id[]" id="curso_id" required multiple size="6" style="min-width: 320px;">
                                <?php foreach ($cursos as $curso) : ?>
                                    <option value="<?php echo esc_attr($curso->ID); ?>">
                                        <?php echo esc_html($curso->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">Segure Ctrl (Windows) ou Command (Mac) para selecionar múltiplos cursos.</p>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="status">Status</label></th>
                    <td>
                        <select name="status" id="status">
                            <option value="ativo" <?php echo $item && $item->status === 'ativo' ? 'selected' : ''; ?>>Ativo</option>
                            <option value="suspenso" <?php echo $item && $item->status === 'suspenso' ? 'selected' : ''; ?>>Suspenso</option>
                            <option value="revogado" <?php echo $item && $item->status === 'revogado' ? 'selected' : ''; ?>>Revogado</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="data_fim">Data de Expiração</label></th>
                    <td>
                        <input type="datetime-local" name="data_fim" id="data_fim" 
                               value="<?php echo $item && $item->data_fim ? date('Y-m-d\TH:i', strtotime($item->data_fim)) : ''; ?>">
                        <p class="description">Deixe vazio para acesso vitalício.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <button type="submit" name="acesso_cursos_save" value="1" class="button button-primary">Salvar</button>
                <a href="<?php echo admin_url('admin.php?page=acesso-cursos'); ?>" class="button">Cancelar</a>
            </p>
        </form>
    </div>
    <script>
    (function() {
        var searchInput = document.getElementById('user_search');
        var userSelect = document.getElementById('user_id');

        if (!searchInput || !userSelect || searchInput.disabled) {
            return;
        }

        function normalize(text) {
            return (text || '')
                .toString()
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '');
        }

        function filterUsers() {
            var term = normalize(searchInput.value.trim());
            var options = userSelect.options;

            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                if (!option.value) {
                    option.hidden = false;
                    continue;
                }

                var haystack = [
                    option.dataset.name,
                    option.dataset.email,
                    option.dataset.id,
                    option.text
                ].join(' ');

                option.hidden = term ? normalize(haystack).indexOf(term) === -1 : false;
            }
        }

        searchInput.addEventListener('input', filterUsers);
    })();
    </script>
    <?php
}

// =============================================================================
// PÁGINA DE LISTA DE ALUNOS
// =============================================================================

function acesso_cursos_alunos_page() {
    $action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
    
    switch ($action) {
        case 'view':
            acesso_cursos_aluno_detalhes();
            break;
        default:
            acesso_cursos_alunos_list();
            break;
    }
}

function acesso_cursos_alunos_list() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    // Filtros
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
    $filter_curso = isset($_GET['filter_curso']) ? (int) $_GET['filter_curso'] : 0;
    $filter_status_acesso = isset($_GET['filter_status_acesso']) ? sanitize_text_field($_GET['filter_status_acesso']) : '';
    
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
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">Lista de Alunos</h1>
        
        <?php if (isset($_GET['msg'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    switch ($_GET['msg']) {
                        case 'updated': echo 'Aluno atualizado com sucesso!'; break;
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Filtros -->
        <form method="get" style="margin: 20px 0; display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <input type="hidden" name="page" value="acesso-cursos-alunos">
            
            <input type="search" name="s" placeholder="Buscar por nome, email ou ID" value="<?php echo esc_attr($search); ?>" style="min-width: 250px;">
            
            <select name="filter_curso">
                <option value="">Todos os cursos</option>
                <?php foreach ($cursos as $curso) : ?>
                    <option value="<?php echo $curso->ID; ?>" <?php selected($filter_curso, $curso->ID); ?>>
                        <?php echo esc_html($curso->post_title); ?>
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
        
        <p class="description">Total: <strong><?php echo $total_users; ?></strong> alunos encontrados</p>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 50px;">ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Cadastro</th>
                    <th>Último Login</th>
                    <th style="width: 120px;">Cursos Ativos</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)) : ?>
                    <tr><td colspan="7">Nenhum aluno encontrado.</td></tr>
                <?php else : ?>
                    <?php foreach ($users as $user) :
                        // Contar cursos com acesso ativo
                        $cursos_ativos = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM $table_name 
                             WHERE user_id = %d AND status = 'ativo' 
                             AND (data_fim IS NULL OR data_fim >= NOW())",
                            $user->ID
                        ));
                        
                        // Último login (meta _last_login ou similar)
                        $last_login = get_user_meta($user->ID, 'last_login', true);
                        if (!$last_login) {
                            $last_login = get_user_meta($user->ID, '_last_login', true);
                        }
                        ?>
                        <tr>
                            <td><strong>#<?php echo $user->ID; ?></strong></td>
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                <?php if ($user->first_name || $user->last_name) : ?>
                                    <br><small style="color: #666;"><?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="mailto:<?php echo esc_attr($user->user_email); ?>">
                                    <?php echo esc_html($user->user_email); ?>
                                </a>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($user->user_registered)); ?></td>
                            <td>
                                <?php if ($last_login) : ?>
                                    <?php echo date('d/m/Y H:i', strtotime($last_login)); ?>
                                <?php else : ?>
                                    <em style="color: #999;">—</em>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($cursos_ativos > 0) : ?>
                                    <span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                        <?php echo $cursos_ativos; ?> curso<?php echo $cursos_ativos > 1 ? 's' : ''; ?>
                                    </span>
                                <?php else : ?>
                                    <span style="background: #e5e7eb; color: #666; padding: 2px 8px; border-radius: 10px; font-size: 12px;">
                                        Nenhum
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=acesso-cursos-alunos&action=view&user_id=' . $user->ID); ?>">Ver Detalhes</a> |
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>">Editar Perfil</a> |
                                <a href="<?php echo admin_url('admin.php?page=acesso-cursos&filter_user=' . $user->ID); ?>">Ver Acessos</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
        <!-- Paginação -->
        <?php if ($total_pages > 1) : ?>
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
    </div>
    <?php
}

function acesso_cursos_aluno_detalhes() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'acesso_cursos';
    
    $user_id = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
    $user = get_user_by('ID', $user_id);
    
    if (!$user) {
        echo '<div class="wrap"><div class="notice notice-error"><p>Aluno não encontrado.</p></div></div>';
        return;
    }
    
    // Buscar todos os acessos do aluno
    $acessos = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE user_id = %d ORDER BY created_at DESC",
        $user_id
    ));
    
    // Buscar todos os cursos disponíveis
    $cursos = get_posts([
        'post_type' => 'curso',
        'posts_per_page' => -1,
        'orderby' => 'title',
        'order' => 'ASC'
    ]);
    
    // Mapear acessos por curso_id
    $acessos_map = [];
    foreach ($acessos as $acesso) {
        $acessos_map[$acesso->curso_id] = $acesso;
    }
    
    // Meta do usuário
    $last_login = get_user_meta($user_id, 'last_login', true) ?: get_user_meta($user_id, '_last_login', true);
    $phone = get_user_meta($user_id, 'billing_phone', true) ?: get_user_meta($user_id, 'phone', true);
    
    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline">
            <a href="<?php echo admin_url('admin.php?page=acesso-cursos-alunos'); ?>" style="text-decoration: none;">← </a>
            Detalhes do Aluno
        </h1>
        
        <?php if (isset($_GET['msg'])) : ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <?php
                    switch ($_GET['msg']) {
                        case 'acesso_atualizado': echo 'Acesso atualizado com sucesso!'; break;
                        case 'acesso_concedido': echo 'Acesso concedido com sucesso!'; break;
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
        
        <!-- Card do Perfil -->
        <div style="display: flex; gap: 20px; margin: 20px 0; flex-wrap: wrap;">
            <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; flex: 1; min-width: 300px;">
                <h2 style="margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    <?php echo get_avatar($user->ID, 48, '', '', ['style' => 'vertical-align: middle; margin-right: 10px; border-radius: 50%;']); ?>
                    <?php echo esc_html($user->display_name); ?>
                </h2>
                
                <table class="form-table" style="margin: 0;">
                    <tr>
                        <th style="padding: 8px 0; width: 120px;">ID</th>
                        <td style="padding: 8px 0;"><strong>#<?php echo $user->ID; ?></strong></td>
                    </tr>
                    <tr>
                        <th style="padding: 8px 0;">Email</th>
                        <td style="padding: 8px 0;">
                            <a href="mailto:<?php echo esc_attr($user->user_email); ?>"><?php echo esc_html($user->user_email); ?></a>
                        </td>
                    </tr>
                    <?php if ($user->first_name || $user->last_name) : ?>
                    <tr>
                        <th style="padding: 8px 0;">Nome Completo</th>
                        <td style="padding: 8px 0;"><?php echo esc_html(trim($user->first_name . ' ' . $user->last_name)); ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if ($phone) : ?>
                    <tr>
                        <th style="padding: 8px 0;">Telefone</th>
                        <td style="padding: 8px 0;"><?php echo esc_html($phone); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th style="padding: 8px 0;">Cadastrado em</th>
                        <td style="padding: 8px 0;"><?php echo date('d/m/Y H:i', strtotime($user->user_registered)); ?></td>
                    </tr>
                    <tr>
                        <th style="padding: 8px 0;">Último Login</th>
                        <td style="padding: 8px 0;">
                            <?php echo $last_login ? date('d/m/Y H:i', strtotime($last_login)) : '<em style="color:#999;">Não registrado</em>'; ?>
                        </td>
                    </tr>
                </table>
                
                <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                    <a href="<?php echo admin_url('user-edit.php?user_id=' . $user->ID); ?>" class="button">Editar Perfil Completo</a>
                </div>
            </div>
            
            <!-- Resumo de Acessos -->
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
                        <span style="background: #22c55e; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                            <?php echo $total_ativos; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Expirados</span>
                        <span style="background: #f59e0b; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                            <?php echo $total_expirados; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Suspensos</span>
                        <span style="background: #6b7280; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                            <?php echo $total_suspensos; ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Revogados</span>
                        <span style="background: #ef4444; color: white; padding: 2px 12px; border-radius: 10px; font-weight: 600;">
                            <?php echo $total_revogados; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Lista de Cursos e Acessos -->
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
                        <th style="width: 250px;">Ações Rápidas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cursos as $curso) :
                        $acesso = isset($acessos_map[$curso->ID]) ? $acessos_map[$curso->ID] : null;
                        $tem_acesso = $acesso && $acesso->status === 'ativo' && (!$acesso->data_fim || strtotime($acesso->data_fim) >= time());
                        $expirado = $acesso && $acesso->status === 'ativo' && $acesso->data_fim && strtotime($acesso->data_fim) < time();
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html($curso->post_title); ?></strong></td>
                            <td>
                                <?php if (!$acesso) : ?>
                                    <span style="color: #9ca3af;">Sem acesso</span>
                                <?php elseif ($expirado) : ?>
                                    <span style="color: #f59e0b; font-weight: 600;">Expirado</span>
                                <?php elseif ($acesso->status === 'ativo') : ?>
                                    <span style="color: #22c55e; font-weight: 600;">Ativo</span>
                                <?php elseif ($acesso->status === 'suspenso') : ?>
                                    <span style="color: #6b7280; font-weight: 600;">Suspenso</span>
                                <?php else : ?>
                                    <span style="color: #ef4444; font-weight: 600;">Revogado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($acesso && $acesso->data_fim) : ?>
                                    <?php echo date('d/m/Y', strtotime($acesso->data_fim)); ?>
                                <?php elseif ($acesso && $acesso->status === 'ativo') : ?>
                                    <em>Vitalício</em>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $acesso ? date('d/m/Y', strtotime($acesso->created_at)) : '—'; ?>
                            </td>
                            <td>
                                <?php if (!$acesso || $acesso->status !== 'ativo' || $expirado) : ?>
                                    <button type="submit" name="acao_rapida" value="ativar_<?php echo $curso->ID; ?>" class="button button-primary button-small">
                                        Conceder Acesso
                                    </button>
                                <?php else : ?>
                                    <button type="submit" name="acao_rapida" value="suspender_<?php echo $curso->ID; ?>" class="button button-small">
                                        Suspender
                                    </button>
                                    <button type="submit" name="acao_rapida" value="revogar_<?php echo $curso->ID; ?>" class="button button-small" style="color: #dc3232;">
                                        Revogar
                                    </button>
                                <?php endif; ?>
                                <?php if ($acesso && $acesso->status === 'suspenso') : ?>
                                    <button type="submit" name="acao_rapida" value="reativar_<?php echo $curso->ID; ?>" class="button button-primary button-small">
                                        Reativar
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>
        
        <!-- Conceder acesso com data -->
        <div style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-top: 20px; max-width: 500px;">
            <h3 style="margin-top: 0;">Conceder Acesso com Data de Expiração</h3>
            <form method="post" style="display: flex; flex-direction: column; gap: 10px;">
                <?php wp_nonce_field('aluno_conceder_acesso'); ?>
                <input type="hidden" name="user_id" value="<?php echo $user->ID; ?>">
                
                <label>
                    <strong>Curso:</strong><br>
                    <select name="curso_id" required style="width: 100%;">
                        <option value="">Selecione...</option>
                        <?php foreach ($cursos as $curso) : ?>
                            <option value="<?php echo $curso->ID; ?>"><?php echo esc_html($curso->post_title); ?></option>
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
    </div>
    <?php
}
