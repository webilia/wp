<?php
namespace Webilia\WP;

/**
 * Form Class
 *
 * @package Utils
 */
class Form
{
    /**
     * Constructor method
     */
	public function __construct()
    {
	}

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function label(array $args): string
    {
        if(!count($args)) return '';

        $required = (isset($args['required']) and $args['required']);

        return '<label
            for="' . (isset($args['for']) ? esc_attr($args['for']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '">' .
            esc_html($args['title']) . ($required ? ' ' . self::required_star() : '') . '
        </label>';
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function text(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'text');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function number(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'number');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function url(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'url');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function tel(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'tel');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function email(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'email');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function datepicker(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'date');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function checkbox(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'checkbox');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function file(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'file');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function hidden(array $args): string
    {
        if(!count($args)) return '';
        return self::input($args, 'hidden');
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function separator(array $args = []): string
    {
        return '<div class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '">
            ' . (isset($args['label']) ? esc_html($args['label']) : '') . '
        </div>';
    }

    /**
     * @param array<string> $args
     * @param string $type
     * @return string
     */
    public static function input(array $args, string $type = 'text'): string
    {
        if(!count($args)) return '';

        $attributes = self::concat_attributes($args);

        $required = (isset($args['required']) and $args['required']);
        return '<input
            type="' . esc_attr($type) . '"
            name="' . (isset($args['name']) ? esc_attr($args['name']) : '') . '"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '"
            value="' . (isset($args['value']) ? esc_attr($args['value']) : '') . '"
            placeholder="' . (isset($args['placeholder']) ? esc_attr($args['placeholder']) : '') . '" ' .
            trim($attributes) . ' ' .
            ($required ? 'required' : '') . '
        >';
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function dropdown(array $args): string
    {
        return self::select($args);
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function select(array $args): string
    {
        if(!count($args)) return '';

        $options = '';

        // Show Empty Option
        if(isset($args['show_empty']) and $args['show_empty'])
        {
            $empty_label = (isset($args['empty_label']) ? esc_html($args['empty_label']) : '-----');
            $options .= '<option
                value="" ' .
                ((isset($args['value']) and !is_array($args['value']) and $args['value'] == '') ? 'selected' : '') . '>' .
                $empty_label . '
            </option>';
        }

        foreach($args['options'] as $value => $label)
        {
            $options .= '<option
                value="' . esc_attr($value) . '" ' .
                (
                    (
                        isset($args['value'])
                        and (
                            (!is_array($args['value']) and trim($args['value']) !== '' and $args['value'] === $value)
                            or (is_array($args['value']) and in_array($value, $args['value']))
                        )
                    ) ? 'selected' : ''
                ) . '>' .
                esc_html($label) . '
            </option>';
        }

        $attributes = self::concat_attributes($args);

        // Required
        $required = (isset($args['required']) and $args['required']) ? true : false;

        return '<select
            name="' . (isset($args['name']) ? esc_attr($args['name']) : '') . '"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '" ' .
            trim($attributes) . ' ' . ($required ? 'required' : '') . '
        >' .
            $options . '                       
        </select>';
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function checkboxes(array $args): string
    {
        if(!count($args)) return '';

        $attributes = self::concat_attributes($args);

        // Required
        $required = (isset($args['required']) and $args['required']) ? true : false;

        $checkboxes = '';
        foreach($args['options'] as $value => $label)
        {
            $checkboxes .= '<li>
                <label>
                    <input
                        name="' . (isset($args['name']) ? esc_attr($args['name']) : '') . '"
                        type="checkbox"
                        value="' . esc_attr($value) . '" ' .
                        ((isset($args['value']) and is_array($args['value']) and in_array($value, $args['value'])) ? 'checked' : '') . ' ' .
                        trim($attributes) . ' ' . ($required ? 'required' : '') . '
                    >' .
                    esc_html($label) . '
                </label>
            </li>';
        }

        return '<ul class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '">' . $checkboxes . '</ul>';
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function textarea(array $args): string
    {
        if(!count($args)) return '';

        $required = (isset($args['required']) and $args['required']) ? true : false;
        return '<textarea
            name="' . esc_attr($args['name']) . '"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '"
            placeholder="' . (isset($args['placeholder']) ? esc_attr($args['placeholder']) : '') . '"
            rows="' . (isset($args['rows']) ? esc_attr($args['rows']) : '') . '" ' .
            ($required ? 'required' : '') . '
        >' . (isset($args['value']) ? esc_textarea(stripslashes($args['value'])) : '') . '</textarea>';
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function editor(array $args): string
    {
        if(!count($args)) return '';

        $value = isset($args['value']) ? stripslashes($args['value']) : '';
        $id = isset($args['id']) ? esc_attr($args['id']) : '';

        $name = isset($args['name']) ? esc_attr($args['name']) : '';
        $args['textarea_name'] = $name;

        $args['textarea_rows'] = isset($args['rows']) ? (int) $args['rows'] : 10;

        ob_start();
        wp_editor($value, $id, $args);
        return (string) ob_get_clean();
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function colorpicker(array $args): string
    {
        if(!count($args)) return '';

        return '<input
            type="text"
            name="' . esc_attr($args['name']) . '"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '"
            value="' . (isset($args['value']) ? esc_attr($args['value']) : '') . '"
            data-default-color="' . (isset($args['default']) ? esc_attr($args['default']) : '') . '"
        >';
    }

    /**
     * @param string $taxonomy
     * @param array<string> $args
     * @return string
     */
    public static function taxonomy(string $taxonomy, array $args = []): string
    {
        // Get Terms
        $terms = get_terms([
            'taxonomy' => $taxonomy,
            'orderby' => (isset($args['orderby']) ? $args['orderby'] : 'name'),
            'order' => (isset($args['order']) ? $args['order'] : 'ASC'),
            'hide_empty' => (isset($args['hide_empty']) ? $args['hide_empty'] : false),
        ]);

        if(is_wp_error($terms)) return '';

        $options = [];
        foreach($terms as $term)
        {
            if(isset($args['name_as_value']) and $args['name_as_value']) $options[$term->name] = $term->name;
            else $options[$term->term_id] = $term->name;
        }

        // Options
        $args['options'] = $options;

        // Dropdown Field
        return self::select($args);
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function pages(array $args): string
    {
        if(!count($args)) return '';

        // Get WordPress Pages
        $pages = get_pages();

        // Not array of pages
        if(!is_array($pages)) return '';

        $options = [];
        foreach($pages as $page) $options[$page->ID] = $page->post_title;

        $args['options'] = $options;

        // Dropdown Field
        return self::select($args);
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function posts(array $args): string
    {
        if(!count($args)) return '';

        $post_type = $args['post_type'] ?? 'post';
        $post_per_page = $args['post_per_page'] ?? -1;

        $options = '';
        $query = ['post_type' => $post_type, 'posts_per_page' => $post_per_page];
        $posts = get_posts($query);

        // Not array of posts
        if(!is_array($posts)) return '';

        // Show Empty Option
        if(isset($args['show_empty']) and $args['show_empty'])
        {
            $options .= '<option
                value="" '
                . ((isset($args['value']) and esc_attr($args['value']) == '') ? 'selected="selected"' : '')
            . '>' . ((isset($args['empty_label']) and trim($args['empty_label'])) ? $args['empty_label'] : '-----') . '</option>';
        }

        foreach($posts as $post)
        {
            $options .= '<option
                value="' . absint($post->ID) . '" ' .
                ((isset($args['value']) and $args['value'] == $post->ID) ? 'selected="selected"' : '') . '
            >' . esc_html($post->post_title) . '</option>';
        }

        return '<select
            name="' . esc_attr($args['name']) . '"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : '') . '"
        >' . $options . '</select>';
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function users(array $args): string
    {
        if(!count($args)) return '';

        $args['echo'] = 0;

        return wp_dropdown_users($args);
    }

    /**
     * @param array<string> $args
     * @return string
     */
    public static function submit(array $args = []): string
    {
        if(!count($args)) return '';

        return self::button($args, 'submit');
    }

    /**
     * @param array<string> $args
     * @param string $type
     * @return string
     */
    public static function button(array $args = [], $type = 'button'): string
    {
        if(!count($args)) return '';

        return '<button
            type="'.esc_attr($type).'"
            id="' . (isset($args['id']) ? esc_attr($args['id']) : '') . '"
            class="' . (isset($args['class']) ? esc_attr($args['class']) : 'button button-primary') . '"
        >' .
                esc_html($args['label']) . '
        </button>';
    }

    /**
     * @param string $action
     * @param string $name
     * @return string
     */
    public static function nonce(string $action, string $name = '_wpnonce'): string
    {
        return wp_nonce_field($action, $name, true, false);
    }

    /**
     * @return string
     */
    public static function required_star(): string
    {
        return '<span class="required">*</span>';
    }

    /**
     * @param array<mixed> $args
     * @return string
     */
    public static function concat_attributes(array $args): string
    {
        $attributes = '';
        if(isset($args['attributes']) and is_array($args['attributes']) and count($args['attributes']))
        {
            foreach($args['attributes'] as $key => $value)
            {
                $attributes .= $key . '="' . esc_attr($value) . '" ';
            }
        }

        return $attributes;
    }
}
