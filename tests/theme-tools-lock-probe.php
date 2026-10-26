<?php
// Shared probes for the deterministic and real-MySQL lock regressions.
class ImportLockProbe extends \Webilia\WP\DemoImport\Boot {
    public $now;
    public $contentCalls = 0;
    public $widgetCalls = 0;
    public function claim($token) { return $this->invokeLock('claim_import_lock', [$this->key(), $token]); }
    public function owns($token) { return $this->invokeLock('owns_import_lock', [$this->key(), $token]); }
    public function release($token) { return $this->invokeLock('release_import_lock', [$this->key(), $token]); }
    public function renew() { return $this->invokeLock('refresh_active_import_lock', []); }
    public function key() { return 'webilia_wp_demo_import_lock_' . get_current_user_id(); }
    public function checkpointKey() { return $this->import_data_transient_key() . '_checkpoint'; }
    public function state() { return $this->get_import_data(); }
    public function saveState($state) { $this->set_import_data($state); }
    public function clearState() { $this->clear_import_data(); }
    protected function import_lock_time(): int { return $this->now ?? parent::import_lock_time(); }
    protected function import_content_file(string $path) { $this->contentCalls++; return null; }
    protected function import_widgets_file(string $path) { $this->widgetCalls++; return null; }
    private function invokeLock($name, $args) {
        $method = new ReflectionMethod(\Webilia\WP\DemoImport\Boot::class, $name);
        $method->setAccessible(true); return $method->invokeArgs($this, $args);
    }
}
function importLockMemory($value = null) {
    $property = new ReflectionProperty(\Webilia\WP\DemoImport\Boot::class, 'held_import_locks');
    $property->setAccessible(true);
    if ($value !== null) $property->setValue(null, $value);
    return $property->getValue();
}
function importLockConfig($demo = null) {
    $demo = $demo ?? ['key' => 'lock-test', 'local_import_file' => __DIR__ . '/fixtures/theme-tools/content.xml'];
    return ['base_url' => 'https://site.test/wp', 'ui_url' => 'https://site.test/ui',
        'callbacks' => ['demos' => static function () use ($demo) { return [$demo]; }]];
}
