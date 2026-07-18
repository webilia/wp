<?php
namespace Webilia\WP;

class Announcements
{
    /**
     * Product basename.
     *
     * @var string
     */
    private $basename;

    /**
     * Announcements server URL.
     *
     * @var string
     */
    private $server;

    /**
     * Site URL.
     *
     * @var string
     */
    private $url;

    /**
     * Constructor.
     *
     * @param string $basename
     * @param string|null $url
     * @param string $server
     */
    public function __construct(
        string $basename,
        ?string $url = null,
        string $server = 'https://api.webilia.com/announcements'
    )
    {
        $this->setBasename($basename);
        $this->setUrl($url ?? get_site_url());
        $this->setServer($server);
    }

    /**
     * Set product basename.
     *
     * @param string $basename
     * @return void
     */
    public function setBasename(string $basename)
    {
        $this->basename = sanitize_text_field($basename);
    }

    /**
     * Get product basename.
     *
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basename;
    }

    /**
     * Set site URL.
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->url = esc_url_raw($url);
    }

    /**
     * Get site URL.
     *
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Set announcements server URL.
     *
     * @param string $server
     * @return void
     */
    public function setServer(string $server)
    {
        $this->server = esc_url_raw($server);
    }

    /**
     * Get announcements server URL.
     *
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * Fetch announcements from remote server.
     *
     * @param mixed[] $params
     * @return mixed[]
     */
    public function getAnnouncements(array $params = []): array
    {
        $request = wp_remote_get($this->server, [
            'body' => array_merge([
                'product' => $this->basename,
                'url' => $this->url,
            ], $params),
        ]);

        $announcements = [];
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            $body = wp_remote_retrieve_body($request);
            $response = json_decode($body, true);

            if (is_array($response)) $announcements = $response;
        }

        return $announcements;
    }
}
