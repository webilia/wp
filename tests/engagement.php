<?php
// Focused WordPress doubles; no network and no WordPress installation required.
spl_autoload_register(static function ($class) {
    $prefix = 'Webilia\\WP\\';
    if (strpos($class, $prefix) === 0) require __DIR__ . '/../src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
});
require __DIR__ . '/../src/Announcements.php';
require __DIR__ . '/../src/AnnouncementManager.php';
require __DIR__ . '/../src/Plugin/Feedback.php';
class JsonResponse extends RuntimeException {
    public $success;
    public $data;
    public $status;
    public function __construct($success, $data, $status) { $this->success = $success; $this->data = $data; $this->status = $status; }
}
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$GLOBALS['options'] = [];
$GLOBALS['hooks'] = [];
$GLOBALS['calls'] = [];
$GLOBALS['allow'] = true;
$GLOBALS['remote'] = ['code' => 200, 'body' => '[]'];
function add_action($hook, $callback) { $GLOBALS['hooks'][$hook][] = $callback; }
function get_site_url() { return 'https://site.test'; }
function get_current_blog_id() { return 1; }
function get_current_screen() { return (object) ['id' => $GLOBALS['screen'] ?? 'consumer']; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? ($key === 'date_format' ? 'Y-m-d' : $default); }
function update_option($key, $value, $autoload = null) { $GLOBALS['options'][$key] = $value; return true; }
function wp_next_scheduled($hook) { return $GLOBALS['scheduled'][$hook] ?? false; }
function wp_schedule_event($at, $interval, $hook) { $GLOBALS['scheduled'][$hook] = $at; }
function wp_json_encode($data) { return json_encode($data); }
function sanitize_text_field($text) { return trim(strip_tags((string) $text)); }
function sanitize_textarea_field($text) { return trim(strip_tags($text)); }
function sanitize_key($text) { return preg_replace('/[^a-z0-9_-]/', '', strtolower($text)); }
function esc_url_raw($url, $protocols = null) { return preg_match('~^(https?://|/(?!/))~', $url) ? $url : ''; }
function wp_kses_post($html) { return strip_tags($html, '<strong><p>'); }
function wp_date($format, $timestamp) { return gmdate($format, $timestamp); }
function wp_unslash($value) { return stripslashes($value); }
function __( $text, $domain = null) { return $text; }
function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES); }
function admin_url($path) { return 'https://site.test/wp-admin/' . $path; }
function wp_create_nonce($action) { return $action; }
function current_user_can($cap, ...$args) { $GLOBALS['cap_checks'][] = [$cap, $args]; return $GLOBALS['allow']; }
function check_ajax_referer($action, $key = '_ajax_nonce') { if (($_POST[$key] ?? '') !== $action) wp_send_json_error('nonce', 403); }
function wp_send_json_error($data = null, $status = 200) { throw new JsonResponse(false, $data, $status); }
function wp_send_json_success($data = null) { throw new JsonResponse(true, $data, 200); }
function add_query_arg($params, $url) { return $url . '?' . http_build_query($params); }
function is_wp_error($response) { return $response === false; }
function wp_remote_get($url, $args) { $GLOBALS['calls'][] = [$url, $args]; return $GLOBALS['remote']; }
function wp_remote_post($url, $args) { $GLOBALS['posts'][] = [$url, $args]; return $GLOBALS['feedback_remote'] ?? ['code' => 200, 'body' => '{"status":1}']; }
function wp_remote_retrieve_response_code($response) { return $response['code']; }
function wp_remote_retrieve_body($response) { return $response['body']; }
function response($callback) { try { $callback(); } catch (JsonResponse $response) { return $response; } throw new RuntimeException('Expected JSON response'); }
function feedKey() { foreach (array_keys($GLOBALS['options']) as $key) if (strpos($key, 'webilia_announcements_v1_') === 0) return $key; }

use Webilia\WP\AnnouncementManager as Manager;
use Webilia\WP\Announcements;
use Webilia\WP\Plugin\Feedback;
$client = new Announcements(['one/one.php']);
check($client->fetch()['success'], 'Empty successful list must be distinguishable from failure.');
foreach ([false, ['code' => 503, 'body' => '[]'], ['code' => 200, 'body' => '{}'], ['code' => 200, 'body' => 'bad']] as $bad) {
    $GLOBALS['remote'] = $bad;
    check(!$client->fetch()['success'] && $client->getAnnouncements() === [], 'Failure shape and legacy API.');
}
$GLOBALS['calls'] = [];
$GLOBALS['remote'] = ['code' => 200, 'body' => json_encode([
    ['id' => 1, 'title' => 'Shared', 'body' => '<script>bad()</script><strong>safe</strong>', 'priority' => 5, 'cta' => ['label' => 'Danger', 'url' => 'javascript:bad()']],
    ['id' => 2, 'title' => 'Future', 'start_at' => time() + 1000],
    ['id' => 3, 'title' => 'Expired', 'end_at' => time() - 1000],
    ['id' => 4, 'title' => 'Low priority', 'priority' => 1],
])];
$config = ['basenames' => ['one/one.php'], 'screens' => ['consumer'], 'local_items' => [['id' => 'local', 'title' => 'Local']]];
Manager::register('one', $config);
Manager::register('same', $config);
Manager::register('two', ['basenames' => ['two/two.php']]);
Manager::schedule(); Manager::schedule();
check(count($GLOBALS['scheduled']) === 1 && count($GLOBALS['hooks'][Manager::CRON_HOOK]) === 1, 'One cron registration.');
$a = Manager::snapshot('one');
check(count($GLOBALS['calls']) === 1 && $GLOBALS['calls'][0][1]['timeout'] === 5, 'First load uses bounded fetch.');
check($a['active_count'] === 3 && $a['items'][0]['id'] === 'remote:1', 'Scheduling, expiration, and priority.');
check($a['items'][0]['url'] === '' && strpos($a['items'][0]['description_html'], '<script>') === false, 'Remote sanitation.');
Manager::snapshot('same'); Manager::snapshot('one');
check(count($GLOBALS['calls']) === 1, 'Identical product sets reuse cache.');
Manager::snapshot('two');
check(count($GLOBALS['calls']) === 2 && strpos($GLOBALS['calls'][1][0], 'two%2Ftwo.php') !== false, 'Different sets retain server targeting.');
check(Manager::dismiss('one', 'remote:1'), 'Dismiss known ID.');
check(Manager::snapshot('two')['active_count'] === 1, 'Shared ID dismissal propagates across products.');
check(!Manager::dismiss('two', 'local:one:local'), 'Cannot dismiss another consumer local item.');
check(Manager::dismiss('one', 'local:one:local') && Manager::snapshot('same')['active_count'] === 2, 'Local IDs are namespaced.');
$key = feedKey(); $cache = $GLOBALS['options'][$key];
$cache['success'] = time() - 90000; $cache['attempt'] = $cache['success']; $GLOBALS['options'][$key] = $cache;
$GLOBALS['remote'] = false;
$a = Manager::snapshot('one'); $requestCount = count($GLOBALS['calls']);
check(!$a['unavailable'] && count($a['items']) === 3, 'Failed refresh retains usable previous feed.');
Manager::snapshot('one'); check(count($GLOBALS['calls']) === $requestCount, 'Failed attempt waits one hour.');
$GLOBALS['options'][$key]['success'] = time() - 604801;
check(Manager::snapshot('one')['unavailable'], 'Stale feed expires after seven days.');
$_POST = ['consumer' => 'one', 'nonce' => 'invalid', 'id' => 'remote:1'];
check(response([Manager::class, 'ajaxDismiss'])->status === 403, 'Nonce check.');
$GLOBALS['allow'] = false; $_POST['nonce'] = 'webilia_announcements_one';
check(response([Manager::class, 'ajaxRead'])->status === 403, 'Read capability check.');
$GLOBALS['allow'] = true;
$_POST = ['consumer' => 'two', 'nonce' => 'webilia_announcements_two', 'id' => 'unknown'];
check(response([Manager::class, 'ajaxDismiss'])->status === 400, 'Membership check.');

class LegacyFeedback extends Feedback {
    public $handled = 0;
    public function reasons(): array { return ['other' => ['title' => 'Other', 'placeholder' => 'Explain', 'icon' => 'question']]; }
    public function save() { $this->handled++; }
}
$legacy = new LegacyFeedback(['basename' => 'legacy/plugin.php', 'plugin' => 'legacy']);
$shared = new \Webilia\WP\Plugin\UIFeedback(['basename' => 'wibee/wibee.php', 'plugin' => 'wibee', 'ui' => true, 'reasons' => [
    'other' => ['title' => 'Other', 'placeholder' => 'Explain'], 'temporary' => ['title' => 'Temporary'],
]]);
check(count($GLOBALS['hooks']['wp_ajax_web_dfd']) === 1, 'Single dispatcher.');
$_POST = ['basename' => 'legacy/plugin.php']; Feedback::dispatch(); check($legacy->handled === 1, 'Dispatch preserves subclass override.');
$_POST = ['basename' => 'wibee/wibee.php', 'nonce' => 'webilia_feedback_wibee/wibee.php', 'action_type' => 'submit_feedback', 'reason_key' => 'other', 'details' => 'Some comment'];
check(response([Feedback::class, 'dispatch'])->success && $legacy->handled === 1, 'Only targeted instance handles submission.');
check($GLOBALS['posts'][0][1]['body']['basename'] === 'wibee/wibee.php' && $GLOBALS['posts'][0][1]['body']['details'] === 'Some comment', 'Payload uses configured identity and selected details.');
$_POST['reason_key'] = 'temporary'; response([Feedback::class, 'dispatch']); check($GLOBALS['posts'][1][1]['body']['details'] === '', 'Unused details omitted.');
$count = count($GLOBALS['posts']);
foreach (['skip_deactivate', 'cancel'] as $action) { $_POST['action_type'] = $action; check(!response([Feedback::class, 'dispatch'])->success, 'No server submission for skip/cancel.'); }
check(count($GLOBALS['posts']) === $count, 'Skip and Cancel send nothing.');
$_POST['action_type'] = 'submit_feedback'; $_POST['reason_key'] = 'invalid';
check(response([Feedback::class, 'dispatch'])->status === 400 && count($GLOBALS['posts']) === $count, 'Reason validation.');
$_POST['reason_key'] = 'other'; $_POST['nonce'] = 'wrong'; check(response([Feedback::class, 'dispatch'])->status === 403, 'Target nonce.');
$_POST['nonce'] = 'webilia_feedback_wibee/wibee.php'; $GLOBALS['allow'] = false;
check(response([Feedback::class, 'dispatch'])->status === 403, 'Per-plugin capability.'); $GLOBALS['allow'] = true;
foreach ([false, ['code' => 500, 'body' => '{"status":1}'], ['code' => 200, 'body' => '{"status":0}']] as $bad) {
    $GLOBALS['feedback_remote'] = $bad; check(response([Feedback::class, 'dispatch'])->status === 502, 'Failed feedback keeps deactivation optional.');
}
$_POST['basename'] = 'unregistered/plugin.php'; check(response([Feedback::class, 'dispatch'])->status === 400, 'Unknown plugin cannot be submitted.');
echo "WordPress engagement checks passed.\n";
