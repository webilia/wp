<?php
namespace Webilia\WP;

use DateTimeZone;

/**
 * Class DateTime
 * @package Webilia\WP\Date
 */
class DateTime
{
    const FORMAT_STANDARD = 'Y-m-d H:i';

    /**
     * @var string
     */
    protected string $datetime;

    /**
     * @var DateTimeZone|null
     */
    protected ?DateTimeZone $tz;

    /**
     * Constructor
     *
     * @param string $datetime
     * @param string|DateTimeZone|int|null $timezone
     */
    public function __construct(string $datetime, $timezone = null)
    {
        // Date & Time
        $this->datetime = $datetime;

        // Current Timezone
        $this->set_timezone($timezone);
    }

    /**
     * @param string|DateTimeZone|int|null $timezone
     * @return DateTime
     */
    public function set_timezone($timezone = null): DateTime
    {
        // Timezone
        $this->tz = (new TimeZone($timezone))->get();

        return $this;
    }

    /**
     * Convert Datetime to UTC
     *
     * @param string $format
     * @return string
     */
    public function to_UTC(string $format): string
    {
        return $this->to_timezone($format, 'UTC');
    }

    /**
     * Convert Datetime to Global Timezone
     *
     * @param string $format
     * @return string
     */
    public function to_global_timezone(string $format): string
    {
        return $this->to_timezone($format);
    }

    /**
     * Convert datetime to certain timezone
     * @param string $format
     * @param string|DateTimeZone|int|null $timezone
     * @return string
     */
    public function to_timezone(string $format, $timezone = null): string
    {
        // Target Timezone
        $timezone = (new TimeZone($timezone))->get();

        // Convert & Format
        return $this->convert($timezone)->format($format);
    }

    /**
     * @param string|DateTimeZone|int|null $timezone
     * @return Date
     */
    public function convert($timezone = null): Date
    {
        return Date::parse($this->datetime, $this->tz)->timezone($timezone);
    }
}
