<?php
namespace Webilia\WP;

/**
 * Rewrite Rules Class
 * @package WordPress
 */
class RewriteRules
{
    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @return void
     */
    public static function todo(): void
    {
        update_option(self::key(), 1, true);
	}

    /**
     * @return void
     */
    public static function flush(): void
    {
        // if flush is not needed
        if(!get_option(self::key(), 0)) return;

        // Perform the flush on WordPress init hook
        add_action('init', [self::class, 'perform']);
	}

    /**
     * @return void
     */
    public static function perform(): void
    {
        // Flush the rules
        global $wp_rewrite;
        $wp_rewrite->flush_rules(false);

        // remove the to do
        delete_option(self::key());
	}

    /**
     * @return string
     */
    public static function key(): string
    {
        return 'webilia_todo_rr_flush';
	}
}
