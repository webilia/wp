# Theme tools (WP 1.2 / UI 1.5)

These modules extract the existing Demo Importer and Settings Panel into Composer libraries.
PHP 7.4+ is supported. Loading Composer's autoloader registers no hooks. Both modules are opt-in.

## Installation

```sh
composer require webilia/wp:^1.2 webilia/ui:^1.5
```

`webilia/ui` is optional for the other WP utilities. A missing or incompatible UI installation
throws a `LogicException` when a theme-tool feature initializes. Consumers install one consistent
UI 1.x version across active products; mixed UI versions are not a v1 compatibility guarantee.
Supply the URL of each installed package explicitly. The packages need not be siblings or live
in WordPress's default content directory. Compiled UI assets ship with the Composer package.

## Settings Panel

Initialize before `admin_menu`, `admin_init`, and `customize_register`, normally on
`after_setup_theme` for theme integrations:

```php
use Webilia\WP\SettingsPanel\Boot;
use Webilia\WP\SettingsPanel\Panel;

(new Boot($wpPackageUrl, $uiPackageUrl))->init();
Panel::set_args('example_theme_settings', [
    'display_name' => __('Example Theme', 'example'),
    'page_slug' => 'example-settings',
    'page_parent' => 'themes.php',
    'page_permissions' => 'manage_options',
    'customizer' => true,
    'show_import_export' => true,
    'save_defaults' => true,
]);
Panel::set_section('example_theme_settings', [
    'id' => 'general', 'title' => __('General', 'example'),
    'fields' => [
        ['id' => 'enabled', 'type' => 'switch', 'title' => __('Enabled', 'example'), 'default' => 1],
        ['id' => 'title', 'type' => 'text', 'title' => __('Title', 'example'),
         'required' => ['enabled', '=', 1]],
        ['id' => 'font', 'type' => 'typography', 'title' => __('Typography', 'example')],
        ['id' => 'image', 'type' => 'media', 'title' => __('Image', 'example')],
    ],
]);
Panel::register('example_theme_settings');
```

Keep the original option name and field IDs, including case and punctuation, when adopting the new namespace. No data migration
is necessary. Existing argument/section/field arrays, nested values, defaults, subsection/tab
structure, conditionals, reset behavior, JSON import/export, typography, media/gallery, editor,
color controls, repeater/slides/social fields, sortable controls, and query-backed options remain
supported. Defaults initialize a missing option; they do not overwrite existing settings.
Saved options retain the same array structure and Customizer setting IDs use `option_name[field]`.
Section resets remove fields without defaults while preserving unrelated values; full resets replace
the option with defaults. WordPress's registered sanitizer retains these replacement semantics.
Query-backed `data => 'posts'` fields accept `post_type` in both admin and Customizer controls.

Panels are keyed by their option name. Register each option once; repeated registration is inert.
One dispatcher routes save/reset requests to that registered owner and checks its capability and
nonce. Each option must have a single active owner during staged adoption. `Boot::settings()` and
`Settings` retain the lower-level registration API, including asset discovery for their initialized
admin and Customizer screens. `Panel` also registers field metadata, defaults, and fonts.

Public hooks retain their names and argument order, including `webilia_settings_panel_updated`,
`webilia_settings_panel/page_header_html`, `webilia_include_html_file`, and the
`webilia_settings_panel_*` fonts filters. Template overrides execute in the WP adapter context.
The default settings/actions templates are rendered by UI with prepared field HTML.

## Demo Importer

```php
use Webilia\WP\DemoImport\Bootstrap;

Bootstrap::init([
    'base_url' => $wpPackageUrl,
    'ui_url' => $uiPackageUrl,
    'text_domain' => 'example',
    'admin_page' => [
        'enabled' => true, 'parent_slug' => 'themes.php',
        'capability' => 'manage_options', 'menu_title' => __('Demo Import', 'example'),
    ],
    'page_setup' => ['menu_slug' => 'example-demo-import'],
    'callbacks' => [
        'demos' => static function () {
            return [[
                'key' => 'starter', 'import_file_name' => 'Starter',
                'import_file_url' => 'https://example.com/demo/content.xml',
                'import_widget_file_url' => 'https://example.com/demo/widgets.json',
                'import_customizer_file_url' => 'https://example.com/demo/customizer.dat',
                'import_wsp' => [['option_name' => 'example_theme_settings',
                    'file_url' => 'https://example.com/demo/settings.json']],
                'plugins' => [['name' => 'Example', 'slug' => 'example', 'required' => true]],
            ]];
        },
        'before_content_import' => $beforeContent,
        'before_widgets_import' => $beforeWidgets,
        'after_import' => $afterImport,
    ],
]);
```

This is one importer for the active theme, initialized once per request. It retains local WXR,
widget and Customizer file keys (`local_import_file`, `local_import_widget_file`,
`local_import_customizer_file`), remote demo files, `import_wsp` and legacy `import_redux` JSON,
preview/categories, required/preselected plugins, branding, intro/title configuration, and
`plugins`, `plugins_for_demo`, `start_import`, and lifecycle callbacks. Deprecated `import_files`
and `register_plugins` callback aliases still work. They are callback aliases; the retained global
filter names are `wdi/demos` and `wdi/plugins`. Public `wdi/*` hooks and callback argument
order remain unchanged. Plugin resolution uses `plugins_for_demo` when configured, otherwise
the demo's inline `plugins` list, otherwise the global plugin list. Product-specific imports and theme setup stay in the supplied callbacks.

WXR imports fetch attachments and keep the current user as the default author. Widget JSON keeps
sidebar assignments; unknown sidebars use inactive widgets. Customizer files remain serialized
arrays of `template`, `mods`, and optional `options`, with object deserialization disabled and an
active-theme match required. Plugin installation/activation retains its extra WordPress
capability checks. Downloaded files are removed after completion/failure; configured local
source files are not deleted. Nonempty configured local paths must be readable files; missing
files fail preparation rather than silently skipping a stage. Omitted optional files remain supported.
The full WXR document is validated before import mutations, and
configured settings JSON must be readable and valid; validation errors stop the flow.

The main AJAX stage checkpoints completed content, widget and settings steps. Repeating a
completed request returns its saved progress response; recovery after a declared failure skips
completed steps. An atomic per-user request guard rejects overlapping requests. An interrupted
stage with an uncertain result stops instead of repeating side effects: inspect/recover the
partial site before clearing its saved import state and starting again. Normal finalization
clears checkpoints and temporary files. The bundled WXR code is GPL-2.0-or-later; see its LICENSE/NOTICE.

## Staged adoption and assets

The old local packages remain independent. No aliases claim `Webilia\SettingsPanel`,
`Webilia\DemoImport`, or `AwesomeMotive\WPContentImporter2`. The extracted adapters use
`webilia_wp_settings_*` and `webilia_wp_demo_*` AJAX actions, isolated nonces/import state,
`webilia-wp-*` adapter handles and `webilia-ui-v1-*` UI handles. Public lifecycle hooks stay shared
so existing listeners continue to work; listeners should already select their own option/theme.
The importer keeps the existing JSON response/status shapes; transports now use isolated actions.

All bundled theme-tool CSS and JavaScript lives in the UI package. Reusable modules load from
`$uiPackageUrl/dist/{css,js}/`; WordPress-specific browser adapters load from
`$uiPackageUrl/dist/wordpress/{css,js}/`. WP supplies the PHP enqueueing, native WordPress
dependencies and localized configuration. Existing `webilia-wp-*` adapter handles stay compatible,
while the asset cache version follows UI. Assets load only on participating screens/Customizer contexts.
`custom_style` still loads configured styles/scripts. Map the old admin style dependency to
`webilia-ui-v1-settings-panel` and the old script dependency to `webilia-wp-settings-panel`.
The old `webilia-settings-panel-admin` dependency is translated for configured overrides.

Phase 2 integrations change PHP imports, supply the two package URLs, and update custom CSS/JS
selectors: `webilia-*` and `wb-*` presentation selectors become `wui-sp-*`; `wdi-*` becomes
`wui-di-*`, `wdi__*` becomes `wui-di__*`, and `js-wdi-*` becomes `js-wui-di-*`.
`js-webilia-*` becomes `js-wui-sp-*`. Existing `--webilia-*` settings theme variables and stored
panel-theme values remain unchanged. Do not enable old and new owners of the same option.
No Listdomer/Placora code or lockfiles change in this phase.

## Validation and release

With the companion UI checkout available, run `composer test` and `npm test`. Set
`WEBILIA_UI_PATH` to the UI package root if it is not the sibling `webilia-ui` directory.
Tests use focused WordPress doubles and data-format fixtures; they do not install plugins,
fetch demos, or modify a live site. CI runs on PHP 7.4 and 8.4 against pinned companion UI source.

After both PRs pass review and are merged, publish UI `v1.5.0`, then WP `v1.2.0`, and verify
an authenticated Composer consumer resolves both stable constraints before Phase 2 updates.
Do not tag feature branches or move existing tags.
