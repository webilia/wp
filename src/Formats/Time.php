<?php
namespace Webilia\WP\Formats;

/**
 * Class Time Format
 * @package Webilia\WP\Formats
 */
class Time
{
    /**
     * @return string
     */
    public static function wp(): string
    {
        return get_option('time_format');
    }

    /**
     * @return string
     */
    public static function tech(): string
    {
        return 'H:i:s';
    }

    /**
     * @return string
     */
    public static function js(): string
    {
        return 'HH:mm';
    }
}
