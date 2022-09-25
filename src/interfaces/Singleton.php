<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Singleton
 * @package Webilia\WP\Interfaces
 */
interface Singleton
{
    /**
     * @return Singleton
     */
    public static function getInstance(): Singleton;
}
