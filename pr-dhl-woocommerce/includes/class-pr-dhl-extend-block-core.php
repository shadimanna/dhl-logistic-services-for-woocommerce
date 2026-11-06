<?php

use Automattic\WooCommerce\Blocks\Package;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CartSchema;
use Automattic\WooCommerce\Blocks\StoreApi\Schemas\CheckoutSchema;

/**
 * DHL checkout Extend WC Core.
 */
if ( ! class_exists( 'PR_DHL_Extend_Block_core' ) ) :

	class PR_DHL_Extend_Block_core {

		/**
		 * Plugin Identifier, unique to each plugin.
		 *
		 * @var string
		 */
		private $name = 'pr-dhl';

		/**
		 * Bootstraps the class and hooks required data.
		 */
		public function __construct() {
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', [
				$this,
				'save_dhl_checkout_fields'
			], 10, 2 );

			add_action( 'woocommerce_store_api_checkout_update_order_meta', array( $this, 'remove_unpaid_preferred_day' ) );

			// Register the update callback when WooCommerce Blocks is loaded
			add_action( 'init', [ $this, 'register_store_api_callback' ] );

			// Register fee calculation
			add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_preferred_day_fee' ] );

			// Placing an order empties the cart; the selection must not carry over to
			// the next cart in the same session.
			add_action( 'woocommerce_cart_emptied', [ $this, 'clear_preferred_day_selection' ] );


		}


		/**
		 * Saves the dhl fields to the order's metadata.
		 *
		 * @return void
		 */
		public function save_dhl_checkout_fields( \WC_Order $order, \WP_REST_Request $request ) {
			$pr_dhl_request_data = isset( $request['extensions'][ $this->name ] ) ? $request['extensions'][ $this->name ] : array();
			$dhl_label_options   = array();
			// Only keep the preferred day when the order actually carries its fee, so a
			// request that skipped the cart fee cannot book the paid service for free.
			if ( ! empty( $pr_dhl_request_data['preferredDay'] ) && $this->order_paid_preferred_day_fee( $order ) ) {
				$dhl_label_options['pr_dhl_preferred_day'] = wc_clean( $pr_dhl_request_data['preferredDay'] );
			}

			$settings     = PR_DHL()->get_shipping_dhl_settings();
			$has_location = ! empty( $settings['dhl_preferred_location'] ) && 'yes' === $settings['dhl_preferred_location'];
			$has_neighbor = ! empty( $settings['dhl_preferred_neighbour'] ) && 'yes' === $settings['dhl_preferred_neighbour'];
			$both_enabled = $has_location && $has_neighbor;

			$dropp_off_choice = isset( $pr_dhl_request_data['preferredLocationNeighbor'] )
				? wc_clean( $pr_dhl_request_data['preferredLocationNeighbor'] )
				: '';
			$dropp_off_choice = in_array( $dropp_off_choice, array(
				'none',
				'location',
				'neighbor'
			), true ) ? $dropp_off_choice : '';

			if ( $both_enabled ) {

				if ( 'location' === $dropp_off_choice ) {
					$loc = isset( $pr_dhl_request_data['preferredLocation'] ) ? trim( $pr_dhl_request_data['preferredLocation'] ) : '';
					if ( '' === $loc ) {
						throw new Exception( __( 'Please enter the preferred location.', 'dhl-for-woocommerce' ) );
					}
					$dhl_label_options['pr_dhl_preferred_location_neighbor'] = 'location';
					$dhl_label_options['pr_dhl_preferred_location']          = wc_clean( $loc );

				} elseif ( 'neighbor' === $dropp_off_choice ) {
					$nm  = isset( $pr_dhl_request_data['preferredNeighborName'] ) ? trim( $pr_dhl_request_data['preferredNeighborName'] ) : '';
					$adr = isset( $pr_dhl_request_data['preferredNeighborAddress'] ) ? trim( $pr_dhl_request_data['preferredNeighborAddress'] ) : '';
					if ( '' === $nm || '' === $adr ) {
						throw new Exception( __( 'Please enter the preferred neighbor name and address.', 'dhl-for-woocommerce' ) );
					}
					$dhl_label_options['pr_dhl_preferred_location_neighbor'] = 'neighbor';
					$dhl_label_options['pr_dhl_preferred_neighbour_name']    = wc_clean( $nm );
					$dhl_label_options['pr_dhl_preferred_neighbour_address'] = wc_clean( $adr );
				}

			} else {

				if ( $has_location ) {
					$loc = isset( $pr_dhl_request_data['preferredLocation'] ) ? trim( $pr_dhl_request_data['preferredLocation'] ) : '';
					if ( '' !== $loc ) {
						$dhl_label_options['pr_dhl_preferred_location_neighbor'] = 'location';
						$dhl_label_options['pr_dhl_preferred_location']          = wc_clean( $loc );
					}
				} elseif ( $has_neighbor ) {
					$nm  = isset( $pr_dhl_request_data['preferredNeighborName'] ) ? trim( $pr_dhl_request_data['preferredNeighborName'] ) : '';
					$adr = isset( $pr_dhl_request_data['preferredNeighborAddress'] ) ? trim( $pr_dhl_request_data['preferredNeighborAddress'] ) : '';
					if ( '' !== $nm || '' !== $adr ) {
						if ( '' === $nm || '' === $adr ) {
							throw new Exception( __( 'Please enter the preferred neighbor name and address.', 'dhl-for-woocommerce' ) );
						}
						$dhl_label_options['pr_dhl_preferred_location_neighbor'] = 'neighbor';
						$dhl_label_options['pr_dhl_preferred_neighbour_name']    = wc_clean( $nm );
						$dhl_label_options['pr_dhl_preferred_neighbour_address'] = wc_clean( $adr );
					}
				}
			}

			if ( ! empty( $pr_dhl_request_data['closest_drop_point'] ) ) {
				$dhl_label_options['pr_dhl_cdp_delivery'] = wc_clean( $pr_dhl_request_data['closest_drop_point'] );
			}

			if ( ! empty( $dhl_label_options ) ) {
				PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order->get_id(), $dhl_label_options );
			}
			
			// The Post Number only applies to Packstation / Postfiliale deliveries. Persist it only
			// when the address type or the shipping address itself is a droppoint; otherwise drop any
			// stale value so a Regular Address never carries a Post Number to the DHL API. Mirrors the
			// classic checkout's clear_post_number_for_regular_address().
			$address_type       = isset( $pr_dhl_request_data['addressType'] ) ? wc_clean( $pr_dhl_request_data['addressType'] ) : '';
			$shipping_postnum   = isset( $pr_dhl_request_data['postNumber'] ) ? sanitize_text_field( $pr_dhl_request_data['postNumber'] ) : '';
			$shipping_address_1 = $order->get_shipping_address_1();

			if ( PR_DHL()->is_droppoint( $address_type, $shipping_address_1 ) ) {
				$order->update_meta_data( '_shipping_dhl_postnum', $shipping_postnum );
			} else {
				$order->delete_meta_data( '_shipping_dhl_postnum' );
			}

			// Save the order to persist changes.
			$order->save();

		}

		/**
		 * Removes the saved preferred day from a checkout order that no longer carries its fee.
		 *
		 * WooCommerce re-syncs a draft, pending or failed order from the cart when the
		 * checkout loads or is placed again, and save_dhl_label_items() merges into the saved
		 * items, so a day saved by an attempt that had the fee would otherwise stay after the
		 * fee is gone. A day the order still pays for is kept.
		 *
		 * The items are read and saved through a freshly loaded order, as
		 * save_dhl_label_items() saves them: in a Store API batch request the checkout can
		 * pass the order object of an earlier attempt that never loaded them.
		 *
		 * @param \WC_Order $order The order the checkout re-synced from the cart.
		 *
		 * @return void
		 */
		public function remove_unpaid_preferred_day( \WC_Order $order ) {
			if ( $this->order_paid_preferred_day_fee( $order ) ) {
				return;
			}

			$saved_order = wc_get_order( $order->get_id() );
			$label_items = $saved_order ? $saved_order->get_meta( '_pr_shipment_dhl_label_items' ) : '';

			if ( ! is_array( $label_items ) || ! array_key_exists( 'pr_dhl_preferred_day', $label_items ) ) {
				return;
			}

			unset( $label_items['pr_dhl_preferred_day'] );
			$saved_order->update_meta_data( '_pr_shipment_dhl_label_items', $label_items );
			$saved_order->save_meta_data();
		}

		/**
		 * Register the update callback with WooCommerce Store API
		 */
		public function register_store_api_callback() {
			if ( function_exists( 'woocommerce_store_api_register_update_callback' ) ) {
				woocommerce_store_api_register_update_callback(
					array(
						'namespace' => $this->name,
						'callback'  => [ $this, 'store_api_callback' ],
					)
				);
			}
		}

		/**
		 * Callback function for the Store API update
		 *
		 * @param array $data Data sent from the client.
		 */
		public function store_api_callback( $data ) {
			// Earlier versions stored the price and label the client sent. Those keys are
			// no longer read; clear them so a session from before the upgrade cannot
			// carry a client-chosen amount over.
			WC()->session->__unset( 'pr_dhl_preferred_day_fee' );
			WC()->session->__unset( 'pr_dhl_preferred_day_label' );

			// The client only says whether a day is selected: the block sends the price
			// when a day is picked and 0 when it is cleared. The amount charged comes
			// from the merchant setting in add_preferred_day_fee().
			$selected = isset( $data['action'], $data['price'] ) && 'update_preferred_day_fee' === $data['action'] && (float) $data['price'] > 0;

			if ( $selected ) {
				WC()->session->set( 'pr_dhl_preferred_day_selected', true );
			} else {
				WC()->session->__unset( 'pr_dhl_preferred_day_selected' );
			}
		}

		/**
		 * Forget the preferred day selection recorded in the session.
		 */
		public function clear_preferred_day_selection() {
			WC()->session->__unset( 'pr_dhl_preferred_day_selected' );
		}

		/**
		 * The fee label, shared with the classic checkout and the order fee check.
		 *
		 * @return string
		 */
		protected function get_preferred_day_fee_label() {
			return esc_html__( 'DHL Delivery Day', 'dhl-for-woocommerce' );
		}

		/**
		 * The preferred day fee the merchant configured.
		 *
		 * @return float Zero when the setting is empty.
		 */
		protected function get_preferred_day_cost() {
			$settings = PR_DHL()->get_shipping_dhl_settings();

			if ( empty( $settings['dhl_preferred_day_cost'] ) ) {
				return 0.0;
			}

			return (float) wc_format_decimal( $settings['dhl_preferred_day_cost'] );
		}

		/**
		 * Whether the order carries the configured preferred day fee.
		 *
		 * @param \WC_Order $order The order being placed.
		 * @return bool True when the service is free or the fee line is on the order.
		 */
		protected function order_paid_preferred_day_fee( \WC_Order $order ) {
			// Compare at the store's price decimals: the fee line may keep more digits
			// than the shop displays, or fewer than the setting has.
			$decimals = wc_get_price_decimals();
			$cost     = round( $this->get_preferred_day_cost(), $decimals );

			if ( $cost <= 0 ) {
				return true;
			}

			$label = $this->get_preferred_day_fee_label();

			foreach ( $order->get_fees() as $fee ) {
				if ( $fee->get_name() === $label && round( (float) $fee->get_total(), $decimals ) >= $cost ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Adds the preferred day fee to the WooCommerce cart.
		 *
		 * @param \WC_Cart $cart The WooCommerce cart object.
		 */
		public function add_preferred_day_fee( $cart ) {
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}
			if ( ! $cart->needs_shipping() ) {
				WC()->session->__unset( 'pr_dhl_preferred_day_selected' );

				return;
			}
			// The session only records that a day was selected; the amount comes from
			// the setting and the label is the one the classic checkout uses.
			$fee_amount = WC()->session->get( 'pr_dhl_preferred_day_selected' ) ? $this->get_preferred_day_cost() : 0;
			$fee_label  = $this->get_preferred_day_fee_label();

			// Remove existing DHL fees if they exist
			$new_fees = array();
			foreach ( $cart->get_fees() as $fee ) {
				if ( strpos( $fee->name, 'DHL' ) === false ) {
					$new_fees[] = $fee;
				}
			}
			$cart->fees_api()->set_fees( $new_fees );

			if ( $fee_amount > 0 ) {
				// Add the fee to the cart
				$cart->add_fee( $fee_label, $fee_amount, true, '' );
			}
		}

	}

endif;
