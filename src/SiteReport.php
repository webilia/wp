<?php
namespace Webilia\WP;

class SiteReport
{
    /**
     * Send a report to Webilia.
     *
     * @param string $basename Required. Plugin basename.
     * @param string $url Required. Site URL.
     * @param array $report Required. Report data.
     *
     * @return bool
     */
    public static function send(string $basename, string $url, array $report): bool
    {
        $basename = sanitize_text_field($basename);
        $url = esc_url_raw($url);

        if ($basename === '' || $url === '' || empty($report)) return false;

        $response = wp_remote_post('https://api.webilia.com/report', [
            'body' => [
                'basename' => $basename,
                'url' => $url,
                'report' => $report,
            ],
        ]);

        if (is_wp_error($response)) return false;

        $code = wp_remote_retrieve_response_code($response);

        return $code === 200;
    }
}
