<?php
namespace Webilia\WP;

use Webilia\WP\Interfaces\Entity as EntityInterface;
use Webilia\WP\Interfaces\Metadata;

/**
 * Class Entity
 *
 * @package WordPress
 */
abstract class Entity implements EntityInterface
{
    const TERM = 'term';
    const USER = 'user';
    const POST = 'post';

    /**
     * Entity ID
     * @var integer
     */
    protected int $id;

    /**
     * Entity Type
     * @var string
     */
    protected string $type;

    /**
     * Metadata Handler
     *
     * @var Metadata
     */
    public Metadata $meta;

    /**
     * Constructor
     *
     * @param int $id
     * @param string $type
     * @param Metadata $meta
     */
    public function __construct(int $id, string $type, Metadata $meta)
    {
        $this->id = $id;
        $this->type = $type;

        // Metadata Handler
        $this->meta = $meta;
    }

    /**
     * {@inheritDoc}
     */
    public function type(): string
    {
        return $this->type;
    }

    /**
     * {@inheritDoc}
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function meta_all(): array
    {
        return $this->meta->all();
    }

    /**
     * {@inheritDoc}
     */
    public function meta_get(string $key, $default, bool $single = true)
    {
        return $this->meta->get($key, $default, $single);
    }

    /**
     * {@inheritDoc}
     */
    public function meta_save(string $key, $value = null, $prev = null)
    {
        return $this->meta->save($key, $value, $prev);
    }

    /**
     * {@inheritDoc}
     */
    public function meta_delete(string $key, $value = null): bool
    {
        return $this->meta->delete($key, $value);
    }

    /**
     * {@inheritDoc}
     */
    public function meta_store(array $values): bool
    {
        return $this->meta->store($values);
    }
}
