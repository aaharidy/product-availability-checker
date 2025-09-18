<?php
/**
 * Frontend AJAX Handler
 *
 * Handles AJAX requests for availability checking from the frontend
 *
 * @package product-availability-checker
 */

namespace PAVC\Frontend;

use PAVC\Traits\Singleton;
use PAVC\Services\Service_Manager;
use PAVC\Services\Codes_Service;

/**
 * Frontend AJAX Handler class
 *
 * Single Responsibility: Handle AJAX requests for availability checking
 */
class Frontend_Ajax_Handler {

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
		// Register AJAX handlers for both logged in and non-logged in users
		add_action( 'wp_ajax_pavc_check_availability', array( $this, 'handle_check_availability' ) );
		add_action( 'wp_ajax_nopriv_pavc_check_availability', array( $this, 'handle_check_availability' ) );
	}

	/**
	 * Handle availability check AJAX request.
	 */
	public function handle_check_availability() {
		// Verify nonce
		check_ajax_referer( 'pavc_check_availability', 'nonce' );

		// Get and validate input
		$zip_code   = sanitize_text_field( $_POST['zip_code'] ?? '' );
		$product_id = intval( $_POST['product_id'] ?? 0 );

		if ( empty( $zip_code ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Zip code is required.', 'product-availability-checker' ),
				)
			);
		}

		if ( empty( $product_id ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Product ID is required.', 'product-availability-checker' ),
				)
			);
		}

		// Check availability using the codes service
		/** @var Codes_Service $codes_service */
		$codes_service = Service_Manager::get( Codes_Service::class );
		$code_data     = $codes_service->get_code_by_zip( $zip_code );

		if ( ! $code_data ) {
			// Zip code not found - default to unavailable
			$this->store_availability_in_session( 'unavailable', $product_id, $zip_code );

			wp_send_json_success(
				array(
					'availability' => 'unavailable',
					'message'      => __( 'Delivery not available in your area.', 'product-availability-checker' ),
					'zip_code'     => $zip_code,
				)
			);
		}

		// Store availability status in session for cart validation
		$this->store_availability_in_session( $code_data['availability'], $product_id, $zip_code );

		// Prepare response
		$response_data = array(
			'availability' => $code_data['availability'],
			'zip_code'     => $zip_code,
		);

		// Add custom message if available
		if ( ! empty( $code_data['message'] ) ) {
			$response_data['message'] = $code_data['message'];
		} else {
			// Default messages based on availability
			if ( $code_data['availability'] === 'available' ) {
				$response_data['message'] = __( 'Available for delivery in your area.', 'product-availability-checker' );
			} else {
				$response_data['message'] = __( 'Not available for delivery in your area.', 'product-availability-checker' );
			}
		}

		wp_send_json_success( $response_data );
	}

	/**
	 * Store availability information in session for cart validation.
	 *
	 * @param string $availability Availability status.
	 * @param int    $product_id Product ID.
	 * @param string $zip_code Zip code.
	 */
	private function store_availability_in_session( $availability, $product_id, $zip_code ) {
		if ( ! WC()->session ) {
			return;
		}

		WC()->session->set( 'pavc_availability_status', $availability );
		WC()->session->set( 'pavc_checked_product_id', $product_id );
		WC()->session->set( 'pavc_checked_zip_code', $zip_code );
		WC()->session->set( 'pavc_check_timestamp', time() );
	}
}
