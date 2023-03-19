<?php
namespace Webilia\WP\Interfaces;

use Webilia\WP\Interfaces\Entity\Post as PostInterface;

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
     * @param PostInterface $entity
     * @param array<mixed> $data
     * @return void
     */
    public function store(PostInterface $entity, array $data): void;

    /**
     * Get Entity by ID
     *
     * @param int $id
     * @return PostInterface
     */
    public function entity(int $id): PostInterface;
}
