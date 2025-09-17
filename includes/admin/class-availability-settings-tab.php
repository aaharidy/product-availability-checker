<?php
/**
 * Availability Settings Tab
 *
 * Handles the display and functionality of the Availability tab in WooCommerce settings
 *
 * @package product-availability-checker
 */

namespace PAVC\Admin;

use PAVC\Services\Service_Manager;
use PAVC\Services\Codes_Service;
use PAVC\Templates_Loader;

/**
 * Availability Settings Tab class
 *
 * Single Responsibility: Display and handle Availability settings tab content
 */
class Availability_Settings_Tab {

	/**
	 * Display the settings tab content.
	 */
	public function display() {
		$this->enqueue_assets();
		$this->render_tab_content();
	}

	/**
	 * Enqueue necessary assets for the tab.
	 */
	private function enqueue_assets() {
		$this->enqueue_scripts();
		$this->enqueue_styles();
	}

	/**
	 * Enqueue JavaScript files.
	 */
	private function enqueue_scripts() {
		wp_enqueue_script(
			'pavc-admin-availability',
			PAVC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'wp-api-fetch', 'wp-i18n', 'jquery' ),
			PAVC_VERSION,
			true
		);

		// Localize script with REST API data
		wp_localize_script(
			'pavc-admin-availability',
			'pavcAdmin',
			array(
				'strings' => $this->get_localized_strings(),
			)
		);
	}

	/**
	 * Enqueue CSS files.
	 */
	private function enqueue_styles() {
		wp_enqueue_style(
			'pavc-admin-availability',
			PAVC_PLUGIN_URL . 'assets/css/admin.css',
			array(),
			PAVC_VERSION
		);
	}

	/**
	 * Render the tab content using template.
	 */
	private function render_tab_content() {
		/** @var Codes_Service $codes_service */
		$codes_service = Service_Manager::get( Codes_Service::class );

		// Get pagination parameters
		$page     = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
		$per_page = isset( $_GET['per_page'] ) ? max( 1, intval( $_GET['per_page'] ) ) : 10;

		// Get codes with pagination
		$codes_data = $codes_service->get_codes(
			array(
				'page'     => $page,
				'per_page' => $per_page,
			)
		);

		// Prepare template data
		$template_data = array(
			'codes'        => $codes_data['codes'] ?? array(),
			'total_codes'  => $codes_data['total'] ?? 0,
			'current_page' => $page,
			'per_page'     => $per_page,
			'total_pages'  => $codes_data['pages'] ?? 1,
			'page_title'   => __( 'Availability Codes Management', 'product-availability-checker' ),
		);

		Templates_Loader::load_admin( 'availability-settings-tab', $template_data );
	}

	/**
	 * Get localized strings for JavaScript.
	 *
	 * @return array Localized strings.
	 */
	private function get_localized_strings() {
		return array(
			'confirmDelete'   => __( 'Are you sure you want to delete this code?', 'product-availability-checker' ),
			'success'         => __( 'Operation completed successfully.', 'product-availability-checker' ),
			'error'           => __( 'An error occurred. Please try again.', 'product-availability-checker' ),
			'addCode'         => __( 'Add Code', 'product-availability-checker' ),
			'editCode'        => __( 'Edit', 'product-availability-checker' ),
			'deleteCode'      => __( 'Delete', 'product-availability-checker' ),
			'code'            => __( 'Code', 'product-availability-checker' ),
			'available'       => __( 'Available', 'product-availability-checker' ),
			'unavailable'     => __( 'Unavailable', 'product-availability-checker' ),
			'actions'         => __( 'Actions', 'product-availability-checker' ),
			'save'            => __( 'Save', 'product-availability-checker' ),
			'cancel'          => __( 'Cancel', 'product-availability-checker' ),
			'noCodes'         => __( 'No codes found.', 'product-availability-checker' ),
			'noCustomMessage' => __( 'No custom message set', 'product-availability-checker' ),
		);
	}
}
