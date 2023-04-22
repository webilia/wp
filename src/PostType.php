<?php
namespace Webilia\WP;

use Webilia\WP\Interfaces\Entity\Post as PostEntity;
use Webilia\WP\Interfaces\PostType as PostTypeInterface;
use Webilia\WP\Traits\Nonce;
use WP_Post;

/**
 * Class Post Type
 *
 * @package Webilia\WP\PostTypes
 */
abstract class PostType implements PostTypeInterface
{
    use Nonce;

    /**
     * @var string
     */
    public string $PT;

    /**
     * Key to access request data
     * @var string|null
     */
    protected ?string $request_key = null;

    /**
     * Constructor
     *
     * @param array<mixed> $args
     */
    public function __construct(array $args)
    {
        $this->PT = $args['PT'];
        $this->nonce_name = $args['nonce_name'] ?? '_wpnonce';
        $this->nonce_action = $args['nonce_action'] ?? $this->PT;
        $this->request_key = $args['request_key'] ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function init(): void
    {
        // Register Post Type
        add_action('init', [$this, 'register']);

        // Endpoints
        add_action('init', [$this, 'endpoints'], 60);

        // Columns
        add_filter('manage_'.$this->PT.'_posts_columns', [$this, 'columns']);
        add_action('manage_'.$this->PT.'_posts_custom_column', [$this, 'content'], 10, 2);

        // Search
        add_action('restrict_manage_posts', [$this, 'filters']);

        // Metaboxes
        add_action('add_meta_boxes', [$this, 'metaboxes'], 10, 2);

        // Save
        add_action('save_post', [$this, 'save'], 10, 2);

        // Delete
        add_action('delete_post', [$this, 'deleted']);

        // New Status
        add_action('transition_post_status', [$this, 'status'], 10, 3);
    }

    /**
     * Register Post Type
     *
     * @return void
     */
    public function register(): void
    {
        register_post_type($this->PT, $this->args());
    }

    /**
     * Register endpoints
     *
     * @return void
     */
    public function endpoints(): void
    {
    }

    /**
     * Register Columns
     *
     * @param array<string> $columns
     * @return array<string>
     */
    public function columns(array $columns): array
    {
        return $columns;
    }

    /**
     * Generate Columns Content
     *
     * @param string $column_name
     * @param int $post_id
     * @return void
     */
    public function content(string $column_name, int $post_id): void
    {
    }

    /**
     * Register Metaboxes
     *
     * @param string $post_type
     * @param WP_Post $post
     * @return void
     */
    public function metaboxes(string $post_type, WP_Post $post): void
    {
    }

    /**
     * Save the Post Type Content
     *
     * @param int $post_id
     * @param WP_Post $post
     * @return void
     */
    abstract public function save(int $post_id, WP_Post $post): void;

    /**
     * {@inheritDoc}
     */
    abstract public function store(PostEntity $entity, array $data): void;

    /**
     * Post Type Deleted
     *
     * @param int $post_id
     * @return void
     */
    public function deleted(int $post_id): void
    {
    }

    /**
     * Status Updated
     *
     * @param string $new_status
     * @param string $old_status
     * @param WP_Post $post
     * @return void
     */
    abstract public function status(string $new_status, string $old_status, WP_Post $post): void;

    /**
     * Register Filter Options
     *
     * @param string $post_type
     * @return void
     */
    public function filters(string $post_type): void
    {
    }

    /**
     * {@inheritDoc}
     */
    abstract public function entity(int $id): PostEntity;
}
