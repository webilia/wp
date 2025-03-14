<?php
namespace Webilia\WP\Entities;

use Webilia\WP\Entity;
use Webilia\WP\Metadata\Post as PostMetadata;
use Webilia\WP\Interfaces\Entity\Post as PostInterface;

class Post extends Entity implements PostInterface
{
    /**
     * Constructor
     * @param int $id
     */
    public function __construct(int $id)
    {
        parent::__construct(
            $id,
            Entity::POST,
            new PostMetadata($id)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        return get_post($this->id);
    }
}
