<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class System_Cursos_Admin_Reports
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'add_reports_menu'], 20);
    }

    public function add_reports_menu()
    {
        // Forçar no admin_menu_hook correto (o parent do plugin no caso)
        add_submenu_page(
            'lms-suporte-rapido',
            'Relatórios',
            'Relatórios',
            'manage_options',
            'lms-suporte-rapido-reports',
            [$this, 'render_reports_page']
        );
    }

    public function render_reports_page()
    {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'logins';

        echo '<div class="wrap">';
        echo '<h1>Relatórios LMS</h1>';
        
        echo '<nav class="nav-tab-wrapper">';
        echo '<a href="?page=lms-suporte-rapido-reports&tab=logins" class="nav-tab ' . ($active_tab == 'logins' ? 'nav-tab-active' : '') . '">Últimos Logins</a>';
        echo '<a href="?page=lms-suporte-rapido-reports&tab=aulas" class="nav-tab ' . ($active_tab == 'aulas' ? 'nav-tab-active' : '') . '">Aulas Assistidas</a>';
        echo '<a href="?page=lms-suporte-rapido-reports&tab=status" class="nav-tab ' . ($active_tab == 'status' ? 'nav-tab-active' : '') . '">Status Matrículas</a>';
        echo '</nav>';

        echo '<div class="sc-report-content" style="margin-top: 20px;">';
        
        switch ($active_tab) {
            case 'logins':
                $this->render_logins_table();
                break;
            case 'aulas':
                $this->render_aulas_table();
                break;
            case 'status':
                $this->render_status_table();
                break;
        }

        echo '</div>';
        echo '</div>';
    }

    private function render_logins_table()
    {
        require_once plugin_dir_path(__FILE__) . 'lists/class-logins-list-table.php';
        $table = new System_Cursos_Logins_List_Table();
        $table->prepare_items();
        
        echo '<form method="post">';
        $table->search_box('Buscar Usuário', 'search_id');
        $table->display();
        echo '</form>';
    }

    private function render_aulas_table()
    {
        require_once plugin_dir_path(__FILE__) . 'lists/class-aulas-list-table.php';
        $table = new System_Cursos_Aulas_List_Table();
        $table->prepare_items();

        echo '<form method="post">';
        $table->search_box('Buscar Usuário', 'search_id');
        $table->display();
        echo '</form>';
    }

    private function render_status_table()
    {
        require_once plugin_dir_path(__FILE__) . 'lists/class-status-list-table.php';
        $table = new System_Cursos_Status_List_Table();
        $table->prepare_items();

        echo '<form method="post">';
        $table->search_box('Buscar Usuário ou Curso', 'search_id');
        $table->display();
        echo '</form>';
    }
}
