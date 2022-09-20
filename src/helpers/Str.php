<?php
namespace Webilia\WP\Helpers;

/**
 * String Class
 * @package Utils
 */
class Str
{
    /**
     * @param mixed $string
     * @return bool
     */
    public static function is($string): bool
    {
        return is_string($string);
    }

    /**
     * Convert a string to human readable format
     *
     * @param string $string
     * @return string
     */
    public static function humanify(string $string): string
    {
        $string = str_replace(['_', '-'], ' ', $string);
        return ucwords($string);
    }

    /**
     * Lowercase a string
     *
     * @param mixed $string
     * @return string
     */
    public static function lowercase($string): string
    {
        return is_string($string) ? strtolower($string) : '';
    }

    /**
     * Lowercase a string
     *
     * @param mixed $string
     * @return string
     */
    public static function uppercase($string): string
    {
        return is_string($string) ? strtoupper($string) : '';
    }

    /**
     * Left trim a string
     *
     * @param mixed $string
     * @param string $chars
     * @return string
     */
    public static function ltrim($string, $chars = " \t\n\r\0\x0B"): string
    {
        return Str::is($string) ? ltrim($string, $chars) : '';
    }

    /**
     * Right trim a string
     *
     * @param mixed $string
     * @param string $chars
     * @return string
     */
    public static function rtrim($string, $chars = " \t\n\r\0\x0B"): string
    {
        return Str::is($string) ? rtrim($string, $chars) : '';
    }

    /**
     * Trim a string
     *
     * @param mixed $string
     * @param string $chars
     * @return string
     */
    public static function trim($string, $chars = " \t\n\r\0\x0B"): string
    {
        return Str::is($string) ? trim($string, $chars) : '';
    }

    /**
     * Check if string has some characters
     *
     * @param mixed $string
     * @return bool
     */
    public static function filled($string): bool
    {
        return Str::trim($string) !== '';
    }

    /**
     * Check if string is empty
     *
     * @param mixed $string
     * @return bool
     */
    public static function empty($string): bool
    {
        return !Str::filled($string);
    }

    /**
     * @param mixed $string
     * @param string $falsy
     * @param string|null $truthy
     * @return string
     */
    public static function ternary($string, string $falsy, string $truthy = null): string
    {
        // Use string as truthy value
        if(is_null($truthy)) $truthy = $string;

        return Str::trim($string) ? $truthy : $falsy;
    }
}
