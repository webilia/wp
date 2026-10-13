<?php

namespace Webilia\WP\Plugin;

/** Feedback adapter usable even if another plugin loaded the legacy base class first. */
class UIFeedback extends Feedback
{
    use FeedbackUI;

    /** @var array<string, mixed> */
    protected $ui_config = [];

    /**
     * @param array<string, mixed> $args
     * @return void
     */
    public function __construct(array $args)
    {
        $this->ui_config = $args;
        $args['ui'] = true;
        parent::__construct($args);
    }

    /**
     * @return void
     */
    public function init()
    {
        FeedbackRegistry::register($this);
        add_action('current_screen', function () {
            $screen = get_current_screen();
            if (!$screen || !in_array($screen->id, ['plugins', 'plugins-network'], true)) return;
            add_action('admin_enqueue_scripts', [$this, 'enqueue']);
            add_action('admin_footer', [$this, 'dialog']);
        });
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function reasons(): array
    {
        return $this->ui_config['reasons'] ?? [];
    }

    /**
     * @return void
     */
    public function dialog()
    {
        $this->uiDialog();
    }

    /**
     * @return void
     */
    public function save()
    {
        $this->saveShared();
    }
}
