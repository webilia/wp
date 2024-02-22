<?php
namespace Webilia\WP\Plugin;

/**
 * Plugin Licensing Class.
 *
 * @package Plugin
 * @version	1.0.0
 */
class Licensing
{
    const STATUS_VALID = 1;
    const STATUS_INVALID = 2;
    const ERROR_CONNECTION = 3;
    const ERROR_UNKNOWN = 4;

    /**
     * @var string
     */
    private $license_key_option;

    /**
     * @var string
     */
    private $activation_id_option;

    /**
     * @var string
     */
    private $basename;

    /**
     * The plugin remote licensing server
     *
     * @var string
     */
    private $server;

    /**
     * @param string $license_key_option
     * @param string $activation_id_option
     * @param string $basename
     * @param string $server
     */
    public function __construct(
        string $license_key_option,
        string $activation_id_option,
        string $basename,
        string $server = 'https://api.webilia.com/licensing'
    )
    {
        $this->server = $server;
        $this->license_key_option = $license_key_option;
        $this->activation_id_option = $activation_id_option;
        $this->basename = $basename;
    }

    /**
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * @param string $server
     * @return void
     */
    public function setServer(string $server)
    {
        $this->server = $server;
    }

    /**
     * @return string
     */
    public function getLicenseKeyOption(): string
    {
        return $this->license_key_option;
    }

    /**
     * @param string $license_key_option
     * @return void
     */
    public function setLicenseKeyOption(string $license_key_option)
    {
        $this->license_key_option = $license_key_option;
    }

    /**
     * @return string
     */
    public function getActivationIdOption(): string
    {
        return $this->activation_id_option;
    }

    /**
     * @param string $activation_id_option
     * @return void
     */
    public function setActivationIdOption(string $activation_id_option)
    {
        $this->activation_id_option = $activation_id_option;
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * @param string $basename
     * @return void
     */
    public function setBasename(string $basename)
    {
        $this->basename = $basename;
    }

    /**
     * @return mixed
     */
    public function getLicenseKey()
    {
        return get_option($this->license_key_option);
    }

    /**
     * @return mixed
     */
    public function getActivationId()
    {
        return get_option($this->activation_id_option);
    }

    /**
     * Check to See if License is Valid
     *
     * @return bool
     */
    public function isValid(): bool
    {
        // License Key
        $license_key = $this->getLicenseKey();
        if(!trim($license_key)) return false;

        // Activation ID
        $activation_id = $this->getActivationId();
        if(!trim($activation_id)) return false;

        $request = wp_remote_get($this->server, [
            'body' => [
                'action' => 'validate',
                'basename' => $this->basename,
                'code' => $license_key,
                'url' => get_site_url(),
                'activation_id' => $activation_id
            ]
        ]);

        if(!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            $JSON = wp_remote_retrieve_body($request);
            $response = json_decode($JSON, true);

            return isset($response['status']) && $response['status'];
        }

        return false;
    }

    /**
     * @param string $license_key
     * @return mixed[]
     */
    public function activate(string $license_key): array
    {
        $request = wp_remote_get($this->server, [
            'timeout' => 10,
            'body' => [
                'action' => 'activate',
                'basename' => $this->basename,
                'code' => $license_key,
                'url' => get_site_url()
            ]
        ]);

        $activation_id = null;

        if(!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            $JSON = wp_remote_retrieve_body($request);
            $response = json_decode($JSON, true);

            $status = $response['status'] ?? 0;
            $message = $status ? self::STATUS_VALID : self::STATUS_INVALID;
            $activation_id = $response['activation_id'] ?? null;

            // NO JSON Response
            if(!is_array($response)) $message = self::ERROR_CONNECTION;

            if($status)
            {
                // Save License Key
                update_option($this->license_key_option, $license_key);

                // Save Activation ID for Validation
                update_option($this->activation_id_option, $activation_id);
            }
        }
        else
        {
            $status = 0;
            $message = is_wp_error($request) ? $request->get_error_message() : self::ERROR_UNKNOWN;
        }

        return [$status, $message, $activation_id];
    }

    /**
     * @param string $license_key
     * @return bool
     */
    public function deactivate(string $license_key): bool
    {
        $request = wp_remote_get($this->server, [
            'timeout' => 10,
            'body' => [
                'action' => 'deactivate',
                'basename' => $this->basename,
                'code' => $license_key,
                'url' => get_site_url()
            ]
        ]);

        $status = 0;
        if(!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            $JSON = wp_remote_retrieve_body($request);
            $response = json_decode($JSON, true);

            $status = $response['status'] ?? 0;
            if($status)
            {
                // Delete License Key
                delete_option($this->license_key_option);

                // Delete Activation ID
                delete_option($this->activation_id_option);
            }
        }

        return $status;
    }
}
