<?php
/**
 * Main Plugin Class for Product Availability Checker.
 *
 * @package product-availability-checker
 */

namespace PAVC;

use PAVC\Traits\Singleton;

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
	}
}
