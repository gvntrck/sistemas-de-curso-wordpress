<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Barra_Lateral
{
    /**
     * class-shortcode-barra-lateral.php
     *
     * Shortcode [barra-lateral-aluno]
     * Renderiza a barra lateral do aluno com navegação e progresso.
     *
     * @package SistemaCursos
     * @version 1.0.0
     */
    public function __construct()
    {
        add_shortcode('barra-lateral-aluno', [$this, 'render_shortcode']);
    }

    public function render_shortcode($atts)
    {
        $atts = shortcode_atts([
            'link_inicio' => '#',
            'link_minha_conta' => '#',
            'link_meus_cursos' => '#',
            'link_todos_cursos' => '#',
            'link_certificados' => '#',
            'link_admin' => '#',
        ], $atts, 'barra-lateral-aluno');

        // Current User Data
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->display_name;
        $avatar_url = get_avatar_url($user_id, ['size' => 96]);

        ob_start();
        ?>
        <style>
            /* Scoped Styles for Sidebar Shortcode */
            .sc-sidebar-container {
                font-family: 'Inter', sans-serif;
                box-sizing: border-box;
            }

            .sc-sidebar-card {
                width: 300px;
                min-height: 514px;
                background-color: #111111;
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 2rem 1.5rem;
                color: white;
                overflow: hidden;
                margin: 0 auto;
                /* Center if single */
            }

            .sc-sidebar-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 1.5rem;
            }

            .sc-sidebar-avatar-container {
                width: 6rem;
                /* 96px */
                height: 6rem;
                border-radius: 9999px;
                border: 2px solid #fcc419;
                /* brand-gold */
                display: flex;
                align-items: center;
                justify-content: center;
                margin-bottom: 0.75rem;
                overflow: hidden;
            }

            .sc-sidebar-avatar {
                width: 100%;
                height: 100%;
                object-fit: cover;
            }

            .sc-sidebar-username {
                color: #71717a;
                font-size: 1rem;
                font-weight: 500;
                margin: 0;
            }

            .sc-sidebar-progress-section {
                width: 100%;
                margin-bottom: 2.5rem;
                padding: 0 1rem;
                box-sizing: border-box;
            }

            .sc-sidebar-nav {
                width: 100%;
            }

            .sc-sidebar-nav ul {
                list-style: none;
                padding: 0;
                margin: 0;
            }

            .sc-sidebar-nav li {
                margin-bottom: 1rem;
            }

            .sc-sidebar-nav li:last-child {
                margin-bottom: 0;
            }

            .sc-sidebar-link {
                display: flex;
                align-items: center;
                gap: 1rem;
                text-decoration: none;
                transition: opacity 0.2s;
                padding: 0.25rem 0;
            }

            .sc-sidebar-link:hover {
                opacity: 0.8;
            }

            .sc-sidebar-icon {
                width: 1.25rem;
                height: 1.25rem;
                color: #fcc419;
                /* brand-gold */
                flex-shrink: 0;
            }

            .sc-sidebar-text {
                font-size: 1rem;
                font-weight: 700;
                color: white;
            }

            .sc-sidebar-divider {
                padding-top: 1rem;
                margin-top: 1rem;
                border-top: 1px solid #2a2a2a;
            }
        </style>

        <div class="sc-sidebar-container">
            <aside class="sc-sidebar-card" id="sidebar-card">
                <!-- Header -->
                <header class="sc-sidebar-header">
                    <div class="sc-sidebar-avatar-container">
                        <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user_name); ?>"
                            class="sc-sidebar-avatar">
                    </div>
                    <h2 class="sc-sidebar-username">
                        <?php echo esc_html($user_name); ?>
                    </h2>
                </header>

                <!-- Progress Section -->
                <section class="sc-sidebar-progress-section">
                    <?php echo do_shortcode('[barra-progresso-geral]'); ?>
                </section>

                <!-- Navigation -->
                <nav class="sc-sidebar-nav">
                    <ul>
                        <li>
                            <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_inicio']); ?>">
                                <i class="sc-sidebar-icon" data-lucide="home"></i>
                                <span class="sc-sidebar-text">Inicio</span>
                            </a>
                        </li>
                        <li>
                            <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_minha_conta']); ?>">
                                <i class="sc-sidebar-icon" data-lucide="user"></i>
                                <span class="sc-sidebar-text">Minha conta</span>
                            </a>
                        </li>
                        <li>
                            <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_meus_cursos']); ?>">
                                <i class="sc-sidebar-icon" data-lucide="folder"></i>
                                <span class="sc-sidebar-text">Meus cursos</span>
                            </a>
                        </li>
                        <li>
                            <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_todos_cursos']); ?>">
                                <i class="sc-sidebar-icon" data-lucide="folder-open"></i>
                                <span class="sc-sidebar-text">Todos os cursos</span>
                            </a>
                        </li>
                        <li>
                            <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_certificados']); ?>">
                                <i class="sc-sidebar-icon" data-lucide="award"></i>
                                <span class="sc-sidebar-text">Meus certificados</span>
                            </a>
                        </li>

                        <?php if (current_user_can('manage_options') && !empty($atts['link_admin']) && $atts['link_admin'] !== '#'): ?>
                            <li class="sc-sidebar-divider">
                                <a class="sc-sidebar-link" href="<?php echo esc_url($atts['link_admin']); ?>">
                                    <i class="sc-sidebar-icon" data-lucide="wrench"></i>
                                    <span class="sc-sidebar-text">Admin</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </aside>
        </div>

        <script>
            // Initialize Lucide icons if not already initialized
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            } else if (!window.lucideLoading) {
                // Fallback: Load Lucide if missing (Safety net)
                window.lucideLoading = true;
                const script = document.createElement('script');
                script.src = "https://unpkg.com/lucide@latest";
                script.onload = () => lucide.createIcons();
                document.head.appendChild(script);
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
