<?php
namespace Webilia\WP\Entities;

use Webilia\WP\Entity;
use Webilia\WP\Metadata\Term as TermMetadata;
use Webilia\WP\Interfaces\Entity\Term as TermInterface;

/**
 * Class Term Entity
 *
 * @package WordPress
 */
class Term extends Entity implements TermInterface
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
            new TermMetadata($id)
        );
    }

    /**
     * {@inheritDoc}
     */
    public function get()
    {
        return get_term($this->id);
    }
}
