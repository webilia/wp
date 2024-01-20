<?php
namespace Webilia\WP\Helpers;

/**
 * Array Class
 * @package Utils
 */
class Arr
{
    /**
     * @param mixed $array
     * @return bool
     */
    public static function is($array): bool
    {
        return is_array($array);
    }

    /**
     * @param mixed $array
     * @return bool
     */
    public static function not($array): bool
    {
        return !Arr::is($array);
    }

    /**
     * @param mixed $var
     * @param string|null $key
     * @return bool
     */
    public static function filled($var, string $key = null): bool
    {
        if($key) return is_array($var) and isset($var[$key]) and is_array($var[$key]) and count($var[$key]);
        else return is_array($var) and count($var);
    }

    /**
     * @param mixed $var
     * @param string|null $key
     * @return bool
     */
    public static function empty($var, string $key = null): bool
    {
        return !Arr::filled($var, $key);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function isset($var, string $key): bool
    {
        return is_array($var) and isset($var[$key]);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function notset($var, string $key): bool
    {
        return !Arr::isset($var, $key);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function notset_or_true($var, string $key): bool
    {
        return Arr::notset($var, $key) or (Arr::isset($var, $key) and $var[$key]);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function isset_and_array($var, string $key): bool
    {
        return Arr::isset($var, $key) and Arr::is($var[$key]);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function truthy($var, string $key): bool
    {
        return Arr::isset($var, $key) and $var[$key];
    }

    /**
     * @param mixed $var
     * @param string $key
     * @return bool
     */
    public static function falsy($var, string $key): bool
    {
        return Arr::notset($var, $key) or (Arr::isset($var, $key) and !$var[$key]);
    }

    /**
     * @param mixed $var
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function ternary($var, string $key, $default)
    {
        return Arr::isset($var, $key) ? $var[$key] : $default;
    }

    /**
     * @param array<mixed> $a
     * @param array<mixed> $b
     * @return array<mixed>
     */
    public static function append(array $a, array $b): array
    {
        $result = $a;
        foreach($b as $k => $v)
        {
            if(is_array($v) && isset($result[$k])) $result[$k] = self::append($result[$k], $v);
            else if(!isset($result[$k])) $result[$k] = $v;
        }

        return $result;
    }
}
