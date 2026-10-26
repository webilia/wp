<?php
require __DIR__ . '/theme-tools-bootstrap.php';

use Webilia\WP\SettingsPanel\Panel;
use Webilia\WP\SettingsPanel\Settings;
use Webilia\WP\SettingsPanel\Form;

class ReviewSettings extends Settings {
    public function url() { return $this->current_page_url(); }
}
class ReviewImport extends \Webilia\WP\DemoImport\Boot {
    public $contentCalls = 0;
    public $widgetCalls = 0;
    public $customizerCalls = 0;
    public function demos() { return $this->demo(); }
    public function plugins($demos) { return $this->plugins_for_demo_index(0, $demos); }
    public function globalPlugins() { return $this->plugin(); }
    public function settingsFiles($items) { return $this->import_wsp_data($items); }
    public function state() { return $this->get_import_data(); }
    public function clearState() { $this->clear_import_data(); }
    public function url() { return $this->current_page_url(); }
    protected function import_content_file(string $path) { $this->contentCalls++; return null; }
    protected function import_widgets_file(string $path) { $this->widgetCalls++; return parent::import_widgets_file($path); }
    protected function import_customizer_file(string $path) { $this->customizerCalls++; return parent::import_customizer_file($path); }
}
$config = ['base_url' => 'https://site.test/wp', 'ui_url' => 'https://site.test/ui'];
$settingsBoot = new \Webilia\WP\SettingsPanel\Boot($config['base_url'], $config['ui_url']);
$settingsBoot->init();
$direct = $settingsBoot->settings();
$direct->set_config(['option_name' => 'Direct_Options', 'menu_slug' => 'DirectPage', 'customizer' => true, 'capability' => 'edit_theme_options']);
$direct->init();
check(Panel::get_args_by_page('DirectPage') === [], 'Direct registration does not require a Panel registry entry.');
$_GET['page'] = 'DirectPage'; $settingsBoot->assets();
check(isset($GLOBALS['scripts']['webilia-wp-settings-panel'], $GLOBALS['styles']['webilia-ui-v1-settings-panel']), 'Direct Settings screens load shared UI and WP adapters.');
$GLOBALS['scripts'] = []; $GLOBALS['styles'] = []; $GLOBALS['capabilities']['edit_theme_options'] = false;
$settingsBoot->assets();
check(!$GLOBALS['scripts'] && !$GLOBALS['styles'], 'Direct Settings screen discovery enforces its configured capability.');
$GLOBALS['capabilities']['edit_theme_options'] = true; $GLOBALS['doing'] = 'customize_controls_enqueue_scripts';
$settingsBoot->assets();
check(isset($GLOBALS['scripts']['webilia-wp-settings-panel']), 'Direct Customizer registrations load settings adapters without Panel registrations.');
$GLOBALS['doing'] = ''; $GLOBALS['scripts'] = []; $GLOBALS['styles'] = [];
foreach (['unrelated', 'directpage', ['DirectPage']] as $page) {
    $_GET['page'] = $page; $settingsBoot->assets();
    check(!$GLOBALS['scripts'] && !$GLOBALS['styles'], 'Unrelated, wrong-case and malformed pages do not load assets.');
}
$probe = new ReviewImport($config);
$plugins = $probe->plugins([['key' => 'inline', 'plugins' => [['slug' => 'inline', 'name' => 'Inline', 'required' => true]]]]);
check($plugins[0]['slug'] === 'inline' && $plugins[0]['required'] && $plugins[0]['preselected'], 'Inline demo plugin requirements are retained.');
$probe->set_config(['callbacks' => ['plugins' => static function () { return [['slug' => 'global', 'name' => 'Global']]; }]]);
check($probe->plugins([['key' => 'global']])[0]['slug'] === 'global', 'Global plugin fallback remains available.');
$probe->set_config(['callbacks' => ['plugins_for_demo' => static function () { return []; }]]);
check($probe->plugins([['key' => 'inline', 'plugins' => [['slug' => 'inline']]]]) === [], 'Explicit per-demo callback can opt out of plugins.');

// The source package's deprecated aliases are callbacks, not filter names.
$legacy = new ReviewImport($config + ['callbacks' => [
    'import_files' => static function ($initial) { check($initial === [], 'Legacy demo callback argument.'); return [['key' => 'callback']]; },
    'register_plugins' => static function ($initial) { check($initial === [], 'Legacy plugin callback argument.'); return [['slug' => 'callback']]; },
]]);
add_filter('wdi/demos', static function ($demos, $config) { check(isset($config['ui_url']), 'Demo filter config argument.'); $demos[] = ['key' => 'filter']; return $demos; }, 10, 2);
add_filter('wdi/plugins', static function ($plugins, $config) { check(isset($config['ui_url']), 'Plugin filter config argument.'); $plugins[] = ['slug' => 'filter']; return $plugins; }, 10, 2);
check(array_column($legacy->demos(), 'key') === ['callback', 'filter'], 'Existing demos hook and callback alias are preserved.');
check(array_column($legacy->globalPlugins(), 'slug') === ['callback', 'filter'], 'Existing plugins hook and callback alias are preserved.');
unset($GLOBALS['hooks']['wdi/demos'], $GLOBALS['hooks']['wdi/plugins']);

$GLOBALS['options']['Theme_Options'] = ['TitleCase' => 'Saved', 'Accent.Color' => '#123456', 'Enabled_Flag' => 1, 'NoDefault' => 'Remove', 'Unknown' => 'Keep'];
$GLOBALS['options']['theme_options'] = ['TitleCase' => 'Other owner'];
Panel::set_args('Theme_Options', ['page_slug' => 'CasePage', 'page_parent' => 'themes.php', 'save_defaults' => true, 'customizer' => true]);
Panel::set_section('Theme_Options', ['id' => 'general', 'title' => 'General', 'fields' => [
    ['id' => 'TitleCase', 'type' => 'text', 'default' => 'Default'],
    ['id' => 'NoDefault', 'type' => 'text'],
    ['id' => 'Accent.Color', 'type' => 'color', 'default' => '#abcdef'],
    ['id' => 'Enabled_Flag', 'type' => 'checkbox', 'default' => 1],
    ['id' => 'BodyFont', 'type' => 'typography', 'color_origin' => 'Accent.Color', 'default' => ['color' => '']],
]]);
Panel::register('Theme_Options');
do_action('admin_init');
$other = new ReviewSettings(); $other->set_config(['option_name' => 'theme_options']); $other->init();
check(get_option('Theme_Options')['TitleCase'] === 'Saved' && !isset(get_option('Theme_Options')['titlecase']), 'Existing exact field keys survive defaults.');
check(Panel::get_field_type('Theme_Options', 'TitleCase') === 'text' && Panel::get_field_meta('Theme_Options', 'Accent.Color')['default'] === '#abcdef', 'Metadata uses exact field IDs.');
check(Panel::get_color_origins('Theme_Options')['BodyFont'] === 'Accent.Color', 'Color origins use exact field IDs.');
$_POST = ['opt_name' => 'Theme_Options', 'nonce' => 'webilia_wp_settings_actions', 'data' => http_build_query(['Theme_Options' => ['TitleCase' => 'New']])];
check(response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); })->data['success'], 'Case-sensitive option owner receives saves.');
check(get_option('Theme_Options')['TitleCase'] === 'New' && get_option('Theme_Options')['Enabled_Flag'] === 0, 'Save/checkbox clearing retains exact keys.');
check(get_option('theme_options')['TitleCase'] === 'Other owner', 'A differently cased owner remains unchanged.');
$_POST['section'] = 'general';
response(static function () { do_action('wp_ajax_webilia_wp_settings_reset_section'); });
check(get_option('Theme_Options')['TitleCase'] === 'Default' && get_option('Theme_Options')['Unknown'] === 'Keep', 'Section reset targets original keys.');
check(!array_key_exists('NoDefault', get_option('Theme_Options')), 'Section reset removes fields without defaults through the registered sanitizer.');
$GLOBALS['options']['Theme_Options']['NoDefault'] = 'Remove again';
response(static function () { do_action('wp_ajax_webilia_wp_settings_reset_all'); });
check(get_option('Theme_Options')['Accent.Color'] === '#abcdef' && !isset(get_option('Theme_Options')['accentcolor']), 'Reset all retains punctuation and case.');
check(!array_key_exists('NoDefault', get_option('Theme_Options')) && !array_key_exists('Unknown', get_option('Theme_Options')), 'Reset all persists only defaults through the registered sanitizer.');
$_POST['data'] = http_build_query(['Theme_Options' => ['TitleCase' => 'After reset']]);
response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); });
check(get_option('Theme_Options')['TitleCase'] === 'After reset' && get_option('Theme_Options')['Accent.Color'] === '#abcdef', 'Normal partial-save merging is restored after reset persistence.');
$GLOBALS['options']['Theme_Options']['BodyFont'] = ['font-family' => 'Open Sans'];
do_action('wp_enqueue_scripts');
check(strpos($GLOBALS['styles']['wui-sp-settings-panel-google-fonts-Theme_Options']['url'] ?? '', 'family=Open+Sans') !== false, 'Font registration and enqueueing preserve the exact option and typography keys.');
$manager = new WP_Customize_Manager(); do_action('customize_register', $manager);
check(isset($manager->settings['Theme_Options[TitleCase]'], $manager->settings['Theme_Options[Accent.Color]']), 'Customizer IDs match original persistent keys.');
$_POST['opt_name'] = ['Theme_Options'];
check(response(static function () { do_action('wp_ajax_webilia_wp_settings_save'); })->status === 400, 'Non-string option keys are rejected.');

$lower = new ReviewSettings();
$lower->add_section('general', 'General', null, ['has_content' => true]);
$lower->add_field('Exact.ID', 'Field', 'general', static function ($args) { echo esc_html($args['sentinel']); }, ['sentinel' => 'Callback argument']);
ob_start(); $lower->render_page(); $rendered = ob_get_clean();
check(strpos($rendered, 'Callback argument') !== false, 'Lower-level callbacks receive registered arguments.');
$postField = ['id' => 'Product', 'type' => 'select', 'data' => 'posts', 'post_type' => 'product'];
Panel::set_args('Product_Options', ['page_slug' => 'products']);
Panel::set_section('Product_Options', ['id' => 'general', 'fields' => [$postField]]);
Panel::register('Product_Options');
$GLOBALS['post_queries'] = [];
ob_start();
foreach ($GLOBALS['hooks']['admin_menu'] as $entry) {
    if (is_array($entry[0]) && $entry[0][0] instanceof Settings && $entry[0][0]->option_name() === 'Product_Options') {
        // Render the registered owner rather than calling Form directly.
        $owner = $entry[0][0];
        $owner->render_page();
    }
}
$postRendered = ob_get_clean();
check(strpos($postRendered, 'product record') !== false && end($GLOBALS['post_queries'])['post_type'] === 'product', 'Settings-page fields forward configured query post types.');
$customRendered = Panel::render_customizer_field($postField + ['customizer_link_attr' => 'data-customize-setting-link="Product_Options[Product]"'], 'Product_Options');
check(strpos($customRendered, 'product record') !== false && end($GLOBALS['post_queries'])['post_type'] === 'product', 'Customizer fields forward configured query post types.');
$literal = 'C:\\site\\path / \\d+ / {"escaped":"\\\\value"}';
$editor = Form::editor(['name' => 'theme[editor]', 'id' => 'editor', 'value' => $literal]);
check(strpos($editor, esc_html($literal)) !== false, 'Editor values retain literal backslashes.');

$bar = new class { public $nodes = []; public function add_node($node) { $this->nodes[] = $node; } };
$lower->set_config(['menu_slug' => 'CasePage', 'parent_slug' => 'themes.php']); $lower->register_page(); $lower->register_admin_bar($bar);
check($GLOBALS['menus']['CasePage'] === 'themes.php' && $bar->nodes[0]['href'] === 'https://site.test/wp-admin/themes.php?page=CasePage', 'Admin bar follows the actual submenu parent and case.');
$lower->set_config(['parent_slug' => 'edit.php?post_type=book']);
check($lower->url() === 'https://site.test/wp-admin/edit.php?post_type=book&page=CasePage', 'Custom post-type parent queries survive.');
$lower->set_config(['parent_slug' => 'webilia']);
check($lower->url() === 'https://site.test/wp-admin/admin.php?page=CasePage', 'Plugin menu parent routes through admin.php.');
$lower->set_config(['menu_type' => 'menu', 'parent_slug' => 'themes.php']);
check($lower->url() === 'https://site.test/wp-admin/admin.php?page=CasePage', 'Top-level settings use admin.php.');
$importUrl = new ReviewImport($config + ['admin_page' => ['parent_slug' => 'themes.php'], 'page_setup' => ['menu_slug' => 'ImportCase']]);
$importUrl->add_admin_page();
check($importUrl->url() === 'https://site.test/wp-admin/themes.php?page=ImportCase', 'Importer return URL follows its submenu.');
add_filter('wdi/page_setup', static function ($setup) { $setup['parent_slug'] = 'tools.php'; $setup['menu_slug'] = 'filtered'; return $setup; });
$importUrl->add_admin_page();
check($importUrl->url() === 'https://site.test/wp-admin/tools.php?page=filtered', 'Filtered registration controls the return URL.');
$_GET['page'] = 'filtered'; $GLOBALS['scripts'] = []; $GLOBALS['styles'] = [];
$importUrl->assets();
check(isset($GLOBALS['scripts']['webilia-wp-demo-import'], $GLOBALS['styles']['webilia-ui-v1-demo-import']), 'Filtered importer registration controls asset loading.');
$_GET['page'] = 'ImportCase'; $GLOBALS['scripts'] = []; $GLOBALS['styles'] = []; $importUrl->assets();
check(!$GLOBALS['scripts'] && !$GLOBALS['styles'], 'Original importer slug no longer loads assets after filtering.');
$unregistered = new ReviewImport($config);
check($unregistered->url() === 'https://site.test/wp-admin/tools.php?page=filtered', 'Native redirects resolve page filters even before admin_menu registration.');
unset($GLOBALS['hooks']['wdi/page_setup']);
$GLOBALS['menu_urls']['CasePage'] = 'https://site.test/wp-admin/admin.php?page=CasePage';
check($lower->url() === $GLOBALS['menu_urls']['CasePage'], 'Registered WordPress menu URLs take precedence.');
unset($GLOBALS['menu_urls']);

$temporary = [];
function reviewFile($contents) { global $temporary; $path = tempnam(sys_get_temp_dir(), 'webilia-review-'); file_put_contents($path, $contents); $temporary[] = $path; return $path; }
register_shutdown_function(static function () use (&$temporary) { foreach ($temporary as $path) if (is_file($path)) unlink($path); });
$localContent = __DIR__ . '/fixtures/theme-tools/content.xml';
foreach (['local_import_file', 'local_import_widget_file', 'local_import_customizer_file'] as $missingKey) {
    $missingDemo = ['key' => 'missing-local', 'local_import_file' => $localContent];
    $missingDemo[$missingKey] = '/missing/configured-import-file';
    $missing = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($missingDemo) { return [$missingDemo]; }]]);
    $_POST = ['security' => 'webilia-wp-demo-ajax-verification', 'selected' => 0];
    check(response([$missing, 'ajax_import_demo_data'])->status === 500, 'An explicitly configured missing local file cannot report success.');
    check($missing->contentCalls === 0 && $missing->widgetCalls === 0, 'Missing configured local files are rejected before any import stage.');
    $missing->clearState();
}
check(is_file($localContent), 'Failed preparation never deletes a local source.');
$missingDemo = ['key' => 'cleanup', 'import_file_url' => 'https://files.test/content.xml', 'local_import_widget_file' => '/missing/widgets.json'];
$GLOBALS['downloads']['https://files.test/content.xml'] = '<rss/>';
$missing = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($missingDemo) { return [$missingDemo]; }]]);
check(response([$missing, 'ajax_import_demo_data'])->status === 500, 'A missing local file also fails after remote preparation.');
foreach ($GLOBALS['downloaded'] as $path) check(!is_file($path), 'Preparation failure cleans earlier owned downloads.');
$missing->clearState();
$omittedDemo = ['key' => 'omitted', 'local_import_file' => $localContent];
$omitted = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($omittedDemo) { return [$omittedDemo]; }]]);
check(response([$omitted, 'ajax_import_demo_data'])->data['status'] === 'afterAllImportAJAX', 'Omitted optional local files remain supported.');
$omitted->clearState();
foreach (['', '{bad', '<html>Error</html>', 'null', '"scalar"'] as $contents) {
    $file = reviewFile($contents); $GLOBALS['options']['invalid_settings'] = ['keep' => true];
    check(is_wp_error($probe->settingsFiles([['option_name' => 'invalid_settings', 'file_path' => $file]])), 'Invalid settings file must fail.');
    check(get_option('invalid_settings') === ['keep' => true], 'Invalid settings cannot overwrite existing options.');
}
check(is_wp_error($probe->settingsFiles([['option_name' => 'missing', 'file_path' => '/missing/settings.json']])), 'Missing configured settings file fails.');
$valid = reviewFile('{"updated":true}'); $invalid = reviewFile('{bad');
$GLOBALS['options']['first_set'] = ['keep' => true];
check(is_wp_error($probe->settingsFiles([['option_name' => 'first_set', 'file_path' => $valid], ['option_name' => 'second_set', 'file_path' => $invalid]])), 'All configured sets must validate.');
check(get_option('first_set') === ['keep' => true], 'No earlier set is written when a later file is invalid.');

$GLOBALS['wp_registered_sidebars'] = ['sidebar' => []];
$GLOBALS['wp_registered_widget_controls'] = [['id_base' => 'text', 'name' => 'Text']];
$GLOBALS['downloads'] = ['https://files.test/content.xml' => '<rss/>', 'https://files.test/widgets.json' => file_get_contents(__DIR__ . '/fixtures/theme-tools/widgets.json'), 'https://files.test/settings.json' => '{bad'];
$demo = ['key' => 'retry', 'import_file_url' => 'https://files.test/content.xml', 'import_widget_file_url' => 'https://files.test/widgets.json', 'import_wsp' => [['option_name' => 'imported_settings', 'file_url' => 'https://files.test/settings.json']]];
$events = [];
$retry = new ReviewImport($config + ['callbacks' => [
    'demos' => static function () use ($demo) { return [$demo]; },
    'before_content_import' => static function () use (&$events) { $events[] = 'before_content'; },
    'before_widgets_import' => static function () use (&$events) { $events[] = 'before_widgets'; },
    'after_import' => static function () use (&$events) { $events[] = 'after'; },
]]);
$_POST = ['security' => 'webilia-wp-demo-ajax-verification', 'selected' => 0];
$failed = response([$retry, 'ajax_import_demo_data']);
check($failed->status === 500 && !isset($retry->state()['files']), 'Settings failure reports an error and cleans downloads.');
foreach ($GLOBALS['downloaded'] as $path) check(!file_exists($path), 'Failure removes every owned download.');
$GLOBALS['downloads']['https://files.test/settings.json'] = '{"updated":true}';
$completed = response([$retry, 'ajax_import_demo_data']);
check($completed->data['status'] === 'afterAllImportAJAX' && get_option('imported_settings')['updated'], 'A declared failure resumes successfully.');
$downloadCount = count($GLOBALS['downloaded']);
check(response([$retry, 'ajax_import_demo_data'])->data === $completed->data, 'A lost response can replay the completed stage response.');
check(count($GLOBALS['downloaded']) === $downloadCount && $retry->contentCalls === 1 && $retry->widgetCalls === 1, 'Retries do not download or repeat completed stages.');
check(count(get_option('sidebars_widgets')['sidebar']) === 1 && $events === ['before_content', 'before_widgets'], 'Retries cannot duplicate widgets or callbacks.');
response([$retry, 'ajax_after_import_data']);
check($events === ['before_content', 'before_widgets', 'after'] && $retry->state() === [], 'Finalization preserves lifecycle and clears checkpoints.');
foreach ($GLOBALS['downloaded'] as $path) check(!file_exists($path), 'Finalization removes every owned download.');

$nested = null; $overlap = null;
$overlap = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($demo) { return [$demo]; }, 'before_content_import' => static function () use (&$nested, &$overlap) { $nested = response([$overlap, 'ajax_import_demo_data']); }]]);
response([$overlap, 'ajax_import_demo_data']);
check($nested->status === 409 && $overlap->contentCalls === 1 && $overlap->widgetCalls === 1, 'Overlapping requests are rejected before non-idempotent work.');
$overlap->clearState();
$interrupted = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($demo) { return [$demo]; }, 'before_widgets_import' => static function () { throw new RuntimeException('Interrupted callback'); }]]);
check(response([$interrupted, 'ajax_import_demo_data'])->status === 409, 'Interrupted callbacks produce an actionable stage error.');
check(response([$interrupted, 'ajax_import_demo_data'])->status === 409 && $interrupted->contentCalls === 1, 'Uncertain partial stages are never silently replayed.');
foreach ($GLOBALS['downloaded'] as $path) check(!file_exists($path), 'Interrupted stages clean their owned downloads.');
$interrupted->clearState();

$customDemo = $demo + ['import_customizer_file_url' => 'https://files.test/customizer.dat'];
$GLOBALS['downloads']['https://files.test/customizer.dat'] = 'invalid';
$customEvents = [];
$customRetry = new ReviewImport($config + ['callbacks' => [
    'demos' => static function () use ($customDemo) { return [$customDemo]; },
    'after_import' => static function () use (&$customEvents) { $customEvents[] = 'after'; },
]]);
check(response([$customRetry, 'ajax_import_demo_data'])->data['status'] === 'customizerAJAX', 'Main response retains the Customizer transition.');
check(response([$customRetry, 'ajax_after_import_data'])->status === 409 && $customEvents === [], 'Finalization cannot skip a pending Customizer stage.');
check(response([$customRetry, 'ajax_import_customizer_data'])->status === 500, 'Invalid Customizer reports an error.');
$retryState = $customRetry->state(); $downloadCount = count($GLOBALS['downloaded']);
$customMods = $GLOBALS['mods'] ?? [];
check(response([$customRetry, 'ajax_import_customizer_data'])->status === 409, 'A direct retry cannot treat cleaned preparation state as an omitted file.');
check(response([$customRetry, 'ajax_after_import_data'])->status === 409 && $customEvents === [], 'Failed Customizer imports cannot report successful finalization.');
check($customRetry->state() === $retryState && count($GLOBALS['downloaded']) === $downloadCount && ($GLOBALS['mods'] ?? []) === $customMods, 'Rejected retries preserve main-stage checkpoints without downloading or applying data.');
$GLOBALS['downloads']['https://files.test/customizer.dat'] = file_get_contents(__DIR__ . '/fixtures/theme-tools/customizer.dat');
check(response([$customRetry, 'ajax_import_demo_data'])->data['status'] === 'customizerAJAX', 'Customizer retries prepare fresh owned files.');
check($customRetry->contentCalls === 1 && $customRetry->widgetCalls === 1, 'Later Customizer failures cannot cause main-stage duplication.');
check(response([$customRetry, 'ajax_import_customizer_data'])->data['status'] === 'afterAllImportAJAX', 'Corrected Customizer data can finish recovery.');
check($GLOBALS['mods']['accent'] === '#123456' && get_option('blogname') === 'Imported site', 'Successful recovery applies Customizer mods and options before advancing.');
check(response([$customRetry, 'ajax_import_customizer_data'])->data['status'] === 'afterAllImportAJAX' && $customRetry->customizerCalls === 2, 'Lost successful Customizer responses replay without importing twice.');
check(response([$customRetry, 'ajax_import_demo_data'])->data['status'] === 'afterAllImportAJAX', 'Main response reflects completed Customizer progress.');
response([$customRetry, 'ajax_after_import_data']);
check($customEvents === ['after'], 'Recovery invokes finalization only after Customizer success.');
foreach ($GLOBALS['downloaded'] as $path) check(!file_exists($path), 'Customizer recovery cleans every owned download.');

$expired = new ReviewImport($config + ['callbacks' => ['demos' => static function () use ($customDemo) { return [$customDemo]; }]]);
response([$expired, 'ajax_import_demo_data']);
$staged = $expired->state()['files']['customizer'];
check(is_file($staged), 'Expiry fixture prepares a Customizer download.');
$savedTransients = $GLOBALS['transients']; $GLOBALS['transients'] = [];
check(response([$expired, 'ajax_import_customizer_data'])->status === 409 && $expired->customizerCalls === 0, 'Expired transient state cannot advance the Customizer stage.');
check(response([$expired, 'ajax_after_import_data'])->status === 409, 'Expired transient state cannot report import completion.');
$GLOBALS['transients'] = $savedTransients; $expired->clearState();


class ValidationWXR extends \Webilia\WP\DemoImport\Importer\WXRImporter {
    public $events = [];
    protected function import_start($file) { $this->events[] = 'start'; }
    protected function post_process() { $this->events[] = 'post_process'; }
    public function remap_featured_images() {}
    protected function import_end() { $this->events[] = 'end'; }
}
$validXML = file_get_contents(__DIR__ . '/fixtures/theme-tools/content.xml');
foreach (['', substr($validXML, 0, -8), '<html><body>Error</body></html>', '<rss><channel><title>No WXR version</title></channel></rss>'] as $xml) {
    $wxr = new ValidationWXR();
    $previous = libxml_use_internal_errors(false);
    check(is_wp_error($wxr->import(reviewFile($xml))), 'Malformed/non-WXR documents cannot report success.');
    check($wxr->events === [], 'XML preflight rejects files before any WP import mutation.');
    check(libxml_use_internal_errors() === false, 'XML preflight restores the caller error mode.');
    libxml_use_internal_errors($previous);
}
$wxr = new ValidationWXR();
check(!is_wp_error($wxr->import(__DIR__ . '/fixtures/theme-tools/content.xml')) && $wxr->events === ['start', 'post_process', 'end'], 'Valid WXR retains its import lifecycle.');
echo "Theme-tools review regressions passed.\n";
