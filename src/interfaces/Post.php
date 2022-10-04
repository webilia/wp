<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Post
 * @package Webilia\WP\Interfaces
 */
interface Post extends Initiable
{
    /**
     * Post Type Definition
     *
     * @return array<mixed>
     */
    public function args(): array;

    /**
     * @param int $post_id
     * @param array<mixed> $data
     * @return void
     */
    public function store(int $post_id, array $data): void;
}
