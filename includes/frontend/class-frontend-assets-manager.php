<?php
/**
 * Frontend Assets Manager
 *
 * Handles enqueuing of frontend CSS and JavaScript files
 *
 * @package product-availability-checker
 */

namespace PAVC\Frontend;

use PAVC\Traits\Singleton;

/**
 * Frontend Assets Manager class
 *
 * Single Responsibility: Manage frontend asset loading and localization
 */
class Frontend_Assets_Manager {

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
	}

	/**
	 * Enqueue frontend assets on product pages.
	 */
	public function enqueue_frontend_assets() {
		// Only load on single product pages
		if ( ! is_product() ) {
			return;
		}

		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

	/**
	 * Enqueue JavaScript files.
	 */
	private function enqueue_scripts() {
		wp_enqueue_script(
			'pavc-frontend',
			PAVC_PLUGIN_URL . 'assets/js/frontend.js',
			array( 'jquery' ),
			PAVC_VERSION,
			true
		);

		// Localize script with AJAX data
		wp_localize_script(
			'pavc-frontend',
			'pavcFrontend',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'pavc_check_availability' ),
				'strings'   => $this->get_localized_strings(),
				'productId' => get_the_ID(),
			)
		);
	}

	/**
	 * Enqueue CSS files.
	 */
	private function enqueue_styles() {
		wp_enqueue_style(
			'pavc-frontend',
			PAVC_PLUGIN_URL . 'assets/css/frontend.css',
			array(),
			PAVC_VERSION
		);
	}

	/**
	 * Get localized strings for JavaScript.
	 *
	 * @return array Localized strings.
	 */
	private function get_localized_strings() {
		return array(
			'checkAvailability'   => __( 'Check Availability', 'product-availability-checker' ),
			'enterZipCode'        => __( 'Enter your zip code', 'product-availability-checker' ),
			'zipCodePlaceholder'  => __( 'Enter zip code...', 'product-availability-checker' ),
			'available'           => __( 'Available in your area', 'product-availability-checker' ),
			'unavailable'         => __( 'Not available in your area', 'product-availability-checker' ),
			'checking'            => __( 'Checking availability...', 'product-availability-checker' ),
			'error'               => __( 'Error checking availability. Please try again.', 'product-availability-checker' ),
			'invalidZipCode'      => __( 'Please enter a valid zip code.', 'product-availability-checker' ),
			'productNotAvailable' => __( 'This product is not available for delivery to your area.', 'product-availability-checker' ),
		);
	}
}
