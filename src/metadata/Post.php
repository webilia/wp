<?php
namespace Webilia\WP\Metadata;

use Webilia\WP\Entity;
use Webilia\WP\Metadata;

class Post extends Metadata implements \Webilia\WP\Interfaces\Metadata
{
    /**
     * Constructor
     * @param int $id
     */
    public function __construct(int $id)
    {
        parent::__construct([
            'id' => $id,
            'entity' => Entity::POST
        ]);
    }
}
