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
     * @version 1.2.0
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
        $user_name = trim((string) get_user_meta($user_id, 'first_name', true));
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
                position: relative;
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
                overflow: visible;
                margin: 0 auto;
                transition: width 0.3s ease, min-height 0.3s ease, padding 0.3s ease, border-radius 0.3s ease;
            }

            /* Toggle Button */
            .sc-sidebar-toggle {
                align-self: flex-end;
                width: 32px;
                height: 32px;
                border-radius: 50%;
                background: var(--color-accent, #fcc419);
                border: none;
                color: #111;
                cursor: pointer;
                display: flex;
                align-items: center;
                justify-content: center;
                z-index: 20;
                transition: background 0.2s ease;
                padding: 0;
                line-height: 1;
                margin-bottom: 0.5rem;
                flex-shrink: 0;
            }
            .sc-sidebar-toggle:hover {
                background: #e0ac00;
            }
            .sc-sidebar-toggle svg {
                width: 16px;
                height: 16px;
                transition: transform 0.3s ease;
                stroke: #111;
            }

            /* Collapsed State */
            .sc-sidebar-container.collapsed .sc-sidebar-card {
                width: 72px;
                min-height: auto;
                padding: 1rem 0.5rem;
                border-radius: 16px;
                align-items: center;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-header,
            .sc-sidebar-container.collapsed .sc-sidebar-progress-section {
                display: none;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-text {
                display: none;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-link {
                justify-content: center;
                padding: 0.65rem;
                gap: 0;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-icon {
                width: 1.4rem;
                height: 1.4rem;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-nav li {
                margin-bottom: 0.5rem;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-link.lms-nav-active {
                border-left: none;
                background: rgba(252, 196, 25, 0.15);
            }
            .sc-sidebar-container.collapsed .sc-sidebar-toggle svg {
                transform: rotate(180deg);
            }
            .sc-sidebar-container.collapsed .sc-sidebar-divider {
                padding-top: 0.5rem;
                margin-top: 0.5rem;
            }

            /* Tooltip on collapsed icons */
            .sc-sidebar-container.collapsed .sc-sidebar-link {
                position: relative;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-link::after {
                content: attr(data-tooltip);
                position: absolute;
                left: calc(100% + 10px);
                top: 50%;
                transform: translateY(-50%);
                background: #222;
                color: #e0e0e0;
                padding: 4px 10px;
                border-radius: 6px;
                font-size: 0.8rem;
                white-space: nowrap;
                opacity: 0;
                pointer-events: none;
                transition: opacity 0.2s;
                z-index: 100;
            }
            .sc-sidebar-container.collapsed .sc-sidebar-link:hover::after {
                opacity: 1;
            }

            .sc-sidebar-header {
                display: flex;
                flex-direction: column;
                align-items: center;
                margin-bottom: 1.5rem;
                transition: opacity 0.2s ease;
            }

            .sc-sidebar-avatar-container {
                width: 6rem;
                height: 6rem;
                border-radius: 50%;
                border: 2px solid var(--color-accent, #fcc419);
                margin-bottom: 0.75rem;
                overflow: hidden;
            }

            .sc-sidebar-avatar-container img.sc-sidebar-avatar {
                width: 100% !important;
                height: 100% !important;
                object-fit: cover !important;
                display: block !important;
                max-height: none !important;
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
                transition: opacity 0.2s ease;
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
                transition: opacity 0.2s, background 0.2s, padding 0.3s, gap 0.3s;
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
                transition: width 0.3s, height 0.3s;
            }

            .sc-sidebar-text {
                font-size: 1rem;
                font-weight: 700;
                color: var(--color-text-primary, #e0e0e0);
                transition: opacity 0.2s;
                white-space: nowrap;
            }

            .sc-sidebar-divider {
                padding-top: 1rem;
                margin-top: 1rem;
                border-top: 1px solid var(--color-border, #2a2a2a);
            }

            /* Mobile hamburger */
            .sc-sidebar-mobile-toggle {
                display: none;
                position: fixed;
                bottom: 20px;
                left: 20px;
                width: 48px;
                height: 48px;
                border-radius: 50%;
                background: var(--color-accent, #fcc419);
                color: #111;
                border: none;
                cursor: pointer;
                z-index: 1001;
                align-items: center;
                justify-content: center;
                box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                padding: 0;
            }
            .sc-sidebar-mobile-toggle svg {
                width: 22px;
                height: 22px;
            }

            /* Mobile overlay */
            .sc-sidebar-overlay {
                display: none;
                position: fixed;
                inset: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }

            @media (max-width: 900px) {
                .sc-sidebar-toggle {
                    display: none !important;
                }
                .sc-sidebar-mobile-toggle {
                    display: flex;
                }
                .sc-sidebar-container {
                    position: fixed;
                    top: 0;
                    left: -320px;
                    height: 100vh;
                    z-index: 1000;
                    transition: left 0.3s ease;
                }
                .sc-sidebar-container .sc-sidebar-card {
                    border-radius: 0 16px 16px 0;
                    height: 100%;
                    overflow-y: auto;
                    min-height: 100vh;
                }
                .sc-sidebar-container.collapsed .sc-sidebar-card {
                    width: 300px;
                    padding: 2rem 1.5rem;
                    border-radius: 0 16px 16px 0;
                }
                .sc-sidebar-container.collapsed .sc-sidebar-header,
                .sc-sidebar-container.collapsed .sc-sidebar-progress-section,
                .sc-sidebar-container.collapsed .sc-sidebar-text {
                    display: initial;
                }
                .sc-sidebar-container.collapsed .sc-sidebar-link {
                    justify-content: flex-start;
                    padding: 0.5rem 0.75rem;
                    gap: 1rem;
                }
                .sc-sidebar-container.collapsed .sc-sidebar-link::after {
                    display: none;
                }
                .sc-sidebar-container.collapsed .sc-sidebar-icon {
                    width: 1.25rem;
                    height: 1.25rem;
                }
                .sc-sidebar-container.mobile-open {
                    left: 0;
                }
                .sc-sidebar-overlay.active {
                    display: block;
                }
            }
        </style>

        <div class="sc-sidebar-overlay" id="sc-sidebar-overlay"></div>
        <button class="sc-sidebar-mobile-toggle" id="sc-mobile-toggle" aria-label="Menu">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        </button>
        <div class="sc-sidebar-container" id="sc-sidebar-container">
            <aside class="sc-sidebar-card" id="sidebar-card">
                <!-- Toggle -->
                <button class="sc-sidebar-toggle" id="sc-sidebar-toggle" aria-label="Recolher menu">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                </button>
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
                                        data-view="<?php echo esc_attr($item['view']); ?>"
                                        data-tooltip="<?php echo esc_attr($item['label']); ?>">
                                        <i class="sc-sidebar-icon" data-lucide="<?php echo esc_attr($item['icon']); ?>"></i>
                                        <span class="sc-sidebar-text"><?php echo esc_html($item['label']); ?></span>
                                    </a>
                                </li>
                            <?php else:
                                $link = isset($link_atts[$item['link_key']]) ? $link_atts[$item['link_key']] : '#';
                                ?>
                                <li>
                                    <a class="sc-sidebar-link" href="<?php echo esc_url($link); ?>"
                                        data-tooltip="<?php echo esc_attr($item['label']); ?>">
                                        <i class="sc-sidebar-icon" data-lucide="<?php echo esc_attr($item['icon']); ?>"></i>
                                        <span class="sc-sidebar-text"><?php echo esc_html($item['label']); ?></span>
                                    </a>
                                </li>
                            <?php endif;
                        endforeach; ?>

                        <?php if (in_array('administrator', (array) $current_user->roles)):
                            if ($painel_mode): ?>
                                <li class="sc-sidebar-divider">
                                    <a class="sc-sidebar-link lms-nav-link" href="#" data-view="cadastro" data-tooltip="Admin">
                                        <i class="sc-sidebar-icon" data-lucide="wrench"></i>
                                        <span class="sc-sidebar-text">Admin</span>
                                    </a>
                                </li>
                            <?php elseif (!empty($link_atts['link_admin']) && $link_atts['link_admin'] !== '#'): ?>
                                <li class="sc-sidebar-divider">
                                    <a class="sc-sidebar-link" href="<?php echo esc_url($link_atts['link_admin']); ?>" data-tooltip="Admin">
                                        <i class="sc-sidebar-icon" data-lucide="wrench"></i>
                                        <span class="sc-sidebar-text">Admin</span>
                                    </a>
                                </li>
                            <?php endif;
                        endif; ?>
                    </ul>
                </nav>
            </aside>
        </div><!-- .sc-sidebar-container -->

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

            // Sidebar collapse/expand
            (function() {
                var container = document.getElementById('sc-sidebar-container');
                var toggleBtn = document.getElementById('sc-sidebar-toggle');
                var mobileBtn = document.getElementById('sc-mobile-toggle');
                var overlay = document.getElementById('sc-sidebar-overlay');
                var STORAGE_KEY = 'sc_sidebar_collapsed';

                if (!container || !toggleBtn) return;

                // Restore state from localStorage (desktop only)
                if (window.innerWidth > 900 && localStorage.getItem(STORAGE_KEY) === '1') {
                    container.classList.add('collapsed');
                }

                toggleBtn.addEventListener('click', function() {
                    container.classList.toggle('collapsed');
                    localStorage.setItem(STORAGE_KEY, container.classList.contains('collapsed') ? '1' : '0');
                });

                // Mobile open/close
                if (mobileBtn && overlay) {
                    mobileBtn.addEventListener('click', function() {
                        var isOpen = container.classList.contains('mobile-open');
                        if (isOpen) {
                            container.classList.remove('mobile-open');
                            overlay.classList.remove('active');
                        } else {
                            container.classList.add('mobile-open');
                            overlay.classList.add('active');
                        }
                    });
                    overlay.addEventListener('click', function() {
                        container.classList.remove('mobile-open');
                        overlay.classList.remove('active');
                    });
                    // Close sidebar on nav link click (mobile)
                    container.addEventListener('click', function(e) {
                        if (e.target.closest('.sc-sidebar-link') && window.innerWidth <= 900) {
                            container.classList.remove('mobile-open');
                            overlay.classList.remove('active');
                        }
                    });
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }
}
