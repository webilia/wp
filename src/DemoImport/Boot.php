<?php
namespace Webilia\WP\DemoImport;

class Boot extends Base
{
    /** @var boolean */
    private static $ran = false;
    /** @var string */
    protected string $base_url;
    /** @var array<mixed> */
    private $temporary_files = [];

    /**
     * @param mixed $config
     */
    public function __construct($config)
    {
        if (empty($config['base_url']) || empty($config['ui_url']))
        {
            throw new \InvalidArgumentException('Supply base_url (WP package root) and ui_url (UI package root).');
        }

        $this->set_config($config);

        $this->base_url = (string) ($config['base_url'] ?? '');
    }

    /**
     * Bootstrap hooks and admin registrations once per request.
     * @return void
     */
    public function init(): void
    {
        if (self::$ran) return;
        \Webilia\WP\ThemeTools::requireUI();
        self::$ran = true;

        add_action('admin_post_webilia_wp_demo_start_import', [$this, 'start_import']);
        add_action('wp_ajax_webilia_wp_demo_install_plugin', [$this, 'ajax_install_plugin']);
        add_action('wp_ajax_webilia_wp_demo_import_demo_data', [$this, 'ajax_import_demo_data']);
        add_action('wp_ajax_webilia_wp_demo_import_customizer_data', [$this, 'ajax_import_customizer_data']);
        add_action('wp_ajax_webilia_wp_demo_after_import_data', [$this, 'ajax_after_import_data']);
        add_filter('admin_body_class', [$this, 'admin_body_class']);

        $this->register_assets();
        $this->register_admin_page();
    }

    /**
     * Run a configured callback and fall back when it is missing or returns null.
     */
    /**
     * @param string $key
     * @param array<mixed> $args
     * @param mixed $default
     * @return mixed
     */
    protected function callback(string $key, array $args, $default)
    {
        $callback = $this->get_config('callbacks.' . $key, null);
        if (!is_callable($callback)) return $default;

        $result = call_user_func_array($callback, $args);
        return $result === null ? $default : $result;
    }

    /**
     * Apply the package-specific filter namespace.
     */
    /**
     * @param string $hook
     * @param mixed $value
     * @param mixed ...$args
     * @return mixed
     */
    protected function apply_wdi_filters(string $hook, $value, ...$args)
    {
        return apply_filters('wdi/' . $hook, $value, ...$args);
    }

    /**
     * Dispatch an action inside the package-specific hook namespace.
     */
    /**
     * @param string $hook
     * @param mixed ...$args
     * @return void
     */
    protected function do_wdi_action(string $hook, ...$args): void
    {
        do_action('wdi/' . $hook, ...$args);
    }

    /**
     * Resolve a config value into a non-empty string, including callable values.
     */
    /**
     * @param mixed $value
     * @param string $fallback
     * @return string
     */
    protected function config_string($value, string $fallback = ''): string
    {
        if (is_callable($value)) $value = call_user_func($value, $fallback, $this->get_config());
        if (!is_string($value) || trim($value) === '') return $fallback;

        return $value;
    }

    /**
     * Load demo definitions from callbacks and filters.
     */
    /**
     * @return array<mixed>
     */
    protected function demo(): array
    {
        $demos = $this->callback('demos', [], null);
        if ($demos === null) $demos = $this->callback('import_files', [[]], []);
        if (!is_array($demos)) return [];

        $filtered = $this->apply_wdi_filters('demos', $demos, $this->get_config());
        return is_array($filtered) ? $filtered : [];
    }

    /**
     * Load plugin definitions from callbacks and filters.
     */
    /**
     * @return array<mixed>
     */
    protected function plugin(): array
    {
        $plugins = $this->callback('plugins', [[]], null);
        if ($plugins === null) $plugins = $this->callback('register_plugins', [[]], []);
        if (!is_array($plugins)) return [];

        $filtered = $this->apply_wdi_filters('plugins', $plugins, $this->get_config());
        return is_array($filtered) ? $filtered : [];
    }

    /**
     * Register admin asset loading when enabled in config.
     */
    /**
     * @return void
     */
    protected function register_assets(): void
    {
        if (!$this->get_config('assets.enabled', false)) return;

        $hook = $this->get_config('assets.enqueue_hook', 'admin_enqueue_scripts');
        if (!is_string($hook) || trim($hook) === '') $hook = 'admin_enqueue_scripts';

        add_action($hook, [$this, 'assets']);
    }

    /**
     * Register the importer admin page when enabled in config.
     */
    /**
     * @return void
     */
    protected function register_admin_page(): void
    {
        if (!$this->get_config('admin_page.enabled', false)) return;

        add_action('admin_menu', [$this, 'add_admin_page'], 9);
    }

    /**
     * Add the configured submenu page for the demo importer.
     */
    /**
     * @return void
     */
    public function add_admin_page(): void
    {
        $default_page_title = $this->config_string($this->get_config('page_setup.page_title', $this->default_demo_import_label()),
            $this->default_demo_import_label());
        $page_setup = [
            'parent_slug' => (string) $this->get_config('admin_page.parent_slug', ''),
            'menu_slug' => (string) $this->get_config('page_setup.menu_slug', ''),
            'page_title' => $default_page_title,
            'menu_title' => $this->config_string($this->get_config('admin_page.menu_title', $default_page_title), $default_page_title),
        ];
        $page_setup = $this->apply_wdi_filters('page_setup', $page_setup, $this->get_config());
        if (!is_array($page_setup)) return;

        $parent_slug = isset($page_setup['parent_slug']) ? (string) $page_setup['parent_slug'] : '';
        $menu_slug = isset($page_setup['menu_slug']) ? (string) $page_setup['menu_slug'] : '';
        $page_title = isset($page_setup['page_title']) ? (string) $page_setup['page_title'] : $this->default_demo_import_label();
        $menu_title = isset($page_setup['menu_title']) ? (string) $page_setup['menu_title'] : $page_title;
        $capability = (string) $this->get_config('admin_page.capability', 'manage_options');
        $position = $this->get_config('admin_page.position', null);

        if ($parent_slug === '' || $menu_slug === '') return;

        if ($position === null || $position === '')
        {
            add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, [$this, 'admin_page']);
            return;
        }

        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, [$this, 'admin_page'], (int) $position);
    }

    /**
     * Render the importer screen with demos, plugins, and status notices.
     */
    /**
     * @return void
     */
    public function admin_page(): void
    {
        if (!current_user_can((string) $this->get_config('admin_page.capability', 'manage_options'))) return;
        $demos = $this->demo();
        echo \Webilia\UI\DemoImport\Screen::render([
            'demos' => $demos,
            'plugins_by_demo' => $this->plugins_by_demo($demos),
            'categories' => $this->page_categories($demos),
            'ajax_url' => $this->ajax_url(),
            'ajax_nonce' => $this->ajax_nonce(),
            'post_url' => admin_url('admin-post.php'),
            'post_action' => 'webilia_wp_demo_start_import',
            'nonce_html' => wp_nonce_field('webilia_wp_demo_start_import', '_wpnonce', true, false),
            'current_page_url' => $this->current_page_url(),
            'visit_url' => $this->visit_url(),
            'guide_url' => $this->guide_url(),
            'page_header_html' => $this->page_header_html(),
            'page_title_html' => $this->page_title_html(),
            'page_intro_html' => $this->page_intro_html(),
            'page_notice' => $this->page_notice(),
            'labels' => \Webilia\WP\ThemeTools::labels(\Webilia\UI\DemoImport\Screen::LABELS, $this->text_domain()),
        ]);
    }

    /**
     * Render optional page header markup supplied by the host integration.
     */
    /**
     * @return string
     */
    public function page_header_html(): string
    {
        $header = $this->apply_wdi_filters('page_header_html', '', [
            'title' => $this->page_header_title($this->page_title_html()),
            'url' => $this->current_page_url(),
        ], $this->get_config());

        return is_string($header) ? trim($header) : '';
    }

    /**
     * Handle the non-AJAX import form submission flow.
     */
    /**
     * @return void
     */
    public function start_import(): void
    {
        if (!is_admin()) wp_die(esc_html__('Invalid request.', $this->text_domain()));

        $capability = (string) $this->get_config('admin_page.capability', 'manage_options');
        if (!current_user_can($capability)) wp_die(esc_html__('You are not allowed to do this action.', $this->text_domain()));

        check_admin_referer('webilia_wp_demo_start_import');

        $demo_key = isset($_POST['demo_key']) ? sanitize_text_field((string) $_POST['demo_key']) : '';
        $demos = $this->demo();
        $selected = [];
        foreach ($demos as $demo)
        {
            if (!is_array($demo)) continue;
            if (($demo['key'] ?? '') !== $demo_key) continue;
            $selected = $demo;
            break;
        }

        $start_import = $this->get_config('callbacks.start_import');
        if (!empty($selected) && is_callable($start_import))
        {
            $ok = (bool) call_user_func($start_import, $selected, $demos);
        }
        else
        {
            $ok = !empty($selected) ? $this->run_native_import($selected, $demos) : false;
        }

        wp_safe_redirect(add_query_arg('webilia_wp_demo_notice', $ok ? 'completed' : 'failed', $this->current_page_url()));
        exit;
    }

    /**
     * Import demo content, widgets, and wsp data through the AJAX flow.
     */
    /**
     * @return void
     */
    public function ajax_import_demo_data(): void
    {
        $this->verify_ajax_call();

        $selected_index = isset($_POST['selected']) ? absint($_POST['selected']) : 0;
        $demos = $this->demo();
        $selected = isset($demos[$selected_index]) && is_array($demos[$selected_index]) ? $demos[$selected_index] : [];

        if (!$selected)
        {
            wp_send_json(['error' => esc_html__('Selected demo is invalid.', $this->text_domain())], 400);
        }

        $files = $this->prepare_import_files($selected);
        if (is_wp_error($files))
        {
            wp_send_json(['error' => $files->get_error_message()], 500);
        }

        $this->set_import_data([
            'selected' => $selected,
            'demos' => $demos,
            'files' => $files,
        ]);

        $this->before_content_import($selected);

        $content_result = $this->import_content_file((string) ($files['content'] ?? ''));
        if (is_wp_error($content_result))
        {
            $this->clear_import_data();
            wp_send_json(['error' => $content_result->get_error_message()], 500);
        }

        $this->before_widgets_import($selected);
        $widgets_result = $this->import_widgets_file((string) ($files['widgets'] ?? ''));
        if (is_wp_error($widgets_result))
        {
            $this->clear_import_data();
            wp_send_json(['error' => $widgets_result->get_error_message()], 500);
        }

        $wsp_result = $this->import_wsp_data($files['wsp'] ?? []);
        if (is_wp_error($wsp_result))
        {
            $this->clear_import_data();
            wp_send_json(['error' => $wsp_result->get_error_message()], 500);
        }

        if (!empty($files['customizer']))
        {
            wp_send_json(['status' => 'customizerAJAX']);
        }

        wp_send_json(['status' => 'afterAllImportAJAX']);
    }

    /**
     * Import customizer data after the main AJAX import finishes.
     */
    /**
     * @return void
     */
    public function ajax_import_customizer_data(): void
    {
        $this->verify_ajax_call();

        $data = $this->get_import_data();
        $customizer_file = isset($data['files']['customizer']) ? (string) $data['files']['customizer'] : '';
        if ($customizer_file !== '')
        {
            $result = $this->import_customizer_file($customizer_file);
            if (is_wp_error($result))
            {
                $this->clear_import_data();
                wp_send_json(['error' => $result->get_error_message()], 500);
            }
        }

        wp_send_json(['status' => 'afterAllImportAJAX']);
    }

    /**
     * Finalize the AJAX import flow and return the completion message.
     */
    /**
     * @return void
     */
    public function ajax_after_import_data(): void
    {
        $this->verify_ajax_call();

        $data = $this->get_import_data();
        $selected = isset($data['selected']) && is_array($data['selected']) ? $data['selected'] : [];

        if ($selected) $this->after_import($selected);
        $this->clear_import_data();

        $title = esc_html__('Import Complete!', $this->text_domain());
        $subtitle = '<p>' . esc_html__('Congrats, your demo was imported successfully. You can now begin editing your site.',
            $this->text_domain()) . '</p>';
        $message = '<p>' . esc_html__('Demo data imported successfully.', $this->text_domain()) . '</p>';

        wp_send_json([
            'title' => $title,
            'subtitle' => $subtitle,
            'message' => $message,
        ]);
    }

    /**
     * Install or activate a plugin required by the selected demo.
     */
    /**
     * @return void
     */
    public function ajax_install_plugin(): void
    {
        $this->verify_ajax_call();

        $selected_index = isset($_POST['selected']) ? absint($_POST['selected']) : 0;
        $slug = isset($_POST['slug']) ? sanitize_key((string) $_POST['slug']) : '';

        if ($slug === '')
        {
            wp_send_json(['error' => esc_html__('Plugin slug is invalid.', $this->text_domain())], 400);
        }

        $demos = $this->demo();
        $plugins = $this->plugins_for_demo_index($selected_index, $demos);

        $target = null;
        foreach ($plugins as $plugin)
        {
            if (!is_array($plugin)) continue;
            if (($plugin['slug'] ?? '') !== $slug) continue;
            $target = $plugin;
            break;
        }

        if (!$target)
        {
            wp_send_json(['error' => esc_html__('Plugin is not available for selected demo.', $this->text_domain())], 404);
        }

        $result = $this->install_or_activate_plugin($target);
        if (is_wp_error($result))
        {
            $status = in_array($result->get_error_code(), [
                'webilia_wp_demo_plugin_activation_forbidden',
                'webilia_wp_demo_plugin_install_forbidden',
            ], true) ? 403 : 500;

            wp_send_json(['error' => $result->get_error_message()], $status);
        }

        wp_send_json([
            'slug' => $slug,
            'status' => $this->plugin_status_by_slug($slug),
            'message' => esc_html__('Plugin is ready.', $this->text_domain()),
        ]);
    }

    /**
     * Run the full import process without AJAX and report success as a boolean.
     */
    /**
     * @param array<mixed> $selected
     * @param array<mixed> $demos
     * @return bool
     */
    protected function run_native_import(array $selected, array $demos): bool
    {
        $files = $this->prepare_import_files($selected);
        if (is_wp_error($files)) return false;

        $this->before_content_import($selected);
        if (is_wp_error($this->import_content_file((string) ($files['content'] ?? ''))))
        {
            $this->cleanup_import_files($files);
            return false;
        }

        $this->before_widgets_import($selected);
        if (is_wp_error($this->import_widgets_file((string) ($files['widgets'] ?? ''))))
        {
            $this->cleanup_import_files($files);
            return false;
        }

        if (is_wp_error($this->import_wsp_data($files['wsp'] ?? [])))
        {
            $this->cleanup_import_files($files);
            return false;
        }

        if (is_wp_error($this->import_customizer_file((string) ($files['customizer'] ?? ''))))
        {
            $this->cleanup_import_files($files);
            return false;
        }

        $this->after_import($selected);
        $this->cleanup_import_files($files);

        return true;
    }

    /**
     * Verify AJAX nonce and current user capability before continuing.
     */
    /**
     * @return void
     */
    protected function verify_ajax_call(): void
    {
        check_ajax_referer('webilia-wp-demo-ajax-verification', 'security');

        $capability = (string) $this->get_config('admin_page.capability', 'manage_options');
        if (!current_user_can($capability))
        {
            wp_send_json(['error' => esc_html__('You are not allowed to do this action.', $this->text_domain())], 403);
        }
    }

    /**
     * Build the transient key used to persist import state per user.
     */
    /**
     * @return string
     */
    protected function import_data_transient_key(): string
    {
        return 'webilia_wp_demo_import_data_' . get_current_user_id();
    }

    /**
     * Store import state between AJAX steps.
     */
    /**
     * @param array<mixed> $data
     * @return void
     */
    protected function set_import_data(array $data): void
    {
        set_transient($this->import_data_transient_key(), $data, HOUR_IN_SECONDS);
    }

    /**
     * Retrieve the saved import state for the current user.
     */
    /**
     * @return array<mixed>
     */
    protected function get_import_data(): array
    {
        $data = get_transient($this->import_data_transient_key());
        return is_array($data) ? $data : [];
    }

    /**
     * Remove saved import state and any downloaded temp files.
     */
    /**
     * @return void
     */
    protected function clear_import_data(): void
    {
        $data = $this->get_import_data();
        if (isset($data['files']) && is_array($data['files'])) $this->cleanup_import_files($data['files']);
        delete_transient($this->import_data_transient_key());
    }

    /**
     * Download or resolve all files needed for a demo import.
     */
    /**
     * @param array<mixed> $selected
     * @return mixed
     */
    protected function prepare_import_files(array $selected)
    {
        $this->temporary_files = [];
        $content = $this->download_import_file_from_demo($selected, 'import_file_url', 'local_import_file');
        if (is_wp_error($content))
        {
            $this->cleanup_import_files(['temporary' => $this->temporary_files]);
            return $content;
        }

        $widgets = $this->download_import_file_from_demo($selected, 'import_widget_file_url', 'local_import_widget_file');
        if (is_wp_error($widgets))
        {
            $this->cleanup_import_files(['temporary' => $this->temporary_files]);
            return $widgets;
        }

        $customizer = $this->download_import_file_from_demo($selected, 'import_customizer_file_url', 'local_import_customizer_file');
        if (is_wp_error($customizer))
        {
            $this->cleanup_import_files(['temporary' => $this->temporary_files]);
            return $customizer;
        }

        $wsp_items = [];
        $selected_wsp_items = [];

        if (!empty($selected['import_wsp']) && is_array($selected['import_wsp']))
        {
            $selected_wsp_items = $selected['import_wsp'];
        }
        else if (!empty($selected['import_redux']) && is_array($selected['import_redux']))
        {
            $selected_wsp_items = $selected['import_redux'];
        }

        if ($selected_wsp_items)
        {
            foreach ($selected_wsp_items as $wsp_item)
            {
                if (!is_array($wsp_item)) continue;

                $option_name = isset($wsp_item['option_name']) ? (string) $wsp_item['option_name'] : '';
                $file_url = isset($wsp_item['file_url']) ? (string) $wsp_item['file_url'] : '';
                if ($option_name === '' || $file_url === '') continue;

                $file_path = $this->download_remote_file($file_url);
                if (is_wp_error($file_path))
        {
            $this->cleanup_import_files(['temporary' => $this->temporary_files]);
            return $file_path;
        }

                $wsp_items[] = [
                    'option_name' => $option_name,
                    'file_path' => $file_path,
                ];
            }
        }

        if ($content === '')
        {
            $this->cleanup_import_files(['temporary' => $this->temporary_files]);
            return new \WP_Error('webilia_wp_demo_missing_content', esc_html__('No content import file was provided for this demo.',
                $this->text_domain()));
        }

        return [
            'temporary' => $this->temporary_files,
            'content' => $content,
            'widgets' => $widgets,
            'customizer' => $customizer,
            'wsp' => $wsp_items,
        ];
    }

    /**
     * Resolve a demo import file from a remote URL or local fallback path.
     */
    /**
     * @param array<mixed> $demo
     * @param string $remote_key
     * @param string $local_key
     * @return mixed
     */
    protected function download_import_file_from_demo(array $demo, string $remote_key, string $local_key = '')
    {
        $remote = isset($demo[$remote_key]) ? (string) $demo[$remote_key] : '';
        if ($remote !== '')
        {
            return $this->download_remote_file($remote);
        }

        if ($local_key !== '')
        {
            $local = isset($demo[$local_key]) ? (string) $demo[$local_key] : '';
            if ($local !== '' && file_exists($local)) return $local;
        }

        return '';
    }

    /**
     * Download a remote import file to a temporary local path.
     */
    /**
     * @param string $url
     * @return mixed
     */
    protected function download_remote_file(string $url)
    {
        if (!wp_http_validate_url($url))
        {
            return new \WP_Error('webilia_wp_demo_invalid_url', esc_html__('Invalid remote import file URL.', $this->text_domain()));
        }

        if (!function_exists('download_url')) require_once ABSPATH . 'wp-admin/includes/file.php';
        $path = download_url($url, 120);

        if (is_wp_error($path)) return $path;
        $this->temporary_files[] = $path;
        return $path;
    }

    /**
     * Delete temporary files created during the import process.
     */
    /**
     * @param array<mixed> $files
     * @return void
     */
    protected function cleanup_import_files(array $files): void
    {
        // Only paths returned by download_url are owned by this import. Never delete a
        // configured local source file, even when it happens to live in the temp directory.
        foreach ((array) ($files['temporary'] ?? []) as $path)
        {
            if (is_string($path) && is_file($path)) unlink($path);
        }
    }

    /**
     * Import WXR content data and attachments from the given file.
     */
    /**
     * @param string $path
     * @return mixed
     */
    protected function import_content_file(string $path)
    {
        if ($path === '') return null;
        if (!file_exists($path))
        {
            return new \WP_Error('webilia_wp_demo_content_missing', esc_html__('Content file was not found.', $this->text_domain()));
        }

        $this->include_importer_dependencies();

        $logger = new \Webilia\WP\DemoImport\Importer\WPImporterLogger();
        $importer = new \Webilia\WP\DemoImport\Importer\WXRImporter([
            'fetch_attachments' => true,
            'default_author' => get_current_user_id(),
        ]);
        $importer->set_logger($logger);

        add_filter('wxr_importer.pre_process.user', '__return_false');

        $result = $importer->import($path);
        if (is_wp_error($result)) return $result;

        return null;
    }

    /**
     * Load the importer classes required for WXR content imports.
     */
    /**
     * @return void
     */
    protected function include_importer_dependencies(): void
    {
        if (!class_exists('\WP_Importer')) require_once ABSPATH . 'wp-admin/includes/class-wp-importer.php';
    }

    /**
     * Import widget instances and assign them to sidebars.
     */
    /**
     * @param string $path
     * @return mixed
     */
    protected function import_widgets_file(string $path)
    {
        if ($path === '') return null;
        if (!file_exists($path))
        {
            return new \WP_Error('webilia_wp_demo_widgets_missing', esc_html__('Widgets file was not found.', $this->text_domain()));
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '')
        {
            return new \WP_Error('webilia_wp_demo_widgets_invalid', esc_html__('Widgets data file is empty.', $this->text_domain()));
        }

        $data = json_decode($raw);
        if (!is_object($data))
        {
            return new \WP_Error('webilia_wp_demo_widgets_invalid', esc_html__('Widgets file format is invalid.', $this->text_domain()));
        }

        global $wp_registered_sidebars;
        $available_widgets = $this->available_widgets();

        foreach ($data as $sidebar_id => $widgets)
        {
            if ($sidebar_id === 'wp_inactive_widgets') continue;
            $use_sidebar_id = isset($wp_registered_sidebars[$sidebar_id]) ? $sidebar_id : 'wp_inactive_widgets';
            if (!is_object($widgets)) continue;

            foreach ($widgets as $widget_instance_id => $widget)
            {
                $id_base = preg_replace('/-[0-9]+$/', '', (string) $widget_instance_id);
                if ($id_base === '' || !isset($available_widgets[$id_base])) continue;

                $widget_array = json_decode(json_encode($widget), true);
                if (!is_array($widget_array)) continue;

                $single_widget_instances = get_option('widget_' . $id_base);
                if (!is_array($single_widget_instances)) $single_widget_instances = ['_multiwidget' => 1];

                $single_widget_instances[] = $widget_array;
                end($single_widget_instances);
                $new_instance_id_number = key($single_widget_instances);
                if ($new_instance_id_number === 0 || $new_instance_id_number === '0')
                {
                    $new_instance_id_number = 1;
                    $single_widget_instances[$new_instance_id_number] = $single_widget_instances[0];
                    unset($single_widget_instances[0]);
                }

                if (isset($single_widget_instances['_multiwidget']))
                {
                    $multiwidget = $single_widget_instances['_multiwidget'];
                    unset($single_widget_instances['_multiwidget']);
                    $single_widget_instances['_multiwidget'] = $multiwidget;
                }

                update_option('widget_' . $id_base, $single_widget_instances);

                $sidebars_widgets = get_option('sidebars_widgets');
                if (!is_array($sidebars_widgets)) $sidebars_widgets = [];

                $new_instance_id = $id_base . '-' . $new_instance_id_number;
                if (!isset($sidebars_widgets[$use_sidebar_id]) || !is_array($sidebars_widgets[$use_sidebar_id]))
                {
                    $sidebars_widgets[$use_sidebar_id] = [];
                }

                $sidebars_widgets[$use_sidebar_id][] = $new_instance_id;
                update_option('sidebars_widgets', $sidebars_widgets);
            }
        }

        return null;
    }

    /**
     * Collect the registered widget types that can accept imported instances.
     */
    /**
     * @return array<mixed>
     */
    protected function available_widgets(): array
    {
        global $wp_registered_widget_controls;
        $available_widgets = [];
        if (!is_array($wp_registered_widget_controls)) return $available_widgets;

        foreach ($wp_registered_widget_controls as $widget)
        {
            if (empty($widget['id_base'])) continue;
            $id_base = (string) $widget['id_base'];
            if (isset($available_widgets[$id_base])) continue;

            $available_widgets[$id_base] = [
                'id_base' => $id_base,
                'name' => isset($widget['name']) ? (string) $widget['name'] : $id_base,
            ];
        }

        return $available_widgets;
    }

    /**
     * Import WSP option sets from downloaded JSON files.
     */
    /**
     * @param array<mixed> $wsp_items
     * @return mixed
     */
    protected function import_wsp_data(array $wsp_items)
    {
        if (!$wsp_items) return null;

        foreach ($wsp_items as $item)
        {
            if (!is_array($item)) continue;

            $option_name = isset($item['option_name']) ? (string) $item['option_name'] : '';
            $file_path = isset($item['file_path']) ? (string) $item['file_path'] : '';
            if ($option_name === '' || $file_path === '' || !file_exists($file_path)) continue;

            $raw = file_get_contents($file_path);
            if (!is_string($raw) || trim($raw) === '') continue;

            $options = json_decode($raw, true);
            if (!is_array($options)) continue;

            update_option($option_name, $options);
        }

        return null;
    }

    /**
     * Import customizer mods and related options from an export file.
     */
    /**
     * @param string $path
     * @return mixed
     */
    protected function import_customizer_file(string $path)
    {
        if ($path === '') return null;
        if (!file_exists($path))
        {
            return new \WP_Error('webilia_wp_demo_customizer_missing', esc_html__('Customizer file was not found.', $this->text_domain()));
        }

        $raw = file_get_contents($path);
        if (!is_string($raw) || trim($raw) === '')
        {
            return new \WP_Error('webilia_wp_demo_customizer_invalid', esc_html__('Customizer data file is empty.', $this->text_domain()));
        }

        $data = @unserialize($raw, ['allowed_classes' => false]);
        if (!is_array($data) || !isset($data['template']) || !isset($data['mods']) || !is_array($data['mods']))
        {
            return new \WP_Error('webilia_wp_demo_customizer_invalid', esc_html__('Customizer data format is invalid.', $this->text_domain()));
        }

        if ((string) $data['template'] !== get_template())
        {
            return new \WP_Error('webilia_wp_demo_customizer_theme_mismatch', esc_html__('Customizer file does not match the active theme.',
                $this->text_domain()));
        }

        foreach ($data['mods'] as $key => $val)
        {
            if (!is_string($key) || $key === '') continue;
            set_theme_mod($key, $val);
        }

        if (!empty($data['options']) && is_array($data['options']))
        {
            foreach ($data['options'] as $option_key => $option_value)
            {
                if (!is_string($option_key) || $option_key === '') continue;
                update_option($option_key, $option_value);
            }
        }

        return null;
    }

    /**
     * Enqueue configured assets only on the importer admin page.
     */
    /**
     * @return void
     */
    public function assets(): void
    {
        if (!$this->is_demo_import_page() || !current_user_can((string) $this->get_config('admin_page.capability', 'manage_options'))) return;
        \Webilia\UI\WordPress\Assets::enqueue(['demo-import'], (string) $this->get_config('ui_url', ''));
        wp_enqueue_style('webilia-wp-demo-import', rtrim($this->base_url, '/') . '/assets/css/demo-import.css',
            ['webilia-ui-v1-demo-import'], \Webilia\WP\Version::VALUE);
        $handle = 'webilia-wp-demo-import';
        wp_enqueue_script($handle, rtrim($this->base_url, '/') . '/assets/js/demo-import.js', ['jquery', 'webilia-ui-v1-demo-import'],
            \Webilia\WP\Version::VALUE, true);
        wp_localize_script($handle, 'webiliaWPImportLabels', $this->js_translations());
        foreach (['styles' => 'wp_enqueue_style', 'scripts' => 'wp_enqueue_script'] as $key => $enqueue)
        {
            foreach ((array) $this->get_config('assets.' . $key, []) as $item)
            {
                if (empty($item['handle']) || empty($item['src'])) continue;
                $src = preg_match('/^https?:\/\//i', $item['src']) ? $item['src'] : rtrim($this->base_url, '/') . '/assets/' .
                    ltrim($item['src'], '/');
                $enqueue($item['handle'], $src, $item['deps'] ?? [], $item['ver'] ?? \Webilia\WP\Version::VALUE,
                    $key === 'scripts' ? ($item['in_footer'] ?? true) : ($item['media'] ?? 'all'));
            }
        }
    }

    /**
     * Fire callbacks and actions before widget import starts.
     */
    /**
     * @param array<mixed> $selected
     * @return void
     */
    protected function before_widgets_import(array $selected): void
    {
        $this->callback('before_widgets_import', [$selected], null);
        $this->do_wdi_action('before_widgets_import', $selected, $this->get_config());
    }

    /**
     * Fire callbacks and actions before content import starts.
     */
    /**
     * @param array<mixed> $selected
     * @return void
     */
    protected function before_content_import(array $selected): void
    {
        $this->callback('before_content_import', [$selected], null);
        $this->do_wdi_action('before_content_import', $selected, $this->get_config());
    }

    /**
     * Fire callbacks and actions after the full import completes.
     */
    /**
     * @param array<mixed> $selected
     * @return void
     */
    protected function after_import(array $selected): void
    {
        $this->callback('after_import', [$selected], null);
        $this->do_wdi_action('after_import', $selected, $this->get_config());
    }

    /**
     * Check whether the current admin request targets the importer page.
     */
    /**
     * @return bool
     */
    protected function is_demo_import_page(): bool
    {
        if (!is_admin()) return false;

        $menu_slug = (string) $this->get_config('page_setup.menu_slug', '');
        if ($menu_slug === '') return false;

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';
        if ($page === '') return false;

        return $page === sanitize_key($menu_slug);
    }

    /**
     * Build the admin URL for the importer page.
     */
    /**
     * @return string
     */
    protected function current_page_url(): string
    {
        $slug = sanitize_key((string) $this->get_config('page_setup.menu_slug', ''));
        if ($slug === '') return admin_url();

        return admin_url('admin.php?page=' . $slug);
    }

    /**
     * @param mixed $classes
     * @return string
     */
    public function admin_body_class($classes): string
    {
        $classes = is_string($classes) ? $classes : '';
        if (!$this->is_demo_import_page()) return $classes;

        $class = ' webilia-wp-demo-import-page';
        if (strpos($classes, trim($class)) !== false) return $classes;

        return $classes . $class;
    }

    /**
     * @return string
     */
    protected function ajax_url(): string
    {
        return admin_url('admin-ajax.php');
    }

    /**
     * @return string
     */
    protected function ajax_nonce(): string
    {
        return wp_create_nonce('webilia-wp-demo-ajax-verification');
    }

    /**
     * @return string
     */
    protected function visit_url(): string
    {
        return home_url('/');
    }

    /**
     * @return string
     */
    protected function guide_url(): string
    {
        return (string) $this->get_config('success_buttons.first_button.href', $this->get_config('title.help_url', ''));
    }

    /**
     * @return string
     */
    protected function page_notice(): string
    {
        static $notice = null;
        if (is_string($notice)) return $notice;

        $notice = isset($_GET['webilia_wp_demo_notice']) ? sanitize_text_field((string) $_GET['webilia_wp_demo_notice']) : '';
        if ($notice === 'started') $notice = esc_html__('Demo import has been started.', $this->text_domain());
        else if ($notice === 'failed') $notice = esc_html__('Demo import could not be started.', $this->text_domain());
        else if ($notice === 'completed') $notice = esc_html__('Demo import completed successfully.', $this->text_domain());
        else $notice = '';

        return $notice;
    }

    /**
     * @param array<mixed> $plugins_by_demo
     * @return string
     */
    protected function page_plugins_by_demo_json(array $plugins_by_demo): string
    {
        $json = wp_json_encode($plugins_by_demo);
        return is_string($json) ? $json : '{}';
    }

    /**
     * @param array<mixed> $demos
     * @return array<mixed>
     */
    protected function page_categories(array $demos): array
    {
        $categories = [];

        foreach ($demos as $demo)
        {
            if (!is_array($demo) || empty($demo['categories']) || !is_array($demo['categories'])) continue;

            foreach ($demo['categories'] as $category)
            {
                $label = is_scalar($category) ? (string) $category : '';
                if ($label === '') continue;

                $categories[sanitize_key($label)] = $label;
            }
        }

        return $categories;
    }

    /**
     * @param string $title_html
     * @return string
     */
    protected function page_header_title(string $title_html): string
    {
        $title = trim(wp_strip_all_tags($title_html));
        return $title !== '' ? $title : $this->default_demo_importer_label();
    }

    /**
     * @return string
     */
    protected function page_title_html(): string
    {
        static $title_html = null;
        if (is_string($title_html)) return $title_html;

        $title_html = $this->content('title', '');
        return $title_html;
    }

    /**
     * @return string
     */
    protected function page_intro_html(): string
    {
        static $intro_html = null;
        if (is_string($intro_html)) return $intro_html;

        $intro_html = $this->content('intro', '');
        return $intro_html;
    }

    /**
     * @param string $text
     * @param string $fallback
     * @return string
     */
    protected function intro_text(string $text = '', string $fallback = ''): string
    {
        $intro = trim($text);
        return $intro !== '' ? $intro : trim($fallback);
    }

    /**
     * Resolve a configured content block from template, HTML, or plain text.
     */
    /**
     * @param string $group
     * @param mixed $fallback
     * @return string
     */
    protected function content(string $group, $fallback): string
    {
        $template = $this->get_config($group . '.template', '');
        if (is_string($template) && trim($template) !== '')
        {
            $template_output = $this->template_content($group, $template, $fallback);
            if ($template_output !== '') return $this->filter_content_output($group, $template_output, $fallback);
        }

        $html = $this->get_config($group . '.html', '');
        if (is_callable($html)) $html = call_user_func($html, $fallback, $this->get_config());

        if (is_string($html) && trim($html) !== '') return $this->filter_content_output($group, $html, $fallback);

        $text = $this->get_config($group . '.text', '');
        if (is_callable($text)) $text = call_user_func($text, $fallback, $this->get_config());

        if (!is_string($text) || trim($text) === '') return '';

        if ($group === 'intro')
        {
            return $this->filter_content_output($group, '<div class="wui-di__intro-text"><p class="about-description">' . esc_html($text) .
                '</p></div>', $fallback);
        }

        return $this->filter_content_output($group, esc_html($text), $fallback);
    }

    /**
     * Apply content-specific filters and keep the original string when needed.
     */
    /**
     * @param string $group
     * @param string $content
     * @param mixed $fallback
     * @return string
     */
    protected function filter_content_output(string $group, string $content, $fallback): string
    {
        $filtered = $this->apply_wdi_filters($group . '_content', $content, $fallback, $this->get_config());
        return is_string($filtered) ? $filtered : $content;
    }

    /**
     * Render a configured HTML template for a content block.
     */
    /**
     * @param string $group
     * @param string $template
     * @param mixed $fallback
     * @return string
     */
    protected function template_content(string $group, string $template, $fallback): string
    {
        $text = $this->get_config($group . '.text', '');
        if (is_callable($text)) $text = call_user_func($text, $fallback, $this->get_config());
        if (!is_string($text)) $text = '';

        $logo_url = $this->config_string($this->get_config('branding.logo_url', ''), '');

        return $this->include_html_file($template, [
            'parameters' => [
                'text' => $text,
                'fallback' => is_string($fallback) ? $fallback : '',
                'logo_url' => $logo_url,
                'text_domain' => $this->text_domain(),
            ],
            'return_output' => true,
        ]);
    }

    /**
     * @return string
     */
    protected function default_demo_import_label(): string
    {
        return esc_html__('Demo Import', $this->text_domain());
    }

    /**
     * @return string
     */
    protected function default_demo_importer_label(): string
    {
        return esc_html__('Demo Importer', $this->text_domain());
    }

    /**
     * @return array<mixed>
     */
    protected function js_translations(): array
    {
        return [
            'status_active' => esc_html__('Active', $this->text_domain()),
            'status_installed' => esc_html__('Installed', $this->text_domain()),
            'status_missing' => esc_html__('Not installed', $this->text_domain()),
            'status_unknown' => esc_html__('Unknown', $this->text_domain()),
            'plugin_fallback_name' => esc_html__('Plugin', $this->text_domain()),
            'plugin_type_required' => esc_html__('Required', $this->text_domain()),
            'plugin_type_recommended' => esc_html__('Recommended', $this->text_domain()),
            'stage_waiting' => esc_html__('Waiting', $this->text_domain()),
            'stage_installing' => esc_html__('Installing', $this->text_domain()),
            'stage_importing_content' => esc_html__('Importing Content', $this->text_domain()),
            'stage_applying_customizer' => esc_html__('Applying Customizer', $this->text_domain()),
            'stage_finalizing' => esc_html__('Finalizing', $this->text_domain()),
            'stage_failed' => esc_html__('Failed', $this->text_domain()),
            'stage_completed' => esc_html__('Completed', $this->text_domain()),
            'done_title_default' => esc_html__('Import completed', $this->text_domain()),
            'done_title_demo' => esc_html__('%s import completed', $this->text_domain()),
            'done_title_finished' => esc_html__('Import finished', $this->text_domain()),
            'done_message_default' => esc_html__('Your demo import has been completed.', $this->text_domain()),
            'done_message_success' => esc_html__('Demo import completed successfully.', $this->text_domain()),
            'log_no_plugins_required' => esc_html__('No plugin installation is required for this demo.', $this->text_domain()),
            'log_installing_plugin' => esc_html__('Installing plugin: %1$s (%2$d/%3$d)', $this->text_domain()),
            'log_plugin_ready' => esc_html__('Plugin ready: %s', $this->text_domain()),
            'log_processing_content_batch' => esc_html__('Processing content batch %d.', $this->text_domain()),
            'log_applying_customizer' => esc_html__('Applying customizer data.', $this->text_domain()),
            'log_running_post_import' => esc_html__('Running post-import tasks.', $this->text_domain()),
            'log_plugin_failed' => esc_html__('Plugin installation failed: %s', $this->text_domain()),
            'log_content_failed' => esc_html__('Content import failed: %s', $this->text_domain()),
            'log_content_success' => esc_html__('Content import completed successfully.', $this->text_domain()),
            'log_import_started' => esc_html__('Import started for "%s".', $this->text_domain()),
            'log_plugin_step_completed' => esc_html__('Plugin installation step completed.', $this->text_domain()),
            'log_content_started' => esc_html__('Starting content import for "%s".', $this->text_domain()),
            'error_plugin_installation_failed' => esc_html__('Plugin installation failed.', $this->text_domain()),
            'error_import_failed' => esc_html__('Import failed.', $this->text_domain()),
            'error_unknown_server' => esc_html__('Unknown server error.', $this->text_domain()),
        ];
    }

    /**
     * Build the plugin list keyed by demo index for the admin UI.
     */
    /**
     * @param array<mixed> $demos
     * @return array<mixed>
     */
    protected function plugins_by_demo(array $demos): array
    {
        $plugins_by_demo = [];

        foreach ($demos as $index => $demo)
        {
            $plugins_by_demo[(string) $index] = $this->plugins_for_demo_index((int) $index, $demos);
        }

        return $plugins_by_demo;
    }

    /**
     * Resolve and normalize plugins for one demo entry.
     */
    /**
     * @param int $index
     * @param array<mixed> $demos
     * @return array<mixed>
     */
    protected function plugins_for_demo_index(int $index, array $demos): array
    {
        $demo = isset($demos[$index]) && is_array($demos[$index]) ? $demos[$index] : [];
        if (!$demo) return [];

        $use_specific_callback = is_callable($this->get_config('callbacks.plugins_for_demo', null));
        if ($use_specific_callback)
        {
            $plugins = $this->callback('plugins_for_demo', [$demo, $index, $demos], []);
        }
        else
        {
            $plugins = $this->plugin();
        }

        $plugins = $this->apply_wdi_filters('plugins_for_demo', $plugins, $demo, $index, $demos, $this->get_config());
        return $this->normalize_plugins($plugins);
    }

    /**
     * Normalize plugin definitions for display and installation.
     */
    /**
     * @param mixed $plugins
     * @return array<mixed>
     */
    protected function normalize_plugins($plugins): array
    {
        if (!is_array($plugins)) return [];

        $normalized = [];
        foreach ($plugins as $plugin)
        {
            if (!is_array($plugin)) continue;

            $slug = isset($plugin['slug']) ? (string) $plugin['slug'] : '';
            $name = isset($plugin['name']) ? (string) $plugin['name'] : $slug;
            if ($name === '') continue;

            $normalized[] = [
                'name' => $name,
                'slug' => $slug,
                'required' => !empty($plugin['required']),
                'preselected' => array_key_exists('preselected', $plugin) ? !empty($plugin['preselected']) : !empty($plugin['required']),
                'source' => isset($plugin['source']) ? (string) $plugin['source'] : '',
                'status' => $this->plugin_status_by_slug($slug),
            ];
        }

        return $normalized;
    }

    /**
     * Determine whether a plugin slug is missing, installed, or active.
     */
    /**
     * @param string $slug
     * @return string
     */
    protected function plugin_status_by_slug(string $slug): string
    {
        if ($slug === '') return 'unknown';

        if (!function_exists('is_plugin_active')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';

        $plugins = get_plugins();
        $installed = false;

        foreach ($plugins as $plugin_file => $plugin_data)
        {
            if (strpos((string) $plugin_file, $slug . '/') !== 0) continue;
            $installed = true;
            if (is_plugin_active($plugin_file)) return 'active';
        }

        return $installed ? 'installed' : 'missing';
    }

    /**
     * Find the installed plugin entry file for a slug.
     */
    /**
     * @param string $slug
     * @return string
     */
    protected function plugin_file_by_slug(string $slug): string
    {
        if ($slug === '') return '';

        if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugins = get_plugins();

        foreach ($plugins as $plugin_file => $plugin_data)
        {
            if (strpos((string) $plugin_file, $slug . '/') !== 0) continue;
            return (string) $plugin_file;
        }

        return '';
    }

    /**
     * Activate an installed plugin or install it first when needed.
     */
    /**
     * @param array<mixed> $plugin
     * @return mixed
     */
    protected function install_or_activate_plugin(array $plugin)
    {
        $slug = isset($plugin['slug']) ? sanitize_key((string) $plugin['slug']) : '';
        if ($slug === '')
        {
            return new \WP_Error('webilia_wp_demo_plugin_slug_invalid', esc_html__('Plugin slug is invalid.', $this->text_domain()));
        }

        if (!function_exists('is_plugin_active')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugin_file = $this->plugin_file_by_slug($slug);

        if ($plugin_file !== '' && is_plugin_active($plugin_file)) return true;

        if (!current_user_can('activate_plugins'))
        {
            return new \WP_Error('webilia_wp_demo_plugin_activation_forbidden', esc_html__('You are not allowed to activate plugins.',
                $this->text_domain()));
        }

        if ($plugin_file !== '')
        {
            $activation = activate_plugin($plugin_file);
            if (is_wp_error($activation)) return $activation;
            return true;
        }

        if (!current_user_can('install_plugins'))
        {
            return new \WP_Error('webilia_wp_demo_plugin_install_forbidden', esc_html__('You are not allowed to install plugins.',
                $this->text_domain()));
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $source = isset($plugin['source']) ? (string) $plugin['source'] : '';
        if ($source === '')
        {
            $api = plugins_api('plugin_information', ['slug' => $slug, 'fields' => ['sections' => false]]);
            if (is_wp_error($api))
            {
                return $api;
            }

            $source = isset($api->download_link) ? (string) $api->download_link : '';
        }

        if ($source === '')
        {
            return new \WP_Error('webilia_wp_demo_plugin_source_missing', esc_html__('Plugin source is missing.', $this->text_domain()));
        }

        $skin = new \Automatic_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader($skin);
        $installed = $upgrader->install($source);
        if (is_wp_error($installed))
        {
            return $installed;
        }

        if ($installed !== true)
        {
            return new \WP_Error('webilia_wp_demo_plugin_install_failed', esc_html__('Plugin installation failed.', $this->text_domain()));
        }

        $plugin_file = $this->plugin_file_by_slug($slug);
        if ($plugin_file === '')
        {
            return new \WP_Error('webilia_wp_demo_plugin_file_missing', esc_html__('Installed plugin entry point was not found.',
                $this->text_domain()));
        }

        $activation = activate_plugin($plugin_file);
        if (is_wp_error($activation)) return $activation;

        return true;
    }
}
