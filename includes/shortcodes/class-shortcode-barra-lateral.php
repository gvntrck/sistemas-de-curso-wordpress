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
     * Possui método estático render_sidebar_html() reutilizável pelo [lms-painel].
     *
     * @package SistemaCursos
     * @version 1.1.1
     */
    public function __construct()
    {
        add_shortcode('barra-lateral-aluno', [$this, 'render_shortcode']);
    }

    /**
     * Renderiza a sidebar como shortcode standalone (retrocompatível).
     */
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

        return self::render_sidebar_html(false, $atts);
    }

    /**
     * Método estático reutilizável para renderizar a sidebar.
     *
     * @param bool  $painel_mode  Se true, links usam data-view para navegação SPA.
     * @param array $link_atts    Atributos de link (usado somente quando painel_mode = false).
     * @return string HTML da sidebar.
     */
    public static function render_sidebar_html($painel_mode = false, $link_atts = [])
    {
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $user_name = $current_user->display_name;
        $avatar_id = get_user_meta($user_id, 'local_user_avatar_attachment_id', true);
        $avatar_url = '';
        if ($avatar_id) {
            $avatar_url = wp_get_attachment_image_url($avatar_id, 'medium');
        }
        if (empty($avatar_url)) {
            $avatar_url = get_avatar_url($user_id, ['size' => 96]);
        }

        // Definir itens de navegação
        $nav_items = [
            [
                'icon' => 'home',
                'label' => 'Inicio',
                'view' => 'inicio',
                'link_key' => 'link_inicio',
            ],
            [
                'icon' => 'user',
                'label' => 'Minha conta',
                'view' => 'minha-conta',
                'link_key' => 'link_minha_conta',
            ],
            [
                'icon' => 'folder',
                'label' => 'Meus cursos',
                'view' => 'meus-cursos',
                'link_key' => 'link_meus_cursos',
            ],
            [
                'icon' => 'folder-open',
                'label' => 'Todos os cursos',
                'view' => 'todos-cursos',
                'link_key' => 'link_todos_cursos',
            ],
            [
                'icon' => 'award',
                'label' => 'Meus certificados',
                'view' => 'certificados',
                'link_key' => 'link_certificados',
            ],
        ];

        ob_start();
        ?>
        <style>
            /* Scoped Styles for Sidebar Shortcode */
            .sc-sidebar-container {
                font-family: var(--font-family, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
                box-sizing: border-box;
            }

            .sc-sidebar-card {
                width: 300px;
                min-height: 514px;
                background-color: var(--color-bg-tertiary, #111111);
                border-radius: 24px;
                box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
                display: flex;
                flex-direction: column;
                align-items: center;
                padding: 2rem 1.5rem;
                color: var(--color-text-primary, #e0e0e0);
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
                border: 2px solid var(--color-accent, #fcc419);
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
                color: var(--color-text-muted, #7a7a7a) !important;
                font-family: var(--font-family, 'Roboto', sans-serif) !important;
                font-size: 16px !important;
                font-weight: 400 !important;
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
                transition: opacity 0.2s, background 0.2s;
                padding: 0.5rem 0.75rem;
                border-radius: 8px;
                cursor: pointer;
            }

            .sc-sidebar-link:hover {
                opacity: 1;
                background: rgba(128, 128, 128, 0.15);
            }

            .sc-sidebar-link.lms-nav-active {
                background: rgba(128, 128, 128, 0.2);
                opacity: 1;
                border-left: 3px solid var(--color-accent, #fcc419);
            }

            .sc-sidebar-icon {
                width: 1.25rem;
                height: 1.25rem;
                color: var(--color-accent, #fcc419);
                flex-shrink: 0;
            }

            .sc-sidebar-text {
                font-size: 1rem;
                font-weight: 700;
                color: var(--color-text-primary, #e0e0e0);
            }

            .sc-sidebar-divider {
                padding-top: 1rem;
                margin-top: 1rem;
                border-top: 1px solid var(--color-border, #2a2a2a);
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
                        <?php foreach ($nav_items as $index => $item):
                            $is_first = ($index === 0);
                            if ($painel_mode): ?>
                                <li>
                                    <a class="sc-sidebar-link lms-nav-link<?php echo $is_first ? ' lms-nav-active' : ''; ?>" href="#"
                                        data-view="<?php echo esc_attr($item['view']); ?>">
                                        <i class="sc-sidebar-icon" data-lucide="<?php echo esc_attr($item['icon']); ?>"></i>
                                        <span class="sc-sidebar-text"><?php echo esc_html($item['label']); ?></span>
                                    </a>
                                </li>
                            <?php else:
                                $link = isset($link_atts[$item['link_key']]) ? $link_atts[$item['link_key']] : '#';
                                ?>
                                <li>
                                    <a class="sc-sidebar-link" href="<?php echo esc_url($link); ?>">
                                        <i class="sc-sidebar-icon" data-lucide="<?php echo esc_attr($item['icon']); ?>"></i>
                                        <span class="sc-sidebar-text"><?php echo esc_html($item['label']); ?></span>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach; ?>

                        <?php if (in_array('administrator', (array) $current_user->roles)):
                            if ($painel_mode): ?>
                                <li class="sc-sidebar-divider">
                                    <a class="sc-sidebar-link lms-nav-link" href="#" data-view="cadastro">
                                        <i class="sc-sidebar-icon" data-lucide="wrench"></i>
                                        <span class="sc-sidebar-text">Admin</span>
                                    </a>
                                </li>
                            <?php elseif (!empty($link_atts['link_admin']) && $link_atts['link_admin'] !== '#'): ?>
                                <li class="sc-sidebar-divider">
                                    <a class="sc-sidebar-link" href="<?php echo esc_url($link_atts['link_admin']); ?>">
                                        <i class="sc-sidebar-icon" data-lucide="wrench"></i>
                                        <span class="sc-sidebar-text">Admin</span>
                                    </a>
                                </li>
                            <?php endif;
                        endif; ?>
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
