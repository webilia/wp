<?php
namespace Webilia\WP\Traits;

use Webilia\WP\Interfaces\PluginProfile;
use Webilia\WP\View as ViewAdapter;

trait View
{
    /**
     * @var PluginProfile
     */
    protected static PluginProfile $profile;

    /**
     * @return ViewAdapter
     */
    public static function view(): ViewAdapter
    {
        return new ViewAdapter(self::$profile);
    }

    /**
     * @param string $view
     * @param array<mixed> $args
     * @return void
     */
    public static function print(string $view, array $args = []): void
    {
        self::view()->print($view, $args);
    }

    /**
     * @param string $view
     * @param array<mixed> $args
     * @return string
     */
    public static function render(string $view, array $args = []): string
    {
        return self::view()->render($view, $args);
    }
}
