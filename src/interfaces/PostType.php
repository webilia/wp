<?php
namespace Webilia\WP\Interfaces;

use Webilia\WP\Entities\Post;

/**
 * Interface Post Type
 * @package Webilia\WP\Interfaces
 */
interface PostType extends Initiable
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

    /**
     * Get Entity by ID
     *
     * @param int $post_id
     * @return Post
     */
    public function entity(int $post_id): Post;
}
