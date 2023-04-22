<?php
namespace Webilia\WP;

/**
 * Class Color
 * @package Utils
 */
class Color
{
    /**
     * @param string $bg_color
     * @return string
     */
    public static function text_color(string $bg_color): string
    {
        // Clean it
        $bg_color = trim($bg_color, '# ');

        $r = hexdec(substr($bg_color, 0, 2));
        $g = hexdec(substr($bg_color, 2, 2));
        $b = hexdec(substr($bg_color, 4, 2));

        $yiq = ((($r * 299) + ($g * 587) + ($b * 114)) / 1000);
        return ($yiq >= 130) ? '#000000' : '#ffffff';
    }

    /**
     * @param string $hex
     * @param int $percent
     * @return string
     */
    public static function brightness(string $hex, int $percent): string
    {
        $hex = ltrim($hex, '#');

        // 6 Character Color
        if(strlen($hex) == 3) $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];

        $hex = array_map('hexdec', str_split($hex, 2));
        foreach($hex as &$color)
        {
            $adjustableLimit = ($percent < 0) ? $color : (255 - $color);
            $adjustAmount = ceil($adjustableLimit * $percent);

            $color = str_pad(dechex((int) ($color + $adjustAmount)), 2, '0', STR_PAD_LEFT);
        }

        return '#' . implode($hex);
    }

    /**
     * @param string $color
     * @param int $percent
     * @return string
     */
    public static function lighter(string $color, int $percent): string
    {
        return self::brightness($color, ($percent / 100));
    }

    /**
     * @param string $color
     * @param int $percent
     * @return string
     */
    public static function darker(string $color, int $percent): string
    {
        return self::brightness($color, -($percent / 100));
    }
}
