<?php
namespace Webilia\WP\SettingsPanel;

class Settings extends Base
{
    /** @var array<mixed> */
    private static $instances = [];
    /** @var boolean */
    private static $actions_registered = false;
    /** @var boolean */
    private $persisting_reset = false;

    /** @var array<string, mixed> */
    protected $config = [
        'option_name' => 'webilia_settings_panel',
        'page_title' => 'Webilia Settings',
        'menu_title' => 'Settings',
        'display_version' => '',
        'capability' => 'manage_options',
        'menu_slug' => 'webilia-settings',
        'menu_position' => null,
        'menu_icon' => 'dashicons-admin-appearance',
        'parent_slug' => 'webilia',
        'menu_type' => '',
        'allow_sub_menu' => true,
        'admin_bar' => false,
        'admin_bar_icon' => '',
        'admin_bar_priority' => 100,
        'page_icon' => '',
        'panel_title' => 'Webilia',
        'panel_intro' => '',
        'dev_mode' => false,
        'customizer' => false,
        'last_tab' => '',
        'default_show' => false,
        'default_mark' => '',
        'show_import_export' => false,
        'save_defaults' => false,
    ];

    /** @var array<mixed> */
    protected $sections = [];
    /** @var array<mixed> */
    protected $fields = [];

    /**
     * @param array<mixed> $config
     * @return mixed
     */
    public function set_config(array $config)
    {
        $this->config = array_merge($this->config, $config);
        $this->option_name($this->config['option_name']);
        return $this;
    }

    /**
     * @return void
     */
    public function init()
    {
        \Webilia\WP\ThemeTools::requireUI();
        if (isset(self::$instances[$this->config['option_name']])) return;
        self::$instances[$this->config['option_name']] = $this;
        add_filter('option_page_capability_' . $this->config['menu_slug'], function () { return $this->config['capability'];
});
        add_action('admin_menu', [$this, 'register_page']);
        add_action('admin_init', [$this, 'register_settings']);

        if (!self::$actions_registered)
        {
            self::$actions_registered = true;
            foreach (['save', 'reset_section', 'reset_all'] as $action)
            {
                add_action('wp_ajax_webilia_wp_settings_' . $action, static function () use ($action)
                {
                    $opt = self::request_option_name();
                    if (!isset(self::$instances[$opt])) wp_send_json_error(['message' => 'invalid_opt'], 400);
                    self::$instances[$opt]->{'ajax_' . $action}();
                });
            }
        }

        if (!empty($this->config['customizer']))
        {
            add_action('customize_register', [$this, 'register_customizer']);
            add_action('customize_controls_enqueue_scripts', [$this, 'enqueue_customizer_controls']);
        }

        if (!empty($this->config['admin_bar']))
        {
            $priority = is_numeric($this->config['admin_bar_priority']) ? (int) $this->config['admin_bar_priority'] : 100;
            add_action('admin_bar_menu', [$this, 'register_admin_bar'], $priority);
        }
    }

    /**
     * @param string $page
     * @return array<mixed>
     */
    public static function get_config_by_page(string $page): array
    {
        foreach (self::$instances as $settings)
        {
            if ($settings->config['menu_slug'] === $page) return $settings->config;
        }

        return [];
    }

    /**
     * @return bool
     */
    public static function has_customizer(): bool
    {
        foreach (self::$instances as $settings)
        {
            if (!empty($settings->config['customizer']) && current_user_can($settings->config['capability'])) return true;
        }

        return false;
    }

    /**
     * @param mixed $id
     * @param mixed $title
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public function add_section($id, $title = '', $callback = null, array $args = [])
    {
        $id = sanitize_key($id);
        if ($id === '') return $this;

        $this->sections[$id] = [
            'title' => $title,
            'callback' => $callback,
            'parent_id' => isset($args['parent_id']) ? sanitize_key($args['parent_id']) : '',
            'subsection' => !empty($args['subsection']),
            'icon' => isset($args['icon']) ? (string) $args['icon'] : '',
            'has_content' => !empty($args['has_content']),
        ];

        return $this;
    }

    /**
     * @param mixed $id
     * @param mixed $title
     * @param mixed $section
     * @param mixed $callback
     * @param array<mixed> $args
     * @return mixed
     */
    public function add_field($id, $title, $section, $callback, array $args = [])
    {
        $id = is_scalar($id) ? (string) $id : '';
        $section = sanitize_key($section);
        if ($id === '' || $section === '') return $this;

        $this->fields[$id] = [
            'title' => $title,
            'section' => $section,
            'callback' => $callback,
            'args' => $args,
        ];

        return $this;
    }

    /**
     * @return void
     */
    public function register_page()
    {
        $cfg = $this->config;
        $callback = [$this, 'render_page'];
        $parent_slug = $cfg['parent_slug'];
        $position = $cfg['menu_position'];
        if ($position === '') $position = null;
        if (is_numeric($position)) $position = (float) $position;

        if (($cfg['menu_type'] ?? '') === 'menu' || empty($cfg['allow_sub_menu']))
        {
            $parent_slug = '';
        }

        if (!empty($parent_slug))
        {
            if ($position === null)
            {
                add_submenu_page(
                    $parent_slug,
                    $cfg['page_title'],
                    $cfg['menu_title'],
                    $cfg['capability'],
                    $cfg['menu_slug'],
                    $callback
                );
            }
            else
            {
                add_submenu_page(
                    $parent_slug,
                    $cfg['page_title'],
                    $cfg['menu_title'],
                    $cfg['capability'],
                    $cfg['menu_slug'],
                    $callback,
                    $position
                );
            }

            return;
        }

        add_menu_page(
            $cfg['page_title'],
            $cfg['menu_title'],
            $cfg['capability'],
            $cfg['menu_slug'],
            $callback,
            $cfg['menu_icon'],
            $cfg['menu_position']
        );
    }

    /**
     * @return void
     */
    public function register_settings()
    {
        register_setting($this->config['menu_slug'], $this->config['option_name'], [$this, 'sanitize']);

        foreach ($this->sections as $id => $section)
        {
            add_settings_section(
                $id,
                $section['title'],
                $section['callback'],
                $this->config['menu_slug']
            );
        }

        foreach ($this->fields as $id => $field)
        {
            add_settings_field(
                $id,
                $field['title'],
                $field['callback'],
                $this->config['menu_slug'],
                $field['section'],
                $field['args']
            );
        }
    }

    /**
     * @param string $section_id
     * @return array<mixed>
     */
    public function get_section_fields(string $section_id): array
    {
        $section_id = sanitize_key($section_id);
        if ($section_id === '') return [];

        $result = [];
        foreach ($this->fields as $id => $field)
        {
            $field_section = sanitize_key((string) ($field['section'] ?? ''));
            if ($field_section !== $section_id) continue;
            $result[] = $field + ['id' => $id];
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function render_page()
    {
        if (!current_user_can($this->config['capability'])) return;
        $sections = $this->sections;
        foreach ($sections as $id => &$section)
        {
            ob_start();
            if (is_callable($section['callback'])) call_user_func($section['callback']);
            $section['description_html'] = (string) ob_get_clean();
            $section['fields'] = $this->get_section_fields($id);
            foreach ($section['fields'] as &$field)
            {
                ob_start();
                if (is_callable($field['callback'])) call_user_func($field['callback'], $field['args']);
                $field['html'] = (string) ob_get_clean();
            }

            unset($field);
        }

        unset($section);
        ob_start();
        settings_fields($this->config['menu_slug']);
        $hidden = (string) ob_get_clean();
        $default = sanitize_key((string) ($this->config['last_tab'] ?? ''));
        $nav = $this->panel_nav_position();
        $style = $this->panel_theme_style();
        $attrs = 'class="wrap wui-sp-panel wui" data-wui-sp-default-section="' . esc_attr($default) . '" data-wui-sp-nav-position="' .
            esc_attr($nav) . '" style="' . esc_attr($style) . '"';
        $icon = $this->render_icon($this->config['page_icon'] ?? '');
        if ($icon === '') $icon = $this->render_icon($this->config['menu_icon'] ?? '');
        echo $this->include_html_file('settings.php', ['parameters' => [
            'sections' => $sections, 'config' => $this->config,
            'menu_slug' => $this->config['menu_slug'],
            'nonce' => wp_create_nonce('webilia_wp_settings_actions'),
            'panel_attrs' => $attrs, 'hidden_html' => $hidden,
            'post_url' => admin_url('options.php'),
            'header_html' => $this->page_header_html(),
            'panel_intro_html' => wp_kses_post($this->config['panel_intro']),
            'actions_top_html' => $this->render_actions(),
            'actions_bottom_html' => $this->render_actions(true),
            'panel_title' => $this->config['panel_title'],
            'version' => $this->config['display_version'], 'icon_html' => $icon,
            'labels' => \Webilia\WP\ThemeTools::labels(\Webilia\UI\SettingsPanel\Layout::LABELS, 'webilia-settings-panel'),
        ], 'return_output' => true]);
    }

    /**
     * @return string
     */
    public function page_header_html(): string
    {
        $title = $this->config['panel_title'] ?? ($this->config['page_title'] ?? '');
        $title = is_string($title) ? trim($title) : '';
        $version = isset($this->config['display_version']) ? trim((string) $this->config['display_version']) : '';
        $icon_html = $this->render_icon($this->config['page_icon'] ?? '');
        if ($icon_html === '') $icon_html = $this->render_icon($this->config['menu_icon'] ?? '');

        $header = apply_filters('webilia_settings_panel/page_header_html', '', [
            'title' => $title,
            'url' => $this->current_page_url(),
            'version' => $version,
            'icon_html' => $icon_html,
        ], $this->config);

        if (is_string($header) && trim($header) !== '') return trim($header);
        if ($title === '') return '';

        $html = '<h1 class="wui-sp-page-title">';
        if ($icon_html !== '') $html .= $icon_html . ' ';

        $html .= esc_html($title);

        if ($version !== '') $html .= ' <span class="wui-sp-panel-version">' . esc_html($version) . '</span>';

        $html .= '</h1>';

        return $html;
    }

    /**
     * @param mixed $wp_customize
     * @return void
     */
    public function register_customizer($wp_customize)
    {
        if (!($wp_customize instanceof \WP_Customize_Manager)) return;

        $capability = $this->config['capability'] ?? 'edit_theme_options';
        $opt_name = $this->config['option_name'] ?? '';
        if ($opt_name === '') return;

        $menu_slug = sanitize_key((string) ($this->config['menu_slug'] ?? $opt_name));
        if ($menu_slug === '') $menu_slug = 'wui-sp-settings';

        $priority = 160;
        $menu_position = $this->config['menu_position'] ?? null;
        if (is_numeric($menu_position)) $priority = (int) $menu_position;

        $section_map = [];
        $children_map = [];

        foreach ($this->sections as $section_id => $section)
        {
            $section_id = sanitize_key((string) $section_id);
            if ($section_id === '') continue;

            $parent_id = sanitize_key((string) ($section['parent_id'] ?? ''));
            if ($parent_id === '') continue;

            if (!isset($children_map[$parent_id])) $children_map[$parent_id] = [];
            $children_map[$parent_id][] = $section_id;
        }

        $panel_id = 'webilia_settings_' . $menu_slug;
        $panel_title = $this->config['panel_title'] ?? ($this->config['page_title'] ?? $this->config['menu_title'] ?? 'Settings');
        $panel_desc = $this->config['panel_intro'] ?? '';

        $wp_customize->add_panel($panel_id, [
            'title' => $panel_title,
            'description' => $panel_desc,
            'priority' => $priority,
            'capability' => $capability,
        ]);

        $section_priority = $priority;
        foreach ($this->sections as $section_id => $section)
        {
            $section_id = sanitize_key((string) $section_id);
            if ($section_id === '') continue;
            if (in_array($section_id, ['import_export', 'extra'], true)) continue;

            $title = $section['title'] ?? ucfirst($section_id);
            $desc = '';
            if (isset($section['callback']) && is_callable($section['callback']))
            {
                ob_start();
                call_user_func($section['callback']);
                $desc = trim(ob_get_clean());
            }

            $has_children = !empty($children_map[$section_id]);
            $has_fields = !empty($this->get_section_fields($section_id));

            $customizer_section_id = $menu_slug . '_section_' . $section_id;
            $section_args = [
                'title' => $title,
                'description' => $desc,
                'panel' => $panel_id,
                'priority' => $section_priority,
            ];

            $wp_customize->add_section($customizer_section_id, $section_args);

            $section_map[$section_id] = $customizer_section_id;
            $section_priority += 10;

            if ($has_children && !$has_fields)
            {
                $child_links = [];
                foreach ($children_map[$section_id] as $child_id)
                {
                    $child = $this->sections[$child_id] ?? [];
                    $child_title = $child['title'] ?? ucfirst($child_id);

                    $child_section_id = $menu_slug . '_section_' . $child_id;
                    $child_links[] = '<li id="wui-sp-accordion-section-' . esc_attr($child_section_id) .
                        '" class="accordion-section control-section control-section-default control-subsection"
                        aria-owns="sub-accordion-section-' . esc_attr($child_section_id) .
                        '"><h3 class="accordion-section-title"><button type="button" class="accordion-trigger
                        wui-sp-customizer-subsection-link" aria-expanded="false" aria-controls="' . esc_attr($child_section_id) .
                        '-content" data-wui-sp-section="' . esc_attr($child_section_id) . '" data-wui-sp-panel="' . esc_attr($panel_id) .
                        '">' . esc_html($child_title) . '</button></h3></li>';
                }

                $intro_id = '_section_intro_' . $section_id;
                $intro_setting_id = $opt_name . '[' . $intro_id . ']';
                $intro_desc = '<ul class="wui-sp-customizer-subsection-links">' . implode('', $child_links) . '</ul>';

                $wp_customize->add_setting($intro_setting_id, [
                    'type' => 'option',
                    'capability' => $capability,
                    'default' => '',
                    'sanitize_callback' => 'sanitize_text_field',
                ]);

                $wp_customize->add_control(new Customizer($wp_customize, $intro_setting_id, [
                    'section' => $customizer_section_id,
                    'field' => [
                        'id' => $intro_id,
                        'type' => 'content',
                        'desc' => $intro_desc,
                    ],
                    'field_id' => $intro_id,
                    'field_type' => 'content',
                    'option_name' => $opt_name,
                    'field_base_name' => $intro_setting_id,
                ]));
            }
        }

        foreach ($this->fields as $field_id => $field)
        {
            $field_id = (string) $field_id;
            if ($field_id === '') continue;

            $section_key = sanitize_key((string) ($field['section'] ?? ''));
            if ($section_key === '' || empty($section_map[$section_key])) continue;
            $field_type = strtolower(trim((string) ($field['args']['type'] ?? '')));
            if ($field_type === 'import_export' || $field_type === 'button_preview') continue;

            $setting_id = $opt_name . '[' . $field_id . ']';

            $wp_customize->add_setting($setting_id, [
                'type' => 'option',
                'capability' => $capability,
                'default' => $field['args']['default'] ?? null,
                'sanitize_callback' => [$this, 'sanitize_customizer_value'],
            ]);

            $wp_customize->add_control(new Customizer($wp_customize, $setting_id, [
                'section' => $section_map[$section_key],
                'field' => $field['args'] ?? [],
                'field_id' => $field_id,
                'field_type' => $field['args']['type'] ?? '',
                'option_name' => $opt_name,
                'field_base_name' => $opt_name . '[' . $field_id . ']',
            ]));
        }
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function sanitize_customizer_value($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function sanitize($value)
    {
        // Reset values are complete replacements, not partial form submissions.
        if ($this->persisting_reset) return $value;

        $existing = $this->get_option();
        $existing = is_array($existing) ? $existing : [];
        $value = is_array($value) ? $value : [];

        $import = '';

        if (isset($value['import_export']))
        {
            if (is_array($value['import_export']))
            {
                $import = $value['import_export']['import'] ?? '';
            }
            else if (is_string($value['import_export']))
            {
                $import = $value['import_export'];
            }
        }

        if ($import === '' && isset($value['import']) && is_string($value['import'])) $import = $value['import'];

        // Apply import (array)
        if (is_array($import) && $import)
        {
            unset($value['import_export'], $value['import']);
            return array_replace($existing, $import);
        }

        // Apply import (json string)
        if (is_string($import) && trim($import) !== '')
        {
            $decoded = json_decode($import, true);

            if (!is_array($decoded) || json_last_error() !== JSON_ERROR_NONE)
            {
                $decoded = json_decode(wp_unslash($import), true);
            }

            if (is_array($decoded) && json_last_error() === JSON_ERROR_NONE)
            {
                unset($value['import_export'], $value['import']);
                return array_replace($existing, $decoded);
            }
        }

        unset($value['import_export'], $value['import']);

        $opt_name = $this->config['option_name'] ?? '';
        if ($opt_name !== '')
        {
            $checkbox_fields = Panel::get_fields_meta_by_types($opt_name, ['checkbox', 'checkboxes', 'multi_check']);
            foreach ($checkbox_fields as $field_id => $meta)
            {
                if (array_key_exists($field_id, $value)) continue;

                $type = strtolower(trim((string) ($meta['type'] ?? '')));
                if (in_array($type, ['checkboxes', 'multi_check'], true))
                {
                    $value[$field_id] = [];
                    continue;
                }

                $current = $existing[$field_id] ?? null;
                $default = $meta['default'] ?? null;
                $value[$field_id] = (is_array($current) || is_array($default)) ? [] : 0;
            }
        }

        return array_replace($existing, $value);
    }

    /**
     * @param bool $is_bottom
     * @return string
     */
    protected function render_actions(bool $is_bottom = false): string
    {
        return (string) $this->include_html_file('actions.php', ['parameters' => [
            'bottom' => $is_bottom, 'is_bottom' => $is_bottom,
            'labels' => \Webilia\WP\ThemeTools::labels(\Webilia\UI\SettingsPanel\Layout::LABELS, 'webilia-settings-panel'),
        ], 'return_output' => true]);
    }

    /** Read an exact registered option key; registry lookup provides validation.
     * @return string
     */
    private static function request_option_name(): string
    {
        $value = $_POST['opt_name'] ?? '';
        return is_string($value) ? wp_unslash($value) : '';
    }

    /**
     * @param string $opt_name
     * @return bool
     */
    protected function is_valid_opt_name(string $opt_name): bool
    {
        $expected = (string) ($this->config['option_name'] ?? '');
        if ($expected === '') return false;

        return $expected === $opt_name;
    }

    /**
     * @return mixed
     */
    public function ajax_save()
    {
        if (!current_user_can($this->config['capability'])) wp_send_json_error(['message' => 'forbidden'], 403);
        if (!check_ajax_referer('webilia_wp_settings_actions', 'nonce', false)) wp_send_json_error(['message' => 'invalid_nonce'], 400);

        $opt_name = self::request_option_name();
        if (!$opt_name) wp_send_json_error(['message' => 'missing_opt'], 400);
        if (!$this->is_valid_opt_name($opt_name)) wp_send_json_error(['message' => 'invalid_opt'], 400);

        $raw = $_POST['data'] ?? '';
        $parsed = [];
        if (is_string($raw) && $raw !== '') parse_str($raw, $parsed);

        $settings = $parsed[$opt_name] ?? [];
        if (!is_array($settings)) $settings = [];

        if (!empty($_POST['import_b64']) && is_string($_POST['import_b64']))
        {
            $decoded = base64_decode($_POST['import_b64'], true);
            if ($decoded !== false)
            {
                if (!isset($settings['import_export']) || !is_array($settings['import_export'])) $settings['import_export'] = [];
                $settings['import_export']['import'] = $decoded;
            }
        }

        $merged = $this->sanitize($settings);
        update_option($opt_name, $merged);

        wp_send_json_success(['message' => esc_html__('Settings saved.', 'webilia-settings-panel')]);
    }

    /**
     * @return mixed
     */
    public function ajax_reset_section()
    {
        if (!current_user_can($this->config['capability'])) wp_send_json_error(['message' => 'forbidden'], 403);
        if (!check_ajax_referer('webilia_wp_settings_actions', 'nonce', false)) wp_send_json_error(['message' => 'invalid_nonce'], 400);

        $opt_name = self::request_option_name();
        $section = sanitize_key($_POST['section'] ?? '');
        if (!$opt_name || !$section) wp_send_json_error(['message' => 'missing_section'], 400);
        if (!$this->is_valid_opt_name($opt_name)) wp_send_json_error(['message' => 'invalid_opt'], 400);

        $current = get_option($opt_name, []);
        if (!is_array($current)) $current = [];

        $fields = Panel::get_section_fields($opt_name, $section);
        $defaults = Panel::get_section_defaults($opt_name, $section);
        $color_origins = Panel::get_color_origins($opt_name);

        $updated = $current;
        foreach ($fields as $field_id)
        {
            if (array_key_exists($field_id, $defaults)) $updated[$field_id] = $defaults[$field_id];
            else unset($updated[$field_id]);
        }

        foreach ($fields as $field_id)
        {
            $origin = $color_origins[$field_id] ?? '';
            if ($origin === '') continue;
            if (!array_key_exists($origin, $updated)) continue;

            $meta = Panel::get_field_meta($opt_name, $field_id);
            $updated = self::apply_color_origin($updated, $field_id, $meta, $updated[$origin]);
        }

        $this->persist_reset($opt_name, $updated);
        wp_send_json_success(['message' => esc_html__('Section reset.', 'webilia-settings-panel')]);
    }

    /**
     * @return mixed
     */
    public function ajax_reset_all()
    {
        if (!current_user_can($this->config['capability'])) wp_send_json_error(['message' => 'forbidden'], 403);
        if (!check_ajax_referer('webilia_wp_settings_actions', 'nonce', false)) wp_send_json_error(['message' => 'invalid_nonce'], 400);

        $opt_name = self::request_option_name();
        if (!$opt_name) wp_send_json_error(['message' => 'missing_opt'], 400);
        if (!$this->is_valid_opt_name($opt_name)) wp_send_json_error(['message' => 'invalid_opt'], 400);

        $defaults = Panel::get_all_defaults($opt_name);
        $color_origins = Panel::get_color_origins($opt_name);
        $current = get_option($opt_name, []);
        if (!is_array($current)) $current = [];

        foreach ($color_origins as $field_id => $origin)
        {
            if ($origin === '') continue;

            $origin_value = null;
            if (array_key_exists($origin, $defaults)) $origin_value = $defaults[$origin];
            else if (array_key_exists($origin, $current)) $origin_value = $current[$origin];
            else continue;

            $meta = Panel::get_field_meta($opt_name, $field_id);
            $defaults = self::apply_color_origin($defaults, $field_id, $meta, $origin_value);
        }

        $this->persist_reset($opt_name, $defaults);

        wp_send_json_success(['message' => esc_html__('All settings reset.', 'webilia-settings-panel')]);
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $value
     * @return void
     */
    private function persist_reset(string $opt_name, array $value): void
    {
        $this->persisting_reset = true;
        try
        {
            update_option($opt_name, $value);
        }
        finally
        {
            $this->persisting_reset = false;
        }
    }

    /**
     * @param array<mixed> $values
     * @param string $field_id
     * @param array<mixed> $meta
     * @param mixed $origin_value
     * @return array<mixed>
     */
    protected static function apply_color_origin(array $values, string $field_id, array $meta, $origin_value): array
    {
        if (!array_key_exists($field_id, $values)) return $values;

        $color = self::extract_color_value($origin_value);
        if ($color === null) return $values;

        $field_value = $values[$field_id];
        if (!is_array($field_value))
        {
            $values[$field_id] = $color;
            return $values;
        }

        $field_type = strtolower(trim((string) ($meta['type'] ?? '')));
        $values[$field_id] = self::patch_structured_color($field_value, $field_type, $color);
        return $values;
    }

    /**
     * @param mixed $value
     * @return ?string
     */
    protected static function extract_color_value($value): ?string
    {
        if (is_string($value) || is_numeric($value))
        {
            $color = trim((string) $value);
            return $color === '' ? null : $color;
        }

        if (!is_array($value)) return null;

        foreach (['color', 'border-color', 'regular', 'start', 'end'] as $key)
        {
            if (!array_key_exists($key, $value)) continue;
            if (!is_scalar($value[$key])) continue;
            $color = trim((string) $value[$key]);
            if ($color !== '') return $color;
        }

        return null;
    }

    /**
     * @param array<mixed> $value
     * @param string $field_type
     * @param string $color
     * @return array<mixed>
     */
    protected static function patch_structured_color(array $value, string $field_type, string $color): array
    {
        switch ($field_type)
        {
            case 'background':
            case 'typography':
                $value['color'] = $color;
                return $value;
            case 'border':
                $value['border-color'] = $color;
                return $value;
            case 'link_color':
                $value['regular'] = $color;
                return $value;
            case 'color_gradient':
                if (array_key_exists('start', $value)) $value['start'] = $color;
                if (array_key_exists('end', $value)) $value['end'] = $color;
                return $value;
            default:
                if (array_key_exists('color', $value)) $value['color'] = $color;
                else if (array_key_exists('border-color', $value)) $value['border-color'] = $color;
                else if (array_key_exists('regular', $value)) $value['regular'] = $color;
                return $value;
        }
    }

    /**
     * @param mixed $wp_admin_bar
     * @return void
     */
    public function register_admin_bar($wp_admin_bar)
    {
        if (!is_admin_bar_showing()) return;
        if (!current_user_can($this->config['capability'])) return;
        if (!is_object($wp_admin_bar) || !method_exists($wp_admin_bar, 'add_node')) return;

        $slug = sanitize_key($this->config['menu_slug'] ?? '');
        if ($slug === '') return;

        $icon = $this->config['admin_bar_icon'] ?? '';
        if ($icon === '') $icon = $this->config['menu_icon'] ?? '';

        $title = esc_html($this->config['menu_title']);
        if ($icon !== '')
        {
            if (filter_var($icon, FILTER_VALIDATE_URL))
            {
                $icon_html = '<img class="ab-icon wui-sp-adminbar-icon" src="' . esc_url($icon) . '" alt="">';
            }
            else
            {
                $icon_html = '<span class="ab-icon ' . esc_attr($icon) . '"></span>';
            }

            $title = $icon_html . '<span class="ab-label">' . esc_html($this->config['menu_title']) . '</span>';
        }

        $wp_admin_bar->add_node([
            'id' => 'wui-sp-settings-' . $slug,
            'title' => $title,
            'href' => $this->current_page_url(),
        ]);
    }

    /**
     * @param string $icon
     * @return string
     */
    protected function render_icon(string $icon): string
    {
        $icon = trim($icon);
        if ($icon === '') return '';

        if (filter_var($icon, FILTER_VALIDATE_URL))
        {
            return '<img class="wui-sp-panel-icon" src="' . esc_url($icon) . '" alt="">';
        }

        return '<span class="wui-sp-panel-icon ' . esc_attr($icon) . '"></span>';
    }

    /**
     * @return string
     */
    protected function current_page_url(): string
    {
        $slug = (string) ($this->config['menu_slug'] ?? '');
        if ($slug === '') return admin_url();

        $registered = function_exists('menu_page_url') ? menu_page_url($slug, false) : '';
        if ($registered !== '') return $registered;
        $parent = (string) ($this->config['parent_slug'] ?? '');
        if (($this->config['menu_type'] ?? '') === 'menu' || empty($this->config['allow_sub_menu'])) $parent = '';
        $parent = $parent !== '' ? $parent : 'admin.php';
        // Plugin-defined parent slugs are routed through admin.php by WordPress.
        if (strpos($parent, '.php') === false) $parent = 'admin.php';
        return add_query_arg('page', $slug, admin_url($parent));
    }

    /**
     * @param string $path
     * @return string
     */
    protected function assets_url(string $path): string
    {
        $base_url = $this->config['base_url'] ?? '';
        return trim($base_url, '/') . '/' . ltrim($path, '/');
    }

    /**
     * @param string $path
     * @return string
     */
    protected function asset_version(string $path): string
    {
        $base_path = $this->config['base_path'] ?? '';
        $file = trim($base_path, '/') . '/' . ltrim($path, '/');

        if (file_exists($file)) return (string) filemtime($file);
        return '1';
    }

    /**
     * @return void
     */
    public function enqueue_customizer_controls()
    {
        Boot::enqueue_customizer();
    }

    /**
     * @return string
     */
    protected function panel_theme_style(): string
    {
        $opt_name = $this->config['option_name'] ?? '';
        if ($opt_name === '') return '';

        $themes = Panel::get_panel_themes($opt_name);
        if (!$themes) return '';

        $field_id = Panel::get_arg($opt_name, 'panel_theme_field', 'panel_theme');
        $default_theme = Panel::get_arg($opt_name, 'panel_theme_default', 'listdom');
        $fallback_theme = isset($themes[$default_theme]) ? $default_theme : array_key_first($themes);

        $options = get_option($opt_name, []);
        $selected = is_array($options) ? ($options[$field_id] ?? $default_theme) : $default_theme;
        $selected = is_string($selected) ? $selected : $default_theme;
        $vars = [];
        if ($selected === 'custom')
        {
            $custom_key = $field_id . '_custom';
            $custom_values = is_array($options) ? ($options[$custom_key] ?? []) : [];
            $custom_values = is_array($custom_values) ? $custom_values : [];
            $defaults = [];
            if ($fallback_theme && isset($themes[$fallback_theme]['vars']) && is_array($themes[$fallback_theme]['vars']))
            {
                $defaults = $themes[$fallback_theme]['vars'];
            }

            foreach (Panel::panel_theme_custom_vars() as $key => $label)
            {
                $value = $custom_values[$key] ?? '';
                if ($value === '' && isset($defaults[$key]))
                {
                    $value = $defaults[$key];
                }

                if (!is_scalar($value) || $value === '') continue;
                $vars[$key] = $value;
            }
        }
        else if (isset($themes[$selected]['vars']) && is_array($themes[$selected]['vars']))
        {
            $vars = $themes[$selected]['vars'];
        }

        if (!$vars) return '';
        $style_parts = [];
        foreach ($vars as $key => $value)
        {
            if (!is_string($key) || $key === '') continue;
            if (!is_scalar($value)) continue;

            $style_parts[] = $key . ': ' . trim((string) $value) . ';';
        }

        return $style_parts ? implode(' ', $style_parts) : '';
    }

    /**
     * @return string
     */
    protected function panel_nav_position(): string
    {
        $opt_name = $this->config['option_name'] ?? '';
        if ($opt_name === '') return '';

        $field_id = Panel::get_arg($opt_name, 'panel_nav_position_field', 'panel_nav_position');
        $default_pos = Panel::get_arg($opt_name, 'panel_nav_position_default', 'left');

        $options = get_option($opt_name, []);
        $selected = is_array($options) ? ($options[$field_id] ?? $default_pos) : $default_pos;
        $selected = is_string($selected) ? $selected : $default_pos;

        return in_array($selected, ['left', 'right', 'top'], true) ? $selected : $default_pos;
    }
}
