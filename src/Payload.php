<?php
namespace Webilia\WP;

/**
 * Payload Class
 * @package Utils
 */
class Payload
{
    /**
     * @var array<mixed>
     */
    protected static array $vars;

    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function set(string $key, mixed $value): void
    {
        self::$vars[$key] = $value;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public static function get($key)
    {
        return isset(self::$vars[$key]) ? self::$vars[$key] : null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function remove(string $key): bool
    {
        if(isset(self::$vars[$key]))
        {
            unset(self::$vars[$key]);
            return true;
        }

        return false;
    }
}
