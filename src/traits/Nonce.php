<?php
namespace Webilia\WP\Traits;

use Webilia\WP\Form;
use Webilia\WP\Helpers\Arr;

trait Nonce
{
    /**
     * Name of security nonce parameter
     * @var string
     */
    protected string $nonce_name = '_wpnonce';

    /**
     * Action of Nonce
     * @var string
     */
    protected string $nonce_action = 'webilia-post';

    /**
     * @return bool
     */
    public function nonce_check(): bool
    {
        // Nonce is not set!
        if(Arr::notset($_REQUEST, $this->nonce_name)) return false;

        // Nonce is not valid!
        if(!wp_verify_nonce(
            sanitize_text_field($_REQUEST[$this->nonce_name]),
            $this->nonce_action
        )) return false;

        return true;
    }

    /**
     * @return string
     */
    public function nonce_field(): string
    {
        // Security Nonce Field
        return Form::nonce($this->nonce_action, $this->nonce_name);
    }
}
