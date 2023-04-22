<?php
namespace Webilia\WP;

/**
 * LifeCycle Class
 *
 * @package WordPress
 */
class LifeCycle
{
    /**
     * @var boolean
     */
    public static bool $body_started = false;

    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @return void
     */
    public function init(): void
    {
        add_filter('body_class', function(array $classes): array
        {
            self::set_body_started();
            return $classes;
        });

        add_action('wp_body_open', function(): void
        {
            self::set_body_started();
        });
    }

    /**
     * @return void
     */
    public function set_body_started(): void
    {
        self::$body_started = true;
    }

    /**
     * @return bool
     */
    public static function is_body_started(): bool
    {
        return self::$body_started;
    }
}
