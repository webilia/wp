<?php
namespace Webilia\WP\Interfaces;

/**
 * Interface Metabox
 * @package Webilia\WP\Interfaces
 */
interface Metabox extends Initiable
{
    /**
     * Metabox Output
     *
     * @param int $post_id
     * @return string
     */
    public function output(int $post_id): string;
}
