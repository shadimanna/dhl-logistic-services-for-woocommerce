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
		public function init() {
			add_action( 'woocommerce_store_api_checkout_update_order_from_request', [
				$this,
				'save_dhl_checkout_fields'
			], 10, 2 );

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
		}


	}

endif;
