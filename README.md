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