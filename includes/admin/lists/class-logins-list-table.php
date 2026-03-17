<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class System_Cursos_Logins_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'login',
            'plural'   => 'logins',
            'ajax'     => false
        ]);
    }

    public function get_columns()
    {
        return [
            'cb'         => '<input type="checkbox" />',
            'user'       => 'Usuário',
            'email'      => 'E-mail',
            'last_login' => 'Último Login',
            'ip'         => 'IP',
            'ua'         => 'Navegador/Sistema'
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'user'       => ['user_login', false],
            'email'      => ['user_email', false],
            'last_login' => ['last_login', true]
        ];
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user':
                return sprintf('<strong>%s</strong>', esc_html($item['user_login']));
            case 'email':
                return sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($item['user_email']));
            case 'last_login':
                if (empty($item['last_login'])) {
                    return '<em>Nunca logou</em>';
                }
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['last_login']));
            case 'ip':
                return esc_html($item['ip'] ?: '-');
            case 'ua':
                $ua = esc_html($item['ua'] ?: '-');
                return sprintf('<span title="%1$s">%2$s</span>', $ua, mb_strimwidth($ua, 0, 50, '...'));
            default:
                return print_r($item, true);
        }
    }

    protected function column_cb($item)
    {
        return sprintf('<input type="checkbox" name="user[]" value="%s" />', $item['ID']);
    }

    public function prepare_items()
    {
        global $wpdb;

        $per_page = 20;
        $current_page = $this->get_pagenum();
        
        $search = isset($_POST['s']) ? sanitize_text_field($_POST['s']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'last_login';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Construir a consulta SQL
        $where = "WHERE 1=1";
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)", $search_like, $search_like, $search_like);
        }

        $order_sql = "ORDER BY m1.meta_value DESC"; // Default
        if ($orderby === 'user_login') {
            $order_sql = "ORDER BY u.user_login " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'user_email') {
            $order_sql = "ORDER BY u.user_email " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'last_login') {
            $order_sql = "ORDER BY m1.meta_value " . ($order === 'asc' ? 'ASC' : 'DESC');
        }

        $offset = ($current_page - 1) * $per_page;

        // Total de itens
        $total_items = $wpdb->get_var("SELECT COUNT(u.ID) FROM {$wpdb->users} u $where");

        // Buscar dados paginados
        $sql = "
            SELECT 
                u.ID, 
                u.user_login, 
                u.user_email,
                m1.meta_value as last_login,
                m2.meta_value as login_history
            FROM 
                {$wpdb->users} u
            LEFT JOIN 
                {$wpdb->usermeta} m1 ON u.ID = m1.user_id AND m1.meta_key = 'last_login'
            LEFT JOIN 
                {$wpdb->usermeta} m2 ON u.ID = m2.user_id AND m2.meta_key = '_login_history'
            $where
            $order_sql
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));

        $data = [];
        foreach ($results as $row) {
            $ip = '';
            $ua = '';
            
            // Extrair o último IP do histórico serializado
            if (!empty($row->login_history)) {
                $history = maybe_unserialize($row->login_history);
                if (is_array($history) && !empty($history[0])) {
                    $ip = isset($history[0]['ip']) ? $history[0]['ip'] : '';
                    $ua = isset($history[0]['ua']) ? $history[0]['ua'] : '';
                }
            }

            $data[] = [
                'ID'         => $row->ID,
                'user_login' => $row->user_login,
                'user_email' => $row->user_email,
                'last_login' => $row->last_login,
                'ip'         => $ip,
                'ua'         => $ua
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
