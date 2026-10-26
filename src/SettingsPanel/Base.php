<?php
namespace Webilia\WP\SettingsPanel;

class Base
{
    /** @var string */
    protected $option_name = 'webilia_settings_panel';
    /** @var string */
    protected $text_domain = 'webilia-settings-panel';

    /**
     * @param mixed $override
     * @return mixed
     */
    public function option_name($override = '')
    {
        if (is_string($override) && $override !== '') $this->option_name = $override;
        return $this->option_name;
    }

    /**
     * @param mixed $override
     * @return mixed
     */
    public function text_domain($override = '')
    {
        if (is_string($override) && $override !== '') $this->text_domain = $override;
        return $this->text_domain;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public function get_option($default = [])
    {
        return get_option($this->option_name, $default);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    public function update_option($value)
    {
        return update_option($this->option_name, $value);
    }

    /**
     * @param mixed $file
     * @param mixed $args
     * @return mixed
     */
    public function include_html_file($file = '', $args = [])
    {
        // File is empty
        if (!trim($file)) return esc_html__('HTML file is empty!', $this->text_domain);

        // Core File
        $uiRoot = dirname((new \ReflectionClass(\Webilia\UI\SettingsPanel\Layout::class))->getFileName());
        $default_path = $uiRoot . '/templates/' . ltrim($file, '/');
        $path = $default_path;

        // Apply Filter
        $path = apply_filters('webilia_include_html_file', $path, $file, $args);

        if ($path === $default_path && in_array($file, ['settings.php', 'actions.php'], true))
        {
            if (!empty($args['return_path'])) return $path;
            $renderer = $file === 'settings.php' ? \Webilia\UI\SettingsPanel\Layout::class : \Webilia\UI\SettingsPanel\Actions::class;
            $output = $renderer::render($args['parameters'] ?? []);
            if (!empty($args['return_output'])) return $output;
            echo $output;
            return '';
        }

        // File does not exist
        if (!file_exists($path)) return esc_html__('HTML file does not exist! Check the file path.', $this->text_domain);

        // Return the File Path
        if (isset($args['return_path']) && $args['return_path']) return $path;

        // Parameters passed
        if (isset($args['parameters']) && is_array($args['parameters']) && count($args['parameters'])) extract($args['parameters']);

        // Start buffering
        ob_start();

        // Include Once
        if (isset($args['include_once']) && $args['include_once']) include_once $path;
        else include $path;

        // Get Buffer
        $output = ob_get_clean();

        // Return the file output
        if (isset($args['return_output']) && $args['return_output']) return $output;

        // Print the output
        echo $output;
        return '';
    }

    /**
     * @param array<mixed> $response
     * @return mixed
     */
    public function response(array $response)
    {
        echo wp_json_encode($response, JSON_NUMERIC_CHECK);
        exit;
    }
}
