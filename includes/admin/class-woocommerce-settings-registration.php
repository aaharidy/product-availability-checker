<?php
/**
 * WooCommerce Settings Registration
 *
 * Handles registration of custom settings tabs in WooCommerce
 *
 * @package product-availability-checker
 */

namespace PAVC\Admin;

use PAVC\Traits\Singleton;

/**
 * WooCommerce Settings Registration class
 *
 * Single Responsibility: Register custom settings tabs in WooCommerce
 */
class WooCommerce_Settings_Registration {

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
		add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_settings_tab' ), 50 );
		add_action( 'woocommerce_settings_tabs_availability', array( $this, 'display_settings_tab' ) );
		add_action( 'woocommerce_update_options_availability', array( $this, 'save' ) );
	}

	/**
	 * Add custom settings tab to WooCommerce.
	 *
	 * @param array $settings_tabs Array of WooCommerce setting tabs.
	 * @return array Modified settings tabs.
	 */
	public function add_settings_tab( $settings_tabs ) {
		$settings_tabs['availability'] = __( 'Availability', 'product-availability-checker' );
		return $settings_tabs;
	}

	/**
	 * Display the settings tab content.
	 */
	public function display_settings_tab() {
		global $hide_save_button;
		$hide_save_button = true;

		$tab_content = new Availability_Settings_Tab();
		$tab_content->display();
	}

	/**
	 * Save settings - Not needed as we handle saving via REST API
	 */
	public function save() {
		// No implementation needed
	}
}
