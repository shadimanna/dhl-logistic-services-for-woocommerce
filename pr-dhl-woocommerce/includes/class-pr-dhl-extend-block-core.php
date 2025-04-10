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


			// Register the update callback when WooCommerce Blocks is loaded
			add_action( 'init', [ $this, 'register_store_api_callback' ] );

			// Register fee calculation
			add_action( 'woocommerce_cart_calculate_fees', [ $this, 'add_preferred_day_fee' ] );


		}


		/**
		 * Saves the dhl fields to the order's metadata.
		 *
		 * @return void
		 */
		public function save_dhl_checkout_fields( \WC_Order $order, \WP_REST_Request $request ) {
			$pr_dhl_request_data = $request['extensions'][ $this->name ];
			$dhl_label_options   = array();
			if ( ! empty( $pr_dhl_request_data['preferredDay'] ) ) {
				$dhl_label_options['pr_dhl_preferred_day'] = wc_clean( $pr_dhl_request_data['preferredDay'] );
			}

			if ( ! empty( $pr_dhl_request_data['preferredLocationNeighbor'] ) ) {
				$dhl_label_options['pr_dhl_preferred_location_neighbor'] = wc_clean( $pr_dhl_request_data['preferredLocationNeighbor'] );

				if ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'location' ) {
					if ( ! empty( $pr_dhl_request_data['preferredLocation'] ) ) {
						$dhl_label_options['pr_dhl_preferred_location'] = wc_clean( $pr_dhl_request_data['preferredLocation'] );
					} else {
						// Handle the error or throw an exception
						throw new Exception( __( 'Please enter the preferred location.', 'dhl-for-woocommerce' ) );
					}
				}

				if ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'neighbor' ) {
					if ( ! empty( $pr_dhl_request_data['preferredNeighborName'] ) && ! empty( $pr_dhl_request_data['preferredNeighborAddress'] ) ) {
						$dhl_label_options['pr_dhl_preferred_neighbour_name']    = wc_clean( $pr_dhl_request_data['preferredNeighborName'] );
						$dhl_label_options['pr_dhl_preferred_neighbour_address'] = wc_clean( $pr_dhl_request_data['preferredNeighborAddress'] );
					} else {
						throw new Exception( __( 'Please enter the preferred neighbor name and address.', 'dhl-for-woocommerce' ) );
					}
				}
			}

			if ( ! empty( $dhl_label_options ) ) {
				PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order->get_id(), $dhl_label_options );
			}
			if ( ! empty( $pr_dhl_request_data['preferredDay'] ) ) {
				$dhl_label_options['pr_dhl_preferred_day'] = wc_clean( $pr_dhl_request_data['preferredDay'] );
			}
			// Extract billing and shipping house numbers with sanitization
			$shipping_postnum = sanitize_text_field( $pr_dhl_request_data['postNumber'] ) ;

			// Update billing and shipping house numbers
			$order->update_meta_data( '_shipping_dhl_postnum', $shipping_postnum );
			/**
			 * Save the order to persist changes
			 */
			$order->save();

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
			if ( isset( $data['action'] ) && 'update_preferred_day_fee' === $data['action'] ) {
				$price = isset( $data['price'] ) ? floatval( $data['price'] ) : 0;
				$label = isset( $data['label'] ) ? sanitize_text_field( $data['label'] ) : '';

				// Store the fee amount and label in session
				WC()->session->set( 'pr_dhl_preferred_day_fee', $price );
				WC()->session->set( 'pr_dhl_preferred_day_label', $label );
			}
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

			// Get the fee amount and label from session
			$fee_amount = WC()->session->get( 'pr_dhl_preferred_day_fee', 0 );
			$fee_label  = WC()->session->get( 'pr_dhl_preferred_day_label', '' );

			// Remove existing DHL fees if they exist
			$new_fees = array();
			foreach ( $cart->get_fees() as $fee ) {
				if ( strpos( $fee->name, 'DHL' ) === false ) {
					$new_fees[] = $fee;
				}
			}
			$cart->fees_api()->set_fees( $new_fees );

			if ( $fee_amount > 0 && ! empty( $fee_label ) ) {
				// Add the fee to the cart
				$cart->add_fee( $fee_label, $fee_amount, true, '' );
			}
		}

	}

endif;
