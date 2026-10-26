<?php
// Set WEBILIA_TEST_MYSQL_HOST to run against an isolated test database.
if (!getenv('WEBILIA_TEST_MYSQL_HOST')) { echo "MySQL lock integration skipped (no test database configured).\n"; return; }
require __DIR__ . '/theme-tools-bootstrap.php';
require __DIR__ . '/theme-tools-lock-probe.php';

class MySQLImportLockDatabase extends ThemeToolsLockDatabase {
    public function __construct($table) {
        $this->options = $table;
        $this->connection = new mysqli(getenv('WEBILIA_TEST_MYSQL_HOST'), getenv('WEBILIA_TEST_MYSQL_USER') ?: 'root',
            getenv('WEBILIA_TEST_MYSQL_PASSWORD') ?: '', getenv('WEBILIA_TEST_MYSQL_DATABASE') ?: 'webilia_lock_tests',
            (int) (getenv('WEBILIA_TEST_MYSQL_PORT') ?: 3306));
    }
    public function prepare($sql, ...$args) {
        $escaped = array_map(function ($value) { return "'" . $this->connection->real_escape_string((string) $value) . "'"; }, $args);
        return vsprintf($sql, $escaped);
    }
    public function query($query) { $this->connection->query($query); return $this->connection->affected_rows; }
    public function get_var($query) { $row = $this->connection->query($query)->fetch_row(); return $row[0] ?? null; }
}

if (($argv[1] ?? '') === 'worker') {
    $GLOBALS['wpdb'] = new MySQLImportLockDatabase($argv[2]);
    $worker = new ImportLockProbe(importLockConfig());
    check($worker->claim('killed-worker'), 'Worker acquired its import lock.');
    echo "READY\n"; fflush(STDOUT);
    while (true) usleep(100000);
}

$table = 'webilia_lock_test_' . bin2hex(random_bytes(8));
$aDB = new MySQLImportLockDatabase($table); $bDB = new MySQLImportLockDatabase($table);
$aDB->query("CREATE TABLE {$table} (option_name varchar(191) PRIMARY KEY, option_value longtext NOT NULL, autoload varchar(20) NOT NULL)");
$process = null; $pipes = [];
try {
    $a = new ImportLockProbe(importLockConfig()); $b = new ImportLockProbe(importLockConfig());
    $GLOBALS['wpdb'] = $aDB; check($a->claim('active'), 'Real MySQL claims the first lease.');
    $memory = importLockMemory(); importLockMemory([]);
    $b->now = time() + 3600; $GLOBALS['wpdb'] = $bDB;
    check(!$b->claim('contender'), 'A live session remains exclusive after an hour-long stage exceeds its lease.');
    importLockMemory($memory); $GLOBALS['wpdb'] = $aDB;
    check($a->owns('active'), 'The long-running owner retains the exact token.');
    $a->now = time() + 3600; $a->renew(); check($a->owns('active'), 'Real MySQL renews using conditional SQL.');
    $a->release('wrong'); check($a->owns('active'), 'A different token cannot release the session guard.');
    $a->release('active');
    $aDB->query($aDB->prepare("INSERT INTO {$table} VALUES (%s, %s, 'no')", $a->key(), json_encode(['token' => 'expired', 'expires' => 1])));
    $GLOBALS['wpdb'] = $bDB;
    check($b->claim('recovered') && $b->owns('recovered'), 'Real MySQL atomically replaces an expired record.');
    $a->release('active'); check($b->owns('recovered'), 'A former owner cannot release the replacement lease.');
    $b->release('recovered'); importLockMemory([]);

    $process = proc_open([PHP_BINARY, __FILE__, 'worker', $table], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    check(is_resource($process), 'Crash-test worker starts.');
    stream_set_timeout($pipes[1], 10);
    check(trim((string) fgets($pipes[1])) === 'READY', 'Worker has acquired its real database guard before termination.');
    $b->now = time() + 3600;
    check(!$b->claim('before-kill'), 'A separate PHP worker blocks a concurrent recovery request.');
    proc_terminate($process, 9);
    foreach ($pipes as $pipe) fclose($pipe); $pipes = [];
    proc_close($process); $process = null;
    $acquired = false;
    for ($attempt = 0; $attempt < 100 && !$acquired; $attempt++) { $acquired = $b->claim('after-kill'); if (!$acquired) usleep(10000); }
    check($acquired && $b->owns('after-kill'), 'SIGKILL releases the MySQL guard without finally/shutdown and permits stale recovery.');
    $b->release('after-kill');
    echo "MySQL session, lease, concurrent-worker and SIGKILL checks passed.\n";
} finally {
    if (is_resource($process)) { proc_terminate($process, 9); foreach ($pipes as $pipe) fclose($pipe); proc_close($process); }
    $aDB->query("DROP TABLE {$table}");
    $aDB->connection->close(); $bDB->connection->close();
}
