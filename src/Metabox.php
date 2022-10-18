<?php
namespace Webilia\WP;

use Webilia\WP\Helpers\Arr;
use Webilia\WP\Interfaces\Metabox as MetaboxInterface;
use Webilia\WP\Interfaces\PostType;
use WP_Post;

/**
 * Class Metabox
 * @package Bookup
 */
abstract class Metabox implements MetaboxInterface
{
    const CONTEXT_ADVANCED = 'advanced';
    const CONTEXT_NORMAL = 'normal';
    const CONTEXT_SIDE = 'side';
    const PRIORITY_CORE = 'core';
    const PRIORITY_DEFAULT = 'default';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_LOW = 'low';

    /**
     * @var string
     */
    public string $id;

    /**
     * @var string
     */
    public string $title;

    /**
     * @var string
     */
    public string $context;

    /**
     * @var string
     */
    public string $priority;

    /**
     * @var mixed
     */
    public $callback;

    /**
     * @var string
     */
    public string $PT;

    /**
     * Post Type Definition
     * @var PostType|null
     */
    public ?PostType $post_type;

    /**
     * Constructor
     *
     * @param array<mixed> $args
     */
    public function __construct(array $args)
    {
        $this->id = $args['id'];
        $this->title = $args['title'];
        $this->context = $args['context'] ?? self::CONTEXT_NORMAL;
        $this->priority = $args['priority'] ?? self::PRIORITY_HIGH;
        $this->PT = $args['PT'];
        $this->post_type = $args['post_type'] ?? null;
        $this->callback = Arr::isset_and_array($args, 'callback') ? $args['callback'] : [$this, 'metabox'];
    }

    /**
     * Register Metabox
     *
     * @param string $post_type
     * @param WP_Post $post
     * @return void
     */
    public function register(string $post_type, WP_Post $post): void
    {
        add_meta_box($this->id, $this->title, $this->callback, $this->PT, $this->context, $this->priority);
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        add_action('add_meta_boxes', [$this, 'register'], 10, 2);
    }

    /**
     * @param WP_Post $post
     * @return void
     */
    public function metabox(WP_Post $post): void
    {
        echo $this->output($post->ID);
    }

    /**
     * {@inheritDoc}
     */
    abstract public function output(int $post_id): string;
}
