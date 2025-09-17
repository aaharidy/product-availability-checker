<?php
/**
 * Class Admin
 *
 * @package product-availability-checker
 * @since 1.0.0
 */

namespace PAVC\Admin;

use PAVC\Traits\Singleton;

/**
 * Manage admin related functionality.
 */
class Admin_Loader {

	use Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the admin functionality.
	 */
	private function init() {
		$this->load_woocommerce_integration();
	}

	/**
	 * Load WooCommerce integration.
	 */
	private function load_woocommerce_integration() {
		WooCommerce_Settings_Registration::instance();
	}
}
