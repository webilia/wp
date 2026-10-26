<?php
namespace Webilia\WP\DemoImport;

class Base
{
    /** @var array<string, mixed> */
    protected $config = [
        'ui_url' => '',
        'text_domain' => 'webilia-demo-import',
        'admin_page' => [
            'enabled' => false,
            'parent_slug' => '',
            'capability' => 'manage_options',
            'menu_title' => 'Demo Import',
            'position' => null,
        ],
        'assets' => [
            'enabled' => true,
            'base_url' => '',
            'styles' => [],
            'scripts' => [],
            'enqueue_hook' => 'admin_enqueue_scripts',
        ],
        'page_setup' => [
            'parent_slug' => 'admin.php',
            'page_title' => 'Demo Import',
            'menu_title' => 'Demo Import',
            'menu_slug' => 'webilia-demo-import',
        ],
        'menu' => [
            'enabled' => true,
        ],
        'intro' => [
            'enabled' => true,
            'template' => 'intro.php',
            'html' => '',
            'text' => '',
        ],
        'title' => [
            'enabled' => true,
            'template' => 'title.php',
            'html' => '',
            'text' => 'Demo Importer',
        ],
        'branding' => [
            'logo_url' => '',
        ],
        'success_buttons' => [
            'enabled' => true,
            'first_button' => [
                'href' => '',
                'target' => '',
            ],
        ],
        'callbacks' => [
            'demos' => null,
            'plugins' => null,
            // Deprecated: Use callbacks.demos instead.
            'import_files' => null,
            // Deprecated: Use callbacks.plugins instead.
            'register_plugins' => null,
            'plugins_for_demo' => null,
            'before_widgets_import' => null,
            'before_content_import' => null,
            'after_import' => null,
            'start_import' => null,
        ],
    ];

    /**
     * @param array<mixed> $config
     */
    public function __construct(array $config = [])
    {
        if ($config) $this->set_config($config);
    }

    /**
     * @param array<mixed> $config
     * @return self
     */
    public function set_config(array $config): self
    {
        $this->config = $this->merge_config($this->config, $config);
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get_config(string $key = '', $default = null)
    {
        if ($key === '') return $this->config;

        $current = $this->config;
        foreach (explode('.', $key) as $segment)
        {
            if (!is_array($current) || !array_key_exists($segment, $current)) return $default;
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * @param array<mixed> $defaults
     * @param array<mixed> $config
     * @return array<mixed>
     */
    protected function merge_config(array $defaults, array $config): array
    {
        foreach ($config as $key => $value)
        {
            if (isset($defaults[$key]) && is_array($defaults[$key]) && is_array($value))
            {
                $defaults[$key] = $this->merge_config($defaults[$key], $value);
                continue;
            }

            $defaults[$key] = $value;
        }

        return $defaults;
    }

    /**
     * @return string
     */
    protected function text_domain(): string
    {
        $domain = $this->get_config('text_domain', 'webilia-demo-import');
        if (!is_string($domain) || trim($domain) === '') return 'webilia-demo-import';

        return $domain;
    }

    /**
     * @param string $file
     * @return string
     */
    protected function html_file_path(string $file): string
    {
        return __DIR__ . '/templates/' . ltrim($file, '/');
    }

    /**
     * @param string $file
     * @param array<mixed> $args
     * @return string
     */
    public function include_html_file(string $file = '', array $args = []): string
    {
        if (trim($file) === '') return '';

        $path = $this->html_file_path($file);
        if (!is_file($path) && in_array($file, ['intro.php', 'title.php', 'progress-template.php', 'setup-plugin-item-template.php',
            'setup-plugins-empty.php'], true))
        {
            $props = $args['parameters'] ?? [];
            $props['labels'] = \Webilia\WP\ThemeTools::labels(\Webilia\UI\DemoImport\Screen::LABELS, $this->text_domain());
            $output = \Webilia\UI\DemoImport\Screen::render($props, $file);
            if (!empty($args['return_output'])) return $output;
            echo $output;
            return '';
        }

        if (!file_exists($path)) return '';

        if (!empty($args['parameters']) && is_array($args['parameters'])) extract($args['parameters']);

        ob_start();
        if (!empty($args['include_once'])) include_once $path;
        else include $path;
        $output = (string) ob_get_clean();

        if (!empty($args['return_output'])) return $output;

        echo $output;
        return '';
    }
}
