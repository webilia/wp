<?php
require __DIR__ . '/fixtures/legacy-announcements.php';
require __DIR__ . '/fixtures/legacy-feedback.php';
spl_autoload_register(static function ($class) {
    $prefix = 'Webilia\\WP\\';
    if (strpos($class, $prefix) === 0) require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
});
$hooks = [];
function add_action($hook, $callback) { $GLOBALS['hooks'][$hook][] = $callback; }
function sanitize_text_field($value) { return (string) $value; }
function esc_url_raw($value) { return (string) $value; }
function get_site_url() { return 'https://site.test'; }
function add_query_arg($params, $url) { return $url . '?' . http_build_query($params); }
function wp_remote_get($url, $args) { return ['body' => '[]']; }
function is_wp_error($response) { return false; }
function wp_remote_retrieve_response_code($response) { return 200; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function wp_unslash($value) { return $value; }
function current_user_can($cap, ...$args) { return true; }
class UIInstance extends \Webilia\WP\Plugin\UIFeedback {
    public $saved = false;
    public function save() { $this->saved = true; }
}
$legacy = new \Webilia\WP\Plugin\Feedback(['basename' => 'legacy/plugin.php']);
$modern = new UIInstance(['basename' => 'wibee/wibee.php', 'reasons' => ['other' => ['title' => 'Other']]]);
$_POST = ['basename' => 'wibee/wibee.php'];
call_user_func($hooks['wp_ajax_webilia_feedback'][0]);
if (!$modern->saved || count($hooks['wp_ajax_webilia_feedback']) !== 1 || count($hooks['wp_ajax_web_dfd']) !== 1) throw new RuntimeException('Load-order routing failed.');
$result = (new \Webilia\WP\AnnouncementClient(['wibee/wibee.php']))->fetch();
if (!$result['success'] || $modern->reasons()['other']['title'] !== 'Other') throw new RuntimeException('Legacy-first adapters failed.');
echo "Legacy-first compatibility checks passed.\n";
