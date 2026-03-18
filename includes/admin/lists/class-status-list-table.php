<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class System_Cursos_Status_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'acesso',
            'plural'   => 'acessos',
            'ajax'     => false
        ]);
    }

    public function get_columns()
    {
        return [
            'user'       => 'Usuário',
            'curso'      => 'Curso',
            'status'     => 'Status',
            'data_fim'   => 'Data Expiração',
            'created_at' => 'Data Concessão'
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'user'       => ['user_login', false],
            'curso'      => ['curso_titulo', false],
            'status'     => ['status', false],
            'data_fim'   => ['data_fim', false],
            'created_at' => ['created_at', true]
        ];
    }

    protected function get_views()
    {
        $status_links = [];
        $current = isset($_GET['filter_status']) ? sanitize_key($_GET['filter_status']) : 'all';
        $base_url = '?page=lms-suporte-rapido-reports&tab=status';

        $status_links['all'] = sprintf(
            '<a href="%s" class="%s">%s</a>',
            $base_url,
            $current === 'all' ? 'current' : '',
            'Todos'
        );

        $statuses = [
            'ativo'    => 'Ativo',
            'suspenso' => 'Suspenso',
            'revogado' => 'Revogado'
        ];

        foreach ($statuses as $key => $label) {
            $status_links[$key] = sprintf(
                '<a href="%s" class="%s">%s</a>',
                $base_url . '&filter_status=' . $key,
                $current === $key ? 'current' : '',
                $label
            );
        }

        return $status_links;
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user':
                return sprintf('<strong>%s</strong><br><small><a href="mailto:%s">%s</a></small>', esc_html($item['user_login']), esc_html($item['user_email']), esc_html($item['user_email']));
            case 'curso':
                $url = get_edit_post_link($item['curso_id']);
                return sprintf('<strong><a href="%s">%s</a></strong>', $url, esc_html($item['curso_title']));
            case 'status':
                $colors = [
                    'ativo'    => '#135e96',
                    'suspenso' => '#d63638',
                    'revogado' => '#8a2424'
                ];
                $color = isset($colors[$item['status']]) ? $colors[$item['status']] : '#666';
                return sprintf('<span style="color: %s; font-weight: bold; text-transform: uppercase;">%s</span>', $color, esc_html($item['status']));
            case 'data_fim':
                if (empty($item['data_fim'])) {
                    return '<em>Vitalício</em>';
                }
                
                $expirou = strtotime($item['data_fim']) < current_time('timestamp');
                $texto   = date_i18n(get_option('date_format'), strtotime($item['data_fim']));
                
                if ($expirou) {
                    return sprintf('<span style="color: #d63638;">%s (Expirado)</span>', $texto);
                }
                return $texto;

            case 'created_at':
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['created_at']));
            default:
                return print_r($item, true);
        }
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $filter = isset($_GET['filter_status']) ? sanitize_key($_GET['filter_status']) : 'all';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $where = "WHERE 1=1";
        
        if ($filter !== 'all') {
            $where .= $wpdb->prepare(" AND a.status = %s", $filter);
        }

        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(
                " AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s OR p.post_title LIKE %s)",
                $search_like, $search_like, $search_like, $search_like
            );
        }

        $order_sql = "ORDER BY a.created_at DESC";
        if ($orderby === 'user_login') {
            $order_sql = "ORDER BY u.user_login " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'curso_titulo') {
            $order_sql = "ORDER BY p.post_title " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'status') {
            $order_sql = "ORDER BY a.status " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'data_fim') {
            $order_sql = "ORDER BY a.data_fim " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'created_at') {
            $order_sql = "ORDER BY a.created_at " . ($order === 'asc' ? 'ASC' : 'DESC');
        }

        $offset = ($current_page - 1) * $per_page;
        $tabela_acessos = $wpdb->prefix . 'acesso_cursos';

        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                a.id as ID,
                a.status,
                a.data_fim,
                a.created_at,
                a.curso_id,
                u.user_login, 
                u.user_email,
                p.post_title as curso_title
            FROM 
                {$tabela_acessos} a
            INNER JOIN {$wpdb->users} u ON a.user_id = u.ID
            INNER JOIN {$wpdb->posts} p ON a.curso_id = p.ID
            $where
            $order_sql
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'ID'          => $row->ID,
                'user_login'  => $row->user_login,
                'user_email'  => $row->user_email,
                'curso_id'    => $row->curso_id,
                'curso_title' => $row->curso_title,
                'status'      => $row->status,
                'data_fim'    => $row->data_fim,
                'created_at'  => $row->created_at
            ];
        }

        $this->items = $data;

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page'    => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ]);
    }
}
