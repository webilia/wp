<?php

namespace Webilia\WP;

trait AnnouncementFetch
{
    /**
     * @param array<string, mixed> $params
     * @return array{success: bool, items: array<int, array<string, mixed>>}
     */
    public function fetch(array $params = []): array
    {
        $url = add_query_arg(array_merge($params, [
            'products' => $this->getBasenames(),
            'url' => $this->getUrl(),
        ]), $this->getServer());
        $request = wp_remote_get($url, ['timeout' => 5]);
        if (is_wp_error($request) || wp_remote_retrieve_response_code($request) !== 200) {
            return ['success' => false, 'items' => []];
        }

        $body = wp_remote_retrieve_body($request);
        $response = json_decode($body, true);
        // The endpoint returns a JSON list, never an error object.
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($response)
            || substr(ltrim($body), 0, 1) !== '[') {
            return ['success' => false, 'items' => []];
        }

        foreach ($response as $item) {
            if (!is_array($item) || !isset($item['id'], $item['title'])
                || !is_scalar($item['id']) || !is_scalar($item['title'])) {
                return ['success' => false, 'items' => []];
            }
        }

        return ['success' => true, 'items' => $response];
    }
}
