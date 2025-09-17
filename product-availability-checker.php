<?php
/*
Plugin Name: Product Availability Checker
Description: A simple plugin to check product availability depending on zip code.
Version: 1.0.0
Author: aabdelrhman
Author URI: https://upwork.com/freelancers/abdoharidy
Requires at least: 5.8
Tested up to: 6.8
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: product-availability-checker
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PAVC_VERSION', '1.0.0' );
define( 'PAVC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PAVC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PAVC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
define( 'PAVC_PLUGIN_FILE', __FILE__ );

// Load the autoloader.
require_once PAVC_PLUGIN_DIR . 'includes/autoload.php';

/**
 * Load the main plugin class.
 */
function product_availability_checker() {
	return \PAVC\Plugin::instance();
}

product_availability_checker();

// Load plugin.
add_action(
	'plugins_loaded',
	function () {
		do_action( 'pavc_loaded' );
	}
);
