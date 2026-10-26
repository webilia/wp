<?php
// Analysis-only declarations for optional UI APIs; never loaded at runtime.
namespace Webilia\UI;
class Html { public static function escape($value): string {} }
class AnnouncementCenter { public static function render(array $props): string {} }
class DeactivationFeedbackDialog { public static function render(array $props): string {} }
namespace Webilia\UI\WordPress;
class Assets { public static function enqueue(array $components, string $packageUrl): void {} }

namespace Webilia\UI;
class Version { public const VALUE = '1.5.0'; }
namespace Webilia\UI\DemoImport;
class Screen { public const LABELS = []; public static function render(array $props = [], string $template = 'page.php'): string {} }
namespace Webilia\UI\SettingsPanel;
class Layout { public const LABELS = []; public static function render(array $props = []): string {} }
class Actions { public static function render(array $props = []): string {} }
class Fields {
    protected static function normalize_args(array $args): array {}
    protected static function resolve_options(array &$args): array {}
    protected static function translate(string $text): string {}
    protected static function trusted_html($html): string {}
    protected static function attachment_url($id): string {}
    protected static function attachment_image_url($id, $size): string {}
public static function label($args = []) {}
public static function input($args = [], $type = 'text') {}
public static function textarea($args = []) {}
public static function select($args = []) {}
public static function checkbox($args = []) {}
public static function checkboxes($args = []) {}
public static function radio($args = []) {}
public static function switcher($args = []) {}
public static function editor($args = []) {}
public static function text($args = []) {}
public static function password($args = []) {}
public static function date($args = []) {}
public static function datetime($args = []) {}
public static function spinner($args = []) {}
public static function slider($args = []) {}
public static function color($args = []) {}
public static function color_rgba($args = []) {}
public static function color_palette($args = []) {}
public static function color_gradient($args = []) {}
public static function color_scheme($args = []) {}
public static function link_color($args = []) {}
public static function background($args = []) {}
public static function border($args = []) {}
public static function box_shadow($args = []) {}
public static function button_set($args = []) {}
public static function button_preview($args = []) {}
public static function dimensions($args = []) {}
public static function divide($args = []) {}
public static function info($args = []) {}
public static function raw($args = []) {}
public static function content($args = []) {}
public static function js_button($args = []) {}
public static function multi_text($args = []) {}
public static function palette($args = []) {}
public static function media($args = []) {}
public static function gallery($args = []) {}
public static function multi_media($args = []) {}
public static function google_maps($args = []) {}
public static function icon_select($args = []) {}
public static function image_select($args = []) {}
public static function select_image($args = []) {}
public static function import_export($args = []) {}
public static function panel_theme($args = []) {}
public static function panel_nav_position($args = []) {}
public static function link_color_scheme($args = []) {}
public static function repeater($args = []) {}
public static function slides($args = []) {}
public static function social_profiles($args = []) {}
public static function sortable($args = []) {}
public static function sorter($args = []) {}
public static function spacing($args = []) {}
public static function section($args = []) {}
public static function tabbed($args = []) {}
public static function typography($args = []) {}
public static function custom_fonts($args = []) {}
public static function customizer($args = []) {}
public static function taxonomy_metaboxes($args = []) {}
public static function user_profile_metaboxes($args = []) {}
public static function widget_areas($args = []) {}
}
