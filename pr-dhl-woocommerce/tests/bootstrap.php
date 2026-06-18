<?php
/**
 * PHPUnit bootstrap.
 *
 * Loads the Composer autoloader (PSR-4 maps PR\DHL\ to includes/) and stubs the
 * minimal set of WordPress functions used by the code under test, so the unit
 * tests can run without a full WordPress install.
 */

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Minimal apply_filters() stub: returns the value unchanged.
	 *
	 * Sufficient for tests that don't register filter callbacks.
	 *
	 * @param string $hook_name The filter hook name (ignored).
	 * @param mixed  $value     The value to filter.
	 * @return mixed The unchanged value.
	 */
	function apply_filters( $hook_name, $value ) {
		return $value;
	}
}
