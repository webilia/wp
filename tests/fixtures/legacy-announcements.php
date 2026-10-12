<?php
namespace Webilia\WP;

class Announcements
{
    /**
     * Product basenames.
     *
     * @var string[]
     */
    private $basenames = [];

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
     * @param string|string[] $basenames
     * @param string|null $url
     * @param string $server
     */
    public function __construct(
        $basenames,
        ?string $url = null,
        string $server = 'https://api.webilia.com/announcements'
    )
    {
        $this->setBasenames((array) $basenames);
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
        $this->setBasenames([$basename]);
    }

    /**
     * Set product basenames.
     *
     * @param string[] $basenames
     * @return void
     */
    public function setBasenames(array $basenames)
    {
        $this->basenames = [];

        foreach ($basenames as $basename)
        {
            $basename = sanitize_text_field((string) $basename);
            if ($basename === '' || in_array($basename, $this->basenames, true)) continue;

            $this->basenames[] = $basename;
        }
    }

    /**
     * Get product basename.
     *
     * @return string
     */
    public function getBasename(): string
    {
        return $this->basenames[0] ?? '';
    }

    /**
     * Get product basenames.
     *
     * @return string[]
     */
    public function getBasenames(): array
    {
        return $this->basenames;
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
        $url = add_query_arg(array_merge($params, [
            'products' => $this->basenames,
            'url' => $this->url,
        ]), $this->server);

        $request = wp_remote_get($url);

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
