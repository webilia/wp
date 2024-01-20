<?php
namespace Webilia\WP\Formats;

/**
 * Class Date Format
 * @package Webilia\WP\Formats
 */
class Date
{
    /**
     * @return string
     */
    public static function wp(): string
    {
        return get_option('date_format');
    }

    /**
     * @return string
     */
    public static function tech(): string
    {
        return 'Y-m-d';
    }

    /**
     * @return string
     */
    public static function js(): string
    {
        return 'YYYY/MM/DD';
    }
}
