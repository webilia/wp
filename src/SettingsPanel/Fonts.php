<?php
namespace Webilia\WP\SettingsPanel;

class Fonts
{
    const TRANSIENT = 'webilia_google_fonts_v1';

    /**
     * @param array<mixed> $args
     * @return array<mixed>
     */
    public static function google(array $args = []): array
    {
        $include_inherit = $args['include_inherit'] ?? true;
        $disable_google = apply_filters('webilia_settings_panel_disable_google_fonts', false);

        $fonts = $disable_google ? [] : get_transient(self::TRANSIENT);
        if (!is_array($fonts) || !$fonts)
        {
            if ($disable_google) $fonts = self::fallback_fonts();
            else $fonts = self::fetch_google_fonts();

            if (!$fonts) $fonts = self::fallback_fonts();

            if ($disable_google)
            {
                delete_transient(self::TRANSIENT);
            }
            else
            {
                $ttl = apply_filters('webilia_settings_panel_fonts_transient', WEEK_IN_SECONDS);
                $ttl = is_numeric($ttl) ? (int) $ttl : WEEK_IN_SECONDS;
                if ($ttl <= 0) $ttl = WEEK_IN_SECONDS;

                set_transient(self::TRANSIENT, $fonts, $ttl);
            }
        }

        if ($include_inherit) $fonts = ['inherit' => esc_html__('Inherit', 'webilia-settings-panel')] + $fonts;

        return apply_filters('webilia_settings_panel_google_fonts', $fonts);
    }

    /**
     * @return array<mixed>
     */
    protected static function fetch_google_fonts(): array
    {
        $response = wp_remote_get('https://fonts.google.com/metadata/fonts', [
            'timeout' => 8,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ]);

        if (is_wp_error($response)) return [];

        $body = wp_remote_retrieve_body($response);
        if (!is_string($body) || trim($body) === '') return [];

        $body = trim($body);
        if (strpos($body, ')]}\'') === 0) $body = trim(substr($body, 4));

        $data = json_decode($body, true);
        $list = $data['familyMetadataList'] ?? [];
        if (!is_array($list) || !$list) return [];

        $fonts = [];
        foreach ($list as $item)
        {
            if (!is_array($item)) continue;
            $family = $item['family'] ?? '';
            if (!$family) continue;
            $fonts[$family] = $family;
        }

        return $fonts;
    }

    /**
     * @return array<mixed>
     */
    protected static function fallback_fonts(): array
    {
        return [
            'Poppins' => 'Poppins',
            'Roboto' => 'Roboto',
            'Open Sans' => 'Open Sans',
            'Lato' => 'Lato',
            'Montserrat' => 'Montserrat',
        ];
    }

    /**
     * @param string $opt_name
     * @param array<mixed> $args
     * @return void
     */
    public static function enqueue_selected(string $opt_name, array $args = []): void
    {
        $opt_name = sanitize_key($opt_name);
        if ($opt_name === '') return;

        $disable_google = apply_filters('webilia_settings_panel_disable_google_fonts', false);
        if ($disable_google) return;

        $fields = Panel::get_fields_meta_by_types($opt_name, ['typography']);
        if (!$fields) return;

        $options = get_option($opt_name, []);
        if (!is_array($options)) $options = [];

        $families = [];
        foreach ($fields as $field_id => $meta)
        {
            $value = $options[$field_id] ?? null;
            if (!is_array($value))
            {
                $default = $meta['default'] ?? null;
                $value = is_array($default) ? $default : [];
            }

            $family = self::normalize_family($value['font-family'] ?? '');
            if ($family === '') continue;

            $families[$family] = $family;
        }

        if (!$families) return;

        $display = isset($args['font_display']) ? sanitize_key((string) $args['font_display']) : 'swap';
        if (!in_array($display, ['auto', 'block', 'swap', 'fallback', 'optional'], true)) $display = 'swap';

        $url = self::google_fonts_url(array_values($families), $display);
        if ($url === '') return;

        wp_enqueue_style('wui-sp-settings-panel-google-fonts-' . $opt_name, $url, [], null);
    }

    /**
     * @param mixed $family
     * @return string
     */
    protected static function normalize_family($family): string
    {
        $family = sanitize_text_field((string) $family);
        $family = trim($family, " \t\n\r\0\x0B'\"");
        if ($family === '') return '';

        $parts = explode(',', $family);
        $family = trim((string) ($parts[0] ?? ''));

        if (strpos($family, ':') !== false)
        {
            $parts = explode(':', $family);
            $family = trim((string) ($parts[0] ?? ''));
        }

        if ($family === '') return '';
        if (in_array(strtolower($family), ['inherit', 'initial', 'unset'], true)) return '';
        if (!preg_match('/^[a-z0-9 \-]+$/i', $family)) return '';

        return $family;
    }

    /**
     * @param array<mixed> $families
     * @param string $display
     * @return string
     */
    protected static function google_fonts_url(array $families, string $display): string
    {
        $query = [];
        foreach ($families as $family)
        {
            if (!is_string($family) || $family === '') continue;
            $query[] = 'family=' . str_replace('%20', '+', rawurlencode($family));
        }

        if (!$query) return '';

        $query[] = 'display=' . $display;
        return 'https://fonts.googleapis.com/css2?' . implode('&', $query);
    }
}
