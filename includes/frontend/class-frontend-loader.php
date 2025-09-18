<?php
/**
 * Frontend Loader
 *
 * @package product-availability-checker
 */

namespace PAVC\Frontend;

use PAVC\Traits\Singleton;

/**
 * Frontend Loader class
 *
 * Single Responsibility: Initialize and coordinate all frontend functionality
 */
class Frontend_Loader {

	use Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize the frontend functionality.
	 */
	private function init() {
		$this->load_frontend_classes();
	}

	/**
	 * Load all frontend classes.
	 */
	private function load_frontend_classes() {
		// Load assets manager
		Frontend_Assets_Manager::instance();

		// Load product availability display
		Product_Availability_Display::instance();

		// Load AJAX handler
		Frontend_Ajax_Handler::instance();
	}
}
