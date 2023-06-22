<?php
/**
 * Class Utils file.
 *
 * @package PR\DHL\Utils
 */

namespace PR\DHL\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Utils.
 *
 * @package PR\DHL\Utils;
 */
class API_Utils {
	/**
	 * Check if its a new merchant.
	 *
	 * @return bool.
	 */
	public static function is_new_merchant() {
		return empty( get_option( 'woocommerce_pr_dhl_paket_settings', array() ) );
	}

	/**
	 * Check if rest api enabled.
	 *
	 * @return bool.
	 */
	public static function is_rest_api_enabled() {
		$settings = get_option( 'woocommerce_pr_dhl_paket_settings', array() );

		return isset( $settings['dhl_default_api'] ) && 'rest-api' === $settings['dhl_default_api'];
	}
}
