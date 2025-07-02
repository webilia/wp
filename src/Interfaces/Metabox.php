<?php
namespace Webilia\WP\Interfaces;

interface Metabox extends Initiable
{
    /**
     * Metabox Title
     *
     * @return string
     */
    public function title(): string;

    /**
     * Metabox Output
     *
     * @param int $post_id
     * @return string
     */
    public function output(int $post_id): string;
}
