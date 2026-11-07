<?php
/**
 * Adds the DHL preferred day fee to the cart as a gross amount.
 *
 * @package PR_DHL
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PR_DHL_Preferred_Day_Fee' ) ) :

	/**
	 * The merchant enters the preferred day fee as a gross price. WooCommerce always
	 * adds tax on top of a fee amount, so the fee is added as the net part and its
	 * tax is fixed to the tax included in the gross price, at the shipping tax rates.
	 *
	 * Static on purpose: the block and the classic checkout hooks add the fee from
	 * different objects and must share one record of it per request, and the order
	 * hooks run without either of them.
	 *
	 * @since 4.1.2
	 */
	class PR_DHL_Preferred_Day_Fee {

		/**
		 * Order fee item meta holding the gross amount charged.
		 *
		 * @since 4.1.2
		 * @var string
		 */
		const GROSS_META = '_pr_dhl_gross_amount';

		/**
		 * Order fee item meta holding the net amount this class last set.
		 *
		 * @since 4.1.2
		 * @var string
		 */
		const NET_META = '_pr_dhl_net_amount';

		/**
		 * Fees added here, keyed by fee ID: the gross amount and the rounded taxes it includes, keyed by rate ID.
		 *
		 * @var array
		 */
		private static $fees = array();

		/**
		 * Change of the fee lines' net amount per order, while WooCommerce calculates the order totals with taxes.
		 *
		 * @var array
		 */
		private static $net_changes = array();

		/**
		 * Register the hooks that keep the fee at its gross amount in the cart and on the order.
		 *
		 * @since 4.1.2
		 * @return void
		 */
		public static function init() {
			add_filter( 'woocommerce_cart_totals_get_fees_from_cart_taxes', array( __CLASS__, 'use_included_taxes' ), 10, 2 );
			add_action( 'woocommerce_checkout_create_order_fee_item', array( __CLASS__, 'save_gross_amount' ), 10, 3 );
			add_action( 'woocommerce_order_before_calculate_totals', array( __CLASS__, 'start_order_totals' ), 10, 2 );
			add_action( 'woocommerce_order_before_calculate_taxes', array( __CLASS__, 'keep_gross_amount_when_exempt' ), 10, 2 );
			add_action( 'woocommerce_order_item_after_calculate_taxes', array( __CLASS__, 'keep_gross_amount' ), 10, 2 );
			add_action( 'woocommerce_order_after_calculate_totals', array( __CLASS__, 'update_order_total' ), 10, 2 );
		}

		/**
		 * Add a fee whose total including tax is exactly the gross amount.
		 *
		 * @since 4.1.2
		 * @param \WC_Cart $cart  The cart.
		 * @param string   $name  Fee name.
		 * @param float    $gross Amount the shopper pays, tax included.
		 * @return void
		 */
		public static function add_to_cart( $cart, $name, $gross ) {
			list( $tax_class, $location ) = self::get_tax_class_and_location( $cart );

			$taxes = self::get_included_taxes( $gross, null === $tax_class ? array() : self::get_shipping_rates( $location, $tax_class ) );
			$fee   = $cart->fees_api()->add_fee(
				array(
					'name'      => $name,
					'amount'    => $gross - array_sum( $taxes ),
					'taxable'   => null !== $tax_class,
					'tax_class' => (string) $tax_class,
				)
			);

			if ( is_wp_error( $fee ) ) {
				return;
			}

			self::$fees[ $fee->id ] = array(
				'gross' => $gross,
				'taxes' => $taxes,
			);
		}

		/**
		 * Replace the tax WooCommerce calculated on the net part with the tax included in the gross amount.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param array  $taxes Fee taxes in cents, keyed by rate ID.
		 * @param object $fee   Fee totals from WC_Cart_Totals.
		 * @return array
		 */
		public static function use_included_taxes( $taxes, $fee ) {
			if ( empty( $fee->taxable ) || ! isset( $fee->object->id, self::$fees[ $fee->object->id ] ) ) {
				return $taxes;
			}

			return wc_add_number_precision_deep( self::$fees[ $fee->object->id ]['taxes'] );
		}

		/**
		 * Remember the gross amount on the order fee line, so a later tax recalculation keeps it.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param \WC_Order_Item_Fee $item    The order fee line.
		 * @param string             $fee_key The cart fee ID.
		 * @param object             $fee     The cart fee.
		 * @return void
		 */
		public static function save_gross_amount( $item, $fee_key, $fee ) {
			if ( ! isset( $fee->id, self::$fees[ $fee->id ] ) ) {
				return;
			}

			$item->update_meta_data( self::GROSS_META, wc_format_decimal( self::$fees[ $fee->id ]['gross'] ) );
			$item->update_meta_data( self::NET_META, wc_format_decimal( $fee->total ) );
		}

		/**
		 * Start recording net amount changes: WooCommerce adds up the fees before it calculates the taxes.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param bool      $and_taxes Whether taxes are calculated too.
		 * @param \WC_Order $order     The order.
		 * @return void
		 */
		public static function start_order_totals( $and_taxes, $order ) {
			self::$net_changes[ $order->get_id() ] = 0.0;
		}

		/**
		 * Split the gross amount again after WooCommerce recalculated the fee line's tax from its net amount.
		 *
		 * A line whose net amount was edited since is left as WooCommerce calculated it.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param \WC_Order_Item $item              The order line.
		 * @param array          $calculate_tax_for Location and tax class used.
		 * @return void
		 */
		public static function keep_gross_amount( $item, $calculate_tax_for ) {
			if ( ! self::is_unedited_fee_line( $item ) ) {
				return;
			}

			$taxable = wc_tax_enabled() && 'taxable' === $item->get_tax_status() && '0' !== $item->get_tax_class();

			self::split_gross_amount( $item, $taxable ? WC_Tax::find_shipping_rates( $calculate_tax_for ) : array() );
		}

		/**
		 * Charge the whole gross amount as net on a VAT exempt order, since WooCommerce then removes the fee tax without firing the item action.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param array     $args  Tax calculation arguments.
		 * @param \WC_Order $order The order.
		 * @return void
		 */
		public static function keep_gross_amount_when_exempt( $args, $order ) {
			/** This filter is documented in woocommerce/includes/abstracts/abstract-wc-order.php */
			if ( ! apply_filters( 'woocommerce_order_is_vat_exempt', 'yes' === $order->get_meta( 'is_vat_exempt' ), $order ) ) {
				return;
			}

			foreach ( $order->get_fees() as $item ) {
				if ( self::is_unedited_fee_line( $item ) ) {
					self::split_gross_amount( $item, array() );
				}
			}
		}

		/**
		 * Add a net amount change made after WooCommerce added up the fees to the order total, which WooCommerce saves next.
		 *
		 * @internal
		 *
		 * @since 4.1.2
		 * @param bool      $and_taxes Whether taxes were calculated.
		 * @param \WC_Order $order     The order.
		 * @return void
		 */
		public static function update_order_total( $and_taxes, $order ) {
			if ( ! isset( self::$net_changes[ $order->get_id() ] ) ) {
				return;
			}

			$change = self::$net_changes[ $order->get_id() ];
			unset( self::$net_changes[ $order->get_id() ] );

			if ( 0.0 !== round( $change, wc_get_price_decimals() ) ) {
				$order->set_total( wc_format_decimal( (float) $order->get_total() + $change, wc_get_price_decimals() ) );
			}
		}

		/**
		 * Whether the order line is a fee added here whose net amount nobody changed since.
		 *
		 * @param \WC_Order_Item $item The order line.
		 * @return bool
		 */
		private static function is_unedited_fee_line( $item ) {
			if ( ! $item instanceof WC_Order_Item_Fee || '' === $item->get_meta( self::GROSS_META ) ) {
				return false;
			}

			$decimals = wc_get_price_decimals();

			return round( (float) $item->get_total(), $decimals ) === round( (float) $item->get_meta( self::NET_META ), $decimals );
		}

		/**
		 * Set the fee line to its gross amount split into net and the tax included at the given rates.
		 *
		 * @param \WC_Order_Item_Fee $item  The fee line.
		 * @param array              $rates Tax rates.
		 * @return void
		 */
		private static function split_gross_amount( $item, $rates ) {
			$gross = (float) $item->get_meta( self::GROSS_META );
			$taxes = self::get_included_taxes( $gross, $rates );
			$net   = wc_format_decimal( $gross - array_sum( $taxes ) );

			if ( isset( self::$net_changes[ $item->get_order_id() ] ) ) {
				self::$net_changes[ $item->get_order_id() ] += (float) $net - (float) $item->get_total();
			}

			$item->set_total( $net );
			$item->set_taxes( $taxes ? array( 'total' => $taxes ) : false );
			$item->update_meta_data( self::NET_META, $net );
		}

		/**
		 * The tax included in a gross amount, rounded per rate.
		 *
		 * @param float $gross Amount including tax.
		 * @param array $rates Tax rates.
		 * @return array Taxes keyed by rate ID.
		 */
		private static function get_included_taxes( $gross, $rates ) {
			return array_map( 'wc_round_tax_total', WC_Tax::calc_tax( $gross, $rates, true ) );
		}

		/**
		 * Shipping tax rates of a tax class at a location, as WC_Tax::get_shipping_tax_rates() finds them.
		 *
		 * @param array  $location  Country, state, postcode and city.
		 * @param string $tax_class Tax class.
		 * @return array
		 */
		private static function get_shipping_rates( $location, $tax_class ) {
			if ( 4 !== count( $location ) ) {
				return array();
			}

			list( $country, $state, $postcode, $city ) = $location;

			return WC_Tax::find_shipping_rates(
				array(
					'country'   => $country,
					'state'     => $state,
					'postcode'  => $postcode,
					'city'      => $city,
					'tax_class' => $tax_class,
				)
			);
		}

		/**
		 * The tax class WooCommerce uses for shipping on this cart, and the tax location it looks the rates up for.
		 *
		 * Mirrors WC_Tax::get_shipping_tax_rates(), whose class lookup is private:
		 * with "based on cart items" the standard class wins, then a single item class,
		 * then the first item class in settings order, otherwise the standard class.
		 *
		 * @param \WC_Cart $cart The cart.
		 * @return array The class, null when no tax applies to the fee, and the location.
		 */
		private static function get_tax_class_and_location( $cart ) {
			$customer = $cart->get_customer();

			if ( ! $customer || ! wc_tax_enabled() || $customer->get_is_vat_exempt() ) {
				return array( null, array() );
			}

			$tax_class = get_option( 'woocommerce_shipping_tax_class' );

			if ( 'inherit' === $tax_class ) {
				$item_classes = $cart->get_cart_item_tax_classes_for_shipping();

				if ( ! $item_classes ) {
					return array( null, array() );
				}

				$ordered   = array_values( array_intersect( WC_Tax::get_tax_class_slugs(), $item_classes ) );
				$tax_class = '';

				if ( ! in_array( '', $item_classes, true ) ) {
					if ( 1 === count( $item_classes ) ) {
						$tax_class = reset( $item_classes );
					} elseif ( $ordered ) {
						$tax_class = $ordered[0];
					}
				}
			}

			$location = WC_Tax::get_tax_location( $tax_class, $customer );

			/** This filter is documented in woocommerce/includes/class-wc-tax.php */
			$tax_class = apply_filters( 'woocommerce_shipping_tax_class', $tax_class, $cart, $customer, $location );

			return array( $tax_class, $location );
		}
	}

endif;
