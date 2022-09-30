<?php
namespace Webilia\WP;

use Webilia\WP\Interfaces\Metadata as MetadataInterface;
use Webilia\WP\Helpers\Arr;

/**
 * Class Metadata
 *
 * @package WordPress
 */
class Metadata implements MetadataInterface
{
    /**
     * @var integer
     */
    public int $id;

    /**
     * @var string
     */
    public string $entity;

    /**
     * Constructor
     *
     * @param array<mixed> $args
     */
    public function __construct(array $args)
    {
        $this->id = $args['id'];
        $this->entity = $args['entity'] ?? Entity::POST;
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        // Raw Meta Data
        $raw = $this->get('');

        // Invalid Raw Data
        if(Arr::not($raw)) return [];

        $data = [];
        foreach($raw as $key => $val) $data[$key] = isset($val[0]) ? (!is_serialized($val[0]) ? $val[0] : unserialize($val[0])) : null;
        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, $default = null, bool $single = true)
    {
        // Term Meta
        if($this->entity === Entity::TERM) $data = get_term_meta($this->id, $key, $single);
        // Use Meta
        else if($this->entity === Entity::USER) $data = get_user_meta($this->id, $key, $single);
        // Post meta
        else $data = get_post_meta($this->id, $key, $single);

        // Default Value
        if(is_null($data) or (is_string($data) and trim($data) === '')) $data = $default;

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function store(array $values): bool
    {
        // Empty Values
        if(Arr::empty($values)) return false;

        // Store All one by one
        foreach($values as $key => $value) $this->save($key, $value);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function save(string $key, $value = null, $prev = null)
    {
        // Term Meta
        if($this->entity === Entity::TERM) return update_term_meta($this->id, $key, $value, $prev);
        // Use Meta
        else if($this->entity === Entity::USER) return update_user_meta($this->id, $key, $value, $prev);
        // Post meta
        else return update_post_meta($this->id, $key, $value, $prev);
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key, $value = null): bool
    {
        // Term Meta
        if($this->entity === Entity::TERM) return delete_term_meta($this->id, $key, $value);
        // Use Meta
        else if($this->entity === Entity::USER) return delete_user_meta($this->id, $key, $value);
        // Post meta
        else return delete_post_meta($this->id, $key, $value);
    }
}
