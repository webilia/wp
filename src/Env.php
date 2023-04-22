<?php
namespace Webilia\WP;

/**
 * Class Env
 * @package WordPress
 */
class Env
{
    /**
     * Constructor method
     */
    public function __construct()
    {
    }

    /**
     * Return Version of PHP
     * @return string
     */
    public static function php_version(): string
    {
        return phpversion();
    }

    /**
     * Return Version of WordPress
     * @return string
     */
    public static function wp_version(): string
    {
        global $wp_version;
        return $wp_version;
    }
}
