<?php
namespace Webilia\WP;

class EmailSubscription
{
    /**
     * Subscribe an email address to Webilia.
     *
     * @param array $args {
     * @type string $email Required. Email address of the subscriber.
     * @type string $basename Required. Plugin basename.
     * @type string $first_name Optional. Subscriber first name.
     * @type string $last_name Optional. Subscriber last name.
     * }
     *
     * @return array ['success' => bool, 'message' => string]
     */
    public static function subscribe(array $args): array
    {
        $email = isset($args['email']) ? sanitize_email($args['email']) : '';
        $basename = isset($args['basename']) ? sanitize_text_field($args['basename']) : '';
        $first_name = isset($args['first_name']) ? sanitize_text_field($args['first_name']) : '';
        $last_name = isset($args['last_name']) ? sanitize_text_field($args['last_name']) : '';

        if (!is_email($email) || trim($basename) === '') return ['success' => false, 'message' => 'Invalid data supplied.'];

        $body = [
            'email' => $email,
            'basename' => $basename,
            'url' => get_site_url(),
        ];

        if ($first_name !== '') $body['first_name'] = $first_name;
        if ($last_name !== '') $body['last_name'] = $last_name;

        $response = wp_remote_post('https://api.webilia.com/subscribers', [
            'body' => $body,
        ]);

        if (is_wp_error($response)) return ['success' => false, 'message' => $response->get_error_message()];

        $code = wp_remote_retrieve_response_code($response);
        $res_body = wp_remote_retrieve_body($response);
        $decoded = json_decode($res_body, true);

        if ($code === 200 && is_array($decoded))
        {
            $success = isset($decoded['success']) && $decoded['success'];
            $message = $decoded['message'] ?? '';

            return ['success' => $success, 'message' => $message];
        }

        return ['success' => false, 'message' => 'Unexpected response from API.'];
    }
}
