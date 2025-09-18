<?php
/**
 * Product Availability Display
 *
 * Handles the display of availability checker on single product pages
 *
 * @package product-availability-checker
 */

namespace PAVC\Frontend;

use PAVC\Traits\Singleton;
use PAVC\Templates_Loader;

/**
 * Product Availability Display class
 *
 * Single Responsibility: Handle availability checker display on product pages
 */
class Product_Availability_Display {

	use Singleton;

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->init();
	}

	/**
	 * Initialize hooks.
	 */
	private function init() {
		// Add availability checker to single product page
		add_action( 'woocommerce_single_product_summary', array( $this, 'display_availability_checker' ), 99 );
	}

	/**
	 * Display the availability checker on product pages.
	 */
	public function display_availability_checker() {
		// Only show on single product pages
		if ( ! is_product() ) {
			return;
		}

		$template_data = array(
			'product_id' => get_the_ID(),
		);

		Templates_Loader::load_frontend( 'availability-checker', $template_data );
	}
}
