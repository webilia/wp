<?php
namespace Webilia\WP\SettingsPanel;

class Boot extends Base
{
    public const VERSION = \Webilia\WP\Version::VALUE;
    /** @var array<mixed> */
    private static $urls = [];
    /** @var boolean */
    private static bool $ran = false;
    /** @var Settings */
    protected Settings $settings;
    /** @var string */
    protected string $base_url;

    /**
     * @param string $base_url
     * @param string $ui_url
     */
    public function __construct(string $base_url, string $ui_url)
    {
        if (trim($base_url) === '' || trim($ui_url) === '')
        {
            throw new \InvalidArgumentException('Supply explicit WP and UI package root URLs.');
        }

        $this->settings = new Settings();
        $this->base_url = rtrim($base_url, '/');
        self::$urls = ['wp' => $this->base_url, 'ui' => rtrim($ui_url, '/')];
    }

    /**
     * @return void
     */
    public function init()
    {
        if (self::$ran) return;
        \Webilia\WP\ThemeTools::requireUI();
        self::$ran = true;

        add_action('admin_enqueue_scripts', [$this, 'assets']);
        add_action('customize_controls_enqueue_scripts', [$this, 'assets']);
    }

    /**
     * @return mixed
     */
    public function settings()
    {
        return $this->settings;
    }

    /**
     * @return void
     */
    public function assets()
    {
        if (!$this->enqueue_assets()) return;
        wp_enqueue_style('webilia-wp-settings-panel', self::$urls['wp'] . '/assets/css/settings-panel.css', ['webilia-ui-v1-settings-panel',
            'wp-color-picker'], self::VERSION);
        wp_enqueue_media();
        wp_enqueue_style('wp-color-picker');
        $ui = ['settings-panel', 'toast'];
        $customizer = is_customize_preview() || doing_action('customize_controls_enqueue_scripts');
        if (!$customizer) $ui[] = 'modal';
        \Webilia\UI\WordPress\Assets::enqueue($ui, self::$urls['ui']);
        wp_enqueue_style('webilia-wp-settings-elusive', self::$urls['ui'] .
            '/dist/vendor/settings-panel/elusive-icons/css/elusive-icons.min.css', [], '2.0.0');
        wp_enqueue_style('webilia-wp-settings-fontawesome', self::$urls['ui'] .
            '/dist/vendor/settings-panel/font-awesome/css/font-awesome.min.css', [], '6.7.2');
        $deps = ['jquery', 'wp-color-picker', 'jquery-ui-sortable', 'webilia-ui-v1-settings-panel', 'webilia-ui-v1-toast'];
        if (!$customizer) $deps[] = 'webilia-ui-v1-modal';
        wp_enqueue_script('webilia-wp-settings-panel', self::$urls['wp'] . '/assets/js/settings-panel.js', $deps, self::VERSION, true);
        wp_localize_script('webilia-wp-settings-panel', 'webiliaWPSettingsI18n', [
            'closeLabel' => __('Dismiss notification', 'webilia-settings-panel'),
            'dialogCloseLabel' => __('Close dialog', 'webilia-settings-panel'),
            'invalidJson' => __('Invalid JSON', 'webilia-settings-panel'),
            'saving' => __('Saving...', 'webilia-settings-panel'),
            'done' => __('Done', 'webilia-settings-panel'),
            'error' => __('Error', 'webilia-settings-panel'),
            'resetTitle' => __('Reset settings?', 'webilia-settings-panel'),
            'resetSection' => __('Reset this section to its defaults?', 'webilia-settings-panel'),
            'resetAll' => __('Reset all settings to defaults?', 'webilia-settings-panel'),
            'reset' => __('Reset', 'webilia-settings-panel'),
            'cancel' => __('Cancel', 'webilia-settings-panel'),
        ]);
        $this->enqueue_override_assets();
    }

    /**
     * @return void
     */
    public static function enqueue_customizer(): void
    {
        \Webilia\UI\WordPress\Assets::enqueue(['settings-customizer'], self::$urls['ui']);
        wp_enqueue_style('webilia-wp-settings-customizer', self::$urls['wp'] . '/assets/css/settings-customizer.css',
            ['webilia-ui-v1-settings-customizer'], self::VERSION);
        wp_enqueue_script('webilia-wp-settings-customizer', self::$urls['wp'] . '/assets/js/settings-customizer.js', ['customize-controls',
            'jquery', 'webilia-wp-settings-panel'], self::VERSION, true);
    }

    /**
     * @return bool
     */
    private function enqueue_assets(): bool
    {
        if (is_customize_preview() || doing_action('customize_controls_enqueue_scripts')) return Panel::has_customizer();

        if (!is_admin()) return false;

        if (isset($GLOBALS['pagenow']) && $GLOBALS['pagenow'] === 'customize.php') return Panel::has_customizer();

        $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
        if ($page === '') return false;

        $args = Panel::get_args_by_page($page);
        return $args && current_user_can($args['page_permissions'] ?? 'manage_options');
    }

    /**
     * @return void
     */
    private function enqueue_override_assets(): void
    {
        if (!is_admin() || !isset($_GET['page'])) return;

        $page = sanitize_key(wp_unslash($_GET['page']));
        if ($page === '') return;

        $args = Panel::get_args_by_page($page);
        if (!$args) return;

        $custom_style = isset($args['custom_style']) && is_array($args['custom_style']) ? $args['custom_style'] : [];
        if (!$custom_style || empty($custom_style['enabled'])) return;

        $style_url = isset($custom_style['style_url']) ? trim((string) $custom_style['style_url']) : '';
        $script_url = isset($custom_style['script_url']) ? trim((string) $custom_style['script_url']) : '';

        $style_version = isset($custom_style['style_version']) ? (string) $custom_style['style_version'] : self::VERSION;
        $script_version = isset($custom_style['script_version']) ? (string) $custom_style['script_version'] : self::VERSION;

        $style_deps = ['webilia-wp-settings-panel'];
        $script_deps = ['webilia-wp-settings-panel'];
        if (isset($custom_style['style_deps']) && is_array($custom_style['style_deps'])) $style_deps = $custom_style['style_deps'];
        if (isset($custom_style['script_deps']) && is_array($custom_style['script_deps'])) $script_deps = $custom_style['script_deps'];

        $style_deps = array_map(static function ($handle)
        {
            return $handle === 'webilia-settings-panel-admin' ? 'webilia-ui-v1-settings-panel' : $handle;
        }, $style_deps);
        $script_deps = array_map(static function ($handle)
        {
            return $handle === 'webilia-settings-panel-admin' ? 'webilia-wp-settings-panel' : $handle;
        }, $script_deps);

        if ($style_url !== '')
        {
            wp_enqueue_style('wui-sp-settings-panel-override', $style_url, $style_deps, $style_version);
        }

        if ($script_url !== '')
        {
            wp_enqueue_script('wui-sp-settings-panel-override', $script_url, $script_deps, $script_version, true);
        }
    }
}
