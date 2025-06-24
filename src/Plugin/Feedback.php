<?php
namespace Webilia\WP\Plugin;

class Feedback
{
    public $textdomain;
    public $alert_class;
    public $success_class;
    public $error_class;
    public $plugin;
    public $basename;

    public function __construct(array $args)
    {
        $this->plugin = $args['plugin'] ?? '';
        $this->basename = $args['basename'] ?? '';
        $this->textdomain = $args['textdomain'] ?? $this->plugin;
        $this->alert_class = isset($args['alert']) && trim($args['alert']) ? $args['alert'] : 'lsd-alert';
        $this->success_class = isset($args['success']) && trim($args['success']) ? $args['success'] : 'lsd-success';
        $this->error_class = isset($args['error']) && trim($args['error']) ? $args['error'] : 'lsd-error';

        $this->init();
    }

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

    public function reasons(): array
    {
        return [];
    }

    /**
     * Print deactivate feedback dialog.
     */
    public function dialog()
    {
        ?>
        <div id="web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?>" class="web-dfd-wrapper web-util-hide">
            <form id="web-dfd-form" method="post">
                <div id="web-dfd-message"></div>
                <?php wp_nonce_field('_web_dfd_nonce'); ?>
                <input type="hidden" name="action" value="web_dfd">
                <input type="hidden" name="basename" value="<?php echo esc_attr($this->basename); ?>">

                <div id="web-dfd-form-caption">
                    <?php echo esc_html__('If you have a moment, please share why you are deactivating this plugin:', $this->textdomain); ?>
                </div>
                <div id="web-dfd-form-body">
                    <?php foreach ($this->reasons() as $reason_key => $reason): ?>
                        <div class="web-dfd-input-wrapper">
                            <div class="web-dfd-radio-input-wrapper">
                                <input id="web-dfd-<?php echo esc_attr($this->plugin); ?>-<?php echo esc_attr($reason_key); ?>"
                                       class="web-dfd-dialog-input" type="radio" name="reason_key"
                                       value="<?php echo esc_attr($reason_key); ?>">
                                <label for="web-dfd-<?php echo esc_attr($this->plugin); ?>-<?php echo esc_attr($reason_key); ?>"
                                       class="web-dfd-dialog-label"><?php echo esc_html($reason['title']); ?></label>
                            </div>
                            <?php if (trim($reason['placeholder'])): ?>
                                <div class="web-dfd-text-wrapper web-util-hide">
                                    <input class="web-dfd-feedback-text" type="text"
                                           name="reason_<?php echo esc_attr($reason_key); ?>"
                                           placeholder="<?php echo esc_attr($reason['placeholder']); ?>"
                                           title="<?php echo esc_attr__('Details', $this->textdomain); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="web-dfd-buttons">
                    <button type="submit" name="action_type" value="skip_deactivate" id="skip-deactivate-plugin"
                            class="web-dfd-button-skip">
                        <?php echo esc_html__('Skip & Deactivate', $this->textdomain); ?>
                    </button>
                    <button type="submit" name="action_type" value="submit_feedback" id="submit-feedback"
                            class="button button-primary">
                        <?php echo esc_html__('Submit & Deactivate', $this->textdomain); ?>
                    </button>
                </div>
            </form>
        </div>
        <style>
        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background-color: rgba(0, 0, 0, 0.4);
            z-index: 9999;
            display: flex;
            justify-content: center;
            align-items: center;
            transition: all 0.3s ease-in;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.8);
            width: 90%;
            max-width: 500px;
            overflow: hidden;
            padding: 24px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-message div {
            margin-top: 0;
            font-size: 13px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input[type=radio] {
            margin: 0;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input[type=radio]:checked::before {
            background-color: #8241ff;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input:focus {
            border-color: #8241ff;
            box-shadow: 0 0 0 1px #8241ff;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input[type="text"] {
            width: 100%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-caption {
            padding: 0.5rem;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: left;
            border-bottom: 1px solid #eaeaea;
            color: #333;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons {
            display: flex;
            align-items: center;
            justify-content: space-between !important;
            gap: 10px;
            margin-top: 20px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons .web-dfd-button-skip {
            background-color: transparent;
            cursor: pointer;
            padding: 0 10px;
            border: none;
            color: #00000096;
            border-radius: 3px;
            box-shadow: none;
            line-height: 28px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons .web-dfd-button-skip:focus,
        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons .web-dfd-button-skip:hover,
        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons .web-dfd-button-skip:active {
            background: #eaeaea;
            box-shadow: none;
            outline-color: #989898;
        }
        .web-util-hide { display: none !important; }
        </style>
        <script>
        /**
         * Plugin Deactivation Feedback
         */
        jQuery('a#deactivate-<?php echo esc_js($this->plugin); ?>').on('click', function (e)
        {
            const $link = jQuery(this);

            if ($link.attr('href').includes('action=deactivate') && $link.attr('href').includes('plugin=<?php echo esc_js($this->plugin); ?>'))
            {
                e.preventDefault();

                // Show the feedback dialog
                const $modal = jQuery('#web-dfd-wrapper-<?php echo esc_js($this->plugin); ?>');
                $modal.removeClass('web-util-hide');

                const $alert = $modal.find("#web-dfd-message");

                // Skip Focus
                $modal.find('#skip-deactivate-plugin').focus();

                // Close modal on clicking outside the content
                $modal.off('click').on('click', function (event)
                {
                    if (jQuery(event.target).is($modal))
                    {
                        $modal.addClass('web-util-hide');
                    }
                });

                // Handle form submission
                const $form = $modal.find('#web-dfd-form');

                // Detect which button was clicked
                $form.find('button[type="submit"]').off('click').on('click', function (event)
                {
                    event.preventDefault(); // Prevent default form submission
                    const actionType = jQuery(this).val(); // Get the button's value (action type)

                    if (actionType === 'submit_feedback')
                    {
                        // Submit feedback and deactivate the plugin
                        const $submitButton = jQuery(this);
                        $submitButton.prop('disabled', true);

                        jQuery.ajax(
                        {
                            url: ajaxurl,
                            method: 'POST',
                            data: $form.serialize(),
                            dataType: "json",
                            success: function (response)
                            {
                                if (response.success)
                                {
                                    $alert.html(`<div class="<?php echo $this->alert_class . ' ' . $this->success_class; ?>">${response.message}</div>`);

                                    setTimeout(() => $modal.addClass('web-util-hide'), 1000);
                                    window.location.href = $link.attr('href');
                                }
                                else
                                {
                                    $alert.html(`<div class="<?php echo esc_js($this->alert_class . ' ' . $this->error_class); ?>">${response.message}</div>`);
                                }
                            },
                            error: function ()
                            {
                                $alert.html(`<div class="<?php echo esc_js($this->alert_class . ' ' . $this->error_class); ?>"><?php echo esc_js(esc_html__('An unexpected error occurred.', $this->textdomain)); ?></div>`);
                            },
                            complete: function ()
                            {
                                $submitButton.prop('disabled', false);
                            }
                        });
                    }
                    else if (actionType === 'skip_deactivate')
                    {
                        // Skip feedback and directly deactivate
                        const $submitButton = jQuery(this);
                        $submitButton.prop('disabled', true);

                        setTimeout(() => $modal.addClass('web-util-hide'), 1000);
                        window.location.href = $link.attr('href');
                    }
                });
            }
        });

        // When a reason is selected
        jQuery('.web-dfd-dialog-input').on('change', function ()
        {
            // Hide all input fields
            jQuery('.web-dfd-text-wrapper').addClass('web-util-hide');

            // Show the input field related to the selected radio button
            if (jQuery(this).is(':checked'))
            {
                jQuery(this).closest('.web-dfd-input-wrapper')
                    .find('.web-dfd-text-wrapper')
                    .removeClass('web-util-hide');
            }
        });
        </script>
        <?php
    }

    /**
     * Ajax deactivate feedback.
     */
    public function save()
    {
        // Check nonce for security
        check_ajax_referer('_web_dfd_nonce');

        // Retrieve the action type to determine which button was clicked
        $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';

        // If the user clicked "Skip & Deactivate," deactivate without feedback
        if ($action_type === 'skip_deactivate')
        {
            echo json_encode(['success' => 1, 'message' => esc_html__('Plugin deactivated.', $this->textdomain)], JSON_NUMERIC_CHECK);
            exit;
        }

        // Plugin
        $basename = isset($_POST['basename']) ? sanitize_text_field($_POST['basename']) : '';

        // Retrieve the reason selected by the user
        $reason_key = isset($_POST['reason_key']) ? sanitize_text_field($_POST['reason_key']) : '';
        $reason_detail = isset($_POST['reason_' . $reason_key]) ? sanitize_text_field($_POST['reason_' . $reason_key]) : '';

        // If no reason was selected, return an error response
        if (trim($reason_key) === '')
        {
            echo json_encode(['success' => 0, 'message' => esc_html__('Please select a reason before deactivating the plugin.', $this->textdomain)], JSON_NUMERIC_CHECK);
            exit;
        }

        // Save feedback if a reason was provided
        if ($reason_key)
        {
            // Make the POST request
            $response = wp_remote_post('https://api.webilia.com/deactivation-feedback', [
                'body' => [
                    'url' => get_site_url(),
                    'basename' => $basename,
                    'reason' => $reason_key,
                    'details' => $reason_detail,
                ],
            ]);

            // Optionally handle the response
            if (is_wp_error($response))
            {
                echo json_encode(['success' => 0, 'message' => sprintf(esc_html__('Error sending deactivation feedback: %s', $this->textdomain), $response->get_error_message())], JSON_NUMERIC_CHECK);
                exit;
            }
        }

        // Return success response
        echo json_encode(['success' => 1, 'message' => esc_html__('Thank you for your feedback!', $this->textdomain)], JSON_NUMERIC_CHECK);
        exit;
    }

    /**
     * Check to see if we're in plugins menu
     */
    private function is_plugins_screen(): bool
    {
        return in_array(get_current_screen()->id, ['plugins', 'plugins-network']);
    }
}
