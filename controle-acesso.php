<?php
/**
 * Controle de Acesso a Cursos
 * Versão: 1.0.1
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
        $user_id = (int) $_POST['user_id'];
        $curso_id = (int) $_POST['curso_id'];
        $status = sanitize_text_field($_POST['status']);
        $data_fim = !empty($_POST['data_fim']) ? sanitize_text_field($_POST['data_fim']) : null;
        
        if ($user_id > 0 && $curso_id > 0) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'acesso_cursos';
            
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
                    <th><label for="user_id">Usuário (ID)</label></th>
                    <td>
                        <input type="number" name="user_id" id="user_id" class="regular-text" 
                               value="<?php echo $item ? $item->user_id : ''; ?>" required 
                               <?php echo $id ? 'readonly' : ''; ?>>
                        <?php if ($id && $item) :
                            $user = get_user_by('ID', $item->user_id);
                            if ($user) : ?>
                                <p class="description"><?php echo esc_html($user->display_name . ' - ' . $user->user_email); ?></p>
                            <?php endif;
                        endif; ?>
                    </td>
                </tr>
                <tr>
                    <th><label for="curso_id">Curso</label></th>
                    <td>
                        <select name="curso_id" id="curso_id" required <?php echo $id ? 'disabled' : ''; ?>>
                            <option value="">Selecione...</option>
                            <?php foreach ($cursos as $curso) : ?>
                                <option value="<?php echo $curso->ID; ?>" <?php echo $item && $item->curso_id == $curso->ID ? 'selected' : ''; ?>>
                                    <?php echo esc_html($curso->post_title); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($id) : ?>
                            <input type="hidden" name="curso_id" value="<?php echo $item->curso_id; ?>">
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
    <?php
}
