<?php
require __DIR__ . '/theme-tools-bootstrap.php';
require __DIR__ . '/theme-tools-lock-probe.php';

$db = $GLOBALS['wpdb'];
$a = new ImportLockProbe(importLockConfig()); $a->now = 1000;
$b = new ImportLockProbe(importLockConfig()); $b->now = 2000;
$key = $a->key();
check($a->claim('owner-a'), 'A new lock is claimed.');
check(!$b->claim('nested'), 'Reentrant claims cannot acquire the same request lock twice.');
$memory = importLockMemory(); importLockMemory([]);
$GLOBALS['wpdb'] = new ThemeToolsLockDatabase();
check(!$b->claim('owner-b'), 'A concurrent session cannot steal a live import even past its lease expiration.');
$GLOBALS['wpdb'] = $db; importLockMemory($memory);
check($a->owns('owner-a') && !$a->owns('wrong'), 'Ownership requires the exact token and session.');
$a->now = 2000; $a->renew();
check(json_decode(ThemeToolsLockDatabase::$rows[$key], true)['expires'] === 2300, 'Checkpoint renewal extends the lease atomically.');
$a->release('wrong'); check($a->owns('owner-a'), 'A wrong token cannot release the active lock.');
$a->release('owner-a');
check(!isset(ThemeToolsLockDatabase::$rows[$key]) && !ThemeToolsLockDatabase::$sessions, 'Normal release removes only the owned row and session guard.');

ThemeToolsLockDatabase::$rows[$key] = json_encode(['token' => 'stale', 'expires' => 999]);
check($b->claim('new-owner'), 'An expired, disconnected lease is recovered.');
check($b->owns('new-owner'), 'Recovered lock belongs to its new token.');
$a->release('owner-a'); check($b->owns('new-owner'), 'The former owner cannot delete a recovered lock.');
$b->release('new-owner');

ThemeToolsLockDatabase::$rows[$key] = json_encode(['token' => 'fresh', 'expires' => 3000]);
check(!$b->claim('too-early') && !ThemeToolsLockDatabase::$sessions, 'An unexpired record is not recovered and the attempted guard is released.');
ThemeToolsLockDatabase::$rows[$key] = json_encode(['token' => 'stale', 'expires' => 999]);
$replacement = json_encode(['token' => 'racing-winner', 'expires' => 4000]);
$db->beforeUpdate = static function () use ($key, $replacement) { ThemeToolsLockDatabase::$rows[$key] = $replacement; };
check(!$b->claim('racing-loser') && ThemeToolsLockDatabase::$rows[$key] === $replacement, 'Stale recovery uses compare-and-swap and cannot overwrite a competing winner.');
check(!ThemeToolsLockDatabase::$sessions, 'Failed compare-and-swap releases its session guard.');
unset(ThemeToolsLockDatabase::$rows[$key]);
$db->failNamedLocks = true;
check(!$b->claim('unsupported') && !isset(ThemeToolsLockDatabase::$rows[$key]), 'Unavailable session locking fails closed before inserting a lease.');
$db->failNamedLocks = false;

// Model SIGKILL: lose the session and request memory without running any cleanup.
$demo = ['key' => 'lock-test', 'local_import_file' => __DIR__ . '/fixtures/theme-tools/content.xml'];
$state = ['signature' => hash('sha256', serialize($demo)), 'selected' => $demo, 'steps' => ['content' => true], 'in_progress' => 'widgets'];
$a = new ImportLockProbe(importLockConfig($demo)); $a->now = 1000;
check($a->claim('crashed'), 'Crash fixture acquires its guard.'); $a->saveState($state);
$db->disconnect(); importLockMemory([]); $GLOBALS['transients'] = [];
$_POST = ['security' => 'webilia-wp-demo-ajax-verification', 'selected' => 0];
$recovery = new ImportLockProbe(importLockConfig($demo)); $recovery->now = 2000;
check(response([$recovery, 'ajax_import_demo_data'])->status === 409, 'Crash recovery refuses an uncertain non-idempotent stage after transient expiry.');
check($recovery->contentCalls === 0 && $recovery->widgetCalls === 0 && $recovery->state()['in_progress'] === 'widgets', 'Durable checkpoints survive without repeating content or widgets.');
$changed = new ImportLockProbe(importLockConfig($demo + ['new-config' => true]));
check(response([$changed, 'ajax_import_demo_data'])->status === 409, 'Changing the selected configuration cannot discard an uncertain checkpoint.');
check(!isset(ThemeToolsLockDatabase::$rows[$key]), 'Recovery releases the stale lock even when manual recovery is needed.');
$recovery->clearState();

$a = new ImportLockProbe(importLockConfig($demo)); $a->now = 1000;
$a->claim('safe-crash'); unset($state['in_progress']); $a->saveState($state);
$db->disconnect(); importLockMemory([]); $GLOBALS['transients'] = [];
$recovery = new ImportLockProbe(importLockConfig($demo)); $recovery->now = 2000;
check(response([$recovery, 'ajax_import_demo_data'])->data['status'] === 'afterAllImportAJAX', 'Known completed checkpoints can resume after a crash.');
check($recovery->contentCalls === 0 && $recovery->widgetCalls === 1, 'Recovery skips completed content and runs only the not-yet-started stage.');
$recovery->clearState();
foreach ([json_encode(['token' => 'lost', 'expires' => 999]), 'legacy-permanent-token'] as $record) {
    ThemeToolsLockDatabase::$rows[$key] = $record;
    $recovery = new ImportLockProbe(importLockConfig()); $recovery->now = 2000;
    check(response([$recovery, 'ajax_import_demo_data'])->status === 409, 'A stale or legacy lock without checkpoints requires inspection.');
    check($recovery->state()['in_progress'] === 'unknown', 'Missing checkpoint uncertainty is durably recorded.');
    check(response([$recovery, 'ajax_import_demo_data'])->status === 409 && $recovery->contentCalls === 0, 'A later retry cannot bypass the recovered crash marker.');
    $recovery->clearState();
}

$a = new ImportLockProbe(importLockConfig()); $a->now = 1000; $a->claim('disconnected');
$a->saveState(['in_progress' => 'content']); $db->disconnect();
check(!$a->owns('disconnected'), 'A reconnected database session does not own the old guard.');
try { $a->renew(); throw new LogicException('Renewal should fail.'); } catch (RuntimeException $error) {}
$a->release('disconnected');
check(isset(ThemeToolsLockDatabase::$rows[$key]), 'A disconnected owner cannot delete a lease without its session guard.');
importLockMemory([]); ThemeToolsLockDatabase::$rows = []; $a->clearState();
$config = importLockConfig();
$config['callbacks']['before_content_import'] = static function () use ($db) { $db->disconnect(); };
$lostSession = new ImportLockProbe($config);
check(response([$lostSession, 'ajax_import_demo_data'])->status === 409 && $lostSession->contentCalls === 0, 'Losing the guard during a callback stops before importing content.');
check($lostSession->state()['in_progress'] === 'content', 'Session loss preserves the uncertain stage for manual recovery.');
ThemeToolsLockDatabase::$rows = []; $lostSession->clearState();
echo "Import lock reliability checks passed.\n";
