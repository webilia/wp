<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Metadata
 * @package Webilia\WP\Interfaces
 */
interface Metadata
{
    /**
     * @return array<mixed>
     */
    public function all(): array;

    /**
     * @param string $key
     * @param mixed $default
     * @param bool $single
     * @return mixed
     */
    public function get(string $key, $default, bool $single = true);

    /**
     * @param string $key
     * @param mixed $value
     * @param mixed $prev
     * @return int|bool
     */
    public function save(string $key, $value = null, $prev = null);

    /**
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function delete(string $key, $value = null): bool;

    /**
     * @param array<mixed> $values
     * @return bool
     */
    public function store(array $values): bool;
}
