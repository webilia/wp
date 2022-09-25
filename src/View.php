<?php
namespace Webilia\WP;

use Webilia\WP\Interfaces\PluginProfile;
use Jenssegers\Blade\Blade;

/**
 * Class View
 * @package Utils
 */
class View
{
    /**
     * @var Blade
     */
    private Blade $blade;

    /**
     * Constructor
     * @param PluginProfile $profile
     */
    public function __construct(PluginProfile $profile)
    {
        // Blade Engine
        $this->blade = new Blade($profile->path().'/views', $profile->path().'/views/cache');
    }

    /**
     * @param string $view
     * @param array<mixed> $args
     * @return string
     */
    public function render(string $view, array $args = []): string
    {
        return $this->blade->render($view, $args);
    }

    /**
     * @param string $view
     * @param array<mixed> $args
     * @return void
     */
    public function print(string $view, array $args = []): void
    {
        echo $this->blade->render($view, $args);
    }
}
