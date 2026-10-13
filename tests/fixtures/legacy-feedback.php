<?php
namespace Webilia\WP\Plugin;

// phpcs:disable Generic.Files.LineLength

class Feedback
{
    /**
     * Plugin textdomain.
     *
     * @var string
     */
    public $textdomain;

    /**
     * Alert CSS class.
     *
     * @var string
     */
    public $alert_class;

    /**
     * Success CSS class.
     *
     * @var string
     */
    public $success_class;

    /**
     * Error CSS class.
     *
     * @var string
     */
    public $error_class;

    /**
     * Primary button CSS class.
     *
     * @var string
     */
    public $primary_button_class;

    /**
     * Secondary button CSS class.
     *
     * @var string
     */
    public $secondary_button_class;

    /**
     * Text button CSS class.
     *
     * @var string
     */
    public $text_button_class;

    /**
     * Icon CSS class.
     *
     * @var string
     */
    public $icon_class;

    /**
     * Plugin slug.
     *
     * @var string
     */
    public $plugin;

    /**
     * Plugin basename.
     *
     * @var string
     */
    public $basename;

    /**
     * Constructor.
     *
     * @param array<string, string> $args
     */
    public function __construct(array $args)
    {
        $this->plugin = $args['plugin'] ?? '';
        $this->basename = $args['basename'] ?? '';
        $this->textdomain = $args['textdomain'] ?? $this->plugin;
        $this->alert_class = isset($args['alert']) && trim($args['alert']) ? $args['alert'] : 'lsd-alert';
        $this->success_class = isset($args['success']) && trim($args['success']) ? $args['success'] : 'lsd-success';
        $this->error_class = isset($args['error']) && trim($args['error']) ? $args['error'] : 'lsd-error';
        $this->primary_button_class = isset($args['primary_button_class']) && trim($args['primary_button_class']) ? $args['primary_button_class'] : 'lsd-primary-button';
        $this->secondary_button_class = isset($args['secondary_button_class']) && trim($args['secondary_button_class']) ? $args['secondary_button_class'] : 'lsd-secondary-button';
        $this->text_button_class = isset($args['text_button_class']) && trim($args['text_button_class']) ? $args['text_button_class'] : 'lsd-text-button';
        $this->icon_class = isset($args['icon_class']) && trim($args['icon_class']) ? $args['icon_class'] : 'listdom-icon';

        $this->init();
    }

    /**
     * Initialize hooks.
     *
     * @return void
     */
    public function init()
    {
        add_action('current_screen', function ()
        {
            if (!$this->is_plugins_screen()) return;

            // Print Dialog
            add_action('admin_footer', [$this, 'dialog']);
        });

        // Ajax
        add_action('wp_ajax_web_dfd', [$this, 'save']);
    }

    /**
     * Get feedback reasons.
     *
     * @return array<string, array<string, string>>
     */
    public function reasons(): array
    {
        return [];
    }

    public function save() { throw new \RuntimeException('Legacy handler must not intercept shared feedback.'); }

    /**
     * Check to see if we're in plugins menu
     *
     * @return bool
     */
    private function is_plugins_screen(): bool
    {
        return in_array(get_current_screen()->id, ['plugins', 'plugins-network']);
    }
}
