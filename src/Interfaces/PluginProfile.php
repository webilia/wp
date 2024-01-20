<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface PluginProfile
 * @package Webilia\WP\Interfaces
 */
interface PluginProfile
{
    /**
     * @return string
     */
    public function path(): string;

    /**
     * @return string
     */
    public function basename(): string;

    /**
     * @return string
     */
    public function dirname(): string;

    /**
     * @return string
     */
    public function url(): string;

    /**
     * @return string
     */
    public function version(): string;
}
