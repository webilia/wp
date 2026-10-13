<?php

namespace Webilia\WP;

use InvalidArgumentException;
use Webilia\UI\AnnouncementCenter;
use Webilia\UI\Html;
use Webilia\UI\WordPress\Assets;

/** Opt-in per-site announcement service. UI is required only when rendering/enqueuing. */
final class AnnouncementManager
{
    public const CRON_HOOK = 'webilia_announcements_refresh';
    private const DISMISSALS = 'webilia_announcements_dismissed_v1';
    /** @var array<string, array<string, mixed>> */
    private static $consumers = [];
    /** @var boolean */
    private static $initialized = false;
    /** @var array<string, bool> */
    private static $rendered = [];

    /**
     * @param string $consumer
     * @param array<string, mixed> $config
     * @return void
     */
    public static function register(string $consumer, array $config): void
    {
        if (!preg_match('/^[a-z][a-z0-9_-]*$/', $consumer) || empty($config['basenames'])) {
            throw new InvalidArgumentException('A consumer ID and actual product basenames are required.');
        }

        $basenames = array_values(array_unique(array_filter(array_map('sanitize_text_field', (array) $config['basenames']))));
        sort($basenames);
        self::$consumers[$consumer] = array_merge([
            'capability' => 'manage_options', 'screens' => [], 'local_items' => [], 'labels' => [],
            'server' => 'https://api.webilia.com/announcements', 'ui_url' => '', 'package_url' => '',
            'title' => 'Announcements',
        ], $config, ['basenames' => $basenames]);
        if (self::$initialized) return;
        self::$initialized = true;
        add_action('init', [self::class, 'schedule']);
        add_action(self::CRON_HOOK, [self::class, 'refreshAll']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueue']);
        add_action('admin_footer', [self::class, 'footer']);
        add_action('wp_ajax_webilia_announcements_read', [self::class, 'ajaxRead']);
        add_action('wp_ajax_webilia_announcements_dismiss', [self::class, 'ajaxDismiss']);
    }

    /**
     * Stop scheduled work when the final registered consumer is deactivated.
     *
     * @param string $consumer
     * @return void
     */
    public static function unregister(string $consumer): void
    {
        unset(self::$consumers[$consumer]);
        if (!self::$consumers) wp_clear_scheduled_hook(self::CRON_HOOK);
    }

    /**
     * @return void
     */
    public static function schedule(): void
    {
        if (!self::$consumers) return;
        if (!wp_next_scheduled(self::CRON_HOOK)) wp_schedule_event(time() + 86400, 'daily', self::CRON_HOOK);
    }

    /**
     * @return void
     */
    public static function refreshAll(): void
    {
        foreach (array_keys(self::$consumers) as $consumer) self::refresh($consumer);
    }

    /**
     * @param string $consumer
     * @return array<string, mixed>
     */
    private static function config(string $consumer): array
    {
        if (!isset(self::$consumers[$consumer])) throw new InvalidArgumentException('Unknown announcement consumer.');
        return self::$consumers[$consumer];
    }

    /**
     * @param array<string, mixed> $config
     * @return string
     */
    private static function cacheKey(array $config): string
    {
        return 'webilia_announcements_v1_' . md5(wp_json_encode([
            get_site_url(), $config['server'], $config['basenames'],
        ]));
    }

    /**
     * @param string $consumer
     * @return void
     */
    public static function refresh(string $consumer): void
    {
        $config = self::config($consumer);
        $key = self::cacheKey($config);
        $cache = (array) get_option($key, []);
        $now = time();
        $attempt = (int) ($cache['attempt'] ?? 0);
        $success = (int) ($cache['success'] ?? 0);
        if ($attempt && $attempt + ($attempt > $success ? 3600 : 86400) > $now) return;
        $cache['attempt'] = $now;
        update_option($key, $cache, false);
        $result = (new AnnouncementClient($config['basenames'], null, $config['server']))->fetch();
        if (!$result['success']) return;
        $items = [];
        foreach ($result['items'] as $raw) {
            $item = self::normalize($raw, 'remote:' . (string) $raw['id']);
            if ($item) $items[$item['id']] = $item;
        }

        update_option($key, ['attempt' => $now, 'success' => $now, 'items' => array_values($items)], false);
    }

    /**
     * @param string $consumer
     * @return array<string, mixed>
     */
    public static function snapshot(string $consumer): array
    {
        $config = self::config($consumer);
        self::refresh($consumer);
        $cache = (array) get_option(self::cacheKey($config), []);
        $usable = !empty($cache['success']) && (int) $cache['success'] + 604800 > time();
        $items = $usable ? (array) ($cache['items'] ?? []) : [];
        $locals = is_callable($config['local_items']) ? call_user_func($config['local_items']) : $config['local_items'];
        foreach ((array) $locals as $raw) {
            if (!is_array($raw) || !isset($raw['id'])) continue;
            $id = !empty($raw['shared']) ? 'local:shared:' : 'local:consumer:' . $consumer . ':';
            $item = self::normalize($raw, $id . (string) $raw['id']);
            if ($item) $items[] = $item;
        }

        $dismissals = (array) get_option(self::DISMISSALS, []);
        $visible = [];
        foreach ($items as $item) {
            if ($item['starts'] > time() || ($item['ends'] > 0 && $item['ends'] <= time())) continue;
            $item['state'] = isset($dismissals[$item['id']]) ? 'dismissed' : 'active';
            $visible[$item['id']] = $item;
        }

        $visible = array_values($visible);
        usort($visible, static function ($a, $b): int {
            return ($b['priority'] <=> $a['priority']) ?: (($b['starts'] <=> $a['starts']) ?: strcmp($a['id'], $b['id']));
        });
        return ['items' => $visible, 'unavailable' => !$usable,
            'active_count' => count(array_filter($visible, static function ($item): bool { return $item['state'] === 'active';
}))];
    }

    /**
     * @param mixed $value
     * @return int
     */
    private static function timestamp($value): int
    {
        if (is_numeric($value)) return max(0, (int) $value);
        return is_string($value) && $value !== '' ? max(0, (int) strtotime($value)) : 0;
    }

    /**
     * @param array<string, mixed> $raw
     * @param string $id
     * @return array<string, mixed>
     */
    private static function normalize(array $raw, string $id): array
    {
        if (!preg_match('/^[a-zA-Z0-9_:.-]{1,200}$/', $id) || empty($raw['title']) || !is_scalar($raw['title'])) return [];
        $start = self::timestamp($raw['start_at'] ?? ($raw['created_at'] ?? 0));
        $end = self::timestamp($raw['end_at'] ?? ($raw['expires_at'] ?? 0));
        $severity = $raw['severity'] ?? 'info';
        if (!in_array($severity, ['info', 'success', 'warning', 'danger', 'error'], true)) $severity = 'info';
        $cta = isset($raw['cta']) && is_array($raw['cta']) ? $raw['cta'] : [];
        $description = $raw['description'] ?? ($raw['body'] ?? '');
        $url = $raw['url'] ?? ($cta['url'] ?? '');
        $label = $raw['cta_label'] ?? ($cta['label'] ?? '');
        return ['id' => $id, 'title' => sanitize_text_field((string) $raw['title']),
            'description_html' => nl2br(wp_kses_post(is_scalar($description) ? (string) $description : ''), false),
            'url' => esc_url_raw(is_scalar($url) ? (string) $url : '', ['http', 'https']),
            'cta_label' => sanitize_text_field(is_scalar($label) ? (string) $label : ''),
            'severity' => $severity, 'priority' => is_numeric($raw['priority'] ?? null) ? (int) $raw['priority'] : 0,
            'starts' => $start, 'ends' => $end, 'date' => $start ? gmdate('c', $start) : '',
            'date_label' => $start ? wp_date(get_option('date_format'), $start) : ''];
    }

    /**
     * @param string $consumer
     * @param string $id
     * @return bool
     */
    public static function dismiss(string $consumer, string $id): bool
    {
        $snapshot = self::snapshot($consumer);
        foreach ($snapshot['items'] as $item) {
            if ($item['id'] !== $id) continue;
            $dismissals = (array) get_option(self::DISMISSALS, []);
            $dismissals[$id] = time();
            update_option(self::DISMISSALS, $dismissals, false);
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    private static function authorizedConsumer(): string
    {
        $consumer = isset($_POST['consumer']) && is_string($_POST['consumer']) ? sanitize_key(wp_unslash($_POST['consumer'])) : '';
        if (!isset(self::$consumers[$consumer]) || !current_user_can(self::$consumers[$consumer]['capability'])) {
            wp_send_json_error(['message' => 'Forbidden'], 403);
        }

        check_ajax_referer('webilia_announcements_' . $consumer, 'nonce');
        return $consumer;
    }

    /**
     * @return void
     */
    public static function ajaxRead(): void
    {
        wp_send_json_success(self::snapshot(self::authorizedConsumer()));
    }

    /**
     * @return void
     */
    public static function ajaxDismiss(): void
    {
        $consumer = self::authorizedConsumer();
        $id = isset($_POST['id']) && is_string($_POST['id']) ? wp_unslash($_POST['id']) : '';
        if (!self::dismiss($consumer, $id)) wp_send_json_error(['message' => 'Unknown announcement'], 400);
        wp_send_json_success(self::snapshot($consumer));
    }

    /**
     * @param array<string, mixed> $config
     * @return bool
     */
    private static function onScreen(array $config): bool
    {
        $screen = get_current_screen();
        return $screen && in_array($screen->id, $config['screens'], true) && current_user_can($config['capability']);
    }

    /**
     * @return void
     */
    public static function enqueue(): void
    {
        foreach (self::$consumers as $consumer => $config) {
            if (!self::onScreen($config)) continue;
            self::snapshot($consumer);
            Assets::enqueue(['announcements'], $config['ui_url']);
            wp_enqueue_script('webilia-wp-v1-announcements', rtrim($config['package_url'], '/') . '/assets/js/announcements.js',
                ['webilia-ui-v1-announcements'], '1.1.0', true);
        }
    }

    /**
     * @param string $consumer
     * @return array<string, mixed>
     */
    public static function action(string $consumer): array
    {
        $config = self::config($consumer);
        if (!current_user_can($config['capability'])) return [];
        $snapshot = self::snapshot($consumer);
        return ['type' => 'button', 'icon' => 'bell', 'label' => $config['title'],
            'target' => 'webilia-announcements-' . $consumer, 'count' => $snapshot['active_count']];
    }

    /**
     * @param string $consumer
     * @return string
     */
    public static function render(string $consumer): string
    {
        $config = self::config($consumer);
        if (!current_user_can($config['capability']) || isset(self::$rendered[$consumer])) return '';
        self::$rendered[$consumer] = true;
        $snapshot = self::snapshot($consumer);
        $props = ['id' => 'webilia-announcements-' . $consumer, 'title' => $config['title'],
            'labels' => $config['labels'], 'class' => $config['class'] ?? ''];
        foreach (['active', 'dismissed'] as $state) {
            $props[$state] = array_values(array_filter($snapshot['items'], static function ($item) use ($state): bool {
                return $item['state'] === $state;
            }));
        }

        $data = array_merge($snapshot, ['consumer' => $consumer, 'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('webilia_announcements_' . $consumer), 'labels' => $config['labels'],
            'scope' => 'site-' . get_current_blog_id()]);
        return '<div data-webilia-announcements="' . Html::escape(wp_json_encode($data)) . '">' . AnnouncementCenter::render($props) . '</div>';
    }

    /**
     * @return void
     */
    public static function footer(): void
    {
        foreach (self::$consumers as $consumer => $config) {
            if (self::onScreen($config)) echo self::render($consumer);
        }
    }
}
