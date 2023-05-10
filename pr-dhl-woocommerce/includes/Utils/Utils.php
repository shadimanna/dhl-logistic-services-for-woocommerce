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
class Utils {
	/**
	 * Check if its a new merchant.
	 *
	 * @return bool.
	 */
	public static function is_new_merchant() {
		return empty( get_option( 'woocommerce_pr_dhl_paket_settings', array() ) );
	}
}
