<?php
/**
 * Services manager class.
 *
 * @package product-availability-checker
 */

namespace PAVC\Services;

use PAVC\Traits\Singleton;

/**
 * Class Service_Manager
 */
class Service_Manager {

	use Singleton;

	/**
	 * Registered services.
	 *
	 * @var array
	 */
	protected $services = array();

	/**
	 * Reflection cache to avoid repeated reflection operations.
	 *
	 * @var array
	 */
	private $reflection_cache = array();

	/**
	 * Private constructor to enforce singleton.
	 */
	private function __construct() {}

	/**
	 * Register a service dynamically if not already registered.
	 *
	 * @param string $alias Service alias (e.g., 'hydration').
	 * @param string $class Fully qualified service class name.
	 * @param bool   $singleton Whether to store as a singleton.
	 * @throws \InvalidArgumentException If class doesn't exist.
	 */
	public function register( $alias, $class, $singleton = true ) {
		// Validate inputs.
		if ( empty( $alias ) || ! is_string( $alias ) ) {
			/* translators: Error message when service alias is invalid */
			throw new \InvalidArgumentException( __( 'Service alias must be a non-empty string.', 'product-availability-checker' ) );
		}

		if ( empty( $class ) || ! is_string( $class ) ) {
			/* translators: Error message when service class is invalid */
			throw new \InvalidArgumentException( __( 'Service class must be a non-empty string.', 'product-availability-checker' ) );
		}

		if ( ! class_exists( $class ) ) {
			/* translators: %s: Service class name */
			throw new \InvalidArgumentException( sprintf( __( "Service class '%s' does not exist.", 'product-availability-checker' ), $class ) );
		}

		$alias_key = $this->get_service_key( $alias );

		if ( ! isset( $this->services[ $alias_key ] ) ) {
			$this->services[ $alias_key ] = $singleton ? new $class() : $class;
		}
	}

	/**
	 * Retrieve a service instance dynamically.
	 *
	 * @param string $class The fully qualified service class name.
	 * @return object|null
	 */
	public static function get( $class ) {
		return self::instance()->resolve( $class );
	}

	/**
	 * Resolve a service instance, registering it if necessary.
	 *
	 * @param string $class The fully qualified service class name.
	 * @return object|null
	 * @throws \InvalidArgumentException If class doesn't exist.
	 */
	public function resolve( $class ) {
		// Validate input.
		if ( empty( $class ) || ! is_string( $class ) ) {
			/* translators: Error message when service class is invalid */
			throw new \InvalidArgumentException( __( 'Service class must be a non-empty string.', 'product-availability-checker' ) );
		}

		if ( ! class_exists( $class ) ) {
			/* translators: %s: Service class name */
			throw new \InvalidArgumentException( sprintf( __( "Service class '%s' does not exist.", 'product-availability-checker' ), $class ) );
		}

		$alias_key = $this->get_service_key( $class );

		if ( ! isset( $this->services[ $alias_key ] ) ) {
			$this->register( $class, $class );
		}

		$service = $this->services[ $alias_key ];

		return is_object( $service ) ? $service : new $service();
	}

	/**
	 * Enable magic static calls like Service_Manager::hydration().
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Arguments.
	 * @return object|null
	 */
	public static function __callStatic( $name, $arguments ) {
		return self::instance()->resolve_by_method( $name, $arguments );
	}

	/**
	 * Enable magic instance calls like $manager->hydration().
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Arguments.
	 * @return object|null
	 */
	public function __call( $name, $arguments ) {
		return $this->resolve_by_method( $name, $arguments );
	}

	/**
	 * Resolve a service by a method call (e.g., `Service_Manager::hydration()`).
	 *
	 * @param string $name Method name.
	 * @param array  $arguments Arguments.
	 * @return object|null
	 */
	private function resolve_by_method( $name, $arguments ) {
		foreach ( $this->services as $class ) {
			$reflection = $this->get_reflection_class( $class );
			$class_name = strtolower( $reflection->getShortName() );

			if ( $class_name === strtolower( $name ) ) {
				return $this->resolve( $reflection->getName() );
			}
		}

		return null;
	}

	/**
	 * Convert a class name to a unique service key.
	 *
	 * @param string $class Class name or alias.
	 * @return string
	 */
	private function get_service_key( $class ) {
		return strtolower( str_replace( '\\', '_', $class ) );
	}

	/**
	 * Get a cached reflection class instance.
	 *
	 * @param string|object $class Class name or object.
	 * @return \ReflectionClass
	 */
	private function get_reflection_class( $class ) {
		$class_name = is_object( $class ) ? get_class( $class ) : $class;

		if ( ! isset( $this->reflection_cache[ $class_name ] ) ) {
			$this->reflection_cache[ $class_name ] = new \ReflectionClass( $class );
		}

		return $this->reflection_cache[ $class_name ];
	}

	/**
	 * Get all registered services.
	 *
	 * @return array Array of registered services.
	 */
	public function get_services() {
		return $this->services;
	}

	/**
	 * Check if a service is registered.
	 *
	 * @param string $class The fully qualified service class name or alias.
	 * @return bool True if service is registered, false otherwise.
	 */
	public function has_service( $class ) {
		$alias_key = $this->get_service_key( $class );
		return isset( $this->services[ $alias_key ] );
	}

	/**
	 * Unregister a service.
	 *
	 * @param string $class The fully qualified service class name or alias.
	 * @return bool True if service was unregistered, false if it wasn't registered.
	 */
	public function unregister( $class ) {
		$alias_key = $this->get_service_key( $class );

		if ( isset( $this->services[ $alias_key ] ) ) {
			unset( $this->services[ $alias_key ] );

			return true;
		}

		return false;
	}

	/**
	 * Clear all registered services.
	 *
	 * @return void
	 */
	public function clear_services() {
		$this->services         = array();
		$this->reflection_cache = array();
	}
}
