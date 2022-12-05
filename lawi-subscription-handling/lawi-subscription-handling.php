<?php

/**
 *
 *
 * @package           lawi-subscription-handling
 * @author            wp-stars
 * @copyright         2019 wp-stars gmbh
 *
 * @wordpress-plugin
 * Plugin Name:       Landwirt Subscriptions
 * Plugin URI:        https://wp-stars.com
 * Description:       This plugin handles all the subscriptions for landwirt
 * Version:           %%version%%
 * Requires PHP:      8.0
 * Author:            wp-stars gmbh
 * Author URI:        https://wp-stars.com
 * Text Domain:
 */


use wps\lawi\Plugin;

// use composer autoload to load classes automatically
require __DIR__ . '/vendor/autoload.php';

if (!session_id()) {
    session_start();
}


// define global plugin with plugin __DIR__ path
$plugin = new Plugin(__DIR__);