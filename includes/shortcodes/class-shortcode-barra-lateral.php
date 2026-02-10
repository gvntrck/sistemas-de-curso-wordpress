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
        <!-- BEGIN: SidebarCard -->
        <aside
            class="w-[300px] h-[514px] bg-[#111111] rounded-[24px] shadow-2xl flex flex-col items-center py-8 px-6 text-white overflow-hidden"
            data-purpose="user-navigation-card" id="sidebar-card">
            <!-- BEGIN: ProfileHeader -->
            <header class="flex flex-col items-center mb-6" data-purpose="profile-section">
                <!-- Profile Avatar with Gold Border -->
                <div
                    class="w-24 h-24 rounded-full border-2 border-brand-gold flex items-center justify-center mb-3 overflow-hidden">
                    <img src="<?php echo esc_url($avatar_url); ?>" alt="<?php echo esc_attr($user_name); ?>"
                        class="w-full h-full object-cover">
                </div>
                <!-- Username -->
                <h2 class="text-[#71717a] text-base font-medium">
                    <?php echo esc_html($user_name); ?>
                </h2>
            </header>
            <!-- END: ProfileHeader -->

            <!-- BEGIN: ProgressSection -->
            <section class="w-full mb-10 px-4" data-purpose="progress-tracker">
                <?php echo do_shortcode('[barra-progresso-geral]'); ?>
            </section>
            <!-- END: ProgressSection -->

            <!-- BEGIN: NavigationMenu -->
            <nav class="w-full" data-purpose="sidebar-navigation">
                <ul class="space-y-4">
                    <!-- Inicio -->
                    <li>
                        <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                            href="<?php echo esc_url($atts['link_inicio']); ?>">
                            <i class="w-5 h-5 text-brand-gold" data-lucide="home"></i>
                            <span class="text-base font-bold text-white">Inicio</span>
                        </a>
                    </li>
                    <!-- Minha conta -->
                    <li>
                        <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                            href="<?php echo esc_url($atts['link_minha_conta']); ?>">
                            <i class="w-5 h-5 text-brand-gold" data-lucide="user"></i>
                            <span class="text-base font-bold text-white">Minha conta</span>
                        </a>
                    </li>
                    <!-- Meus cursos -->
                    <li>
                        <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                            href="<?php echo esc_url($atts['link_meus_cursos']); ?>">
                            <i class="w-5 h-5 text-brand-gold" data-lucide="folder"></i>
                            <span class="text-base font-bold text-white">Meus cursos</span>
                        </a>
                    </li>
                    <!-- Todos os cursos -->
                    <li>
                        <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                            href="<?php echo esc_url($atts['link_todos_cursos']); ?>">
                            <i class="w-5 h-5 text-brand-gold" data-lucide="folder-open"></i>
                            <span class="text-base font-bold text-white">Todos os cursos</span>
                        </a>
                    </li>
                    <!-- Meus certificados -->
                    <li>
                        <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                            href="<?php echo esc_url($atts['link_certificados']); ?>">
                            <i class="w-5 h-5 text-brand-gold" data-lucide="award"></i>
                            <span class="text-base font-bold text-white">Meus certificados</span>
                        </a>
                    </li>

                    <?php if (current_user_can('manage_options') && !empty($atts['link_admin']) && $atts['link_admin'] !== '#'): ?>
                        <!-- Admin (spaced further down) -->
                        <li class="pt-4">
                            <a class="group flex items-center gap-4 transition-colors hover:opacity-80"
                                href="<?php echo esc_url($atts['link_admin']); ?>">
                                <i class="w-5 h-5 text-brand-gold" data-lucide="wrench"></i>
                                <span class="text-base font-bold text-white">Admin</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <!-- END: NavigationMenu -->
        </aside>
        <!-- END: SidebarCard -->
        <script>
            // Initialize Lucide icons if not already initialized
            if (typeof lucide !== 'undefined') {
                lucide.createIcons();
            }
        </script>
        <?php
        return ob_get_clean();
    }
}
