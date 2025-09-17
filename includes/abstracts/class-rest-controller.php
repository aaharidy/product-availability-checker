<?php
/**
 * Class Rest_Controller
 *
 * @package product-availability-checker
 */

namespace PAVC\Abstracts;

use WP_REST_Controller;

/**
 * Base class for rest controllers.
 */
class REST_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = 'pavc/v1';

	/**
	 * Route base.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Check admin permission
	 *
	 * @return bool
	 */
	public function check_admin_permission() {
		return current_user_can( 'manage_options' ) || current_user_can( 'manage_woocommerce' );
	}
}
