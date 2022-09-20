<?php
namespace Webilia\WP;

use Webilia\WP\Helpers\Arr;

/**
 * Option Class
 *
 * @package WordPress
 */
abstract class Option
{
    /**
     * @var string
     */
    protected string $key;

    /**
     * @var mixed
     */
    protected $default;

    /**
     * @var boolean
     */
    protected bool $autoload = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->key = $this->key();
        $this->default = $this->default();
        $this->autoload = $this->autoload();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        return get_option($this->key, $this->default);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function set($value): bool
    {
        return update_option($this->key, $value, $this->autoload);
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public function merge($value): bool
    {
        // Get Styles
        $current = $this->get();
        if(Arr::not($current)) $current = [];

        // Merge
        $final = array_merge($current, $value);

        // Save
        return update_option($this->key, $final, $this->autoload);
    }

    /**
     * @return bool
     */
    public function delete(): bool
    {
        return delete_option($this->key);
    }

    /**
     * @return bool
     */
    public function autoload(): bool
    {
        return false;
    }

    /**
     * @return mixed
     */
    public function default()
    {
        return [];
    }

    /**
     * @return string
     */
    abstract public function key(): string;
}
