<?php
// Boundary fixtures: legacy classes and endpoints must remain independently owned.
namespace Webilia\DemoImport;
class Bootstrap {
    public static function init() { \add_action('wp_ajax_wdi_import_demo_data', static function () {}); }
}
namespace Webilia\SettingsPanel;
class Boot {
    public function init() { \add_action('wp_ajax_webilia_settings_save', static function () {}); }
}
namespace AwesomeMotive\WPContentImporter2;
class WXRImporter {}
