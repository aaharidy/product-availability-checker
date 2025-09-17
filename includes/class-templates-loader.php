<?php
/**
 * Template Loader for Product Availability Checker
 *
 * Handles loading templates with data passing
 *
 * @package product-availability-checker
 */

namespace PAVC;

/**
 * Template Loader class
 */
class Templates_Loader {

	/**
	 * Load a template with data
	 *
	 * @param string $template_path Path to the template from the templates directory.
	 * @param array  $data Data to pass to the template.
	 * @param bool   $return Whether to return the template content or echo it.
	 * @return string|void Template content if $return is true.
	 */
	public static function load( $template_path, $data = array(), $return = false ) {
		$template_file = PAVC_PLUGIN_DIR . 'templates/' . $template_path;

		if ( ! file_exists( $template_file ) ) {
			return '';
		}

		if ( $return ) {
			ob_start();
			self::load_template_with_data( $template_file, $data );
			return ob_get_clean();
		}

		self::load_template_with_data( $template_file, $data );
	}

	/**
	 * Load a template with data, making data available to the template
	 *
	 * @param string $template_file Full path to template file.
	 * @param array  $data Data to pass to the template.
	 */
	private static function load_template_with_data( $template_file, $data ) {
		extract( $data );
		include $template_file;
	}

	/**
	 * Get component HTML
	 *
	 * @param string $component_path Path to the component template.
	 * @param array  $data Data to pass to the component.
	 * @return string Component HTML.
	 */
	public static function get_template_part( $component_path, $data = array() ) {
		return self::load( 'parts/' . $component_path, $data, true );
	}

	/**
	 * Load admin template
	 *
	 * @param string $template_name Template name (without .php extension).
	 * @param array  $data Data to pass to the template.
	 * @param bool   $return Whether to return the template content or echo it.
	 * @return string|void Template content if $return is true.
	 */
	public static function load_admin( $template_name, $data = array(), $return = false ) {
		// Add .php extension if not present
		if ( substr( $template_name, -4 ) !== '.php' ) {
			$template_name .= '.php';
		}

		return self::load( 'admin/' . $template_name, $data, $return );
	}

	/**
	 * Load frontend template
	 *
	 * @param string $template_name Template name (without .php extension).
	 * @param array  $data Data to pass to the template.
	 * @param bool   $return Whether to return the template content or echo it.
	 * @return string|void Template content if $return is true.
	 */
	public static function load_frontend( $template_name, $data = array(), $return = false ) {
		// Add .php extension if not present
		if ( substr( $template_name, -4 ) !== '.php' ) {
			$template_name .= '.php';
		}

		return self::load( 'frontend/' . $template_name, $data, $return );
	}
}
