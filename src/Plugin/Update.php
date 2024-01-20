<?php
namespace Webilia\WP\Plugin;

use stdClass;

/**
 * Plugin Update Class.
 *
 * @package Plugin
 * @version	1.0.0
 */
class Update
{
    /**
     * The plugin current version
     * @var string
     */
    private $version;

    /**
     * Plugin Basename
     * @var string
     */
    private $basename;

    /**
     * @var Licensing|null
     */
    private $licensing;

    /**
     * The core version
     * @var string
     */
    private $coreVersion;

    /**
     * The plugin remote update server
     * @var string
     */
    private $server;

    /**
     * Plugin name (plugin_file)
     * @var string
     */
    private $slug;

    /**
     * @param string $version
     * @param string $basename
     * @param Licensing|null $licensing
     * @param string $coreVersion
     * @param string $server
     */
    public function __construct(
        string $version,
        string $basename,
        Licensing $licensing = null,
        string $coreVersion = '',
        string $server = 'https://api.webilia.com/update'
    )
    {
        $this->version = $version;
        $this->licensing = $licensing;
        $this->coreVersion = $coreVersion;
        $this->server = $server;

        $this->setBasename($basename);

        // define the alternative API for updating checking
        add_filter('pre_set_site_transient_update_plugins', [&$this, 'checkUpdate']);

        // Define the alternative response for information checking
        add_filter('plugins_api', [&$this, 'checkInfo'], 10, 3);
    }

    /**
     * @param string $version
     * @return void
     */
    public function setVersion(string $version)
    {
        $this->version = $version;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $basename
     * @return void
     */
    public function setBasename(string $basename)
    {
        $this->basename = $basename;

        $t = explode('/', $this->basename);
        $this->slug = str_replace('.php', '', $t[1]);
    }

    /**
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * @param Licensing $licensing
     * @return void
     */
    public function setLicensing(Licensing $licensing)
    {
        $this->licensing = $licensing;
    }

    /**
     * @return Licensing
     */
    public function getLicensing(): Licensing
    {
        return $this->licensing;
    }

    /**
     * @param string $coreVersion
     * @return void
     */
    public function setCoreVersion(string $coreVersion)
    {
        $this->coreVersion = $coreVersion;
    }

    /**
     * @return string
     */
    public function getCoreVersion(): string
    {
        return $this->coreVersion;
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
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * Add our self-hosted auto-update plugin to the filter transient
     *
     * @param object $transient
     * @return object $transient
     */
    public function checkUpdate($transient)
    {
        if(empty($transient->checked)) return $transient;

        // Get the remote Information
        $info = $this->getRemoteInformation();

        // If a newer version is available, add the update
        if ($info
            && is_object($info)
            && isset($info->new_version)
            && version_compare($this->version, $info->new_version, '<')
            && (!$this->licensing || $this->licensing->isValid())
        )
        {
            $obj = new stdClass();
            $obj->slug = $this->slug;
            $obj->new_version = $info->new_version;
            $obj->url = $info->url ?? '';
            $obj->package = $info->download_link ?? '';
            $obj->tested = $info->tested ?? '';
            $obj->icons = (array) ($info->icons ?? []);

            if(isset($transient->response)) $transient->response[$this->basename] = $obj;
        }

        return $transient;
    }

    /**
     * Add our self-hosted description to the filter
     *
     * @param boolean $false
     * @param mixed[] $action
     * @param object $arg
     * @return bool|object
     */
    public function checkInfo($false, $action, $arg)
    {
        if(isset($arg->slug) and $arg->slug === $this->slug)
        {
            $info = $this->getRemoteInformation();

            if(!is_object($info)) return false;

            $info->icons = (array) $info->icons;
            $info->sections = (array) $info->sections;

            return $info;
        }

        return false;
    }

    /**
     * Get information about the remote version
     * @return bool|object
     */
    public function getRemoteInformation()
    {
        $request = wp_remote_post($this->server, [
            'body' => [
                'action' => 'info',
                'basename' => $this->basename,
                'current' => $this->version,
                'core' => $this->coreVersion,
                'url' => get_site_url(),
            ]
        ]);

        if(!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            return json_decode(wp_remote_retrieve_body($request));
        }

        return false;
    }
}
