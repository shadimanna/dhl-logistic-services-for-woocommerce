<?php
/**
 * Class Utils file.
 *
 * @package PR\DHL\Utils
 */

namespace PR\DHL\Utils;

use Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController;

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
		/*
		 * Only for Packet
		 */
		if ( 'DE' !== PR_DHL()->get_base_country() ) {
			return false;
		}

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

	/**
	 * Check if HPOS mode is enabled.
	 *
	 * @return bool.
	 */
	public static function is_HPOS() {
		// if WC older than 4.4.0
		if ( ! function_exists( 'wc_get_container' ) ) {
			return false;
		}

		try {
			$wc_container = wc_get_container()->get( CustomOrdersTableController::class );

			// old WC versions compatibility
			if ( ! is_null( $wc_container ) ) {
				return $wc_container->custom_orders_table_usage_is_enabled();
			}
		} catch ( \Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Get PDDP supported countries.
	 *
	 * @return array
	 */
	public static function PDDP_supported_countries() {
		return array( 'GB', 'NO', 'CH', 'US', 'PR' );
	}

	/**
	 * Returns true if the order contains items that need shipping.
	 *
	 * @param \WC_Order $order Order object.
	 *
	 * @return bool
	 */
	public static function order_needs_shipping( \WC_Order $order ): bool {
		foreach ( $order->get_items() as $item ) {
			if ( ! is_a( $item, 'WC_Order_Item_Product' ) ) {
				continue;
			}

			$product = $item->get_product();
			if ( is_a( $product, 'WC_Product' ) && $product->needs_shipping() ) {
				return true;
			}
		}

		return false;
	}
}
