# Webilia WordPress

This package contains numerous valuable utilities for Webilia plugins. Even if you're not a Webilia developer, you can still utilize it for your own project; however, please note that we do not offer support in such cases.

### Install
To include this package in your project, run the following Composer command.

````
composer require webilia/wp
````

### Licensing Server
You can effortlessly activate, deactivate, and validate license codes using the Webilia licensing server. Refer to the sample code below for guidance.

````php
use Webilia\WP\Plugin\Licensing;

// Webilia Licensing Server
$licensing = new Licensing(
    'lsdaddbok_purchase_code', // Key name for get_option function
    'lsdaddbok_activation_id', // Key name for get_option function
    'listdom-booking/listdom-booking.php', // Plugin (Add-on) Basename
    'https://api.webilia.com/licensing' // URL of Licensing Server
);

// Activation
[$status, $message, $activation_id] = $licensing->activate('sample-license-key');

// Deactivation
if($licensing->deactivate('sample-license-key'))
{
    // Do something
}

// Validation
if($licensing->isValid())
{
    // Do something
    // Perhaps run the add-on
}
````

### Update Server
To use the Webilia update server, incorporate the following code into your project.

````php
use Webilia\WP\Plugin\Licensing;
use Webilia\WP\Plugin\Update;

// Webilia Licensing Server
$licensing = new Licensing(
    'lsdaddbok_purchase_code', // Key name for get_option function
    'lsdaddbok_activation_id', // Key name for get_option function
    'listdom-booking/listdom-booking.php', // Plugin (Add-on) Basename
    'https://api.webilia.com/licensing' // URL of Licensing Server
);

// Webilia Update Server
new Update(
    '2.1.0', // Current Plugin Version
    'listdom-booking/listdom-booking.php', // Plugin (Add-on) Basename
    $licensing, // Licensing Server is required if you want to validate the license key before update
    '3.3.0', // Core version: in this case we're sending the listdom version since booking is an add-on for the Listdom 
    'https://api.webilia.com/update' // URL of Update Server
);
````
## Shared plugin engagement (1.1)

Announcements and feedback are opt-in. Their presentation requires `webilia/ui:^1.4`; existing WP utilities and legacy feedback do not require that package. Supply URLs to each package root, including on multisite. Load matching UI versions across consumers because `webilia-ui-v1-*` assets share registration handles.

```php
use Webilia\WP\AnnouncementManager;
use Webilia\WP\Plugin\UIFeedback;

AnnouncementManager::register('example', [
    'basenames' => [plugin_basename(EXAMPLE_FILE)],
    'screens' => ['toplevel_page_example'],
    'capability' => 'manage_options',
    'title' => __('Announcements', 'example'),
    'class' => 'example-theme', // Theme variables belong on the separate drawer root.
    'labels' => $translatedAnnouncementLabels,
    'ui_url' => EXAMPLE_URL . 'vendor/webilia/ui/',
    'package_url' => EXAMPLE_URL . 'vendor/webilia/wp/',
    'local_items' => [], // array or callable; each item needs id and title
]);
// Append this to the shared Header's actions. The footer automatically renders its drawer.
$actions[] = AnnouncementManager::action('example');

new UIFeedback([
    'plugin' => 'example', 'basename' => plugin_basename(EXAMPLE_FILE), 'textdomain' => 'example',
    'ui_url' => EXAMPLE_URL . 'vendor/webilia/ui/',
    'package_url' => EXAMPLE_URL . 'vendor/webilia/wp/',
    'title' => __('Deactivate Example', 'example'),
    'description' => __('Please share your experience.', 'example'),
    'disclosure' => __('Submitting sends the site URL, selected reason and optional comments to Webilia.', 'example'),
    'privacy_url' => 'https://webilia.com/privacy-policy/',
    'labels' => $translatedFeedbackLabels,
    'reason_required' => __('Please select a reason.', 'example'),
    'error' => __('Could not send feedback. You can skip and deactivate.', 'example'),
    'reasons' => ['other' => ['title' => __('Other', 'example'), 'icon' => 'question', 'placeholder' => __('Details', 'example')]],
]);
```

Register before `init`/`current_screen`, normally on `plugins_loaded`. The manager enqueues only configured screens and checks capability. `snapshot(consumer)` fetches initially with a five-second timeout, then refreshes daily; failures retry after one hour and retain the last successful feed for seven days. Successful empty lists replace prior items. One daily cron hook processes distinct configurations once. Call `unregister(consumer)` during deactivation; the final consumer removes the shared schedule. Each site keeps its own non-autoloaded cache and dismissals. Product sets are fetched separately to preserve the API's targeting/exclusion rules.

Remote IDs use `remote:` plus the server ID; local IDs use `local:consumer:consumer-id:item-id`, or `local:shared:id` when an item explicitly has `shared: true`. Local/remote payloads accept API fields `body`, `severity`, `priority`, `start_at`, `end_at`, and `cta`, or `description`, `created_at`, `expires_at`, `url`, and `cta_label`. The manager sanitizes HTML/URLs and applies dates before counting. Register the same local shared ID only for intentionally identical items.

`Announcements::getAnnouncements()` retains its array-only contract. `AnnouncementClient::fetch()` and the updated base client's `fetch()` return `['success' => bool, 'items' => array]`. The extension remains usable if another plugin loads the preceding base class first.

The new Feedback base registers one `web_dfd` dispatcher and retains legacy constructor arguments and `reasons()`, `dialog()`, and `save()` overrides. It also supports explicit `ui: true`. Prefer `UIFeedback`, an adapter extending that same base, for products using the new UI: its isolated `webilia_feedback` action works alongside plugins bundling the older base class. Exact basenames select handlers, with capability and target-specific nonce checks. Skip/Cancel make no request; bulk deactivation is left native. A failed submission never forces deactivation.

Run `composer run test`, `npm ci`, and `npm test`. Package release order: merge/tag UI 1.4, then WP 1.1, then update consumer locks to those stable releases. Never tag an unmerged branch. Keep legacy products on their presentation until their separate migrations.
