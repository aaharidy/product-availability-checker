<?php
/**
 * Main Plugin Class for Product Availability Checker.
 *
 * @package product-availability-checker
 */

namespace PAVC;

use PAVC\Traits\Singleton;
use PAVC\REST_API\REST_API;
use PAVC\Admin\Admin_Loader;

/**
 * Main plugin class.
 */
class Plugin {

	use Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the plugin.
	 */
	private function init() {
		// Load rest api class.
		REST_API::instance();

		// Load admin functionality.
		if ( is_admin() ) {
			Admin_Loader::instance();
		}
	}
}
