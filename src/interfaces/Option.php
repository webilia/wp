<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Option
 * @package Webilia\WP\Interfaces
 */
interface Option
{
    /**
     * @return mixed
     */
    public function get();

    /**
     * @return mixed
     */
    public function default();

    /**
     * @param mixed $value
     * @return bool
     */
    public function set($value): bool;

    /**
     * @param mixed $value
     * @return bool
     */
    public function merge($value): bool;

    /**
     * @return bool
     */
    public function delete(): bool;

    /**
     * @return bool
     */
    public function autoload(): bool;

    /**
     * @return string
     */
    public function key(): string;
}
