<?php
/**
 * Codes service class for managing zip codes and availability.
 *
 * @package product-availability-checker
 */

namespace PAVC\Services;

/**
 * Class Codes_Service
 */
class Codes_Service {

	/**
	 * Option name for storing codes data.
	 *
	 * @var string
	 */
	private $option_name = 'pavc_codes';

	/**
	 * Create a new code entry.
	 *
	 * @param array $data Code data containing zip_code, availability, and optional message.
	 * @return array|WP_Error Created code data with generated ID or error.
	 */
	public function create_code( $data ) {
		// Validate required fields.
		if ( empty( $data['zip_code'] ) ) {
			return new \WP_Error(
				'missing_zip_code',
				/* translators: Error message when zip code is missing */
				__( 'Zip code is required.', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		// Validate zip code format.
		if ( ! $this->validate_zip_code( $data['zip_code'] ) ) {
			return new \WP_Error(
				'invalid_zip_code',
				/* translators: Error message when zip code format is invalid */
				__( 'Zip code format is invalid. Please enter a valid zip code.', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $data['availability'] ) || ! in_array( $data['availability'], array( 'available', 'unavailable' ), true ) ) {
			return new \WP_Error(
				'invalid_availability',
				/* translators: Error message when availability status is invalid */
				__( 'Availability must be either "available" or "unavailable".', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		// Check if zip code already exists.
		$existing_code = $this->get_code_by_zip( $data['zip_code'] );
		if ( $existing_code ) {
			return new \WP_Error(
				'zip_code_exists',
				/* translators: %s: Zip code */
				sprintf( __( 'Zip code "%s" already exists.', 'product-availability-checker' ), $data['zip_code'] ),
				array( 'status' => 409 )
			);
		}

		$codes = $this->get_all_codes();

		// Generate new ID.
		$new_id = $this->generate_next_id( $codes );

		// Prepare code data.
		$code_data = array(
			'id'           => $new_id,
			'zip_code'     => sanitize_text_field( $data['zip_code'] ),
			'availability' => sanitize_text_field( $data['availability'] ),
			'message'      => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : '',
			'created_at'   => current_time( 'mysql' ),
			'updated_at'   => current_time( 'mysql' ),
		);

		// Add to codes array.
		$codes[ $new_id ] = $code_data;

		// Save to options.
		if ( ! update_option( $this->option_name, $codes ) ) {
			return new \WP_Error(
				'save_failed',
				/* translators: Error message when saving code fails */
				__( 'Failed to save code data.', 'product-availability-checker' ),
				array( 'status' => 500 )
			);
		}

		return $code_data;
	}

	/**
	 * Get code by ID.
	 *
	 * @param int $id Code ID.
	 * @return array|null Code data or null if not found.
	 */
	public function get_code_by_id( $id ) {
		$codes = $this->get_all_codes();
		return isset( $codes[ $id ] ) ? $codes[ $id ] : null;
	}

	/**
	 * Get code by zip code.
	 *
	 * @param string $zip_code Zip code to search for.
	 * @return array|null Code data or null if not found.
	 */
	public function get_code_by_zip( $zip_code ) {
		$codes = $this->get_all_codes();

		foreach ( $codes as $code ) {
			if ( $code['zip_code'] === $zip_code ) {
				return $code;
			}
		}

		return null;
	}

	/**
	 * Get all codes with optional filtering and pagination.
	 *
	 * @param array $args Query arguments.
	 * @return array Array of codes.
	 */
	public function get_codes( $args = array() ) {
		$defaults = array(
			'per_page'     => 10,
			'page'         => 1,
			'search'       => '',
			'availability' => '',
			'orderby'      => 'id',
			'order'        => 'DESC',
		);

		$args  = wp_parse_args( $args, $defaults );
		$codes = $this->get_all_codes();

		// Apply search filter.
		if ( ! empty( $args['search'] ) ) {
			$search_term = strtolower( $args['search'] );
			$codes       = array_filter(
				$codes,
				function ( $code ) use ( $search_term ) {
					return strpos( strtolower( $code['zip_code'] ), $search_term ) !== false ||
						strpos( strtolower( $code['message'] ), $search_term ) !== false;
				}
			);
		}

		// Apply availability filter.
		if ( ! empty( $args['availability'] ) ) {
			$codes = array_filter(
				$codes,
				function ( $code ) use ( $args ) {
					return $code['availability'] === $args['availability'];
				}
			);
		}

		// Sort codes.
		$codes = $this->sort_codes( $codes, $args['orderby'], $args['order'] );

		// Apply pagination.
		$total  = count( $codes );
		$offset = ( $args['page'] - 1 ) * $args['per_page'];
		$codes  = array_slice( $codes, $offset, $args['per_page'], true );

		return array(
			'codes' => array_values( $codes ),
			'total' => $total,
			'pages' => ceil( $total / $args['per_page'] ),
			'page'  => $args['page'],
		);
	}

	/**
	 * Update a code by ID.
	 *
	 * @param int   $id Code ID.
	 * @param array $data Updated code data.
	 * @return array|\WP_Error Updated code data or error.
	 */
	public function update_code( $id, $data ) {
		$codes = $this->get_all_codes();

		if ( ! isset( $codes[ $id ] ) ) {
			return new \WP_Error(
				'code_not_found',
				/* translators: %d: Code ID */
				sprintf( __( 'Code with ID %d not found.', 'product-availability-checker' ), $id ),
				array( 'status' => 404 )
			);
		}

		$existing_code = $codes[ $id ];

		// Validate availability if provided.
		if ( isset( $data['availability'] ) && ! in_array( $data['availability'], array( 'available', 'unavailable' ), true ) ) {
			return new \WP_Error(
				'invalid_availability',
				/* translators: Error message when availability status is invalid */
				__( 'Availability must be either "available" or "unavailable".', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		// Validate zip code format if provided.
		if ( isset( $data['zip_code'] ) && ! $this->validate_zip_code( $data['zip_code'] ) ) {
			return new \WP_Error(
				'invalid_zip_code',
				/* translators: Error message when zip code format is invalid */
				__( 'Zip code format is invalid. Please enter a valid zip code.', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		// Check if zip code already exists (excluding current code).
		if ( isset( $data['zip_code'] ) && $data['zip_code'] !== $existing_code['zip_code'] ) {
			$existing_zip = $this->get_code_by_zip( $data['zip_code'] );
			if ( $existing_zip && $existing_zip['id'] !== $id ) {
				return new \WP_Error(
					'zip_code_exists',
					/* translators: %s: Zip code */
					sprintf( __( 'Zip code "%s" already exists.', 'product-availability-checker' ), $data['zip_code'] ),
					array( 'status' => 409 )
				);
			}
		}

		// Update code data.
		$updated_data = array_merge(
			$existing_code,
			array(
				'zip_code'     => isset( $data['zip_code'] ) ? sanitize_text_field( $data['zip_code'] ) : $existing_code['zip_code'],
				'availability' => isset( $data['availability'] ) ? sanitize_text_field( $data['availability'] ) : $existing_code['availability'],
				'message'      => isset( $data['message'] ) ? sanitize_textarea_field( $data['message'] ) : $existing_code['message'],
				'updated_at'   => current_time( 'mysql' ),
			)
		);

		$codes[ $id ] = $updated_data;

		// Save to options.
		if ( ! update_option( $this->option_name, $codes ) ) {
			return new \WP_Error(
				'save_failed',
				/* translators: Error message when updating code fails */
				__( 'Failed to update code data.', 'product-availability-checker' ),
				array( 'status' => 500 )
			);
		}

		return $updated_data;
	}

	/**
	 * Delete a code by ID.
	 *
	 * @param int $id Code ID.
	 * @return bool|\WP_Error True on success or error.
	 */
	public function delete_code( $id ) {
		$codes = $this->get_all_codes();

		if ( ! isset( $codes[ $id ] ) ) {
			return new \WP_Error(
				'code_not_found',
				/* translators: %d: Code ID */
				sprintf( __( 'Code with ID %d not found.', 'product-availability-checker' ), $id ),
				array( 'status' => 404 )
			);
		}

		unset( $codes[ $id ] );

		// Save to options.
		if ( ! update_option( $this->option_name, $codes ) ) {
			return new \WP_Error(
				'delete_failed',
				/* translators: Error message when deleting code fails */
				__( 'Failed to delete code data.', 'product-availability-checker' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Get all codes from options.
	 *
	 * @return array All codes data.
	 */
	private function get_all_codes() {
		return get_option( $this->option_name, array() );
	}

	/**
	 * Generate next available ID.
	 *
	 * @param array $codes Existing codes array.
	 * @return int Next available ID.
	 */
	private function generate_next_id( $codes ) {
		if ( empty( $codes ) ) {
			return 1;
		}

		$max_id = max( array_keys( $codes ) );
		return $max_id + 1;
	}

	/**
	 * Sort codes array.
	 *
	 * @param array  $codes Array of codes.
	 * @param string $orderby Field to sort by.
	 * @param string $order Sort order (ASC/DESC).
	 * @return array Sorted codes array.
	 */
	private function sort_codes( $codes, $orderby, $order ) {
		$order = strtoupper( $order ) === 'ASC' ? SORT_ASC : SORT_DESC;

		switch ( $orderby ) {
			case 'zip_code':
				uasort(
					$codes,
					function ( $a, $b ) use ( $order ) {
						$result = strcmp( $a['zip_code'], $b['zip_code'] );
						return $order === SORT_ASC ? $result : -$result;
					}
				);
				break;
			case 'availability':
				uasort(
					$codes,
					function ( $a, $b ) use ( $order ) {
						$result = strcmp( $a['availability'], $b['availability'] );
						return $order === SORT_ASC ? $result : -$result;
					}
				);
				break;
			case 'created_at':
				uasort(
					$codes,
					function ( $a, $b ) use ( $order ) {
						$result = strcmp( $a['created_at'], $b['created_at'] );
						return $order === SORT_ASC ? $result : -$result;
					}
				);
				break;
			case 'id':
			default:
				uasort(
					$codes,
					function ( $a, $b ) use ( $order ) {
						$result = $a['id'] - $b['id'];
						return $order === SORT_ASC ? $result : -$result;
					}
				);
				break;
		}

		return $codes;
	}

	/**
	 * Clear all codes data (useful for testing or reset).
	 *
	 * @return bool True on success.
	 */
	public function clear_all_codes() {
		return delete_option( $this->option_name );
	}

	/**
	 * Import codes from array (useful for bulk operations).
	 *
	 * @param array $codes_data Array of codes to import.
	 * @param bool  $overwrite Whether to overwrite existing data.
	 * @return bool|\WP_Error True on success or error.
	 */
	public function import_codes( $codes_data, $overwrite = false ) {
		if ( ! is_array( $codes_data ) ) {
			return new \WP_Error(
				'invalid_data',
				/* translators: Error message when import data is invalid */
				__( 'Import data must be an array.', 'product-availability-checker' ),
				array( 'status' => 400 )
			);
		}

		$existing_codes = $overwrite ? array() : $this->get_all_codes();
		$next_id        = $this->generate_next_id( $existing_codes );

		foreach ( $codes_data as $code_data ) {
			if ( ! isset( $code_data['zip_code'] ) || ! isset( $code_data['availability'] ) ) {
				continue;
			}

			// Skip if zip code already exists and not overwriting.
			if ( ! $overwrite && $this->get_code_by_zip( $code_data['zip_code'] ) ) {
				continue;
			}

			$new_code = array(
				'id'           => $next_id++,
				'zip_code'     => sanitize_text_field( $code_data['zip_code'] ),
				'availability' => sanitize_text_field( $code_data['availability'] ),
				'message'      => isset( $code_data['message'] ) ? sanitize_textarea_field( $code_data['message'] ) : '',
				'created_at'   => current_time( 'mysql' ),
				'updated_at'   => current_time( 'mysql' ),
			);

			$existing_codes[ $new_code['id'] ] = $new_code;
		}

		if ( ! update_option( $this->option_name, $existing_codes ) ) {
			return new \WP_Error(
				'import_failed',
				/* translators: Error message when importing codes fails */
				__( 'Failed to import codes data.', 'product-availability-checker' ),
				array( 'status' => 500 )
			);
		}

		return true;
	}

	/**
	 * Validate zip code format using regex.
	 *
	 * @param string $zip_code Zip code to validate.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_zip_code( $zip_code ) {
		if ( empty( $zip_code ) || ! is_string( $zip_code ) ) {
			return false;
		}

		// Remove any whitespace
		$zip_code = trim( $zip_code );
		$patterns = array(
			'/^\d{5}$/',
			'/^\d{5}-\d{4}$/',
			'/^[A-Z]\d[A-Z] \d[A-Z]\d$/',
			'/^[A-Z]{1,2}\d[A-Z\d]? \d[A-Z]{2}$/',
			'/^[A-Z0-9]{2,10}$/',
			'/^\d{4,6}$/',
		);

		// Convert to uppercase for consistent pattern matching
		$zip_code_upper = strtoupper( $zip_code );

		// Check against each pattern
		foreach ( $patterns as $pattern ) {
			if ( preg_match( $pattern, $zip_code_upper ) ) {
				return true;
			}
		}

		return false;
	}
}
