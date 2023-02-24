<?php
namespace Webilia\WP;

use Webilia\WP\Helpers\Arr;

/**
 * Singleton Class
 *
 * @package Utils
 */
class Singleton
{
    /**
     * Constructor
     */
    protected function __construct()
    {
    }

    /**
     * @return mixed
     */
    final public static function getInstance()
    {
        static $instances = [];

        $class = get_called_class();
        if(!Arr::isset($instances, $class))
        {
            $instances[$class] = new $class();
        }

        return $instances[$class];
    }

    /**
     * @return void
     */
    private function __clone()
    {
    }
}
