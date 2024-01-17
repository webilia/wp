<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Entity
 * @package Webilia\WP\Interfaces
 */
interface Entity
{
    /**
     * Entity Type
     * @return string
     */
    public function type(): string;

    /**
     * Entity ID
     *
     * @return int
     */
    public function id(): int;

    /**
     * Entity Data
     *
     * @return mixed
     */
    public function get();

    /**
     * Return all metadata
     *
     * @return array<mixed>
     */
    public function meta_all(): array;

    /**
     * Get one specific metadata
     *
     * @param string $key
     * @param mixed $default
     * @param bool $single
     * @return mixed
     */
    public function meta_get(string $key, $default, bool $single = true);

    /**
     * Save one specific metadata
     *
     * @param string $key
     * @param mixed $value
     * @param mixed $prev
     * @return int|bool
     */
    public function meta_save(string $key, $value = null, $prev = null);

    /**
     * Delete one specific metadata
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public function meta_delete(string $key, $value = null): bool;

    /**
     * Save multiple metadata
     *
     * @param mixed[] $values
     * @return bool
     */
    public function meta_store(array $values): bool;
}
