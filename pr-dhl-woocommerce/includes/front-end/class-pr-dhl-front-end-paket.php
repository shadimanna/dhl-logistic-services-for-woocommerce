<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Shipping Order.
 *
 * @package  PR_DHL_WC_Order
 * @category Shipping
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_Front_End_Paket' ) ) :

class PR_DHL_Front_End_Paket {
	
	private $preferred_services = array();
	
	private $preferred_location_neighbor = array();
	/**
	 * Init and hook in the integration.
	 */
	public function __construct( ) {
		// $this->define_constants();
		$this->init_hooks();

		$this->preferred_services = array(
								'pr_dhl_preferred_day' => __('Preferred Day', 'pr-shipping-dhl'),
								'pr_dhl_preferred_time' => __('Preferred Time', 'pr-shipping-dhl'),
								'pr_dhl_preferred_location_neighbor' => __('Preferred Location or Neighbor', 'pr-shipping-dhl'),
								'pr_dhl_preferred_location' => __('Preferred Location Address', 'pr-shipping-dhl'),
								'pr_dhl_preferred_neighbour_name' => __('Preferred Neighbor Name', 'pr-shipping-dhl'),
								'pr_dhl_preferred_neighbour_address' => __('Preferred Neighbor Address', 'pr-shipping-dhl')
								);

		$this->preferred_location_neighbor = array(
								'preferred_location' => __('Location', 'pr-shipping-dhl'),
								'preferred_neighbor' => __('Neighbor', 'pr-shipping-dhl')
								);

	}

	public function init_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_scripts' ) );
		// Add DHL meta tag
		add_action( 'wp_head', array( $this, 'dhl_add_meta_tags') );

		add_action( 'woocommerce_review_order_after_shipping', array( $this, 'add_preferred_fields' ) );
		add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees' ) );
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_dhl_preferred_fields' ), 10, 2 );
		add_filter( 'woocommerce_get_order_item_totals', array( $this, 'display_dhl_preferred_free_services_values' ), 10, 2 );
	}

	public function dhl_add_meta_tags() {
		$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		
		if( ( isset( $shipping_dhl_settings['dhl_preferred_day'] ) && ( $shipping_dhl_settings['dhl_preferred_day'] == 'yes' ) ) ||	( isset( $shipping_dhl_settings['dhl_preferred_time'] ) && ( $shipping_dhl_settings['dhl_preferred_time'] == 'yes' ) ) || ( isset( $shipping_dhl_settings['dhl_preferred_location'] ) && ( $shipping_dhl_settings['dhl_preferred_location'] == 'yes' ) ) ||	( isset( $shipping_dhl_settings['dhl_preferred_neighbour'] ) && ( $shipping_dhl_settings['dhl_preferred_neighbour'] == 'yes' ) ) ) {
				
				echo '<meta name="58vffw8g4r9_t3e38g4og588915" content="Yes">';
		}
	}

	public function load_styles_scripts() {
		// load scripts on checkout page only
		if( ! is_checkout() ) {
			return;
		}

		// $cod_settings = get_option('woocommerce_cod_settings');
		$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

		if( ! empty( $shipping_dhl_settings['dhl_payment_gateway'] ) ) {
			$frontend_data = array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'cod_enabled'               => true
			); 
		} else {
			$frontend_data = array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'cod_enabled'               => false
			); 
		}


		// Register and load our styles and scripts
		wp_register_script( 'pr-dhl-checkout-frontend', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-checkout-frontend.js', array( 'jquery', 'wc-checkout' ), PR_DHL_VERSION, true );
		wp_localize_script( 'pr-dhl-checkout-frontend', 'pr_dhl_checkout_frontend', $frontend_data);
		wp_enqueue_script( 'pr-dhl-checkout-frontend' );

		wp_enqueue_style( 'pr-dhl-checkout-frontend', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-frontend.css', array(), PR_DHL_VERSION );

		// jquery UI for tool tip
		wp_enqueue_style( 'pr-dhl-jquery-ui-style', PR_DHL_PLUGIN_DIR_URL . '/assets/css/jquery-ui-tooltip.css', array(), '1.0' );
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-ui-tooltip' );
	}
	

	public function add_preferred_fields( ) {
		// woocommerce_form_field('pr_dhl_paket_preferred_location');
		$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
		$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

		// WC 3.0 comaptibilty
		if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			$customer_country = WC()->customer->get_billing_country();
		} else {
			$customer_country = WC()->customer->get_country();
		}

		$base_country_code = PR_DHL()->get_base_country();

		$display_preferred = false;
		// Preferred options are only for Germany customers
		if( $base_country_code == 'DE' && $customer_country == 'DE' ) {

			foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
				$same_day_transfer = get_post_meta( $values['product_id'], '_dhl_no_same_day_transfer', true );

				// If one of the products cannot be transferred same day then don't show preferred services
				if ( $same_day_transfer == 'yes' ) {
					return;
				}
			}
			
			try {

				$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

				if( ! isset( $shipping_dhl_settings ) || empty( $shipping_dhl_settings['dhl_shipping_methods'] ) ) {
					return;
				}

				$wc_methods_dhl = $shipping_dhl_settings['dhl_shipping_methods'];
				if( isset( $chosen_shipping_methods ) ) {

					if( is_array( $chosen_shipping_methods ) ) {

						foreach ($chosen_shipping_methods as $key => $value) {

							$ship_method_slug = $this->get_shipping_method_slug( $value );

							if ( in_array( $ship_method_slug, $wc_methods_dhl ) ) {

								$display_preferred = true;
								break;
							}
						}
					} else {
						$ship_method_slug = $this->get_shipping_method_slug( $chosen_shipping_methods );

						if ( in_array( $ship_method_slug, $wc_methods_dhl ) ) {
							$display_preferred = true;
						}
					}
				}

				$wc_payment_dhl = $shipping_dhl_settings['dhl_payment_gateway'];
				if( isset( $chosen_payment_method ) && ! empty( $wc_payment_dhl) ) {
					if( is_array( $chosen_payment_method ) ) {

						foreach ($chosen_payment_method as $key => $value) {
							// $ship_method_slug = $this->get_shipping_method_slug( $value );

							if ( in_array( $value, $wc_payment_dhl ) ) {
								return;
							}
						}
					} else {
						// $ship_method_slug = $this->get_shipping_method_slug( $chosen_payment_method );
						if ( in_array( $chosen_payment_method, $wc_payment_dhl ) ) {
							return;
						}
					}
				}
				
			} catch (Exception $e) {
				// do nothing
			}
		}

		if( $display_preferred == true ) {
			$template_args = array();
			if ( isset( $_POST['post_data'] ) ) {
				parse_str( $_POST['post_data'], $post_data );

				foreach ( $this->preferred_services as $key => $value) {
					if ( isset( $post_data[ $key ] ) ) {
						// array_push($template_args, $post_data[ $key ] );
						$template_args[ $key . '_selected' ] = wc_clean( $post_data[ $key ] );
					}
				}

			}			

			wc_get_template( 'checkout/dhl-preferred-services.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
		}
	}

	public function add_cart_fees( $cart ) {

		if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
			return;
		}


		// POST information is either in a query string-like variable called 'post_data'...
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
		} else {
			$post_data = $_POST; // ... else it is in the POST variable itself
		}
		
		try {
			
			$shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

			if( ! isset( $shipping_dhl_settings ) ) {
				return;
			}

			if( ! empty( $post_data['pr_dhl_preferred_time'] ) && ! empty( $post_data['pr_dhl_preferred_day'] ) ) {
				if( ! empty( $shipping_dhl_settings['dhl_preferred_day_time_cost'] ) ) {
					$cart->add_fee( __('DHL Preferred Day & Time', 'pr-shipping-dhl'), $shipping_dhl_settings['dhl_preferred_day_time_cost'] );
				}

			} elseif ( ! empty( $post_data['pr_dhl_preferred_time'] ) ) {
				
				if( ! empty( $shipping_dhl_settings['dhl_preferred_time_cost'] ) ) {
					$cart->add_fee( __('DHL Preferred Time', 'pr-shipping-dhl'), $shipping_dhl_settings['dhl_preferred_time_cost'] );
				}

			} elseif ( ! empty( $post_data['pr_dhl_preferred_day'] ) ) {
				
				if( ! empty( $shipping_dhl_settings['dhl_preferred_day_cost'] ) ) {
					$cart->add_fee( __('DHL Preferred Day', 'pr-shipping-dhl'), $shipping_dhl_settings['dhl_preferred_day_cost'] );
				}

			}

			if( ! empty( $shipping_dhl_settings['dhl_cod_fee'] ) && $shipping_dhl_settings['dhl_cod_fee'] == 'yes' && isset( $post_data['payment_method'] ) && $post_data['payment_method'] == 'cod' ) {
				// Add €2 fee to COD usage (Euro is being assumed as currency)
				$cart->add_fee( __('DHL COD fee', 'pr-shipping-dhl'), 2 );
			}
			
		} catch (Exception $e) {
			// do nothing	
		}
	}

	public function process_dhl_preferred_fields( $order_id, $posted ) {
		// save the posted preferences to the order so can be used when generating label
		
		if ( ! isset( $_POST ) ) {
			return;
		}

		foreach ( $this->preferred_services as $key => $value) {
			if ( ! empty( $_POST[ $key ] ) ) {
				$dhl_label_options[ $key ] = wc_clean( $_POST[ $key ] );
			}
		}
		if ( isset( $dhl_label_options ) ) {

			if ( isset( $dhl_label_options['pr_dhl_preferred_location_neighbor'] ) ) {

				if ( ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'preferred_location' ) && ( ! isset( $dhl_label_options['pr_dhl_preferred_location'] ) ) ) {

					throw new Exception( __( 'Please enter the preferred location.', 'pr-shipping-dhl' ));
				}

				if ( ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'preferred_neighbor' ) && ( ! isset( $dhl_label_options['pr_dhl_preferred_neighbour_name'] ) || ! isset( $dhl_label_options['pr_dhl_preferred_neighbour_address'] ) ) ) {

					throw new Exception( __( 'Please enter the preferred neighbor name and address.', 'pr-shipping-dhl' ));
				}

			}

			PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order_id, $dhl_label_options );
		}
		
	}

	private function get_shipping_method_slug( $ship_method ) {
		
		if( empty( $ship_method ) ) {
			return $ship_method;
		}

		// Assumes format 'name:id'
		$new_ship_method = explode(':', $ship_method );
		$new_ship_method = isset( $new_ship_method[0] ) ? $new_ship_method[0] : $ship_method;

		return $new_ship_method;
	}

	public function display_dhl_preferred_free_services_values( $total_rows, $order ) {

		// Might need to change for WC 3.0
		$order_id = $order->get_order_number(); // WHAT HAPPENS WHEN SEQUENCIAL ORDER ID PLUGIN IS INSTALLED?
		// global $order;
		$dhl_label_options = PR_DHL()->get_pr_dhl_wc_order()->get_dhl_label_items( $order_id );
		
		try {

			$dhl_obj = PR_DHL()->get_dhl_factory();
			$preferred_time = $dhl_obj->get_dhl_preferred_time();
			
			$new_rows = array();
			foreach ( $this->preferred_services as $key => $value) {

				if ( ! empty( $dhl_label_options[ $key ] ) ) {
					// NEED TO PLACE THESE ROWS BEFORE THE PAYMENT METHOD
					$new_rows[ $key ]['label'] = $value . ':';

					if ( isset( $preferred_time[ $dhl_label_options[ $key ] ] ) ) {
						$new_rows[ $key ]['value'] = $preferred_time[ $dhl_label_options[ $key ] ];
					} elseif( isset( $this->preferred_location_neighbor[ $dhl_label_options[ $key ] ] ) ) {
						$new_rows[ $key ]['value'] = $this->preferred_location_neighbor[ $dhl_label_options[ $key ] ];
					} else {
						$new_rows[ $key ]['value'] = $dhl_label_options[ $key ];
					}
					
				}
			}
			if ( ! empty( $new_rows ) ) {
				// Instert before payment method
				$insert_before = array_search( 'payment_method', array_keys( $total_rows ) );

				// If no payment method, insert before order total
				if( empty( $insert_before ) ) {
					$insert_before = array_search( 'order_total', array_keys( $total_rows ) );
				}
				if ( empty( $insert_before ) ) {
					$total_rows += $new_rows;
				} else {
					$this->array_insert( $total_rows, $insert_before, $new_rows );
				}

			}

		} catch (Exception $e) {
			// do nothing
		}

		return $total_rows;
	}

	private function array_insert (&$array, $position, $insert_array) { 
	  $first_array = array_splice ($array, 0, $position); 
	  $array = array_merge ($first_array, $insert_array, $array); 
	} 
}

endif;
