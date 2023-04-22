<?php
namespace Webilia\WP;

/**
 * Kses Class
 *
 * @package WordPress
 */
class Kses
{
    /**
     * @var array<string>
     */
    private static ?array $allowed_html_form = null;

    /**
     * @var array<string>
     */
    private static ?array $allowed_html_element = null;

    /**
     * @var array<string>
     */
    private static ?array $allowed_html_rich = null;

    /**
     * @var array<string>
     */
    private static ?array $allowed_html_embed = null;

    /**
     * @var array<string>
     */
    private static ?array $allowed_html_page = null;

    /**
     * @var array<mixed>
     */
    private static array $allowed_attrs = [
        'accept-charset'    => 1,
        'action'            => 1,
        'alt'               => 1,
        'allow'             => 1,
        'allowfullscreen'   => 1,
        'align'             => 1,
        'aria-*'            => 1,
        'autocomplete'      => 1,
        'bgcolor'           => 1,
        'border'            => 1,
        'cellpadding'       => 1,
        'cellspacing'       => 1,
        'checked'           => 1,
        'class'             => 1,
        'cols'              => 1,
        'content'           => 1,
        'data-*'            => 1,
        'dir'               => 1,
        'disabled'          => 1,
        'enctype'           => 1,
        'for'               => 1,
        'frameborder'       => 1,
        'height'            => 1,
        'href'              => 1,
        'id'                => 1,
        'itemprop'          => 1,
        'itemscope'         => 1,
        'itemtype'          => 1,
        'label'             => 1,
        'lang'              => 1,
        'leftmargin'        => 1,
        'marginheight'      => 1,
        'marginwidth'       => 1,
        'max'               => 1,
        'maxlength'         => 1,
        'media'             => 1,
        'method'            => 1,
        'min'               => 1,
        'multiple'          => 1,
        'name'              => 1,
        'novalidate'        => 1,
        'placeholder'       => 1,
        'property'          => 1,
        'readonly'          => 1,
        'rel'               => 1,
        'required'          => 1,
        'rows'              => 1,
        'selected'          => 1,
        'src'               => 1,
        'size'              => 1,
        'style'             => 1,
        'step'              => 1,
        'tabindex'          => 1,
        'target'            => 1,
        'title'             => 1,
        'topmargin'         => 1,
        'type'              => 1,
        'value'             => 1,
        'width'             => 1,
    ];

    /**
     * Kses constructor
     */
    public function __construct()
    {
    }

    /**
     * @return void
     */
    public function init(): void
    {
        add_filter(self::filter_name(), [$this, 'tags'], 10, 2);
    }

    /**
     * @param string $html
     * @return string
     */
    public static function page(string $html): string
    {
        if(is_null(self::$allowed_html_page))
        {
            $allowed = wp_kses_allowed_html('post');
            self::$allowed_html_page = apply_filters(self::filter_name(), $allowed, 'page');
        }

        return wp_kses($html, self::$allowed_html_page);
    }

    /**
     * @param string $html
     * @return string
     */
    public static function form(string $html): string
    {
        if(is_null(self::$allowed_html_form))
        {
            $allowed = wp_kses_allowed_html('post');
            self::$allowed_html_form = apply_filters(self::filter_name(), $allowed, 'form');
        }

        return wp_kses($html, self::$allowed_html_form);
    }

    /**
     * @param string $html
     * @return string
     */
    public static function element(string $html): string
    {
        if(is_null(self::$allowed_html_element))
        {
            $allowed = wp_kses_allowed_html('post');
            self::$allowed_html_element = apply_filters(self::filter_name(), $allowed, 'element');
        }

        return wp_kses($html, self::$allowed_html_element);
    }

    /**
     * Element + Embed
     *
     * @param string $html
     * @return string
     */
    public static function rich(string $html): string
    {
        if(is_null(self::$allowed_html_rich))
        {
            $allowed = wp_kses_allowed_html('post');
            self::$allowed_html_rich = apply_filters(self::filter_name(), $allowed, 'rich');
        }

        return wp_kses($html, self::$allowed_html_rich);
    }

    /**
     * Only Embed
     *
     * @param string $html
     * @return string
     */
    public static function embed(string $html): string
    {
        if(is_null(self::$allowed_html_embed))
        {
            self::$allowed_html_embed = apply_filters(self::filter_name(), [], 'embed');
        }

        return wp_kses($html, self::$allowed_html_embed);
    }

    /**
     * @param array<mixed> $tags
     * @param string $context
     * @return array<mixed>
     */
    public static function tags(array $tags, string $context): array
    {
        if(in_array($context, ['form', 'page']))
        {
            $tags['form'] = self::$allowed_attrs;
            $tags['label'] = self::$allowed_attrs;
            $tags['input'] = self::$allowed_attrs;
            $tags['select'] = self::$allowed_attrs;
            $tags['option'] = self::$allowed_attrs;
            $tags['optgroup'] = self::$allowed_attrs;
            $tags['textarea'] = self::$allowed_attrs;
            $tags['button'] = self::$allowed_attrs;
            $tags['fieldset'] = self::$allowed_attrs;
            $tags['output'] = self::$allowed_attrs;
        }

        if(in_array($context, ['embed', 'rich']))
        {
            if(!isset($tags['iframe'])) $tags['iframe'] = self::$allowed_attrs;
        }

        return $tags;
    }

    /**
     * @return string
     */
    public static function filter_name(): string
    {
        return 'webilia_kses_tags';
    }
}
