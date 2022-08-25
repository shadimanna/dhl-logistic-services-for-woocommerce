<?php
/**
 * A class of utilities for dealing with COT.
 */

namespace PR\DHL\Utils;

final class COTUtil{
	public static function custom_orders_table_usage_is_enabled() {
		$order_controller = wc_get_container()->get('Automattic\WooCommerce\Internal\DataStores\Orders\CustomOrdersTableController');
		if( isset( $order_controller ) ) {
			return $order_controller->custom_orders_table_usage_is_enabled();
		}

		return false;
	}
}