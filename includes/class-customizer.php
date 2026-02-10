<?php
if (!defined('ABSPATH')) {
    exit;
}

class System_Cursos_Customizer
{
    /**
     * class-customizer.php
     *
     * Gerencia as configurações de personalização visual do LMS.
     * Cores, fontes e espaçamentos são salvos no banco e geram CSS dinâmico
     * aplicado com escopo isolado (.lms-sr) no frontend.
     *
     * @package SistemaCursos
     * @version 1.0.0
     */

    const OPTION_KEY = 'lms_sr_customizer';

    public function __construct()
    {
        add_action('wp_ajax_lms_sr_save_customizer', [$this, 'ajax_save']);
        add_action('wp_ajax_lms_sr_reset_customizer', [$this, 'ajax_reset']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dynamic_css'], 20);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_google_fonts'], 5);
        add_filter('do_shortcode_tag', [$this, 'wrap_shortcode_output'], 10, 4);
    }

    public static function get_defaults()
    {
        return [
            'color_bg_primary'      => '#121212',
            'color_bg_secondary'    => '#1a1a1a',
            'color_bg_tertiary'     => '#0a0a0a',
            'color_bg_header_start' => '#1f1f1f',
            'color_bg_header_end'   => '#161616',
            'color_bg_footer'       => '#161616',

            'color_text_primary'    => '#e0e0e0',
            'color_text_heading'    => '#ffffff',
            'color_text_muted'      => '#888888',
            'color_text_label'      => '#666666',

            'color_accent'          => '#FDC110',
            'color_accent_hover'    => '#e6b00f',

            'color_success'         => '#22c55e',
            'color_error'           => '#ef4444',

            'color_border'          => '#2a2a2a',
            'color_border_input'    => '#333333',

            'font_family'           => "'Segoe UI', Roboto, Helvetica, Arial, sans-serif",
            'font_size_base'        => '16',

            'radius_base'           => '6',
            'radius_card'           => '12',
        ];
    }

    public static function get_settings()
    {
        $defaults = self::get_defaults();
        $saved = get_option(self::OPTION_KEY, []);
        return wp_parse_args($saved, $defaults);
    }

    public static function get_dynamic_css()
    {
        $s = self::get_settings();

        $radius_base = intval($s['radius_base']);
        $radius_card = intval($s['radius_card']);

        $css = ".lms-sr {\n";
        $css .= "  --color-bg-primary: {$s['color_bg_primary']};\n";
        $css .= "  --color-bg-secondary: {$s['color_bg_secondary']};\n";
        $css .= "  --color-bg-tertiary: {$s['color_bg_tertiary']};\n";
        $css .= "  --color-bg-header: linear-gradient(180deg, {$s['color_bg_header_start']} 0%, {$s['color_bg_header_end']} 100%);\n";
        $css .= "  --color-bg-footer: {$s['color_bg_footer']};\n";
        $css .= "  --color-bg-input-hover: {$s['color_bg_secondary']};\n";
        $css .= "  --color-bg-input-focus: {$s['color_bg_tertiary']};\n";
        $css .= "  --color-text-primary: {$s['color_text_primary']};\n";
        $css .= "  --color-text-heading: {$s['color_text_heading']};\n";
        $css .= "  --color-text-muted: {$s['color_text_muted']};\n";
        $css .= "  --color-text-label: {$s['color_text_label']};\n";
        $css .= "  --color-accent: {$s['color_accent']};\n";
        $css .= "  --color-accent-hover: {$s['color_accent_hover']};\n";
        $css .= "  --color-accent-shadow: " . self::hex_to_rgba($s['color_accent'], 0.2) . ";\n";
        $css .= "  --color-success: {$s['color_success']};\n";
        $css .= "  --color-success-hover: " . self::darken_hex($s['color_success'], 15) . ";\n";
        $css .= "  --color-success-bg: " . self::hex_to_rgba($s['color_success'], 0.15) . ";\n";
        $css .= "  --color-success-border: " . self::hex_to_rgba($s['color_success'], 0.2) . ";\n";
        $css .= "  --color-error: {$s['color_error']};\n";
        $css .= "  --color-error-bg: " . self::hex_to_rgba($s['color_error'], 0.15) . ";\n";
        $css .= "  --color-error-border: " . self::hex_to_rgba($s['color_error'], 0.2) . ";\n";
        $css .= "  --color-border: {$s['color_border']};\n";
        $css .= "  --color-border-input: {$s['color_border_input']};\n";
        $css .= "  --color-border-input-hover: " . self::lighten_hex($s['color_border_input'], 10) . ";\n";
        $css .= "  --color-border-light: " . self::hex_to_rgba('#ffffff', 0.1) . ";\n";
        $css .= "  --color-border-hover: " . self::hex_to_rgba('#ffffff', 0.2) . ";\n";
        $css .= "  --font-family: {$s['font_family']};\n";
        $css .= "  --font-size-base: 1rem;\n";
        $css .= "  --radius-sm: {$radius_base}px;\n";
        $css .= "  --radius-md: " . ($radius_base + 2) . "px;\n";
        $css .= "  --radius-lg: " . ($radius_base + 4) . "px;\n";
        $css .= "  --radius-xl: {$radius_card}px;\n";
        $css .= "}\n";

        return $css;
    }

    public function enqueue_dynamic_css()
    {
        $css = self::get_dynamic_css();
        wp_add_inline_style('sistema-cursos-style', $css);
    }

    public function enqueue_google_fonts()
    {
        $s = self::get_settings();
        $font = $s['font_family'];

        $google_fonts = self::get_google_fonts_list();

        foreach ($google_fonts as $gfont) {
            if (stripos($font, $gfont) !== false) {
                $font_url = 'https://fonts.googleapis.com/css2?family=' . urlencode($gfont) . ':wght@300;400;500;600;700&display=swap';
                wp_enqueue_style('lms-sr-google-font', $font_url, [], null);
                break;
            }
        }
    }

    public function wrap_shortcode_output($output, $tag, $attr, $m)
    {
        $lms_tags = [
            'minha-conta',
            'meus-cursos',
            'lista-aulas',
            'certificado',
            'barra-progresso-geral',
            'resultado-busca',
            'single-trilha',
            'cursos_da_trilha',
            'cadastro-usuario',
            'barra-lateral-aluno',
            'redireciona-aula',
        ];

        if (in_array($tag, $lms_tags) && empty($GLOBALS['lms_painel_mode'])) {
            $font_size = intval(self::get_settings()['font_size_base']);
            $style = $font_size !== 16 ? ' style="font-size:' . $font_size . 'px;"' : '';
            return '<div class="lms-sr"' . $style . '>' . $output . '</div>';
        }

        return $output;
    }

    public static function get_google_fonts_list()
    {
        return [
            'Inter',
            'Roboto',
            'Open Sans',
            'Montserrat',
            'Poppins',
            'Lato',
            'Nunito',
            'Raleway',
            'Source Sans Pro',
            'Ubuntu',
            'DM Sans',
            'Work Sans',
            'Outfit',
            'Plus Jakarta Sans',
            'Figtree',
        ];
    }

    public static function get_presets()
    {
        return [
            'midnight_gold' => [
                'name'  => 'Midnight Gold',
                'desc'  => 'Escuro elegante com destaque dourado — sofisticado e premium.',
                'emoji' => '🌙',
                'colors' => [
                    'color_bg_primary'      => '#0f0f0f',
                    'color_bg_secondary'    => '#1a1a1a',
                    'color_bg_tertiary'     => '#080808',
                    'color_bg_header_start' => '#1c1c1c',
                    'color_bg_header_end'   => '#111111',
                    'color_bg_footer'       => '#111111',
                    'color_text_primary'    => '#e0e0e0',
                    'color_text_heading'    => '#ffffff',
                    'color_text_muted'      => '#888888',
                    'color_text_label'      => '#666666',
                    'color_accent'          => '#F5A623',
                    'color_accent_hover'    => '#e09510',
                    'color_success'         => '#34D399',
                    'color_error'           => '#F87171',
                    'color_border'          => '#2a2a2a',
                    'color_border_input'    => '#333333',
                ],
            ],
            'ocean_breeze' => [
                'name'  => 'Ocean Breeze',
                'desc'  => 'Azul profundo com acentos ciano — moderno e confiável.',
                'emoji' => '🌊',
                'colors' => [
                    'color_bg_primary'      => '#0B1120',
                    'color_bg_secondary'    => '#111B2E',
                    'color_bg_tertiary'     => '#060D1B',
                    'color_bg_header_start' => '#0F1A30',
                    'color_bg_header_end'   => '#0A1225',
                    'color_bg_footer'       => '#0A1225',
                    'color_text_primary'    => '#CBD5E1',
                    'color_text_heading'    => '#F1F5F9',
                    'color_text_muted'      => '#64748B',
                    'color_text_label'      => '#475569',
                    'color_accent'          => '#22D3EE',
                    'color_accent_hover'    => '#06B6D4',
                    'color_success'         => '#4ADE80',
                    'color_error'           => '#FB7185',
                    'color_border'          => '#1E293B',
                    'color_border_input'    => '#334155',
                ],
            ],
            'forest_moss' => [
                'name'  => 'Forest Moss',
                'desc'  => 'Tons terrosos com verde esmeralda — natural e acolhedor.',
                'emoji' => '🌿',
                'colors' => [
                    'color_bg_primary'      => '#111710',
                    'color_bg_secondary'    => '#1A2118',
                    'color_bg_tertiary'     => '#0A0F09',
                    'color_bg_header_start' => '#1C241A',
                    'color_bg_header_end'   => '#131A12',
                    'color_bg_footer'       => '#131A12',
                    'color_text_primary'    => '#D4DDD2',
                    'color_text_heading'    => '#F0F4EF',
                    'color_text_muted'      => '#7A8B77',
                    'color_text_label'      => '#5A6B57',
                    'color_accent'          => '#10B981',
                    'color_accent_hover'    => '#059669',
                    'color_success'         => '#34D399',
                    'color_error'           => '#F87171',
                    'color_border'          => '#243022',
                    'color_border_input'    => '#2D3B2B',
                ],
            ],
            'royal_purple' => [
                'name'  => 'Royal Purple',
                'desc'  => 'Violeta intenso com lilás vibrante — criativo e marcante.',
                'emoji' => '👑',
                'colors' => [
                    'color_bg_primary'      => '#110E1A',
                    'color_bg_secondary'    => '#1A1525',
                    'color_bg_tertiary'     => '#0A0812',
                    'color_bg_header_start' => '#1E1730',
                    'color_bg_header_end'   => '#130F20',
                    'color_bg_footer'       => '#130F20',
                    'color_text_primary'    => '#DDD6E8',
                    'color_text_heading'    => '#F3F0F7',
                    'color_text_muted'      => '#8B7FA0',
                    'color_text_label'      => '#6B5F80',
                    'color_accent'          => '#A78BFA',
                    'color_accent_hover'    => '#8B5CF6',
                    'color_success'         => '#4ADE80',
                    'color_error'           => '#FB7185',
                    'color_border'          => '#2A2240',
                    'color_border_input'    => '#352B50',
                ],
            ],
            'ember_slate' => [
                'name'  => 'Ember Slate',
                'desc'  => 'Cinza carvão com laranja quente — energético e profissional.',
                'emoji' => '🔥',
                'colors' => [
                    'color_bg_primary'      => '#18181B',
                    'color_bg_secondary'    => '#212124',
                    'color_bg_tertiary'     => '#101012',
                    'color_bg_header_start' => '#242428',
                    'color_bg_header_end'   => '#19191C',
                    'color_bg_footer'       => '#19191C',
                    'color_text_primary'    => '#D4D4D8',
                    'color_text_heading'    => '#FAFAFA',
                    'color_text_muted'      => '#71717A',
                    'color_text_label'      => '#52525B',
                    'color_accent'          => '#F97316',
                    'color_accent_hover'    => '#EA580C',
                    'color_success'         => '#22C55E',
                    'color_error'           => '#EF4444',
                    'color_border'          => '#27272A',
                    'color_border_input'    => '#3F3F46',
                ],
            ],
        ];
    }

    public static function get_font_options()
    {
        return [
            "'Segoe UI', Roboto, Helvetica, Arial, sans-serif" => 'Segoe UI (Padrão)',
            "Arial, Helvetica, sans-serif"                     => 'Arial',
            "'Helvetica Neue', Helvetica, Arial, sans-serif"   => 'Helvetica Neue',
            "'Inter', sans-serif"                              => 'Inter (Google)',
            "'Roboto', sans-serif"                             => 'Roboto (Google)',
            "'Open Sans', sans-serif"                          => 'Open Sans (Google)',
            "'Montserrat', sans-serif"                         => 'Montserrat (Google)',
            "'Poppins', sans-serif"                            => 'Poppins (Google)',
            "'Lato', sans-serif"                               => 'Lato (Google)',
            "'Nunito', sans-serif"                             => 'Nunito (Google)',
            "'Raleway', sans-serif"                            => 'Raleway (Google)',
            "'Source Sans Pro', sans-serif"                    => 'Source Sans Pro (Google)',
            "'Ubuntu', sans-serif"                             => 'Ubuntu (Google)',
            "'DM Sans', sans-serif"                            => 'DM Sans (Google)',
            "'Work Sans', sans-serif"                          => 'Work Sans (Google)',
            "'Outfit', sans-serif"                             => 'Outfit (Google)',
            "'Plus Jakarta Sans', sans-serif"                  => 'Plus Jakarta Sans (Google)',
            "'Figtree', sans-serif"                            => 'Figtree (Google)',
            "Georgia, 'Times New Roman', Times, serif"         => 'Georgia (Serif)',
        ];
    }

    public function ajax_save()
    {
        check_ajax_referer('lms_sr_customizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }

        $defaults = self::get_defaults();
        $settings = [];

        foreach (array_keys($defaults) as $key) {
            if (isset($_POST[$key])) {
                $settings[$key] = sanitize_text_field(wp_unslash($_POST[$key]));
            }
        }

        update_option(self::OPTION_KEY, $settings);
        wp_send_json_success('Configurações salvas com sucesso!');
    }

    public function ajax_reset()
    {
        check_ajax_referer('lms_sr_customizer_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão');
        }

        delete_option(self::OPTION_KEY);
        wp_send_json_success('Configurações restauradas para o padrão!');
    }

    private static function hex_to_rgba($hex, $alpha = 1)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    private static function darken_hex($hex, $percent)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = max(0, hexdec(substr($hex, 0, 2)) - (255 * $percent / 100));
        $g = max(0, hexdec(substr($hex, 2, 2)) - (255 * $percent / 100));
        $b = max(0, hexdec(substr($hex, 4, 2)) - (255 * $percent / 100));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    private static function lighten_hex($hex, $percent)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = min(255, hexdec(substr($hex, 0, 2)) + (255 * $percent / 100));
        $g = min(255, hexdec(substr($hex, 2, 2)) + (255 * $percent / 100));
        $b = min(255, hexdec(substr($hex, 4, 2)) + (255 * $percent / 100));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
