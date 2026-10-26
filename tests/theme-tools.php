<?php
require __DIR__ . '/theme-tools-bootstrap.php';
use Webilia\WP\DemoImport\Bootstrap;
use Webilia\WP\SettingsPanel\Boot;
use Webilia\WP\SettingsPanel\Panel;
use Webilia\WP\SettingsPanel\Form;

set_error_handler(static function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    throw new ErrorException($message, 0, $severity, $file, $line);
});
check($GLOBALS['hooks'] === [], 'Autoload must not register WordPress hooks.');
$settingsBoot = new Boot('https://site.test/custom/vendor/wp/', 'https://cdn.test/ui/');
$settingsBoot->init(); $settingsBoot->init();
Panel::set_args('theme_a', ['page_slug' => 'theme-a', 'customizer' => true, 'save_defaults' => true, 'show_import_export' => true]);
Panel::set_section('theme_a', ['id' => 'general', 'title' => 'General', 'fields' => [
    ['id' => 'title', 'title' => 'Title', 'type' => 'text', 'default' => 'Default'],
    ['id' => 'enabled', 'title' => 'Enabled', 'type' => 'checkbox', 'default' => 1],
    ['id' => 'choices', 'title' => 'Choices', 'type' => 'checkboxes', 'default' => ['a' => 1]],
    ['id' => 'font', 'title' => 'Font', 'type' => 'typography', 'fonts' => ['Poppins' => 'Poppins']],
    ['id' => 'image', 'title' => 'Image', 'type' => 'media'],
]]);
$GLOBALS['options']['theme_a'] = ['title' => 'Existing', 'enabled' => 1, 'choices' => ['a' => 1], 'unknown' => 'Keep', 'font' => ['font-family' => 'Poppins']];
Panel::register('theme_a'); Panel::register('theme_a');
Panel::set_args('theme_b', ['page_slug' => 'theme-b', 'page_permissions' => 'edit_theme_options', 'save_defaults' => true]);
Panel::set_section('theme_b', ['id' => 'general', 'title' => 'General B', 'fields' => [['id' => 'title', 'title' => 'Title', 'type' => 'text', 'default' => 'B default']]]);
Panel::register('theme_b');
check(get_option('theme_a')['title'] === 'Existing' && get_option('theme_a')['unknown'] === 'Keep', 'Registration preserves saved values.');
check(get_option('theme_b')['title'] === 'B default', 'Defaults initialize only a missing option.');
check(count($GLOBALS['hooks']['wp_ajax_webilia_wp_settings_save']) === 1, 'One option-aware dispatcher.');
check(!isset($GLOBALS['hooks']['wp_ajax_webilia_settings_save']), 'Legacy settings AJAX remains unclaimed.');
$_POST = ['opt_name' => 'theme_b', 'nonce' => 'webilia_wp_settings_actions', 'data' => 'theme_b[title]=Changed'];
$result = response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); });
check($result->data['success'] && get_option('theme_b')['title'] === 'Changed', 'Second option owner is routed correctly.');
check(get_option('theme_a')['title'] === 'Existing', 'First option is untouched.');
$GLOBALS['capabilities']['edit_theme_options'] = false;
check(response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); })->status === 403, 'Owner-specific capability enforced.');
$GLOBALS['capabilities']['edit_theme_options'] = true;
$_POST['nonce'] = 'wrong';
check(response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); })->status === 400, 'Invalid settings nonce rejected.');
$_POST = ['opt_name' => 'unknown', 'nonce' => 'webilia_wp_settings_actions'];
check(response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); })->status === 400, 'Unregistered option rejected.');
$_POST = ['opt_name' => 'theme_a', 'nonce' => 'webilia_wp_settings_actions', 'data' => 'theme_a[title]=Saved'];
response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); });
check(get_option('theme_a')['enabled'] === 0 && get_option('theme_a')['choices'] === [], 'Omitted checkbox values clear.');
check(get_option('theme_a')['unknown'] === 'Keep', 'Partial saves preserve existing options.');
$_POST['import_b64'] = base64_encode(json_encode(['title' => 'Imported 雪', 'font' => ['font-family' => 'Poppins']]));
response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); });
check(get_option('theme_a')['title'] === 'Imported 雪' && get_option('theme_a')['unknown'] === 'Keep', 'JSON import preserves text and unrelated options.');
unset($_POST['import_b64']); $_POST['section'] = 'general';
response(static function () { do_action('wp_ajax_webilia_wp_settings_reset_section'); });
check(get_option('theme_a')['title'] === 'Default' && get_option('theme_a')['unknown'] === 'Keep', 'Section reset preserves unrelated options.');
response(static function () { do_action('wp_ajax_webilia_wp_settings_reset_all'); });
check(get_option('theme_a')['title'] === 'Default' && !isset(get_option('theme_a')['unknown']), 'Full reset uses registered defaults.');
check(strpos(Form::select(['name' => 'page', 'data' => 'pages']), 'About') !== false, 'WordPress query-backed options.');
check(strpos(Form::media(['name' => 'theme[image]', 'id' => 'image', 'value' => 8]), '/8.png') !== false, 'Media ID resolution.');
check(strpos(Form::editor(['name' => 'theme[body]', 'id' => 'body', 'value' => 'Copy']), 'data-wp-editor') !== false, 'Native editor adapter.');
$manager = new WP_Customize_Manager();
do_action('customize_register', $manager);
check(isset($manager->settings['theme_a[title]']) && !isset($manager->settings['theme_b[title]']), 'Only enabled panels register Customizer settings.');
check($manager->controls['theme_a[title]']->type === 'webilia_wp_settings_panel', 'Customizer type is isolated.');
$GLOBALS['control_value'] = 'Customizer title';
ob_start(); $manager->controls['theme_a[title]']->render_content(); $controlHtml = ob_get_clean();
check(strpos($controlHtml, 'data-customize-setting-link=') !== false && strpos($controlHtml, 'Customizer title') !== false, 'Customizer value/binding preserved.');
$_GET['page'] = 'theme-b'; $settingsBoot->assets();
check($GLOBALS['scripts']['webilia-wp-settings-panel']['url'] === 'https://site.test/custom/vendor/wp/assets/js/settings-panel.js', 'Explicit WP root URL.');
check($GLOBALS['styles']['webilia-ui-v1-settings-panel']['url'] === 'https://cdn.test/ui/dist/css/settings-panel.css', 'Independent explicit UI root URL.');
$GLOBALS['styles'] = []; $GLOBALS['scripts'] = []; $_GET['page'] = 'unrelated'; $settingsBoot->assets();
check(!$GLOBALS['styles'] && !$GLOBALS['scripts'], 'No assets on unrelated screens.');

class ImportProbe extends \Webilia\WP\DemoImport\Boot {
    public $events = []; public $failContent = false;
    public function widgets($path) { return $this->import_widgets_file($path); }
    public function customizer($path) { return $this->import_customizer_file($path); }
    public function settingsData($items) { return $this->import_wsp_data($items); }
    public function prepare($demo) { return $this->prepare_import_files($demo); }
    public function cleanup($files) { return $this->cleanup_import_files($files); }
    public function setupPlugin($plugin) { return $this->install_or_activate_plugin($plugin); }
    public function stateKey() { return $this->import_data_transient_key(); }
    protected function import_content_file(string $path) { $this->events[] = 'content'; return $this->failContent ? new WP_Error('content_failed', 'Import failed') : null; }
}
$config = ['base_url' => 'https://site.test/vendor/wp/', 'ui_url' => 'https://cdn.test/ui/', 'page_setup' => ['menu_slug' => 'demos'], 'callbacks' => ['demos' => static function () { return [['key' => 'one', 'local_import_file' => __DIR__ . '/fixtures/theme-tools/content.xml']]; }]];
$boot = Bootstrap::init($config); check(Bootstrap::init() === $boot, 'Theme importer remains single-instance.');
check(!isset($GLOBALS['hooks']['wp_ajax_wdi_import_demo_data']) && count($GLOBALS['hooks']['wp_ajax_webilia_wp_demo_import_demo_data']) === 1, 'Isolated importer handler.');
$probe = new ImportProbe($config);
check($probe->stateKey() !== 'wdi_import_data_4', 'Import state cannot collide with legacy state.');
$_POST = ['security' => 'wrong'];
check(response([$probe, 'ajax_import_demo_data'])->status === 403, 'Importer nonce checked.');
$_POST = ['security' => 'webilia-wp-demo-ajax-verification', 'selected' => 0];
$GLOBALS['capabilities']['manage_options'] = false;
check(response([$probe, 'ajax_import_demo_data'])->status === 403, 'Importer capability checked.');
$GLOBALS['capabilities']['manage_options'] = true;
$_POST['selected'] = 99;
check(response([$probe, 'ajax_import_demo_data'])->status === 400, 'Unknown demo rejected.');
$_POST['selected'] = 0;
$probe->set_config(['callbacks' => ['before_content_import' => function () use ($probe) { $probe->events[] = 'before_content'; }, 'before_widgets_import' => function () use ($probe) { $probe->events[] = 'before_widgets'; }, 'after_import' => function () use ($probe) { $probe->events[] = 'after'; }]]);
check(response([$probe, 'ajax_import_demo_data'])->data['status'] === 'afterAllImportAJAX', 'AJAX progress contract retained.');
check(isset($GLOBALS['transients'][$probe->stateKey()]), 'Import state persists until finalization.');
response([$probe, 'ajax_after_import_data']);
check($probe->events === ['before_content', 'content', 'before_widgets', 'after'], 'Callbacks retain lifecycle order.');
check(!isset($GLOBALS['transients'][$probe->stateKey()]), 'Completed state removed.');
$probe->failContent = true;
check(response([$probe, 'ajax_import_demo_data'])->status === 500 && !isset($GLOBALS['transients'][$probe->stateKey()]), 'Failed content import clears state.');
check(is_wp_error($probe->widgets(__DIR__ . '/fixtures/theme-tools/invalid.json')), 'Invalid widget JSON rejected.');
$GLOBALS['wp_registered_sidebars'] = ['sidebar' => []];
$GLOBALS['wp_registered_widget_controls'] = [['id_base' => 'text', 'name' => 'Text']];
$probe->widgets(__DIR__ . '/fixtures/theme-tools/widgets.json');
check(count(get_option('sidebars_widgets')['sidebar']) === 1, 'Widget import assigns a sidebar.');
$probe->settingsData([['option_name' => 'theme_import', 'file_path' => __DIR__ . '/fixtures/theme-tools/settings.json']]);
check(get_option('theme_import')['title'] === 'Imported theme', 'Theme option JSON format.');
check(is_wp_error($probe->customizer(__DIR__ . '/fixtures/theme-tools/wrong-theme.dat')), 'Customizer theme mismatch rejected.');
$probe->customizer(__DIR__ . '/fixtures/theme-tools/customizer.dat');
check($GLOBALS['mods']['accent'] === '#123456' && get_option('blogname') === 'Imported site', 'Customizer mods and options preserved.');
$GLOBALS['capabilities']['activate_plugins'] = false;
check(is_wp_error($probe->setupPlugin(['slug' => 'example'])), 'Plugin activation capability enforced.');
$GLOBALS['capabilities']['activate_plugins'] = true;
check($probe->setupPlugin(['slug' => 'example']) === true && is_plugin_active('example/example.php'), 'Installed plugin activation supported.');
$GLOBALS['plugins'] = []; $GLOBALS['capabilities']['install_plugins'] = false;
check($probe->setupPlugin(['slug' => 'missing'])->get_error_code() === 'webilia_wp_demo_plugin_install_forbidden', 'Installation capability enforced before installing.');
$GLOBALS['downloads'] = ['https://files.test/content.xml' => '<rss/>'];
$partial = $probe->prepare(['import_file_url' => 'https://files.test/content.xml', 'import_widget_file_url' => 'https://files.test/missing.json']);
check(is_wp_error($partial), 'A later file download can fail.');
foreach ($GLOBALS['downloaded'] as $download) check(!file_exists($download), 'Partial-download failure cleans owned files.');
$local = tempnam(sys_get_temp_dir(), 'webilia-local-');
file_put_contents($local, '<rss/>');
$localFiles = $probe->prepare(['local_import_file' => $local]);
$probe->cleanup($localFiles);
check(is_file($local), 'Configured local import source must survive cleanup.');
unlink($local);
$files = $probe->prepare(['import_file_url' => 'https://files.test/content.xml']);
check(file_exists($files['content']), 'Remote files staged.');
$probe->cleanup($files); check(!file_exists($files['content']), 'Temporary download cleanup.');
$wxr = new \Webilia\WP\DemoImport\Importer\WXRImporter();
$wxr->set_logger(new \Webilia\WP\DemoImport\Importer\WPImporterLogger());
$info = $wxr->get_preliminary_information(__DIR__ . '/fixtures/theme-tools/content.xml');
check($info->version === '1.2' && $info->title === 'Fixture site', 'Bundled WXR parser loads from private namespace.');
check(!class_exists('AwesomeMotive\\WPContentImporter2\\WXRImporter', false), 'Legacy importer namespace remains unclaimed.');
$_GET['page'] = 'demos'; $GLOBALS['scripts'] = []; $boot->assets();
check(isset($GLOBALS['scripts']['webilia-wp-demo-import']) && !isset($GLOBALS['scripts']['webilia-demo-import']), 'Importer asset handles isolated.');
ob_start(); $boot->admin_page(); $html = ob_get_clean();
check(strpos($html, 'webilia_wp_demo_start_import') !== false && strpos($html, 'js-wui-di-page') !== false, 'WP controller renders shared demo view.');
echo "Theme-tools WordPress checks passed.\n";
