<?php
namespace Webilia\WP;

use DateTimeZone;

/**
 * Class Date
 * @package Webilia\WP\Date
 */
class TimeZone
{
    /**
     * @var string|DateTimeZone|integer|null
     */
    private $timezone = null;

    /**
     * Constructor
     * @param string|DateTimeZone|integer|null $timezone
     */
    public function __construct($timezone = null)
    {
        $this->timezone = $timezone;
    }

    /**
     * Get Timezone
     * @return DateTimeZone
     */
    public function get(): DateTimeZone
    {
        // Post ID
        if(is_numeric($this->timezone))
        {
            return $this->entity((int) $this->timezone);
        }
        else if(is_string($this->timezone) and trim($this->timezone) and $this->timezone !== 'global') return new DateTimeZone($this->timezone);
        else if($this->timezone instanceof DateTimeZone) return $this->timezone;

        return $this->global();
    }

    /**
     * Entity Timezone
     *
     * @param int $post_id
     * @return DateTimeZone
     */
    public static function entity(int $post_id): DateTimeZone
    {
        return self::global();
    }

    /**
     * Site Timezone
     * @return DateTimeZone
     */
    public static function global(): DateTimeZone
    {
        $timezone_string = get_option('timezone_string');
        $utc_offset = get_option('gmt_offset');

        if(trim($timezone_string) == '' and trim($utc_offset)) $timezone_string = self::by_offset($utc_offset);
        else if(trim($timezone_string) == '' and trim($utc_offset) == '0') $timezone_string = 'UTC';

        return new DateTimeZone($timezone_string);
    }

    /**
     * Timezone String by Offset
     *
     * @param float $offset
     * @return string
     */
    public static function by_offset($offset): string
    {
        $seconds = (int) ($offset * 3600);

        $timezone = timezone_name_from_abbr('', $seconds, 0);
        if($timezone === false)
        {
            $timezones = [
                '-12' => 'Pacific/Auckland',
                '-11.5' => 'Pacific/Auckland',
                '-11' => 'Pacific/Apia',
                '-10.5' => 'Pacific/Apia',
                '-10' => 'Pacific/Honolulu',
                '-9.5' => 'Pacific/Honolulu',
                '-9' => 'America/Anchorage',
                '-8.5' => 'America/Anchorage',
                '-8' => 'America/Los_Angeles',
                '-7.5' => 'America/Los_Angeles',
                '-7' => 'America/Denver',
                '-6.5' => 'America/Denver',
                '-6' => 'America/Chicago',
                '-5.5' => 'America/Chicago',
                '-5' => 'America/New_York',
                '-4.5' => 'America/New_York',
                '-4' => 'America/Halifax',
                '-3.5' => 'America/Halifax',
                '-3' => 'America/Sao_Paulo',
                '-2.5' => 'America/Sao_Paulo',
                '-2' => 'America/Sao_Paulo',
                '-1.5' => 'Atlantic/Azores',
                '-1' => 'Atlantic/Azores',
                '-0.5' => 'UTC',
                '0' => 'UTC',
                '0.5' => 'UTC',
                '1' => 'Europe/Paris',
                '1.5' => 'Europe/Paris',
                '2' => 'Europe/Helsinki',
                '2.5' => 'Europe/Helsinki',
                '3' => 'Europe/Moscow',
                '3.5' => 'Europe/Moscow',
                '4' => 'Asia/Dubai',
                '4.5' => 'Asia/Tehran',
                '5' => 'Asia/Karachi',
                '5.5' => 'Asia/Kolkata',
                '5.75' => 'Asia/Katmandu',
                '6' => 'Asia/Yekaterinburg',
                '6.5' => 'Asia/Yekaterinburg',
                '7' => 'Asia/Krasnoyarsk',
                '7.5' => 'Asia/Krasnoyarsk',
                '8' => 'Asia/Shanghai',
                '8.5' => 'Asia/Shanghai',
                '8.75' => 'Asia/Tokyo',
                '9' => 'Asia/Tokyo',
                '9.5' => 'Asia/Tokyo',
                '10' => 'Australia/Melbourne',
                '10.5' => 'Australia/Adelaide',
                '11' => 'Australia/Melbourne',
                '11.5' => 'Pacific/Auckland',
                '12' => 'Pacific/Auckland',
                '12.75' => 'Pacific/Apia',
                '13' => 'Pacific/Apia',
                '13.75' => 'Pacific/Honolulu',
                '14' => 'Pacific/Honolulu',
            ];

            $timezone = isset($timezones[$offset]) ? $timezones[$offset] : null;
        }

        return $timezone;
    }
}
