<?php
namespace Webilia\WP;

/**
 * ID Class
 * @package Utils
 */
class Id
{
    /**
     * The single instance of the class.
     *
     * @var Id
     */
    protected static ?Id $instance = null;

    /**
     * All IDs that are loaded
     * @var array<int>
     */
    protected static array $IDs = [];

    /**
     * Main Id Instance.
     * Ensures only one instance of Id is loaded or can be loaded.
     *
     * @static
     * @return Id
     */
    public static function getInstance()
    {
        // Get an instance of Class
        if(is_null(self::$instance)) self::$instance = new self();

        // Return the instance
        return self::$instance;
    }

    /**
     * Cloning is forbidden.
     * @return void
     */
    public function __clone() {}

    /**
     * Unserializing instances of this class is forbidden.
     * @return void
     */
    public function __wakeup() {}

    /**
     * Constructor method
     */
    protected function __construct() {}

    /**
     * @param int $id
     * @return int
     */
    public static function get(int $id): int
    {
        $instance = self::getInstance();
        if($instance->duplicated($id))
        {
            $id = $instance->unique();

            $instance->add($id);
            return $id;
        }
        else
        {
            $instance->add($id);
            return $id;
        }
    }

    /**
     * @param int $id
     * @return bool
     */
    public function duplicated(int $id): bool
    {
        return in_array($id, self::$IDs);
    }

    /**
     * @param int $id
     * @return void
     */
    public function add(int $id): void
    {
        self::$IDs[] = $id;
    }

    /**
     * @return int
     */
    public function unique(): int
    {
        $id = mt_rand(10000, 99999);
        if($this->duplicated($id)) $id = $this->unique();

        return $id;
    }

    /**
     * @param int $length
     * @return string
     */
    public static function code(int $length = 10): string
    {
        $keys = array_merge(range(0, 9), range('A', 'Z'), range('a', 'z'));

        $key = '';
        for($i = 0; $i < $length; $i++) $key .= $keys[array_rand($keys)];

        return $key;
    }
}
