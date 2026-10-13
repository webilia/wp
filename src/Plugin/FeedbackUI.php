<?php

namespace Webilia\WP\Plugin;

use Webilia\WP\Version;

/** Optional presentation/submission adapter shared by Feedback and its compatibility subclass. */
trait FeedbackUI
{
    /**
     * @param string $key
     * @return string
     */
    protected function posted(string $key): string
    {
        return isset($_POST[$key]) && is_string($_POST[$key]) ? wp_unslash($_POST[$key]) : '';
    }

    /**
     * @return void
     */
    public function enqueue(): void
    {
        if (!in_array(get_current_screen()->id, ['plugins', 'plugins-network'], true)
            || !current_user_can('deactivate_plugin', $this->basename)) return;
        \Webilia\UI\WordPress\Assets::enqueue(['deactivation'], $this->ui_config['ui_url']);
        wp_enqueue_script('webilia-wp-v1-deactivation', rtrim($this->ui_config['package_url'], '/') . '/assets/js/deactivation.js',
            ['webilia-ui-v1-deactivation'], Version::VALUE, true);
    }

    /**
     * @return void
     */
    protected function uiDialog(): void
    {
        if (!current_user_can('deactivate_plugin', $this->basename)) return;
        $props = $this->ui_config;
        $props['id'] = 'webilia-feedback-' . md5($this->basename);
        $props['reasons'] = $this->reasons();
        $config = ['basename' => $this->basename, 'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webilia_feedback_' . $this->basename),
            'reasonRequired' => $this->ui_config['reason_required'] ?? __('Please select a reason.', $this->textdomain),
            'error' => $this->ui_config['error'] ?? __('Could not send feedback. You can skip and deactivate.', $this->textdomain)];
        echo '<div data-webilia-feedback="' . esc_attr(wp_json_encode($config)) . '">'
            . \Webilia\UI\DeactivationFeedbackDialog::render($props) . '</div>';
    }

    /**
     * @return void
     */
    protected function saveShared(): void
    {
        if ($this->posted('basename') !== $this->basename || !current_user_can('deactivate_plugin', $this->basename)) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        check_ajax_referer('webilia_feedback_' . $this->basename, 'nonce');
        if ($this->posted('action_type') !== 'submit_feedback') wp_send_json_error(['message' => 'Invalid action'], 400);
        $reason = sanitize_text_field($this->posted('reason_key'));
        $reasons = $this->reasons();
        if (!isset($reasons[$reason])) {
            wp_send_json_error(['message' => $this->ui_config['reason_required'] ?? __('Please select a reason.', $this->textdomain)], 400);
        }

        $details = !empty($reasons[$reason]['placeholder']) ? sanitize_textarea_field($this->posted('details')) : '';
        $details = function_exists('mb_substr') ? mb_substr($details, 0, 4000) : substr($details, 0, 4000);
        $response = wp_remote_post('https://api.webilia.com/deactivation-feedback', ['timeout' => 10, 'body' => [
            'url' => get_site_url(), 'basename' => $this->basename, 'reason' => $reason, 'details' => $details,
        ]]);
        $body = is_wp_error($response) ? [] : json_decode(wp_remote_retrieve_body($response), true);
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) < 200
            || wp_remote_retrieve_response_code($response) >= 300 || !is_array($body) || (int) ($body['status'] ?? 0) !== 1) {
            wp_send_json_error([
                'message' => $this->ui_config['error'] ?? __('Could not send feedback. You can skip and deactivate.', $this->textdomain),
            ], 502);
        }

        wp_send_json_success();
    }

}
