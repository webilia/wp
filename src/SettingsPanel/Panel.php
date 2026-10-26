<?php
namespace Webilia\WP\SettingsPanel;

class Panel
{
    /** @var array<mixed> */
    protected static $registry = [];
    /** @var array<mixed> */
    private static $registered = [];
    /** @var array<mixed> */
    protected static $field_index = [];
    /** @var array<mixed> */
    protected static $field_meta = [];
    /** @var array<mixed> */
    protected static $filters = [];
    /** @var array<mixed> */
    protected static $fonts_enqueued = [];

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @return void
     */
    public static function set_args(string $opt_name, array $args): void
    {
        if (!isset($args['opt_name'])) $args['opt_name'] = $opt_name;
        if (!isset(self::$registry[$opt_name])) self::$registry[$opt_name] = [];
        self::$registry[$opt_name]['args'] = $args;
    }

    /**
     * @param string $opt_name
     * @return array<mixed>
     */
    public static function get_args(string $opt_name): array
    {
        $opt_name = sanitize_key($opt_name);
        if ($opt_name === '') return [];

        return self::$registry[$opt_name]['args'] ?? [];
    }

    /**
     * @param string $opt_name
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get_arg(string $opt_name, string $key, $default = null)
    {
        $args = self::get_args($opt_name);
        if (array_key_exists($key, $args)) return $args[$key];
        return $default;
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $section
     * @return void
     */
    public static function set_section(string $opt_name, array $section): void
    {
        if (!isset(self::$registry[$opt_name])) self::$registry[$opt_name] = [];
        if (!isset(self::$registry[$opt_name]['sections'])) self::$registry[$opt_name]['sections'] = [];

        self::$registry[$opt_name]['sections'][] = $section;

        if (!empty($section['fields']) && is_array($section['fields']))
        {
            self::index_fields($opt_name, $section['fields'], $section['id'] ?? '');
        }
    }

    /**
     * @param string $opt_name
     * @return void
     */
    public static function register(string $opt_name): void
    {
        \Webilia\WP\ThemeTools::requireUI();
        if (isset(self::$registered[$opt_name])) return;
        $data = self::$registry[$opt_name] ?? [];
        if (!$data) return;
        self::$registered[$opt_name] = true;

        $args = $data['args'] ?? [];
        $args = self::normalize_args($opt_name, $args);
        $args = self::apply_menu_type($args);

        $sections = $data['sections'] ?? [];
        $sections = self::add_import_export_section($opt_name, $args, $sections);
        $sections = self::add_extra_section($opt_name, $args, $sections);

        self::register_global_variable($opt_name, $args);
        self::register_fonts_controls($args);
        self::register_fonts_enqueue($opt_name, $args);

        if (!empty($args['save_defaults'])) self::save_defaults($opt_name);

        if (empty(self::$filters[$opt_name]))
        {
            self::$filters[$opt_name] = true;
            add_filter('option_' . $opt_name, function ($value) use ($opt_name)
            {
                return Panel::normalize_options($opt_name, $value);
            });
        }

        $settings = new Settings();
        $settings->set_config([
            'option_name' => $opt_name,
            'page_title' => $args['page_title'] ?? ($args['display_name'] ?? 'Theme Settings'),
            'menu_title' => $args['menu_title'] ?? esc_html__('Settings', 'webilia-settings-panel'),
            'menu_slug' => $args['page_slug'] ?? $opt_name,
            'parent_slug' => $args['page_parent'] ?? '',
            'capability' => $args['page_permissions'] ?? 'manage_options',
            'menu_icon' => $args['menu_icon'] ?? 'dashicons-admin-appearance',
            'menu_position' => $args['page_priority'] ?? null,
            'panel_title' => $args['display_name'] ?? esc_html__('Settings', 'webilia-settings-panel'),
            'panel_intro' => $args['page_description'] ?? '',
            'display_version' => $args['display_version'] ?? '',
            'page_icon' => $args['page_icon'] ?? '',
            'menu_type' => $args['menu_type'] ?? '',
            'allow_sub_menu' => $args['allow_sub_menu'] ?? true,
            'admin_bar' => $args['admin_bar'] ?? false,
            'admin_bar_icon' => $args['admin_bar_icon'] ?? '',
            'admin_bar_priority' => $args['admin_bar_priority'] ?? 100,
            'customizer' => $args['customizer'] ?? false,
            'last_tab' => $args['last_tab'] ?? '',
            'default_show' => $args['default_show'] ?? false,
            'default_mark' => $args['default_mark'] ?? '',
            'show_import_export' => $args['show_import_export'] ?? false,
            'save_defaults' => $args['save_defaults'] ?? false,
            'templates_path' => $args['templates_path'] ?? '',
            'hints' => $args['hints'] ?? [],
            'output' => $args['output'] ?? true,
            'output_tag' => $args['output_tag'] ?? true,
            'database' => $args['database'] ?? '',
            'use_cdn' => $args['use_cdn'] ?? true,
            'compiler' => $args['compiler'] ?? false,
            'flyout_submenus' => $args['flyout_submenus'] ?? false,
            'font_display' => $args['font_display'] ?? '',
            'transient_time' => $args['transient_time'] ?? '',
            'disable_google_fonts_link' => $args['disable_google_fonts_link'] ?? false,
            'base_url' => $args['base_url'] ?? '',
            'base_path' => $args['base_path'] ?? '',
        ]);

        $structured_sections = self::normalize_sections($sections);
        foreach ($structured_sections as $section)
        {
            $section_id = $section['id'];
            $section_title = $section['title'] ?? '';
            $section_desc = $section['desc'] ?? '';
            $fields = $section['fields'] ?? [];
            $has_content = !empty($fields) || trim((string) $section_desc) !== '';

            $settings->add_section($section_id, $section_title, function () use ($section_desc)
            {
                if (trim($section_desc) !== '') echo '<p class="wui-sp-section-description">' . wp_kses_post($section_desc) . '</p>';
            }, [
                'parent_id' => $section['parent_id'] ?? '',
                'subsection' => !empty($section['subsection']),
                'icon' => $section['icon'] ?? '',
                'has_content' => $has_content,
            ]);

            foreach ($fields as $field)
            {
                if (!isset($field['id']) || !trim($field['id'])) continue;
                $field_type = strtolower(trim((string) ($field['type'] ?? '')));

                $field_id = (string) $field['id'];
                $type = $field['type'] ?? '';
                $title = ($type === 'info' || $type === 'divide' || $type === 'raw' || $type === 'content') ? '' : ($field['title'] ?? '');
                if ($field_type === 'section')
                {
                    $field['class'] = trim(($field['class'] ?? '') . ' wui-sp-section-type');
                }

                $settings->add_field($field_id, $title, $section_id, function () use ($field, $opt_name)
                {
                    echo Panel::render_field($field, $opt_name);
                }, $field);
            }
        }

        $settings->init();

        add_action('update_option_' . $opt_name, function ($old_value, $value) use ($opt_name)
        {
            do_action('webilia_settings_panel_updated', $opt_name, $old_value, $value);
        }, 10, 2);
    }

    /**
     * @return bool
     */
    public static function has_customizer(): bool
    {
        foreach (self::$registry as $data)
        {
            $args = $data['args'] ?? [];
            if (!empty($args['customizer']) && current_user_can($args['page_permissions'] ?? 'manage_options')) return true;
        }

        return false;
    }

    /**
     * @param string $page
     * @return bool
     */
    public static function is_settings_page(string $page): bool
    {
        $page = sanitize_key($page);
        if ($page === '') return false;

        foreach (self::$registry as $data)
        {
            $args = $data['args'] ?? [];
            $slug = $args['page_slug'] ?? ($args['opt_name'] ?? '');
            $slug = sanitize_key((string) $slug);
            if ($slug !== '' && $slug === $page) return true;
        }

        return false;
    }

    /**
     * @param string $page
     * @return array<mixed>
     */
    public static function get_args_by_page(string $page): array
    {
        $page = sanitize_key($page);
        if ($page === '') return [];

        foreach (self::$registry as $data)
        {
            $args = $data['args'] ?? [];
            $slug = $args['page_slug'] ?? ($args['opt_name'] ?? '');
            $slug = sanitize_key((string) $slug);
            if ($slug !== '' && $slug === $page) return $args;
        }

        return [];
    }

    /**
     * @param array<mixed> $sections
     * @return array<mixed>
     */
    protected static function normalize_sections(array $sections): array
    {
        $result = [];
        $last_parent = '';
        foreach ($sections as $section)
        {
            if (!is_array($section)) continue;
            if (!isset($section['id'])) continue;
            if (!empty($section['subsection']) && empty($section['parent_id']) && $last_parent)
            {
                $section['parent_id'] = $last_parent;
            }

            if (empty($section['subsection'])) $last_parent = $section['id'];
            $result[] = $section;
        }

        return $result;
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @return array<mixed>
     */
    protected static function normalize_args(string $opt_name, array $args): array
    {
        if (!isset($args['opt_name'])) $args['opt_name'] = $opt_name;

        if (isset($args['menu_type'])) $args['menu_type'] = strtolower((string) $args['menu_type']);
        if (array_key_exists('allow_sub_menu', $args)) $args['allow_sub_menu'] = (bool) $args['allow_sub_menu'];
        if (array_key_exists('admin_bar', $args)) $args['admin_bar'] = (bool) $args['admin_bar'];
        if (array_key_exists('customizer', $args)) $args['customizer'] = (bool) $args['customizer'];
        if (array_key_exists('show_import_export', $args)) $args['show_import_export'] = (bool) $args['show_import_export'];
        if (array_key_exists('save_defaults', $args)) $args['save_defaults'] = (bool) $args['save_defaults'];
        if (array_key_exists('extra', $args)) $args['extra'] = (bool) $args['extra'];

        return $args;
    }

    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    protected static function apply_menu_type(array $args): array
    {
        $menu_type = $args['menu_type'] ?? '';
        $allow_sub_menu = $args['allow_sub_menu'] ?? null;

        if ($menu_type === 'menu' || $allow_sub_menu === false) $args['page_parent'] = '';

        return $args;
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @param array<mixed> $sections
     * @return array<mixed>
     */
    protected static function add_import_export_section(string $opt_name, array $args, array $sections): array
    {
        if (empty($args['show_import_export'])) return $sections;

        $section_id = 'import_export';
        foreach ($sections as $section)
        {
            if (!is_array($section)) continue;
            $id = isset($section['id']) ? sanitize_key($section['id']) : '';
            if ($id === $section_id) return $sections;
        }

        $fields = [
            [
                'id' => $section_id,
                'type' => 'import_export',
            ],
        ];

        $sections[] = [
            'id' => $section_id,
            'title' => esc_html__('Import / Export', 'webilia-settings-panel'),
            'icon' => $args['import_export_icon'] ?? 'wui-icon--file-import-export',
            'fields' => $fields,
        ];

        self::index_fields($opt_name, $fields, $section_id);

        return $sections;
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @param array<mixed> $sections
     * @return array<mixed>
     */
    protected static function add_extra_section(string $opt_name, array $args, array $sections): array
    {
        if (empty($args['extra'])) return $sections;

        $section_id = 'extra';
        foreach ($sections as $section)
        {
            if (!is_array($section)) continue;
            $id = isset($section['id']) ? sanitize_key($section['id']) : '';
            if ($id === $section_id) return $sections;
        }

        $themes = self::get_panel_themes($opt_name);
        if (!$themes) return $sections;

        $field_id = $args['panel_theme_field'] ?? 'panel_theme';
        $default_theme = $args['panel_theme_default'] ?? 'listdom';
        $nav_field_id = $args['panel_nav_position_field'] ?? 'panel_nav_position';
        $nav_default = $args['panel_nav_position_default'] ?? 'left';

        $fields = [
            [
                'id' => $field_id,
                'type' => 'panel_theme',
                'title' => esc_html__('Panel Theme', 'webilia-settings-panel'),
                'default' => $default_theme,
                'themes' => $themes,
            ],
            [
                'id' => $nav_field_id,
                'type' => 'panel_nav_position',
                'title' => esc_html__('Sidebar Position', 'webilia-settings-panel'),
                'options' => [
                    'left' => esc_html__('Left', 'webilia-settings-panel'),
                    'right' => esc_html__('Right', 'webilia-settings-panel'),
                    'top' => esc_html__('Top', 'webilia-settings-panel'),
                ],
                'default' => $nav_default,
            ],
        ];

        $sections[] = [
            'id' => $section_id,
            'title' => esc_html__('Extra', 'webilia-settings-panel'),
            'icon' => $args['extra_icon'] ?? 'wui-icon--add-plus-circle',
            'fields' => $fields,
        ];

        self::index_fields($opt_name, $fields, $section_id);

        return $sections;
    }

    /**
     * @param string $opt_name
     * @return array<mixed>
     */
    public static function get_panel_themes(string $opt_name): array
    {
        $themes = self::get_arg($opt_name, 'panel_themes', []);
        if (is_array($themes) && $themes) return $themes;

        return self::default_panel_themes();
    }

    /**
     * @return array<mixed>
     */
    protected static function default_panel_themes(): array
    {
        return [
            'listdom' => [
                'label' => 'Listdom',
                'vars' => [
                    '--webilia-panel-bg' => '#f7f4fb',
                    '--webilia-panel-card' => '#ffffff',
                    '--webilia-panel-border' => '#e3d9f1',
                    '--webilia-panel-text' => '#2a1b3d',
                    '--webilia-panel-muted' => '#6f5b8c',
                    '--webilia-panel-accent' => '#7b3ff2',
                    '--webilia-panel-accent-2' => '#b45bff',
                    '--webilia-nav-bg' => '#E8E4F2',
                    '--webilia-nav-text' => '#000000',
                    '--webilia-nav-hover-bg' => '#D1B9FF',
                    '--webilia-nav-hover-text' => '#000000',
                    '--webilia-nav-active-bg' => '#8241FF',
                    '--webilia-nav-active-text' => '#ffffff',
                    '--webilia-subnav-bg' => '#EFECF5',
                    '--webilia-subnav-border' => '#E8E4F2',
                    '--webilia-subnav-radius' => '10px',
                    '--webilia-subnav-padding' => '12px',
                    '--webilia-subnav-text' => '#000000',
                    '--webilia-subnav-hover-bg' => '#E8E4F2',
                    '--webilia-subnav-active-bg' => '#D1B9FF',
                    '--webilia-subnav-active-text' => '#000000',
                    '--webilia-subnav-indicator-bg' => '#D1B9FF',
                    '--webilia-subnav-indicator-border' => '#E8E4F2',
                ],
            ],
            'ocean' => [
                'label' => 'Ocean',
                'vars' => [
                    '--webilia-panel-bg' => '#f2f8ff',
                    '--webilia-panel-card' => '#ffffff',
                    '--webilia-panel-border' => '#d7e6f7',
                    '--webilia-panel-text' => '#0f2a4a',
                    '--webilia-panel-muted' => '#4f6b8a',
                    '--webilia-panel-accent' => '#1f7aff',
                    '--webilia-panel-accent-2' => '#3fc1ff',
                    '--webilia-nav-bg' => '#eaf3ff',
                    '--webilia-nav-text' => '#0f2a4a',
                    '--webilia-nav-hover-bg' => '#d6e7ff',
                    '--webilia-nav-hover-text' => '#0f2a4a',
                    '--webilia-nav-active-bg' => '#1f7aff',
                    '--webilia-nav-active-text' => '#ffffff',
                    '--webilia-subnav-bg' => '#f1f7ff',
                    '--webilia-subnav-border' => '#d7e6f7',
                    '--webilia-subnav-radius' => '10px',
                    '--webilia-subnav-padding' => '12px',
                    '--webilia-subnav-text' => '#0f2a4a',
                    '--webilia-subnav-hover-bg' => '#e2efff',
                    '--webilia-subnav-active-bg' => '#cfe2ff',
                    '--webilia-subnav-active-text' => '#0f2a4a',
                    '--webilia-subnav-indicator-bg' => '#cfe2ff',
                    '--webilia-subnav-indicator-border' => '#d7e6f7',
                ],
            ],
            'ember' => [
                'label' => 'Ember',
                'vars' => [
                    '--webilia-panel-bg' => '#fff6ef',
                    '--webilia-panel-card' => '#ffffff',
                    '--webilia-panel-border' => '#f2d7c6',
                    '--webilia-panel-text' => '#3b1f14',
                    '--webilia-panel-muted' => '#8c5a45',
                    '--webilia-panel-accent' => '#ff6b3d',
                    '--webilia-panel-accent-2' => '#ffb347',
                    '--webilia-nav-bg' => '#fdeee4',
                    '--webilia-nav-text' => '#3b1f14',
                    '--webilia-nav-hover-bg' => '#f7d9c6',
                    '--webilia-nav-hover-text' => '#3b1f14',
                    '--webilia-nav-active-bg' => '#ff6b3d',
                    '--webilia-nav-active-text' => '#ffffff',
                    '--webilia-subnav-bg' => '#fff3eb',
                    '--webilia-subnav-border' => '#f2d7c6',
                    '--webilia-subnav-radius' => '10px',
                    '--webilia-subnav-padding' => '12px',
                    '--webilia-subnav-text' => '#3b1f14',
                    '--webilia-subnav-hover-bg' => '#f7e1d5',
                    '--webilia-subnav-active-bg' => '#f4cbb8',
                    '--webilia-subnav-active-text' => '#3b1f14',
                    '--webilia-subnav-indicator-bg' => '#f4cbb8',
                    '--webilia-subnav-indicator-border' => '#f2d7c6',
                ],
            ],
            'neutral' => [
                'label' => 'Neutral',
                'vars' => [
                    '--webilia-panel-bg' => '#f0f0f0',
                    '--webilia-panel-card' => '#f0f0f0',
                    '--webilia-panel-border' => '#d4d4d4',
                    '--webilia-panel-text' => '#111111',
                    '--webilia-panel-muted' => '#5b5b5b',
                    '--webilia-panel-accent' => '#111111',
                    '--webilia-panel-accent-2' => '#111111',
                    '--webilia-nav-bg' => '#f0f0f0',
                    '--webilia-nav-text' => '#111111',
                    '--webilia-nav-hover-bg' => '#e2e2e2',
                    '--webilia-nav-hover-text' => '#111111',
                    '--webilia-nav-active-bg' => '#d6d6d6',
                    '--webilia-nav-active-text' => '#111111',
                    '--webilia-subnav-bg' => '#e9e9e9',
                    '--webilia-subnav-border' => '#d4d4d4',
                    '--webilia-subnav-radius' => '10px',
                    '--webilia-subnav-padding' => '12px',
                    '--webilia-subnav-text' => '#111111',
                    '--webilia-subnav-hover-bg' => '#dddddd',
                    '--webilia-subnav-active-bg' => '#d2d2d2',
                    '--webilia-subnav-active-text' => '#111111',
                    '--webilia-subnav-indicator-bg' => '#d2d2d2',
                    '--webilia-subnav-indicator-border' => '#d4d4d4',
                    '--webilia-button-bg' => '#111111',
                    '--webilia-button-text' => '#ffffff',
                    '--webilia-button-border' => '#111111',
                ],
            ],
        ];
    }

    /**
     * @return array<mixed>
     */
    public static function panel_theme_custom_vars(): array
    {
        return [
            '--webilia-panel-card' => __('Background', 'webilia-settings-panel'),
            '--webilia-panel-border' => __('Border', 'webilia-settings-panel'),
            '--webilia-panel-text' => __('Text', 'webilia-settings-panel'),
            '--webilia-panel-accent' => __('Accent', 'webilia-settings-panel'),
            '--webilia-panel-accent-2' => __('Accent secondary', 'webilia-settings-panel'),
            '--webilia-nav-bg' => __('Background', 'webilia-settings-panel'),
            '--webilia-nav-text' => __('Text', 'webilia-settings-panel'),
            '--webilia-nav-hover-bg' => __('Hover background', 'webilia-settings-panel'),
            '--webilia-nav-hover-text' => __('Hover text', 'webilia-settings-panel'),
            '--webilia-nav-active-bg' => __('Active background', 'webilia-settings-panel'),
            '--webilia-nav-active-text' => __('Active text', 'webilia-settings-panel'),
            '--webilia-subnav-bg' => __('Subnav background', 'webilia-settings-panel'),
            '--webilia-subnav-text' => __('Subnav text', 'webilia-settings-panel'),
            '--webilia-subnav-hover-bg' => __('Subnav hover background', 'webilia-settings-panel'),
            '--webilia-subnav-active-bg' => __('Subnav active background', 'webilia-settings-panel'),
            '--webilia-subnav-active-text' => __('Subnav active text', 'webilia-settings-panel'),
            '--webilia-subnav-indicator-bg' => __('Subnav indicator background', 'webilia-settings-panel'),
            '--webilia-subnav-indicator-border' => __('Subnav indicator border', 'webilia-settings-panel'),
        ];
    }

    /**
     * @param string $opt_name
     * @return void
     */
    protected static function save_defaults(string $opt_name): void
    {
        $current = get_option($opt_name, null);
        if ($current !== null) return;

        $defaults = self::get_all_defaults($opt_name);
        update_option($opt_name, $defaults);
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @return void
     */
    protected static function register_global_variable(string $opt_name, array $args): void
    {
        $global_var = $args['global_variable'] ?? '';
        if (!is_string($global_var) || $global_var === '') return;

        $GLOBALS[$global_var] = get_option($opt_name, []);

        add_filter('option_' . $opt_name, function ($value) use ($global_var)
        {
            $GLOBALS[$global_var] = $value;
            return $value;
        });

        add_action('update_option_' . $opt_name, function ($old_value, $value) use ($global_var)
        {
            $GLOBALS[$global_var] = $value;
        }, 10, 2);
    }

    /**
     * @param array<mixed> $args
     * @return void
     */
    protected static function register_fonts_controls(array $args): void
    {
        if (!empty($args['disable_google_fonts_link']))
        {
            add_filter('webilia_settings_panel_disable_google_fonts', '__return_true');
        }

        if (isset($args['transient_time']))
        {
            $ttl = (int) $args['transient_time'];
            if ($ttl > 0)
            {
                add_filter('webilia_settings_panel_fonts_transient', function () use ($ttl)
                {
                    return $ttl;
                });
            }
        }
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @return void
     */
    protected static function register_fonts_enqueue(string $opt_name, array $args): void
    {
        $opt_name = sanitize_key($opt_name);
        if ($opt_name === '') return;
        if (isset(self::$fonts_enqueued[$opt_name])) return;

        self::$fonts_enqueued[$opt_name] = true;

        add_action('wp_enqueue_scripts', function () use ($opt_name, $args)
        {
            Fonts::enqueue_selected($opt_name, $args);
        }, 15);
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $fields
     * @param string $section_id
     * @return void
     */
    protected static function index_fields(string $opt_name, array $fields, string $section_id = ''): void
    {
        if (!isset(self::$field_index[$opt_name])) self::$field_index[$opt_name] = [];
        if (!isset(self::$field_meta[$opt_name])) self::$field_meta[$opt_name] = [];

        foreach ($fields as $field)
        {
            if (!is_array($field)) continue;
            if (empty($field['id'])) continue;

            $type = strtolower(trim((string) ($field['type'] ?? 'text')));
            if ($type === 'section') continue;

            $field_id = (string) $field['id'];

            $type = $field['type'] ?? 'text';
            self::$field_index[$opt_name][$field_id] = $type;

            $color_origin = '';
            if (isset($field['color_origin']) && is_string($field['color_origin'])) $color_origin = (string) $field['color_origin'];

            self::$field_meta[$opt_name][$field_id] = [
                'type' => $type,
                'default' => $field['default'] ?? null,
                'has_default' => array_key_exists('default', $field),
                'section' => $section_id ? sanitize_key($section_id) : '',
                'color_origin' => $color_origin,
            ];
        }
    }

    /**
     * @param string $opt_name
     * @param string $field_id
     * @return string
     */
    public static function get_field_type(string $opt_name, string $field_id): string
    {
        $field_id = (string) $field_id;
        if (!$field_id) return '';

        return self::$field_index[$opt_name][$field_id] ?? '';
    }

    /**
     * @param string $opt_name
     * @param string $field_id
     * @return array<mixed>
     */
    public static function get_field_meta(string $opt_name, string $field_id): array
    {
        $field_id = (string) $field_id;
        if (!$field_id) return [];

        return self::$field_meta[$opt_name][$field_id] ?? [];
    }

    /**
     * @param string $opt_name
     * @param string $section_id
     * @return array<mixed>
     */
    public static function get_section_fields(string $opt_name, string $section_id): array
    {
        $section_id = sanitize_key($section_id);
        if (!$section_id) return [];

        $fields = self::$field_meta[$opt_name] ?? [];
        $result = [];
        foreach ($fields as $field_id => $meta)
        {
            if (($meta['section'] ?? '') === $section_id) $result[] = $field_id;
        }

        return $result;
    }

    /**
     * @param string $opt_name
     * @param string $section_id
     * @return array<mixed>
     */
    public static function get_section_defaults(string $opt_name, string $section_id): array
    {
        $section_id = sanitize_key($section_id);
        if (!$section_id) return [];

        $fields = self::$field_meta[$opt_name] ?? [];
        $defaults = [];
        foreach ($fields as $field_id => $meta)
        {
            if (($meta['section'] ?? '') !== $section_id) continue;
            if (!empty($meta['has_default'])) $defaults[$field_id] = $meta['default'];
        }

        return $defaults;
    }

    /**
     * @param string $opt_name
     * @return array<mixed>
     */
    public static function get_all_defaults(string $opt_name): array
    {
        $fields = self::$field_meta[$opt_name] ?? [];
        $defaults = [];
        foreach ($fields as $field_id => $meta)
        {
            if (!empty($meta['has_default'])) $defaults[$field_id] = $meta['default'];
        }

        return $defaults;
    }

    /**
     * @param string $opt_name
     * @return array<mixed>
     */
    public static function get_color_origins(string $opt_name): array
    {
        $fields = self::$field_meta[$opt_name] ?? [];
        $origins = [];

        foreach ($fields as $field_id => $meta)
        {
            $origin = $meta['color_origin'] ?? '';
            if ($origin !== '') $origins[$field_id] = $origin;
        }

        return $origins;
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $types
     * @return array<mixed>
     */
    public static function get_fields_meta_by_types(string $opt_name, array $types): array
    {
        $types = array_map('strtolower', $types);
        $fields = self::$field_meta[$opt_name] ?? [];
        if (!$fields || !$types) return [];

        $result = [];
        foreach ($fields as $field_id => $meta)
        {
            $type = strtolower(trim((string) ($meta['type'] ?? '')));
            if (in_array($type, $types, true)) $result[$field_id] = $meta;
        }

        return $result;
    }

    /**
     * @param array<mixed> $field
     * @param string $opt_name
     * @return mixed
     */
    protected static function field_value(array $field, string $opt_name)
    {
        if (array_key_exists('value', $field))
        {
            if ($field['value'] !== null) return $field['value'];
        }

        $options = get_option($opt_name, []);
        $id = $field['id'] ?? '';
        if ($id !== '' && is_array($options) && array_key_exists($id, $options)) return $options[$id];

        return $field['default'] ?? null;
    }

    /**
     * @param string $type
     * @return string
     */
    protected static function map_type(string $type): string
    {
        $map = [
            'text' => 'text',
            'textarea' => 'textarea',
            'editor' => 'editor',
            'ace_editor' => 'textarea',
            'color' => 'color',
            'color_rgba' => 'color_rgba',
            'color_gradient' => 'color_gradient',
            'color_palette' => 'color_palette',
            'palette' => 'palette',
            'link_color' => 'link_color',
            'background' => 'background',
            'border' => 'border',
            'box_shadow' => 'box_shadow',
            'button_set' => 'button_set',
            'checkbox' => 'checkbox',
            'multi_check' => 'checkboxes',
            'checkboxes' => 'checkboxes',
            'switch' => 'switcher',
            'select' => 'select',
            'radio' => 'radio',
            'select_image' => 'select_image',
            'image_select' => 'image_select',
            'slider' => 'slider',
            'spinner' => 'spinner',
            'dimensions' => 'dimensions',
            'spacing' => 'spacing',
            'media' => 'media',
            'gallery' => 'gallery',
            'multi_media' => 'multi_media',
            'info' => 'info',
            'raw' => 'raw',
            'divide' => 'divide',
            'section' => 'section',
            'repeater' => 'repeater',
            'slides' => 'slides',
            'social_profiles' => 'social_profiles',
            'sortable' => 'sortable',
            'sorter' => 'sorter',
            'tabbed' => 'tabbed',
            'password' => 'password',
            'date' => 'date',
            'date_time' => 'datetime',
            'datetime' => 'datetime',
            'google_maps' => 'google_maps',
            'icon_select' => 'icon_select',
            'button_preview' => 'button_preview',
            'content' => 'content',
            'custom_fonts' => 'custom_fonts',
            'typography' => 'typography',
            'import_export' => 'import_export',
            'panel_theme' => 'panel_theme',
            'panel_nav_position' => 'panel_nav_position',
        ];

        return $map[$type] ?? 'text';
    }

    /**
     * @param array<mixed> $field
     * @param string $opt_name
     * @return string
     */
    protected static function render_field(array $field, string $opt_name): string
    {
        $type = $field['type'] ?? 'text';
        $field_id = isset($field['id']) ? (string) $field['id'] : '';
        $method = self::map_type($type);
        $value = self::field_value($field, $opt_name);
        $color_origin = '';
        if (isset($field['color_origin']) && is_string($field['color_origin']))
        {
            $color_origin = (string) $field['color_origin'];
        }

        $args = [
            'option_name' => $opt_name,
            'key' => $field_id,
            'id' => $field_id,
            'value' => $value,
            'default' => $field['default'] ?? null,
            'class' => $field['class'] ?? '',
            'options' => $field['options'] ?? [],
            'data' => $field['data'] ?? null,
            'tabs' => $field['tabs'] ?? null,
            'tab_group' => $field['tab_group'] ?? null,
            'placeholder' => $field['placeholder'] ?? '',
            'rows' => $field['rows'] ?? null,
            'attributes' => $field['attributes'] ?? [],
            'google' => $field['google'] ?? false,
            'fonts' => $field['fonts'] ?? [],
            'title' => $field['title'] ?? '',
            'subtitle' => $field['subtitle'] ?? '',
            'desc' => $field['desc'] ?? '',
            'button_text' => $field['button_text'] ?? '',
            'preview_fields' => $field['preview_fields'] ?? [],
        ];
        if ($color_origin !== '') $args['color_origin'] = $color_origin;
        if ($color_origin !== '' && in_array($type, ['color', 'color_rgba'], true))
        {
            $args['attributes']['data-wui-sp-color-origin'] = $color_origin;
        }

        if (!empty($field['customizer_link_attr'])) $args['customizer_link_attr'] = $field['customizer_link_attr'];
        if (isset($field['themes']) && is_array($field['themes'])) $args['themes'] = $field['themes'];

        if (isset($field['data']) && is_array($field['data']))
        {
            $args['options'] = $field['data'];
            $args['data'] = null;
        }

        if (isset($field['min'])) $args['attributes']['min'] = $field['min'];
        if (isset($field['max'])) $args['attributes']['max'] = $field['max'];
        if (isset($field['step'])) $args['attributes']['step'] = $field['step'];
        if (!empty($field['multi']) || !empty($field['multiple'])) $args['multiple'] = true;

        if ($type === 'slider') $args['attributes'] = array_merge(['min' => 0, 'max' => 100, 'step' => 1], $args['attributes']);
        if ($type === 'spinner') $args['attributes'] = array_merge(['step' => 1], $args['attributes']);
        if ($type === 'import_export') $args['value'] = wp_json_encode(get_option($opt_name, []));

        if (in_array($type, ['info', 'raw', 'content'], true))
        {
            $args['content'] = $field['desc'] ?? ($field['subtitle'] ?? '');
        }

        $html = '';
        if (is_callable([Form::class, $method]))
        {
            $html = (string) call_user_func([Form::class, $method], $args);
        }

        $subtitle = $field['subtitle'] ?? '';
        $desc = $field['desc'] ?? '';

        if ($subtitle) $html .= '<p class="wui-sp-field-subtitle">' . wp_kses_post($subtitle) . '</p>';
        if ($desc && !in_array($type, ['info', 'raw', 'content'], true))
        {
            $html .= '<p class="wui-sp-field-description">' . wp_kses_post($desc) . '</p>';
        }

        $required = $field['required'] ?? null;

        $wrapper_attrs = ' class="wui-sp-field wui-sp-field-type-' . esc_attr(sanitize_html_class($type)) . '"';
        if ($field_id !== '') $wrapper_attrs .= ' data-field-id="' . esc_attr($field_id) . '"';
        if ($required) $wrapper_attrs .= ' data-required="' . esc_attr(wp_json_encode($required)) . '"';
        if ($type === 'panel_theme' && !empty($field['themes']) && is_array($field['themes']))
        {
            $wrapper_attrs .= ' data-panel-themes="' . esc_attr(wp_json_encode($field['themes'])) . '"';
        }

        if ($color_origin !== '')
        {
            $wrapper_attrs .= ' data-wui-sp-color-origin="' . esc_attr($color_origin) . '"';
        }

        if (!empty($field['customizer_setting_id']))
        {
            $wrapper_attrs .= ' data-customizer-setting-id="' . esc_attr((string) $field['customizer_setting_id']) . '"';
        }

        if (!empty($field['tab'])) $wrapper_attrs .= ' data-wui-sp-tab="' . esc_attr((string) $field['tab']) . '"';
        if (!empty($field['tab_group'])) $wrapper_attrs .= ' data-wui-sp-tab-group="' . esc_attr((string) $field['tab_group']) . '"';

        return '<div' . $wrapper_attrs . '>' . $html . '</div>';
    }

    /**
     * @param array<mixed> $field
     * @param string $opt_name
     * @return string
     */
    public static function render_customizer_field(array $field, string $opt_name): string
    {
        if (!empty($field['customizer_link_attr']))
        {
            $field['customizer_link_attr'] = trim((string) $field['customizer_link_attr']);
        }

        return self::render_field($field, $opt_name);
    }

    /**
     * @param string $opt_name
     * @param mixed $value
     * @return mixed
     */
    protected static function normalize_options(string $opt_name, $value)
    {
        if (!is_array($value)) return $value;

        $fields = self::$field_meta[$opt_name] ?? [];
        if (!$fields) return $value;

        foreach ($fields as $field_id => $meta)
        {
            if (!array_key_exists($field_id, $value)) continue;

            $type = $meta['type'] ?? '';
            if ($type === 'media')
            {
                $value[$field_id] = self::normalize_media_value($value[$field_id]);
                continue;
            }

            if ($type === 'background' && is_array($value[$field_id]) && isset($value[$field_id]['image']))
            {
                $value[$field_id]['image'] = self::normalize_media_value($value[$field_id]['image']);
            }
        }

        return $value;
    }

    /**
     * @param mixed $value
     * @return array<mixed>
     */
    protected static function normalize_media_value($value): array
    {
        if (is_array($value))
        {
            $id = $value['id'] ?? '';
            $url = $value['url'] ?? '';
            if (!$url && $id) $url = wp_get_attachment_url($id) ?: '';

            return [
                'id' => $id,
                'url' => $url,
            ];
        }

        if (is_numeric($value))
        {
            $id = (int) $value;
            $url = wp_get_attachment_url($id) ?: '';
            return ['id' => $id, 'url' => $url];
        }

        if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL))
        {
            return ['id' => '', 'url' => $value];
        }

        return ['id' => '', 'url' => ''];
    }
}
