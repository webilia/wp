<?php

namespace Webilia\WP\SettingsPanel;

/** WordPress value/query/editor adapters for the shared UI fields. */
class Form extends \Webilia\UI\SettingsPanel\Fields
{
    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    protected static function normalize_args(array $args): array
    {
        if (isset($args['settings']) && $args['settings'] instanceof Settings)
        {
            $args['option_name'] = $args['settings']->option_name();
        }

        if (isset($args['option_name']) && isset($args['key']))
        {
            if (!isset($args['name']) || $args['name'] === '')
            {
                $args['name'] = $args['option_name'] . '[' . $args['key'] . ']';
            }

            if (!isset($args['id']) || $args['id'] === '')
            {
                $args['id'] = $args['key'];
            }

            if (!array_key_exists('value', $args))
            {
                $option = $args['option'] ?? get_option($args['option_name'], []);
                if (is_array($option) && array_key_exists($args['key'], $option)) $args['value'] = $option[$args['key']];
            }
        }

        return $args;
    }

    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    protected static function resolve_options(array &$args): array
    {
        if (!isset($args['data']) || !is_string($args['data'])) return $args['options'] ?? [];

        $data = $args['data'];
        $options = [];

        switch ($data)
        {
            case 'pages':
                foreach (get_pages() as $page) $options[$page->ID] = $page->post_title;
                $args['webilia_source_label'] = esc_html__('Pages', 'webilia-settings-panel');
                break;

            case 'posts':
                $query = [
                    'post_type' => $args['post_type'] ?? 'post',
                    'posts_per_page' => -1,
                ];
                foreach (get_posts($query) as $post) $options[$post->ID] = $post->post_title;
                $args['webilia_source_label'] = esc_html__('Posts', 'webilia-settings-panel');
                break;

            case 'users':
                foreach (get_users() as $user) $options[$user->ID] = $user->display_name;
                $args['webilia_source_label'] = esc_html__('Users', 'webilia-settings-panel');
                break;

            case 'categories':
                foreach (get_categories(['hide_empty' => false]) as $term) $options[$term->term_id] = $term->name;
                $args['webilia_source_label'] = esc_html__('Categories', 'webilia-settings-panel');
                break;

            case 'tags':
                foreach (get_terms(['taxonomy' => 'post_tag', 'hide_empty' => false]) as $term) $options[$term->term_id] = $term->name;
                $args['webilia_source_label'] = esc_html__('Tags', 'webilia-settings-panel');
                break;

            case 'taxonomies':
                foreach (get_taxonomies([], 'objects') as $taxonomy) $options[$taxonomy->name] = $taxonomy->label;
                $args['webilia_source_label'] = esc_html__('Taxonomies', 'webilia-settings-panel');
                break;

            case 'post_types':
                foreach (get_post_types(['public' => true], 'objects') as $post_type) $options[$post_type->name] = $post_type->label;
                $args['webilia_source_label'] = esc_html__('Post Types', 'webilia-settings-panel');
                break;

            default:
                $options = $args['options'] ?? [];
        }

        if (isset($args['webilia_source_label'])) $args['webilia_source_count'] = count($options);
        return $options;
    }

    /**
     * @param mixed $args
     * @return mixed
     */
    public static function editor($args = [])
    {
        $args = static::normalize_args($args);
        if (!count($args)) return false;

        $value = (isset($args['value']) ? stripslashes($args['value']) : '');
        $id = (isset($args['id']) ? esc_attr($args['id']) : '');

        $name = (isset($args['name']) ? esc_attr($args['name']) : '');
        $args['textarea_name'] = $name;
        $args['textarea_rows'] = (isset($args['rows']) ? (int) $args['rows'] : 10);

        ob_start();
        wp_editor($value, $id, $args);
        return ob_get_clean();
    }

    /**
     * @param mixed $args
     * @return mixed
     */
    public static function customizer($args = [])
    {
        $label = $args['label'] ?? esc_html__('Open Customizer', 'webilia-settings-panel');
        $url = admin_url('customize.php');
        return '<a class="button" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
    }

    /**
     * @param mixed $args
     * @return mixed
     */
    public static function widget_areas($args = [])
    {
        $sidebars = $GLOBALS['wp_registered_sidebars'] ?? [];
        $options = [];
        foreach ($sidebars as $sidebar) $options[$sidebar['id']] = $sidebar['name'];

        $args['options'] = $options;
        return static::select($args);
    }
    /**
     * @param string $text
     * @return string
     */
    protected static function translate(string $text): string { return esc_html__($text, 'webilia-settings-panel'); }
    /**
     * @param mixed $html
     * @return string
     */
    protected static function trusted_html($html): string { return wp_kses_post($html); }
    /**
     * @param mixed $id
     * @return string
     */
    protected static function attachment_url($id): string { return (string) wp_get_attachment_url($id); }
    /**
     * @param mixed $id
     * @param mixed $size
     * @return string
     */
    protected static function attachment_image_url($id, $size): string { return (string) wp_get_attachment_image_url($id, $size); }
    /**
     * @param mixed $args
     * @return mixed
     */
    public static function typography($args = [])
    {
        if (empty($args['fonts'])) $args['fonts'] = Fonts::google();
        return parent::typography($args);
    }
}
