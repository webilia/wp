<?php
namespace Webilia\WP\Entities;

use Webilia\WP\Entity;
use Webilia\WP\Metadata\User as UserMetadata;
use Webilia\WP\Interfaces\Entity\User as UserInterface;

/**
 * Class User Entity
 *
 * @package WordPress
 */
class User extends Entity implements UserInterface
{
    /**
     * Constructor
     * @param int $id
     */
    public function __construct(int $id)
    {
        parent::__construct(
            $id,
            Entity::TERM,
            new UserMetadata($id)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        return get_userdata($this->id);
    }
}
