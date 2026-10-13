<?php

namespace Webilia\WP\Plugin;

/** One dispatcher per action; the isolated UI action also works alongside older package copies. */
final class FeedbackRegistry
{
    /** @var array<string, Feedback> */
    private static $instances = [];
    /** @var boolean */
    private static $legacyHook = false;
    /** @var boolean */
    private static $uiHook = false;

    /**
     * @param Feedback $feedback
     * @return void
     */
    public static function register(Feedback $feedback): void
    {
        self::$instances[$feedback->basename] = $feedback;
        if (!self::$uiHook) {
            self::$uiHook = true;
            add_action('wp_ajax_webilia_feedback', [self::class, 'dispatch']);
        }
    }

    /**
     * @return void
     */
    public static function legacyHook(): void
    {
        if (self::$legacyHook) return;
        self::$legacyHook = true;
        add_action('wp_ajax_web_dfd', [self::class, 'dispatch']);
    }

    /**
     * @return void
     */
    public static function dispatch(): void
    {
        $basename = isset($_POST['basename']) && is_string($_POST['basename']) ? sanitize_text_field(wp_unslash($_POST['basename'])) : '';
        if (!isset(self::$instances[$basename])) wp_send_json_error(['message' => 'Unknown plugin'], 400);
        if (!current_user_can('deactivate_plugin', $basename)) wp_send_json_error(['message' => 'Forbidden'], 403);
        self::$instances[$basename]->save();
    }
}
