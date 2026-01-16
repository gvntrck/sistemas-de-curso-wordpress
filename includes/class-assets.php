<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Assets
{
    /**
     * class-assets.php
     *
     * Gerencia o carregamento de scripts (JS) e estilos (CSS) do plugin.
     * Registra e enfileira os assets necessários para o funcionamento correto do frontend e do painel administrativo.
     *
     * @package SistemaCursos
     * @version 1.0.8
     */
    public function __construct()
    {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function enqueue_scripts()
    {
        // Define root URL based on this file's location (includes/)
        $plugin_url = plugin_dir_url(__FILE__) . '../';

        // CSS Principal
        wp_enqueue_style(
            'sistema-cursos-style',
            $plugin_url . 'assets/css/style.css',
            [],
            '1.0.6' // Bump version to force refresh
        );

        // JS Principal
        wp_enqueue_script(
            'sistema-cursos-script',
            $plugin_url . 'assets/js/script.js',
            [],
            '1.0.6',
            true
        );
    }

    public function enqueue_admin_scripts()
    {
        $plugin_url = plugin_dir_url(__FILE__) . '../';

        // JS Principal (necessário para máscaras e CEP no admin)
        wp_enqueue_script(
            'sistema-cursos-script-admin',
            $plugin_url . 'assets/js/script.js',
            [],
            '1.0.4',
            true
        );
    }
}
