<?php
namespace Webilia\WP\DemoImport;

class Bootstrap
{
    /** @var boolean */
    private static $booted = false;
    /** @var Boot|null */
    private static $boot;

    /**
     * @param array<mixed> $config
     * @return Boot
     */
    public static function init(array $config = []): Boot
    {
        if (!self::$booted)
        {
            \Webilia\WP\ThemeTools::requireUI();

            self::$boot = new Boot($config);
            self::$boot->init();
            self::$booted = true;
        }
        else if (self::$boot instanceof Boot && $config)
        {
            self::$boot->set_config($config);
        }

        return self::$boot;
    }
}
