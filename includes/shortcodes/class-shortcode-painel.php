<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Shortcode_Painel
{
    /**
     * class-shortcode-painel.php
     *
     * Shortcode [lms-painel]
     * Renderiza um painel completo estilo SPA com sidebar permanente
     * e carregamento de views via AJAX (sem recarregar a página).
     *
     * Views disponíveis:
     * - inicio       → Meus Cursos (padrão)
     * - minha-conta   → Perfil do Usuário
     * - meus-cursos   → Meus Cursos (igual inicio)
     * - todos-cursos  → Catálogo Completo
     * - certificados  → Certificados
     * - cadastro      → Cadastro de Usuários (admin only)
     *
     * @package SistemaCursos
     * @version 1.1.0
     */
    public function __construct()
    {
        add_shortcode('lms-painel', [$this, 'render_shortcode']);

        // AJAX handlers (logado)
        add_action('wp_ajax_lms_painel_load_view', [$this, 'ajax_load_view']);
    }

    /**
     * Renderiza o layout principal do painel.
     */
    public function render_shortcode($atts)
    {
        if (!is_user_logged_in()) {
            return sprintf(
                '<div class="mc-alert mc-error" style="color: #fff; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); padding: 15px; border-radius: 6px; text-align: center;">%s <a href="%s" style="color: inherit; text-decoration: underline;">%s</a></div>',
                'Você precisa estar logado para acessar o painel.',
                wp_login_url(get_permalink()),
                'Fazer login'
            );
        }

        // Conteúdo inicial: Meus Cursos
        $GLOBALS['lms_painel_mode'] = true;
        $initial_content = do_shortcode('[meus-cursos]');
        unset($GLOBALS['lms_painel_mode']);

        ob_start();
        ?>
        <style>
            /* ===== LMS Painel Layout ===== */
            .lms-painel-wrapper {
                display: flex;
                gap: 2rem;
                align-items: flex-start;
                max-width: 100%;
                font-family: var(--font-family, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif);
            }

            .lms-painel-sidebar {
                flex-shrink: 0;
                position: sticky;
                top: 2rem;
            }

            .lms-painel-content {
                flex: 1;
                min-width: 0;
                min-height: 400px;
                position: relative;
            }

            /* Loading Spinner */
            .lms-painel-loading {
                display: none;
                position: absolute;
                inset: 0;
                background: rgba(17, 17, 17, 0.8);
                z-index: 10;
                border-radius: 12px;
                align-items: center;
                justify-content: center;
            }

            .lms-painel-loading.active {
                display: flex;
            }

            .lms-spinner {
                width: 40px;
                height: 40px;
                border: 3px solid var(--color-accent-shadow, rgba(252, 196, 25, 0.2));
                border-top-color: var(--color-accent, #fcc419);
                border-radius: 50%;
                animation: lms-spin 0.8s linear infinite;
            }

            @keyframes lms-spin {
                to {
                    transform: rotate(360deg);
                }
            }

            /* Fade in animation */
            .lms-painel-content .lms-view-content {
                animation: lms-fadeIn 0.3s ease;
            }

            @keyframes lms-fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(8px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            /* Responsive */
            @media (max-width: 900px) {
                .lms-painel-wrapper {
                    flex-direction: column;
                }

                .lms-painel-sidebar {
                    position: static;
                    width: 100%;
                }

                .lms-painel-sidebar .sc-sidebar-card {
                    width: 100% !important;
                    min-height: auto !important;
                    border-radius: 16px;
                    padding: 1.5rem 1rem;
                }

                .lms-painel-content {
                    width: 100%;
                }
            }
        </style>

        <div class="lms-sr lms-painel-wrapper" id="lms-painel">
            <!-- Sidebar permanente -->
            <div class="lms-painel-sidebar">
                <?php echo System_Cursos_Shortcode_Barra_Lateral::render_sidebar_html(true); ?>
            </div>

            <!-- Área de conteúdo dinâmico -->
            <div class="lms-painel-content">
                <div class="lms-painel-loading" id="lms-loading">
                    <div class="lms-spinner"></div>
                </div>
                <div class="lms-view-content" id="lms-view-content">
                    <?php echo $initial_content; ?>
                </div>
            </div>
        </div>

        <script>
            (function () {
                'use strict';

                var currentView = 'inicio';
                var currentCursoId = null;
                var viewCache = {};
                var ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
                var nonce = '<?php echo wp_create_nonce('lms_painel_nonce'); ?>';

                // Cache da view inicial
                var initialContent = document.getElementById('lms-view-content');
                if (initialContent) {
                    viewCache['inicio'] = initialContent.innerHTML;
                    viewCache['meus-cursos'] = initialContent.innerHTML;
                }

                // Verificar se há view na URL (?lms_view=xxx&curso_id=yyy)
                var urlParams = new URLSearchParams(window.location.search);
                var urlView = urlParams.get('lms_view');
                var urlCursoId = urlParams.get('curso_id');

                /**
                 * Carrega uma view no painel via AJAX.
                 * @param {string} viewName - Nome da view (inicio, meus-cursos, curso, etc.)
                 * @param {boolean} pushState - Se deve atualizar a URL no histórico.
                 * @param {string|null} cursoId - ID do curso (apenas para view 'curso').
                 */
                function loadView(viewName, pushState, cursoId) {
                    if (typeof pushState === 'undefined') pushState = true;
                    if (typeof cursoId === 'undefined') cursoId = null;

                    // Se já é a view atual (e não é a primeira carga), ignorar
                    // Para view de curso, verificar também se é o mesmo curso
                    if (viewName === currentView && !urlView) {
                        if (viewName !== 'curso' || cursoId === currentCursoId) return;
                    }

                    // Mostrar loading
                    var loader = document.getElementById('lms-loading');
                    var content = document.getElementById('lms-view-content');

                    // Atualizar link ativo na sidebar
                    var links = document.querySelectorAll('.lms-nav-link');
                    links.forEach(function (link) {
                        link.classList.remove('lms-nav-active');
                        // Para view curso, marcar 'meus-cursos' como ativo
                        var linkView = link.getAttribute('data-view');
                        if (viewName === 'curso') {
                            if (linkView === 'meus-cursos' || linkView === 'inicio') {
                                link.classList.add('lms-nav-active');
                            }
                        } else if (linkView === viewName) {
                            link.classList.add('lms-nav-active');
                        }
                    });

                    // Cache key: para curso, usar 'curso-ID' para diferenciar
                    var cacheKey = viewName === 'curso' ? 'curso-' + cursoId : viewName;

                    // Se a view está em cache, usar cache
                    if (viewCache[cacheKey]) {
                        content.innerHTML = viewCache[cacheKey];
                        content.className = 'lms-view-content';
                        currentView = viewName;
                        currentCursoId = cursoId;
                        reinitScripts();
                        if (pushState) updateUrl(viewName, cursoId);
                        return;
                    }

                    // AJAX request
                    loader.classList.add('active');

                    var formData = new FormData();
                    formData.append('action', 'lms_painel_load_view');
                    formData.append('view', viewName);
                    formData.append('nonce', nonce);
                    if (cursoId) {
                        formData.append('curso_id', cursoId);
                    }

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            loader.classList.remove('active');

                            if (data.success && data.data.html) {
                                // Não cachear views com formulário para evitar problemas com nonces
                                var noCache = ['minha-conta', 'cadastro'];
                                if (noCache.indexOf(viewName) === -1) {
                                    viewCache[cacheKey] = data.data.html;
                                }

                                content.innerHTML = data.data.html;
                                content.className = 'lms-view-content';
                                currentView = viewName;
                                currentCursoId = cursoId;
                                reinitScripts();

                                if (pushState) updateUrl(viewName, cursoId);
                            } else {
                                content.innerHTML = '<div class="mc-alert mc-error" style="padding:20px; text-align:center; color:#fff;">Erro ao carregar conteúdo. Tente novamente.</div>';
                            }
                        })
                        .catch(function (err) {
                            loader.classList.remove('active');
                            console.error('LMS Painel Error:', err);
                            content.innerHTML = '<div class="mc-alert mc-error" style="padding:20px; text-align:center; color:#fff;">Erro de conexão. Verifique sua internet.</div>';
                        });
                }

                function updateUrl(viewName, cursoId) {
                    if (window.history && window.history.pushState) {
                        var url = new URL(window.location);
                        if (viewName === 'inicio') {
                            url.searchParams.delete('lms_view');
                            url.searchParams.delete('curso_id');
                        } else {
                            url.searchParams.set('lms_view', viewName);
                            if (viewName === 'curso' && cursoId) {
                                url.searchParams.set('curso_id', cursoId);
                            } else {
                                url.searchParams.delete('curso_id');
                            }
                        }
                        window.history.pushState({ lmsView: viewName, cursoId: cursoId || null }, '', url);
                    }
                }

                function reinitScripts() {
                    // Re-inicializar Lucide icons
                    if (typeof lucide !== 'undefined') {
                        lucide.createIcons();
                    }

                    // Re-inicializar carrosséis (do shortcode meus-cursos)
                    var carousels = document.querySelectorAll('#lms-view-content .mc-carousel-wrapper');
                    carousels.forEach(function (wrapper) {
                        var carouselId = wrapper.id;
                        var track = wrapper.querySelector('.mc-carousel-track');
                        var prevBtn = document.querySelector('.mc-prev[data-carousel="' + carouselId + '"]');
                        var nextBtn = document.querySelector('.mc-next[data-carousel="' + carouselId + '"]');

                        if (!track || !prevBtn || !nextBtn) return;

                        var currentIndex = 0;
                        var items = track.querySelectorAll('.curso-item');
                        var itemWidth = 220;
                        var totalItems = items.length;

                        function getVisibleItems() {
                            return Math.floor(wrapper.offsetWidth / itemWidth);
                        }

                        function updateCarousel() {
                            var visibleItems = getVisibleItems();
                            var maxIndex = Math.max(0, totalItems - visibleItems);
                            if (currentIndex < 0) currentIndex = 0;
                            if (currentIndex > maxIndex) currentIndex = maxIndex;
                            track.style.transform = 'translateX(-' + (currentIndex * itemWidth) + 'px)';
                            prevBtn.disabled = currentIndex === 0;
                            nextBtn.disabled = currentIndex >= maxIndex;
                        }

                        prevBtn.onclick = function () { currentIndex--; updateCarousel(); };
                        nextBtn.onclick = function () { currentIndex++; updateCarousel(); };
                        updateCarousel();
                    });

                    // Re-inicializar Lista de Aulas (do shortcode lista-aulas)
                    var listaAulasContainers = document.querySelectorAll('#lms-view-content .lista-aulas');
                    listaAulasContainers.forEach(function (container) {
                        var containerId = container.id;
                        if (containerId && window.SystemCursos && window.SystemCursos.initListaAulas) {
                            window.SystemCursos.initListaAulas(containerId);
                        }
                    });

                    // Re-inicializar tabs (do shortcode cadastro-usuario)
                    var tabLinks = document.querySelectorAll('#lms-view-content .cadastro-tab');
                    tabLinks.forEach(function (tab) {
                        tab.addEventListener('click', function (evt) {
                            var tabName = this.getAttribute('data-tab');
                            if (!tabName) return;

                            // Fechar todas
                            var contents = document.querySelectorAll('#lms-view-content .cadastro-content');
                            contents.forEach(function (c) { c.classList.remove('active'); c.style.display = 'none'; });
                            var tabs = document.querySelectorAll('#lms-view-content .cadastro-tab');
                            tabs.forEach(function (t) { t.classList.remove('active'); });

                            // Abrir selecionada
                            var target = document.getElementById(tabName);
                            if (target) {
                                target.style.display = 'block';
                                setTimeout(function () { target.classList.add('active'); }, 10);
                            }
                            this.classList.add('active');
                        });
                    });

                    // Re-inicializar aba ativa
                    var activeTab = document.querySelector('#lms-view-content .cadastro-content.active');
                    if (activeTab) activeTab.style.display = 'block';

                    // Scroll suave para o topo do conteúdo
                    var painelEl = document.getElementById('lms-painel');
                    if (painelEl) {
                        painelEl.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }

                // Event Listeners para links da sidebar
                document.addEventListener('click', function (e) {
                    // --- Sidebar navigation links ---
                    var link = e.target.closest('.lms-nav-link');
                    if (link) {
                        e.preventDefault();
                        var view = link.getAttribute('data-view');
                        if (view) loadView(view);
                        return;
                    }

                    // --- Curso card click (SPA navigation) ---
                    var cursoLink = e.target.closest('.curso-link[data-curso-id]');
                    if (cursoLink) {
                        e.preventDefault();
                        var cursoId = cursoLink.getAttribute('data-curso-id');
                        if (cursoId) loadView('curso', true, cursoId);
                        return;
                    }

                    // --- Botão voltar para meus cursos (dentro da view curso) ---
                    var voltarBtn = e.target.closest('.lms-voltar-cursos');
                    if (voltarBtn) {
                        e.preventDefault();
                        loadView('meus-cursos');
                        return;
                    }
                });

                // Suporte ao botão voltar/avançar do navegador
                window.addEventListener('popstate', function (e) {
                    if (e.state && e.state.lmsView) {
                        loadView(e.state.lmsView, false, e.state.cursoId || null);
                    } else {
                        loadView('inicio', false);
                    }
                });

                // Carregar view da URL se especificada
                if (urlView && urlView !== 'inicio') {
                    loadView(urlView, false, urlCursoId || null);
                    urlView = null; // Limpa para evitar reload
                }
            })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Handler AJAX para carregar views dinamicamente.
     */
    public function ajax_load_view()
    {
        check_ajax_referer('lms_painel_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Não autorizado.']);
        }

        $view = isset($_POST['view']) ? sanitize_key($_POST['view']) : '';
        $curso_id = isset($_POST['curso_id']) ? (int) $_POST['curso_id'] : 0;

        $html = $this->get_view_html($view, $curso_id);

        if ($html !== false) {
            wp_send_json_success(['html' => $html]);
        } else {
            wp_send_json_error(['message' => 'View não encontrada.']);
        }
    }

    /**
     * Retorna o HTML de uma view específica.
     *
     * @param string $view Nome da view.
     * @param int    $curso_id ID do curso (usado na view 'curso').
     * @return string|false HTML da view ou false se inválida.
     */
    private function get_view_html($view, $curso_id = 0)
    {
        // Ativar modo painel para sub-shortcodes
        $GLOBALS['lms_painel_mode'] = true;

        $html = false;

        switch ($view) {
            case 'inicio':
            case 'meus-cursos':
                $html = do_shortcode('[meus-cursos]');
                break;

            case 'todos-cursos':
                $html = do_shortcode('[meus-cursos mostrar="todos"]');
                break;

            case 'minha-conta':
                $html = do_shortcode('[minha-conta]');
                break;

            case 'certificados':
                $html = do_shortcode('[certificado]');
                break;

            case 'cadastro':
                if (!current_user_can('administrator')) {
                    $html = '<div class="mc-alert mc-error" style="padding:20px; text-align:center; color:#fff;">Acesso restrito a administradores.</div>';
                } else {
                    $html = do_shortcode('[cadastro-usuario]');
                }
                break;

            case 'curso':
                if ($curso_id > 0) {
                    // Botão voltar + player de aulas
                    $voltar = '<div style="margin-bottom: 1rem;">';
                    $voltar .= '<a href="#" class="lms-voltar-cursos" style="display: inline-flex; align-items: center; gap: 0.5rem; color: #fcc419; text-decoration: none; font-weight: 600; font-size: 0.95rem; padding: 0.5rem 0; transition: opacity 0.2s;">';
                    $voltar .= '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>';
                    $voltar .= 'Voltar para Meus Cursos';
                    $voltar .= '</a></div>';

                    $html = $voltar . do_shortcode('[lista-aulas curso_id="' . $curso_id . '"]');
                } else {
                    $html = '<div class="mc-alert mc-error" style="padding:20px; text-align:center; color:#fff;">Curso não especificado.</div>';
                }
                break;

            default:
                $html = false;
                break;
        }

        unset($GLOBALS['lms_painel_mode']);
        return $html;
    }
}
