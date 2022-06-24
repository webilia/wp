<?php
namespace Webilia\WP;

use WP_Error;

/**
 * Class File
 * @package Utils
 */
class File
{
    /**
     * @param string $path
     * @return false|string
     */
    public static function read(string $path)
    {
        return file_get_contents($path);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function exists(string $path): bool
    {
        return file_exists($path);
    }

    /**
     * @param string $path
     * @param string $content
     * @return false|int
     */
    public static function write(string $path, string $content)
    {
        return file_put_contents($path, $content);
    }

    /**
     * @param string $path
     * @param string $content
     * @return false|int
     */
    public static function append(string $path, string $content)
    {
        return file_put_contents($path, $content, FILE_APPEND);
    }

    /**
     * @param string $path
     * @return bool
     */
    public static function delete(string $path): bool
    {
        return unlink($path);
    }

    /**
     * @param string $url
     * @return string|WP_Error
     */
    public static function download(string $url)
    {
        $request = wp_remote_get($url);
        $type = wp_remote_retrieve_header($request, 'content-type');

        if(!$type)
        {
            return new WP_Error('failed', sprintf(
                esc_html__('Failed to download %s file.'),
                '<strong>'.$url.'</strong>'
            ));
        }

        return wp_remote_retrieve_body($request);
    }

    /**
     * @param array<string> $file
     * @return int|WP_Error
     */
    public static function upload(array $file)
    {
        // Include the function
        if(!function_exists('wp_handle_upload')) require_once ABSPATH . 'wp-admin/includes/file.php';

        $uploaded = wp_handle_upload($file, ['test_form' => false]);
        if($uploaded and !isset($uploaded['error']))
        {
            $attachment = [
                'post_mime_type' => $uploaded['type'],
                'post_title' => '',
                'post_content' => '',
                'post_status' => 'inherit',
            ];

            // Add as Attachment
            $attachment_id = wp_insert_attachment($attachment, $uploaded['file']);

            // Update Metadata
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata(
                $attachment_id,
                wp_generate_attachment_metadata($attachment_id, $uploaded['file'])
            );

            return $attachment_id;
        }

        return 0;
    }
}
