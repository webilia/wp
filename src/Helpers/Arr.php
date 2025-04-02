<?php
namespace Webilia\WP\Helpers;

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
     * @param mixed|null $key
     * @return bool
     */
    public static function filled($var, $key = null): bool
    {
        if ($key) return is_array($var) && isset($var[$key]) && is_array($var[$key]) && count($var[$key]);
        else return is_array($var) && count($var);
    }

    /**
     * @param mixed $var
     * @param mixed|null $key
     * @return bool
     */
    public static function empty($var, $key = null): bool
    {
        return !Arr::filled($var, $key);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function isset($var, $key): bool
    {
        return is_array($var) && isset($var[$key]);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function notset($var, $key): bool
    {
        return !Arr::isset($var, $key);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function notset_or_true($var, $key): bool
    {
        return Arr::notset($var, $key) || (Arr::isset($var, $key) && $var[$key]);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function isset_and_array($var, $key): bool
    {
        return Arr::isset($var, $key) && Arr::is($var[$key]);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function truthy($var, $key): bool
    {
        return Arr::isset($var, $key) && $var[$key];
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @return bool
     */
    public static function falsy($var, $key): bool
    {
        return Arr::notset($var, $key) || (Arr::isset($var, $key) && !$var[$key]);
    }

    /**
     * @param mixed $var
     * @param mixed $key
     * @param mixed $default
     * @return mixed
     */
    public static function ternary($var, $key, $default)
    {
        return Arr::isset($var, $key) ? $var[$key] : $default;
    }

    /**
     * @param array $a
     * @param array $b
     * @return array
     */
    public static function append(array $a, array $b): array
    {
        $result = $a;
        foreach ($b as $k => $v)
        {
            if (is_array($v) && isset($result[$k])) $result[$k] = self::append($result[$k], $v);
            else if (!isset($result[$k])) $result[$k] = $v;
        }

        return $result;
    }
}
