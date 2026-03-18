<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class System_Cursos_Aulas_List_Table extends WP_List_Table
{
    public function __construct()
    {
        parent::__construct([
            'singular' => 'aula',
            'plural'   => 'aulas',
            'ajax'     => false
        ]);
    }

    public function get_columns()
    {
        return [
            'user'             => 'Usuário',
            'email'            => 'E-mail',
            'aulas_assistidas' => 'Total de Aulas Assistidas',
            'last_atividade'   => 'Última Aula Concluída'
        ];
    }

    public function get_sortable_columns()
    {
        return [
            'user'             => ['user_login', false],
            'email'            => ['user_email', false],
            'aulas_assistidas' => ['total_aulas', true],
            'last_atividade'   => ['last_atividade', false]
        ];
    }

    protected function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'user':
                return sprintf('<strong>%s</strong>', esc_html($item['user_login']));
            case 'email':
                return sprintf('<a href="mailto:%1$s">%1$s</a>', esc_html($item['user_email']));
            case 'aulas_assistidas':
                return sprintf('<strong>%d</strong>', $item['total_aulas']);
            case 'last_atividade':
                if (empty($item['last_atividade'])) {
                    return '-';
                }
                return date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($item['last_atividade']));
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
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'total_aulas';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        $where = "WHERE 1=1";
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $where .= $wpdb->prepare(" AND (u.user_login LIKE %s OR u.user_email LIKE %s OR u.display_name LIKE %s)", $search_like, $search_like, $search_like);
        }

        $order_sql = "ORDER BY total_aulas DESC";
        if ($orderby === 'user_login') {
            $order_sql = "ORDER BY u.user_login " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'user_email') {
            $order_sql = "ORDER BY u.user_email " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'total_aulas') {
            $order_sql = "ORDER BY total_aulas " . ($order === 'asc' ? 'ASC' : 'DESC');
        } elseif ($orderby === 'last_atividade') {
            $order_sql = "ORDER BY last_atividade " . ($order === 'asc' ? 'ASC' : 'DESC');
        }

        $offset = ($current_page - 1) * $per_page;
        $tabela_progresso = $wpdb->prefix . 'progresso_aluno';

        // Consulta agregada para trazer os dados reais de progresso
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                u.ID, 
                u.user_login, 
                u.user_email,
                COUNT(p.id) as total_aulas,
                MAX(p.data_conclusao) as last_atividade
            FROM 
                {$wpdb->users} u
            INNER JOIN 
                {$tabela_progresso} p ON u.ID = p.user_id
            $where
            GROUP BY 
                u.ID, u.user_login, u.user_email
            $order_sql
            LIMIT %d OFFSET %d
        ";

        $results = $wpdb->get_results($wpdb->prepare($sql, $per_page, $offset));
        
        // Obter total de itens contados pela query
        $total_items = $wpdb->get_var("SELECT FOUND_ROWS()");

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                'ID'               => $row->ID,
                'user_login'       => $row->user_login,
                'user_email'       => $row->user_email,
                'total_aulas'      => (int) $row->total_aulas,
                'last_atividade'   => $row->last_atividade
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
