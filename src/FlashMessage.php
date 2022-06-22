<?php
namespace Webilia\WP;

/**
 * Flash Message Class
 * @package WordPress
 */
class FlashMessage
{
    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @return void
     */
    public function init()
    {
        add_action('admin_notices', [self::class, 'show']);
    }

    /**
     * @param string $message
     * @param string $class
     * @return void
     */
    public static function add(string $message, string $class = 'info'): void
    {
        $classes = ['error', 'info', 'success', 'warning'];
        if(!in_array($class, $classes)) $class = 'info';

        // Option Key
        $key = self::key();

        $flash_messages = maybe_unserialize(get_option($key, []));
        if(!is_array($flash_messages)) return;

        $flash_messages[$class][] = $message;

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
