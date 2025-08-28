<?php
namespace Webilia\WP\Plugin;

class Feedback
{
    public $textdomain;
    public $alert_class;
    public $success_class;
    public $error_class;
    public $primary_button_class;
    public $secondary_button_class;
    public $text_button_class;
    public $icon_class;
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
        $this->primary_button_class = isset($args['primary_button_class']) && trim($args['primary_button_class']) ? $args['primary_button_class'] : 'lsd-primary-button';
        $this->secondary_button_class = isset($args['secondary_button_class']) && trim($args['secondary_button_class']) ? $args['secondary_button_class'] : 'lsd-secondary-button';
        $this->text_button_class = isset($args['text_button_class']) && trim($args['text_button_class']) ? $args['text_button_class'] : 'lsd-text-button';
        $this->icon_class = isset($args['icon_class']) && trim($args['icon_class']) ? $args['icon_class'] : 'listdom-icon';

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
                <?php wp_nonce_field('_web_dfd_nonce'); ?>
                <input type="hidden" name="action" value="web_dfd">
                <input type="hidden" name="basename" value="<?php echo esc_attr($this->basename); ?>">

                <div id="web-dfd-form-caption" class="web-dfd-admin-section-heading">
                    <h4 class="web-dfd-admin-title"><?php echo esc_html__('Sorry to see you go :(', $this->textdomain) ?></h4>
                    <p class="web-dfd-admin-description"><?php echo esc_html__('If you have a moment, please share why you are deactivating this plugin:', $this->textdomain); ?></p>
                </div>
                <div id="web-dfd-form-body">
                    <div id="web-dfd-message" class="web-util-hide"></div>

                    <div id="web-dfd-form-reasons">
                        <?php foreach ($this->reasons() as $reason_key => $reason): ?>
                            <label for="web-dfd-<?php echo esc_attr($this->plugin); ?>-<?php echo esc_attr($reason_key); ?>">
                                <div class="web-dfd-input-wrapper">
                                    <div class="web-dfd-radio-input-wrapper">
                                        <div class="web-dfd-icon">
                                            <i class="<?php echo esc_attr($this->icon_class); ?> <?php echo esc_attr($reason['icon']); ?>"></i>
                                        </div>
                                        <input id="web-dfd-<?php echo esc_attr($this->plugin); ?>-<?php echo esc_attr($reason_key); ?>"
                                               class="web-dfd-dialog-input web-util-hide" type="radio" name="reason_key"
                                               value="<?php echo esc_attr($reason_key); ?>"
                                               data-target="#web-dfd-<?php echo esc_attr($this->plugin); ?>-reason-<?php echo esc_attr($reason_key); ?>">
                                        <label for="web-dfd-<?php echo esc_attr($this->plugin); ?>-<?php echo esc_attr($reason_key); ?>"
                                               class="web-dfd-dialog-label"><?php echo esc_html($reason['title']); ?>
                                        </label>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($this->reasons() as $reason_key => $reason): ?>
                        <?php if (trim($reason['placeholder'])): ?>
                            <div class="web-dfd-text-wrapper web-util-hide" id="web-dfd-<?php echo esc_attr($this->plugin); ?>-reason-<?php echo esc_attr($reason_key); ?>">
                                <div class="web-dfd-feedback-text-wrapper">
                                    <textarea class="web-dfd-feedback-text" name="reason_<?php echo esc_attr($reason_key); ?>" placeholder="<?php echo esc_attr($reason['placeholder']); ?>" title="<?php echo esc_attr__('Details', $this->textdomain); ?>"></textarea>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <p class="web-dfd-admin-description">
                        <?php echo esc_html__('Your feedback ia private and only used for enhancing our products.', $this->textdomain); ?>
                    </p>
                </div>
                <div class="web-dfd-buttons">
                    <button type="submit" name="action_type" value="skip_deactivate" id="skip-deactivate-plugin"
                            class="web-dfd-button-skip <?php echo esc_attr($this->text_button_class); ?>">
                        <?php echo esc_html__('Skip & Deactivate', $this->textdomain); ?>
                    </button>
                    <div class="web-dfd-buttons-submit">
                        <button id="web-dfd-button-cancel" class="<?php echo esc_attr($this->secondary_button_class); ?>" type="button">
                            <?php echo esc_html__('Cancel', $this->textdomain); ?>
                        </button>
                        <button type="submit" name="action_type" value="submit_feedback" id="submit-feedback"
                                class="<?php echo esc_attr($this->primary_button_class); ?>">
                            <?php echo esc_html__('Submit & Deactivate', $this->textdomain); ?>
                        </button>
                    </div>
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
            display: flex;
            flex-direction: column;
            gap: 24px;
            box-shadow: 0 0 10px 0 #0000001A;
            width: 90%;
            max-width: 1000px;
            overflow: hidden;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-message {
            width: 100%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-message div {
            width: 100%;
            margin-top: 0;
            font-size: 13px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            padding: 0 24px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-text-wrapper {
            width: 100%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-feedback-text-wrapper {
            display: flex;
            flex-direction: column;
            gap: 10px;
            border-radius: 10px;
            padding: 24px;
            background: #EBF0FF;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-feedback-text-wrapper .web-dfd-feedback-text {
            width: 100%;
            height: 100%;
            background: white;
            border: 1px solid #8C8F94;
            padding: 5px;
            border-radius: 6px;
            font-weight: 400;
            font-size: 12px;
            line-height: 18px;
            box-shadow: unset;
            min-height: 65px;
            box-sizing: border-box;
            resize: none;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body #web-dfd-form-reasons {
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 12px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body #web-dfd-form-reasons label {
            flex: 1;
            display: block;
            cursor: pointer;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 12px;
            border-radius: 10px;
            border: 1px solid #E8E8E8;
            height: 90px;
            transition: all ease-in-out 0.2s;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper:hover {
            border: 1px solid #666;
            color: #666;
        }

        #web-dfd-form-body .web-dfd-input-wrapper.selected {
            background: linear-gradient(90deg, #666 0%, #111 100%);
        }

        #web-dfd-form-body .web-dfd-input-wrapper.selected label {
            color: white !important;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper .web-dfd-radio-input-wrapper {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            flex-direction: column;
            text-align: center;
            gap: 12px;
            height: 100%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper .web-dfd-radio-input-wrapper .web-dfd-icon i {
            width: 22px;
            height: 22px;
            font-size: 22px;
            padding: 8px;
            background: #EFECF5;
            border-radius: 50%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input[type=radio] {
            margin: 0;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-body .web-dfd-input-wrapper input[type="text"] {
            width: 100%;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-caption {
            font-size: 0.8rem;
            font-weight: 500;
            text-align: left;
            padding: 24px;
            color: #fff;
            background: linear-gradient(90deg, #666 0%, #111 100%);
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form #web-dfd-form-caption .web-dfd-admin-title {
            color: white;
            font-weight: 500 !important;
            font-size: 20px !important;
            line-height: 100%;
            margin: 0;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-admin-section-heading {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> .web-dfd-admin-description {
            font-family: Inter;
            font-weight: 400;
            font-size: 14px;
            line-height: 22px;
            margin: 0 !important;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons {
            display: flex;
            align-items: center;
            justify-content: space-between !important;
            gap: 10px;
            padding: 24px;
            background: #EFECF5;
        }

        #web-dfd-wrapper-<?php echo esc_attr($this->plugin); ?> #web-dfd-form .web-dfd-buttons .web-dfd-buttons-submit {
            display: flex;
            align-items: center;
            gap: 12px;
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

                // Close modal on clicking Cancel button
                $modal.find('#web-dfd-button-cancel').on('click', function (event)
                {
                    $modal.addClass('web-util-hide');
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
                                        setTimeout(() => $modal.addClass('web-util-hide'), 1000);
                                        window.location.href = $link.attr('href');
                                    }
                                    else
                                    {
                                        $alert.removeClass('web-util-hide');
                                        $alert.html(`<div class="<?php echo esc_js($this->alert_class . ' ' . $this->error_class); ?>">${response.message}</div>`);
                                    }
                                },
                                error: function ()
                                {
                                    $alert.removeClass('web-util-hide');
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
            jQuery('.web-dfd-text-wrapper').addClass('web-util-hide');
            jQuery('.web-dfd-input-wrapper').removeClass('selected');
            jQuery(this).closest('.web-dfd-input-wrapper').addClass('selected');

            // Show the corresponding text input
            if (jQuery(this).is(':checked')) {
                const targetId = jQuery(this).data('target');
                jQuery(targetId).removeClass('web-util-hide');
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
        $reason_detail = isset($_POST['reason_' . $reason_key]) ? sanitize_textarea_field($_POST['reason_' . $reason_key]) : '';

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
