<?php
/**
 * Class Codes_Controller
 *
 * @package product-availability-checker
 *
 * @since 1.0.0
 */

namespace PAVC\REST_API\Controllers\V1;

use PAVC\Abstracts\REST_Controller;
use PAVC\Services\Service_Manager;
use PAVC\Services\Codes_Service;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Codes controller class.
 */
class REST_Codes_Controller extends REST_Controller {

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = 'codes';

	/**
	 * Register routes.
	 *
	 * @since 1.0.0
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_codes' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'add_code' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_code' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_code' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_code' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
			)
		);
	}

	/**
	 * Get the Codes service instance.
	 *
	 * @return Codes_Service
	 */
	private function get_codes_service() {
		/** @var Codes_Service $codes_service */
		return Service_Manager::get( Codes_Service::class );
	}

	/**
	 * Get all codes.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_codes( $request ) {
		$codes_service = $this->get_codes_service();

		$args = array(
			'per_page'     => $request->get_param( 'per_page' ) ?: 10,
			'page'         => $request->get_param( 'page' ) ?: 1,
			'search'       => $request->get_param( 'search' ) ?: '',
			'availability' => $request->get_param( 'availability' ) ?: '',
			'orderby'      => $request->get_param( 'orderby' ) ?: 'id',
			'order'        => $request->get_param( 'order' ) ?: 'DESC',
		);

		$result = $codes_service->get_codes( $args );

		$response = new WP_REST_Response( $result['codes'] );
		$response->header( 'X-WP-Total', $result['total'] );
		$response->header( 'X-WP-TotalPages', $result['pages'] );

		return $response;
	}

	/**
	 * Get a single code by ID.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_code( $request ) {
		$codes_service = $this->get_codes_service();
		$code_id       = (int) $request->get_param( 'id' );

		$code = $codes_service->get_code_by_id( $code_id );

		if ( ! $code ) {
			return new WP_Error(
				'code_not_found',
				/* translators: %d: Code ID */
				sprintf( __( 'Code with ID %d not found.', 'product-availability-checker' ), $code_id ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $code );
	}

	/**
	 * Add a new code.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function add_code( $request ) {
		$codes_service = $this->get_codes_service();

		$data = array(
			'zip_code'     => $request->get_param( 'zip_code' ),
			'availability' => $request->get_param( 'availability' ),
			'message'      => $request->get_param( 'message' ),
		);

		$result = $codes_service->create_code( $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result, 201 );
	}

	/**
	 * Update a code.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function update_code( $request ) {
		$codes_service = $this->get_codes_service();
		$code_id       = (int) $request->get_param( 'id' );

		$data = array();

		if ( $request->has_param( 'zip_code' ) ) {
			$data['zip_code'] = $request->get_param( 'zip_code' );
		}

		if ( $request->has_param( 'availability' ) ) {
			$data['availability'] = $request->get_param( 'availability' );
		}

		if ( $request->has_param( 'message' ) ) {
			$data['message'] = $request->get_param( 'message' );
		}

		$result = $codes_service->update_code( $code_id, $data );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $result );
	}

	/**
	 * Delete a code.
	 *
	 * @since 1.0.0
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function delete_code( $request ) {
		$codes_service = $this->get_codes_service();
		$code_id       = (int) $request->get_param( 'id' );

		$result = $codes_service->delete_code( $code_id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response(
			array(
				'deleted' => true,
				/* translators: %d: Code ID */
				'message' => sprintf( __( 'Code with ID %d has been deleted.', 'product-availability-checker' ), $code_id ),
			)
		);
	}

	/**
	 * Get collection parameters.
	 *
	 * @since 1.0.0
	 *
	 * @return array Collection parameters.
	 */
	public function get_collection_params() {
		return array(
			'page'         => array(
				'description'       => __( 'Current page of the collection.', 'product-availability-checker' ),
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page'     => array(
				'description'       => __( 'Maximum number of items to be returned in result set.', 'product-availability-checker' ),
				'type'              => 'integer',
				'default'           => 10,
				'sanitize_callback' => 'absint',
			),
			'search'       => array(
				'description'       => __( 'Limit results to those matching a string.', 'product-availability-checker' ),
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'availability' => array(
				'description'       => __( 'Filter by availability status.', 'product-availability-checker' ),
				'type'              => 'string',
				'enum'              => array( 'available', 'unavailable' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'orderby'      => array(
				'description'       => __( 'Sort collection by field.', 'product-availability-checker' ),
				'type'              => 'string',
				'default'           => 'id',
				'enum'              => array( 'id', 'zip_code', 'availability', 'created_at' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
			'order'        => array(
				'description'       => __( 'Order sort attribute ascending or descending.', 'product-availability-checker' ),
				'type'              => 'string',
				'default'           => 'DESC',
				'enum'              => array( 'ASC', 'DESC' ),
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get schema.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'code',
			'type'       => 'object',
			'properties' => array(
				'id'           => array(
					'description' => __( 'Unique identifier for the resource.', 'product-availability-checker' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'zip_code'     => array(
					'description' => __( 'Zip code.', 'product-availability-checker' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'    => true,
				),
				'availability' => array(
					'description' => __( 'Availability status.', 'product-availability-checker' ),
					'type'        => 'string',
					'enum'        => array( 'available', 'unavailable' ),
					'context'     => array( 'view', 'edit', 'embed' ),
					'required'    => true,
				),
				'message'      => array(
					'description' => __( 'Availability message.', 'product-availability-checker' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit', 'embed' ),
				),
				'created_at'   => array(
					'description' => __( 'Creation date.', 'product-availability-checker' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
				'updated_at'   => array(
					'description' => __( 'Last update date.', 'product-availability-checker' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit', 'embed' ),
					'readonly'    => true,
				),
			),
		);

		return $schema;
	}
}
