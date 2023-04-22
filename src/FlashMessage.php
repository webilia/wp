<?php
namespace Webilia\WP;

/**
 * Flash Message Class
 * @package WordPress
 */
class FlashMessage
{
    const ERROR = 'error';
    const INFO = 'info';
    const SUCCESS = 'success';
    const WARNING = 'warning';

    /**
     * Constructor method
     */
    public function __construct()
    {
    }

    /**
     * @return void
     */
    public function init(): void
    {
        add_action('admin_notices', [self::class, 'show']);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function error(string $message): void
    {
        self::add($message, self::ERROR);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function info(string $message): void
    {
        self::add($message, self::INFO);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function success(string $message): void
    {
        self::add($message, self::SUCCESS);
    }

    /**
     * @param string $message
     * @return void
     */
    public static function warning(string $message): void
    {
        self::add($message, self::WARNING);
    }

    /**
     * @param string $message
     * @param string $class
     * @return void
     */
    private static function add(string $message, string $class = self::INFO): void
    {
        // Option Key
        $key = self::key();

        $flash_messages = maybe_unserialize(get_option($key, []));
        if(!is_array($flash_messages)) return;

        // Define Array
        if(!isset($flash_messages[$class])) $flash_messages[$class] = [];

        // Add if not exists
        if(!in_array($message, $flash_messages[$class])) $flash_messages[$class][] = $message;

        update_option($key, $flash_messages);
    }

    /**
     * @return void
     */
    public static function show(): void
    {
        // Option Key
        $key = self::key();

        $flash_messages = maybe_unserialize(get_option($key, []));
        if(!is_array($flash_messages)) return;

        foreach($flash_messages as $class => $messages)
        {
            foreach($messages as $message)
            {
                echo '<div class="notice notice-'.esc_attr($class).' is-dismissible"><p>'.$message.'</p></div>';
            }
        }

        // Clear Flash Messages
        delete_option($key);
    }

    /**
     * @return string
     */
    public static function key(): string
    {
        return 'webilia_flash_messages';
    }
}
