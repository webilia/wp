<?php
$mode = $argv[1] ?? 'legacy-first';
$uiPath = getenv('WEBILIA_UI_PATH') ?: dirname(__DIR__, 2) . '/webilia-ui';
spl_autoload_register(static function ($class) use ($uiPath, $mode) {
    $prefix = 'Webilia\\WP\\'; $base = dirname(__DIR__) . '/src/';
    if (strpos($class, 'Webilia\\UI\\') === 0 && $mode !== 'missing-ui') { $prefix = 'Webilia\\UI\\'; $base = $uiPath . '/src/'; }
    if (strpos($class, $prefix) !== 0) return;
    $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) require $file;
});
$GLOBALS['hooks'] = [];
function add_action($hook, $callback) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter($hook, $callback) { add_action($hook, $callback); }
function check($condition, $message) { if (!$condition) throw new RuntimeException($message); }
class WP_Importer {}
if ($mode === 'old-ui') eval('namespace Webilia\\UI; class Version { public const VALUE = \'1.4.1\'; }');
check(class_exists(\Webilia\WP\DemoImport\Bootstrap::class) && !$GLOBALS['hooks'], 'Autoload is inert.');
if (in_array($mode, ['missing-ui', 'old-ui'], true)) {
    check(class_exists(\Webilia\WP\Version::class), 'WP utilities load without UI.');
    try { \Webilia\WP\ThemeTools::requireUI(); throw new RuntimeException('Missing UI was accepted.'); }
    catch (LogicException $error) { check(strpos($error->getMessage(), 'webilia/ui:^1.5') !== false, 'Clear dependency error.'); }
    echo "Missing-UI checks passed.\n";
    exit;
}
if ($mode === 'legacy-first') require __DIR__ . '/fixtures/legacy-theme-tools.php';
$new = \Webilia\WP\DemoImport\Bootstrap::init(['base_url' => 'https://site.test/wp/', 'ui_url' => 'https://site.test/ui/']);
\Webilia\WP\DemoImport\Bootstrap::init();
(new \Webilia\WP\SettingsPanel\Boot('https://site.test/wp/', 'https://site.test/ui/'))->init();
check(class_exists(\Webilia\WP\DemoImport\Importer\WXRImporter::class), 'Private importer can autoload independently.');
if ($mode !== 'legacy-first') require __DIR__ . '/fixtures/legacy-theme-tools.php';
\Webilia\DemoImport\Bootstrap::init();
(new \Webilia\SettingsPanel\Boot())->init();
check(count($GLOBALS['hooks']['wp_ajax_webilia_wp_demo_import_demo_data']) === 1, 'One new importer dispatcher.');
check(count($GLOBALS['hooks']['wp_ajax_wdi_import_demo_data']) === 1, 'Legacy importer remains independent.');
check(count($GLOBALS['hooks']['wp_ajax_webilia_settings_save']) === 1, 'Legacy settings remains independent.');
check(!is_a(\Webilia\WP\DemoImport\Importer\WXRImporter::class, 'AwesomeMotive\\WPContentImporter2\\WXRImporter', true), 'No shared importer identity.');
echo "Theme-tools {$mode} checks passed.\n";
