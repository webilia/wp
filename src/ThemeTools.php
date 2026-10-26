<?php

namespace Webilia\WP;

use LogicException;

/** Dependency checks are opt-in: loading webilia/wp alone has no UI requirement. */
final class ThemeTools
{
    /**
     * @return void
     */
    public static function requireUI(): void
    {
        if (!class_exists(\Webilia\UI\Version::class))
        {
            throw new LogicException('Theme tools require webilia/ui:^1.5 before initialization.');
        }

        if (!self::supportsUIVersion(\Webilia\UI\Version::VALUE)
            || !class_exists(\Webilia\UI\SettingsPanel\Layout::class)
            || !class_exists(\Webilia\UI\DemoImport\Screen::class))
        {
            throw new LogicException('Theme tools require webilia/ui:^1.5; load a compatible Composer package before initialization.');
        }
    }

    /**
     * @param string $version
     * @return boolean
     */
    private static function supportsUIVersion(string $version): bool
    {
        return version_compare($version, '1.5.0', '>=') && version_compare($version, '2.0.0', '<');
    }

    /**
     * @param array<mixed> $labels
     * @param string $domain
     * @return array<mixed>
     */
    public static function labels(array $labels, string $domain): array
    {
        $result = [];
        foreach ($labels as $label) $result[$label] = __($label, $domain);
        return $result;
    }
}
