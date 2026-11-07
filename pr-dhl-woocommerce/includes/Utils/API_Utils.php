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
	 * Countries where Paperless Customs is available for DHL Paket International.
	 *
	 * Starts with Switzerland only; extend via the filter to add more later.
	 *
	 * @since [*next-version*]
	 *
	 * @return string[] ISO 3166-1 alpha-2 codes.
	 */
	public static function paperless_supported_countries() {
		/**
		 * Filters the countries where Paperless Customs is available.
		 *
		 * @since [*next-version*]
		 *
		 * @param string[] $countries ISO 3166-1 alpha-2 country codes.
		 */
		return apply_filters( 'pr_dhl_paperless_supported_countries', array( 'CH' ) );
	}

	/**
	 * Whether a shipment qualifies for Paperless Customs.
	 *
	 * Eligible only for DHL Paket International (V53WPAK) to a supported country,
	 * and never together with PDDP (DHL: the two are mutually exclusive).
	 *
	 * @since [*next-version*]
	 *
	 * @param string $country_iso2 Destination country, ISO 3166-1 alpha-2.
	 * @param string $product      DHL product code.
	 * @param bool   $pddp_enabled Whether PDDP is set on the order.
	 * @return bool
	 */
	public static function is_paperless_customs_eligible( $country_iso2, $product, $pddp_enabled = false ) {
		if ( $pddp_enabled ) {
			return false;
		}

		if ( 'V53WPAK' !== $product ) {
			return false;
		}

		$country_iso2 = strtoupper( trim( (string) $country_iso2 ) );

		return in_array( $country_iso2, self::paperless_supported_countries(), true );
	}

	/**
	 * The US and its territories, as ISO 3166-1 alpha-2 codes.
	 *
	 * Covers the US mainland, Puerto Rico, Guam, US Virgin Islands, American Samoa
	 * and the US Minor Outlying Islands.
	 *
	 * @return string[]
	 */
	public static function us_territories() {
		return array( 'US', 'GU', 'AS', 'PR', 'UM', 'VI' );
	}

	/**
	 * The US and its territories, as ISO 3166-1 alpha-3 codes.
	 *
	 * The alpha-3 counterpart of {@see self::us_territories()}, in the same order.
	 *
	 * @return string[]
	 */
	public static function us_territories_iso3() {
		return array( 'USA', 'GUM', 'ASM', 'PRI', 'UMI', 'VIR' );
	}

	/**
	 * Whether a country code refers to the US or one of its territories.
	 *
	 * Accepts either an ISO 3166-1 alpha-2 or alpha-3 code, so callers do not need to
	 * know which form they hold.
	 *
	 * @param  string $country_code Country code in alpha-2 or alpha-3 form.
	 * @return bool
	 */
	public static function is_us_territory( $country_code ) {
		$country_code = strtoupper( trim( (string) $country_code ) );

		return in_array( $country_code, self::us_territories(), true )
			|| in_array( $country_code, self::us_territories_iso3(), true );
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
