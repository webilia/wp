<?php
namespace Webilia\WP\Formats;

/**
 * Class DateTime Format
 * @package Webilia\WP\Formats
 */
class DateTime
{
    /**
     * @return string
     */
    public static function wp(): string
    {
        return Date::wp().' '.Time::wp();
    }

    /**
     * @return string
     */
    public static function tech(): string
    {
        return Date::tech().' '.Time::tech();
    }

    /**
     * @return string
     */
    public static function js(): string
    {
        return Date::js().' '.Time::js();
    }
}
