<?php
// Focused WordPress doubles. Real UI source is supplied by the companion checkout.
$uiPath = getenv('WEBILIA_UI_PATH') ?: dirname(__DIR__, 2) . '/webilia-ui';
spl_autoload_register(static function ($class) use ($uiPath) {
    foreach (['Webilia\\WP\\' => dirname(__DIR__) . '/src/', 'Webilia\\UI\\' => $uiPath . '/src/'] as $prefix => $base) {
        if (strpos($class, $prefix) !== 0) continue;
        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($file)) require $file;
    }
});
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$GLOBALS['hooks'] = []; $GLOBALS['options'] = []; $GLOBALS['transients'] = [];
$GLOBALS['styles'] = []; $GLOBALS['scripts'] = []; $GLOBALS['capabilities'] = [];
$GLOBALS['admin'] = true; $GLOBALS['customizer'] = false;
define('ABSPATH', __DIR__ . '/fixtures/'); define('HOUR_IN_SECONDS', 3600); define('WEEK_IN_SECONDS', 604800);
class WP_Error {
    private $code; private $message;
    public function __construct($code, $message) { $this->code = $code; $this->message = $message; }
    public function get_error_code() { return $this->code; }
    public function get_error_message() { return $this->message; }
}
class WP_Importer {}
class JsonResponse extends RuntimeException {
    public $data; public $status;
    public function __construct($data, $status) { $this->data = $data; $this->status = $status; }
}
class WP_Customize_Manager {
    public $panels = []; public $sections = []; public $settings = []; public $controls = [];
    public function add_panel($id, $args) { $this->panels[$id] = $args; }
    public function add_section($id, $args) { $this->sections[$id] = $args; }
    public function add_setting($id, $args) { $this->settings[$id] = $args; }
    public function add_control($control) { $this->controls[$control->id] = $control; }
}
class WP_Customize_Control {
    public $id; public $params;
    public function __construct($manager, $id, $args) { $this->id = $id; $this->params = $args; }
    public function value() { return $GLOBALS['control_value'] ?? ''; }
    public function get_link() { return 'data-customize-setting-link="' . esc_attr($this->id) . '"'; }
    public function json() { return $this->params; }
}
function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][] = [$callback, $args]; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { add_action($hook, $callback, $priority, $args); }
function do_action($hook, ...$args) { foreach ($GLOBALS['hooks'][$hook] ?? [] as $entry) call_user_func_array($entry[0], array_slice($args, 0, $entry[1])); }
function apply_filters($hook, $value, ...$args) { foreach ($GLOBALS['hooks'][$hook] ?? [] as $entry) $value = call_user_func_array($entry[0], array_slice(array_merge([$value], $args), 0, $entry[1])); return $value; }
function get_option($name, $default = false) { return apply_filters('option_' . $name, $GLOBALS['options'][$name] ?? $default); }
function update_option($name, $value) { $old = $GLOBALS['options'][$name] ?? null; $GLOBALS['options'][$name] = $value; do_action('update_option_' . $name, $old, $value); return true; }
function get_transient($name) { return $GLOBALS['transients'][$name] ?? false; }
function set_transient($name, $value, $ttl) { $GLOBALS['transients'][$name] = $value; }
function delete_transient($name) { unset($GLOBALS['transients'][$name]); }
function current_user_can($capability) { return $GLOBALS['capabilities'][$capability] ?? true; }
function __($text, $domain = '') { return $GLOBALS['translations'][$text] ?? $text; }
function esc_html__($text, $domain = '') { return esc_html($text); }
function esc_attr__($text, $domain = '') { return esc_attr($text); }
function esc_html($value) { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_attr($value) { return esc_html($value); }
function esc_textarea($value) { return esc_html($value); }
function esc_url($value) { return \Webilia\UI\Html::url($value); }
function sanitize_key($value) { return preg_replace('/[^a-z0-9_-]/', '', strtolower((string) $value)); }
function sanitize_html_class($value) { return preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $value); }
function sanitize_text_field($value) { return trim(strip_tags((string) $value)); }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_kses_post($value) { return strip_tags((string) $value, '<p><strong><span><ul><li><h3><button>'); }
function wp_unslash($value) { return is_string($value) ? stripslashes($value) : $value; }
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
function wp_parse_args($value, $defaults) { return array_merge($defaults, $value); }
function absint($value) { return abs((int) $value); }
function wp_create_nonce($action) { return $action; }
function check_ajax_referer($action, $field, $die = true) {
    if (($_POST[$field] ?? '') === $action) return true;
    if (!$die) return false;
    wp_send_json_error(['message' => 'invalid_nonce'], 403);
}
function check_admin_referer($action) { check_ajax_referer($action, '_wpnonce'); }
function wp_send_json($data, $status = 200) { throw new JsonResponse($data, $status); }
function wp_send_json_error($data = null, $status = 200) { wp_send_json(['success' => false, 'data' => $data], $status); }
function wp_send_json_success($data = null) { wp_send_json(['success' => true, 'data' => $data]); }
function response($callback) { try { $callback(); } catch (JsonResponse $result) { return $result; } throw new RuntimeException('Expected JSON response'); }
function is_wp_error($value) { return $value instanceof WP_Error; }
function is_admin() { return $GLOBALS['admin']; }
function is_customize_preview() { return $GLOBALS['customizer']; }
function doing_action($action) { return ($GLOBALS['doing'] ?? '') === $action; }
function admin_url($path = '') { return 'https://site.test/wp-admin/' . $path; }
function home_url($path = '') { return 'https://site.test/' . $path; }
function get_template() { return 'example-theme'; }
function set_theme_mod($key, $value) { $GLOBALS['mods'][$key] = $value; }
function get_current_user_id() { return 4; }
function get_temp_dir() { return sys_get_temp_dir() . '/'; }
function wp_http_validate_url($url) { return strpos($url, 'https://') === 0; }
function wp_enqueue_style($handle, $url = '', $deps = [], $version = null, $media = null) { $GLOBALS['styles'][$handle] = compact('url', 'deps', 'version'); }
function wp_enqueue_script($handle, $url = '', $deps = [], $version = null, $footer = false) { $GLOBALS['scripts'][$handle] = compact('url', 'deps', 'version'); }
function wp_enqueue_media() { $GLOBALS['media_enqueued'] = true; }
function wp_localize_script($handle, $name, $value) { $GLOBALS['localized'][$name] = $value; }
function settings_fields($slug) { echo '<input name="option_page" value="' . esc_attr($slug) . '">'; }
function wp_nonce_field($action, $name = '_wpnonce', $referer = true, $echo = true) { $html = '<input name="' . $name . '" value="' . $action . '">'; if ($echo) echo $html; return $html; }
function get_pages() { return [(object) ['ID' => 2, 'post_title' => 'About']]; }
function wp_get_attachment_url($id) { return 'https://site.test/uploads/' . $id . '.png'; }
function wp_get_attachment_image_url($id, $size) { return wp_get_attachment_url($id); }
function wp_editor($value, $id, $args) { echo '<textarea data-wp-editor="' . $id . '" name="' . $args['textarea_name'] . '">' . esc_html($value) . '</textarea>'; }
function is_plugin_active($file) { return !empty($GLOBALS['active_plugins'][$file]); }
function get_plugins() { return $GLOBALS['plugins'] ?? ['example/example.php' => ['Name' => 'Example']]; }
function activate_plugin($file) { $GLOBALS['active_plugins'][$file] = true; return null; }
function register_setting($group, $name, $callback) { $GLOBALS['registered_settings'][$name] = $callback; }
function add_settings_section($id, $title, $callback, $page) {}
function add_settings_field($id, $title, $callback, $page, $section, $args) {}
function __return_false() { return false; }
function __return_true() { return true; }
function download_url($url, $timeout) {
    if (!isset($GLOBALS['downloads'][$url])) return new WP_Error('download_failed', 'Fixture download failed');
    $path = tempnam(sys_get_temp_dir(), 'webilia-import-');
    file_put_contents($path, $GLOBALS['downloads'][$url]);
    $GLOBALS['downloaded'][] = $path;
    return $path;
}

// Model the unique option-name index used by the import request guard.
class ThemeToolsLockDatabase {
    public $options = 'wp_options';
    private $locks = [];
    public function prepare($sql, ...$args) { return json_encode([$sql, $args]); }
    public function query($query) {
        [$sql, $args] = json_decode($query, true);
        $key = $args[0];
        if (strpos($sql, 'INSERT IGNORE') === 0) {
            if (isset($this->locks[$key])) return 0;
            $this->locks[$key] = $args[1]; return 1;
        }
        if (($this->locks[$key] ?? null) !== $args[1]) return 0;
        unset($this->locks[$key]); return 1;
    }
    public function get_var($query) { [, $args] = json_decode($query, true); return $this->locks[$args[0]] ?? null; }
}
$GLOBALS['wpdb'] = new ThemeToolsLockDatabase();
function add_query_arg($key, $value, $url) {
    $parts = parse_url($url); $query = []; parse_str($parts['query'] ?? '', $query); $query[$key] = $value;
    return ($parts['scheme'] ?? 'https') . '://' . $parts['host'] . ($parts['path'] ?? '') . '?' . http_build_query($query);
}
function is_admin_bar_showing() { return true; }
function add_submenu_page($parent, $title, $label, $capability, $slug, $callback, $position = null) { $GLOBALS['menus'][$slug] = $parent; }
function add_menu_page($title, $label, $capability, $slug, $callback, $icon = '', $position = null) { $GLOBALS['menus'][$slug] = ''; }

function menu_page_url($slug, $echo = true) { return $GLOBALS['menu_urls'][$slug] ?? ''; }
