<?php
/**
 * Class REST_API
 *
 * @package product-availability-checker
 *
 * @since 1.0.0
 */

namespace PAVC\REST_API;

use PAVC\Traits\Singleton;
use PAVC\REST_API\Controllers\V1\REST_Codes_Controller;
use PAVC\Abstracts\REST_Controller;

/**
 * REST_API class.
 */
class REST_API {

	use Singleton;

	/**
	 * REST_API constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Register rest routes.
	 *
	 * @since 1.0.0
	 */
	public function register_rest_routes() {
		$controllers = array(
			REST_Codes_Controller::class,
		);

		foreach ( $controllers as $controller ) {
			$controller = new $controller();
			/** @var REST_Controller $controller */
			$controller->register_routes();
		}
	}
}
