<?php
namespace Webilia\WP;

class Ads
{
    /**
     * Solution ID.
     *
     * @var integer
     */
    private $solution_id;

    /**
     * Ads server URL.
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
     * @param int $solution_id
     * @param string|null $url
     * @param string $server
     */
    public function __construct(int $solution_id, ?string $url = null, string $server = 'https://api.webilia.com/ads')
    {
        $this->solution_id = $solution_id;
        $this->url = $url ?? get_site_url();
        $this->server = $server;
    }

    /**
     * Set solution ID.
     *
     * @param int $solution_id
     * @return void
     */
    public function setSolutionId(int $solution_id)
    {
        $this->solution_id = $solution_id;
    }

    /**
     * Get solution ID.
     *
     * @return int
     */
    public function getSolutionId(): int
    {
        return $this->solution_id;
    }

    /**
     * Set site URL.
     *
     * @param string $url
     * @return void
     */
    public function setUrl(string $url)
    {
        $this->url = $url;
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
     * Set ads server URL.
     *
     * @param string $server
     * @return void
     */
    public function setServer(string $server)
    {
        $this->server = $server;
    }

    /**
     * Get ads server URL.
     *
     * @return string
     */
    public function getServer(): string
    {
        return $this->server;
    }

    /**
     * Fetch ads from remote server.
     *
     * @param mixed[] $params
     * @return mixed[]
     */
    public function getAds(array $params = []): array
    {
        $request = wp_remote_get($this->server, [
            'body' => array_merge([
                'solution_id' => $this->solution_id,
                'url' => $this->url,
            ], $params),
        ]);

        $ads = [];
        if (!is_wp_error($request) && wp_remote_retrieve_response_code($request) === 200)
        {
            $body = wp_remote_retrieve_body($request);
            $response = json_decode($body, true);

            if (is_array($response)) $ads = $response;
        }

        return $ads;
    }
}
