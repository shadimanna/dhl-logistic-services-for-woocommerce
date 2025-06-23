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

		private $cdp_service = array();

		private $shipping_dhl_settings = array();

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {

			$this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

			$this->init_hooks();

			$this->preferred_services = array(
				'pr_dhl_preferred_day'               => esc_html__( 'Delivery Day', 'dhl-for-woocommerce' ),
				'pr_dhl_preferred_location_neighbor' => esc_html__( 'Preferred Location or Neighbor', 'dhl-for-woocommerce' ),
				'pr_dhl_preferred_location'          => esc_html__( 'Preferred Location Address', 'dhl-for-woocommerce' ),
				'pr_dhl_preferred_neighbour_name'    => esc_html__( 'Preferred Neighbor Name', 'dhl-for-woocommerce' ),
				'pr_dhl_preferred_neighbour_address' => esc_html__( 'Preferred Neighbor Address', 'dhl-for-woocommerce' ),
			);

			$this->preferred_location_neighbor = array(
				'preferred_location' => esc_html__( 'Location', 'dhl-for-woocommerce' ),
				'preferred_neighbor' => esc_html__( 'Neighbor', 'dhl-for-woocommerce' ),
			);

			$this->cdp_service = array(
				'pr_dhl_cdp_delivery' => esc_html__( 'Delivery option', 'dhl-for-woocommerce' ),
			);
		}

		public function init_hooks() {

			add_action( 'wp_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

			if ( $this->is_tracking_enabled() && ( $this->is_preferredservice_enabled() || $this->is_parcelfinder_enabled() || $this->is_cdp_enabled() ) ) {
				// Add DHL meta tag
				add_action( 'wp_head', array( $this, 'dhl_add_meta_tags' ) );
			}

			if ( $this->is_cdp_enabled() ) {
				add_action( 'woocommerce_review_order_after_shipping', array( $this, 'add_cdp_fields' ) );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_cdp_fields' ), 10, 2 );
			}

			if ( $this->is_preferredservice_enabled() ) {
				add_action( 'woocommerce_review_order_after_shipping', array( $this, 'add_preferred_fields' ) );
				add_action( 'woocommerce_cart_calculate_fees', array( $this, 'add_cart_fees' ) );
				add_action( 'woocommerce_checkout_process', array( $this, 'verify_preferred_services_fields' ) );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_dhl_preferred_fields' ), 10, 2 );
				add_filter( 'woocommerce_get_order_item_totals', array( $this, 'display_dhl_preferred_free_services_values' ), 10, 2 );
			}

			// Parcel finder hooks
			if ( $this->is_parcelfinder_enabled() ) {
				// add_action( 'woocommerce_after_checkout_billing_form', array( $this, 'add_parcel_finder_btn' ) );
				add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'add_registration_text_above_shipping_fields' ) );

				add_action( 'woocommerce_before_checkout_shipping_form', array( $this, 'add_parcel_finder_btn' ) );
				add_action( 'woocommerce_after_checkout_form', array( $this, 'add_parcel_finder_form' ) );

				add_action( 'wp_ajax_wc_shipment_dhl_parcelfinder_search', array( $this, 'call_parcel_finder' ) );
				add_action( 'wp_ajax_nopriv_wc_shipment_dhl_parcelfinder_search', array( $this, 'call_parcel_finder' ) );

				add_filter( 'woocommerce_checkout_fields', array( $this, 'add_postnum_field' ), 101 );
				add_action( 'woocommerce_checkout_process', array( $this, 'validate_post_number' ) );
				// add_action( 'woocommerce_process_checkout_field_shipping_dhl_postnum_ps', array( $this, 'validate_post_number_required' ) );

				add_filter( 'woocommerce_order_formatted_shipping_address', array( $this, 'display_post_number' ), 10, 2 );
				add_filter( 'woocommerce_localisation_address_formats', array( $this, 'set_format_post_number' ) );
				add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'add_format_post_number' ), 10, 2 );

				add_filter( 'woocommerce_admin_shipping_fields', array( $this, 'admin_order_add_postnum_field' ), 10 );
			}

			if ( $this->is_email_notification_enabled() ) {
				$pos = apply_filters( 'pr_shipping_dhl_email_notification_position', 'woocommerce_review_order_before_submit' );
				add_action( $pos, array( $this, 'add_email_notification_checkbox' ), 10 );
				add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_email_notification_fields' ), 30, 2 );
			}
		}

		/**
		 * Add DHL registration link above the shipping fields.
		 *
		 * @return void
		 */
		public function add_registration_text_above_shipping_fields(): void {
			$link = DHL_GERMAN_REGISTRATION_LINK;

			if ( 'en_US' === get_locale() ) {
				$link = DHL_ENGLISH_REGISTRATION_LINK;
			}

			echo '<div class="registration_info" >';
			echo sprintf( esc_html__( 'For deliveries to DHL Parcel Lockers you have to %1screate a DHL account%2s and get a Post Number.', 'dhl-for-woocommerce' ), '<a href="' . esc_url( $link ) . '" target="_blank">', '</a>' );
			echo '</div>';
		}

		/**
		 * is_using_checkout_block
		 *
		 * @param Array is_using_checkout_block local fields.
		 *
		 * @return boolean
		 */
		protected function is_using_checkout_block() {
			$checkout_page_id = wc_get_page_id( 'checkout' );
			$has_block_checkout = $checkout_page_id && has_block( 'woocommerce/checkout', $checkout_page_id );

			if ( $has_block_checkout) {
				$is_using_checkout_block=true;
			}
			else{
				$is_using_checkout_block=false;
			}
			return $is_using_checkout_block;
		}

		protected function is_tracking_enabled() {
			return false;
		}

		protected function is_preferredservice_enabled() {

			if ( ( isset( $this->shipping_dhl_settings['dhl_preferred_day'] ) && ( $this->shipping_dhl_settings['dhl_preferred_day'] == 'yes' ) ) || ( isset( $this->shipping_dhl_settings['dhl_preferred_location'] ) && ( $this->shipping_dhl_settings['dhl_preferred_location'] == 'yes' ) ) || ( isset( $this->shipping_dhl_settings['dhl_preferred_neighbour'] ) && ( $this->shipping_dhl_settings['dhl_preferred_neighbour'] == 'yes' ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		protected function is_cdp_enabled() {

			if ( isset( $this->shipping_dhl_settings['dhl_closest_drop_point'] ) && ( 'yes' === $this->shipping_dhl_settings['dhl_closest_drop_point'] ) ) {
				return true;
			}

			return false;
		}

		public function dhl_add_meta_tags() {
			echo '<meta name="58vffw8g4r9_t3e38g4og588915" content="Yes">';
		}

		/**
		 * Localization for NL address fields.
		 *
		 * @param Array $checkout_fields Checkout fields.
		 *
		 * @return array
		 */
		public function get_country_locale( $checkout_fields ) {

			if ( $this->is_using_checkout_block() ) {
				$fields_to_order = array(
					'pr-dhl/address_type' 	  => array( 'priority' => 1 ),
					'first_name'          	  => array( 'priority' => 3 ),
					'last_name'           	  => array( 'priority' => 4 ),
					'company'             	  => array( 'priority' => 5 ),
					'country'             	  => array( 'priority' => 6 ),
					'postcode'            	  => array( 'priority' => 7 ),
					'shipping_pr_dhl/postnum' => array( 'priority' => 8 ),
				);
			}

			foreach ( $fields_to_order as $field_key => $field ) {
				foreach ( $field as $override => $value ) {
					$checkout_fields['DE'][ $field_key ][ $override ] = $value;
				}
			}

			/**
			 * Hide extra fields from the other countries .
			 */
			$countries_codes = array_keys( WC()->countries->get_countries() );
			foreach ( $countries_codes as $country_code ) {
				if ( 'DE' !== $country_code ) {
					$checkout_fields[ $country_code ]['pr-dhl/address_type']['hidden']   = true;
					$checkout_fields[ $country_code ]['pr-dhl/address_type']['required'] = false;
					$checkout_fields[ $country_code ]['pr-dhl/postnum']['hidden']   = true;
					$checkout_fields[ $country_code ]['pr-dhl/postnum']['required'] = false;
				}
			}
			return $checkout_fields;
		}

		public function load_styles_scripts() {
			$is_parcelfinder_enabled = $this->is_parcelfinder_enabled();

			if ( $this->is_tracking_enabled() && ( $this->is_preferredservice_enabled() || $is_parcelfinder_enabled ) ) {

				wp_enqueue_script( 'pr-dhl-frontend-pixel', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-frontend-pixel.js', array(), PR_DHL_VERSION, true );
			}

			// load scripts on checkout page only
			if ( ! is_checkout() ) {
				return;
			}

			$frontend_data = array(
				'ajax_url'         => admin_url( 'admin-ajax.php' ),
				'packstation_icon' => PR_DHL_PLUGIN_DIR_URL . '/assets/img/packstation.png',
				'parcelshop_icon'  => PR_DHL_PLUGIN_DIR_URL . '/assets/img/parcelshop.png',
				'post_office_icon' => PR_DHL_PLUGIN_DIR_URL . '/assets/img/post_office.png',
				'opening_times'    => esc_html__( 'Opening Times', 'dhl-for-woocommerce' ),
				'monday'           => esc_html__( 'Monday', 'dhl-for-woocommerce' ),
				'tueday'           => esc_html__( 'Tuesday', 'dhl-for-woocommerce' ),
				'wednesday'        => esc_html__( 'Wednesday', 'dhl-for-woocommerce' ),
				'thrusday'         => esc_html__( 'Thursday', 'dhl-for-woocommerce' ),
				'friday'           => esc_html__( 'Friday', 'dhl-for-woocommerce' ),
				'satuday'          => esc_html__( 'Saturday', 'dhl-for-woocommerce' ),
				'sunday'           => esc_html__( 'Sunday', 'dhl-for-woocommerce' ),
				'services'         => esc_html__( 'Services', 'dhl-for-woocommerce' ),
				'yes'              => esc_html__( 'Yes', 'dhl-for-woocommerce' ),
				'no'               => esc_html__( 'No', 'dhl-for-woocommerce' ),
				'parking'          => esc_html__( 'Parking', 'dhl-for-woocommerce' ),
				'handicap'         => esc_html__( 'Handicap Accessible', 'dhl-for-woocommerce' ),
				'packstation'      => PR_DHL_PACKSTATION,
				'parcelShop'       => PR_DHL_PARCELSHOP,
				'postoffice'       => PR_DHL_POST_OFFICE,
				'branch'           => esc_html__( 'Branch', 'dhl-for-woocommerce' ),
				'select'           => esc_html__( 'Select ', 'dhl-for-woocommerce' ),
				'post_number'      => esc_html__( 'Post Number ', 'dhl-for-woocommerce' ),
				'post_number_tip'  => esc_html__( '<span class="dhl-tooltip" title="Indicate a preferred time, which suits you best for your parcel delivery by choosing one of the displayed time windows.">?</span>', 'dhl-for-woocommerce' ),
				// Translators: %1$s is an opening HTML tag and %2$s is a closing HTML tag for styling the error message.
				'no_api_key'       => sprintf( esc_html__( '%1$sPlease insert an API Key to enable the display of locations in the frontend on a map.%2$s', 'dhl-for-woocommerce' ), '<div class="woocommerce-error">', '</div>' ),
				'post_code_error'  => esc_html__( 'Please enter a postcode to search locations.', 'dhl-for-woocommerce' ),
			);

			if ( ! empty( $this->shipping_dhl_settings['dhl_payment_gateway'] ) ) {
				$frontend_data['cod_enabled'] = true;
			} else {
				$frontend_data['cod_enabled'] = false;
			}

			if ( $this->is_preferredservice_enabled() || $is_parcelfinder_enabled ) {
				$frontend_data['map_type']                       = $this->get_map_type();
				$frontend_data['ship_to_different_address_text'] = esc_html__( 'Ship to a different address or Packstation/Branch?', 'dhl-for-woocommerce' );

				// Register and load our styles and scripts
				wp_register_script( 'pr-dhl-checkout-frontend', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-checkout-frontend.js', array( 'jquery', 'wc-checkout' ), PR_DHL_VERSION, true );
				wp_localize_script( 'pr-dhl-checkout-frontend', 'pr_dhl_checkout_frontend', $frontend_data );
				wp_enqueue_script( 'pr-dhl-checkout-frontend' );

				wp_enqueue_style( 'pr-dhl-checkout-frontend', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-frontend.css', array(), PR_DHL_VERSION );
			}

			if ( $this->is_preferredservice_enabled() ) {
				// jquery UI for tool tip
				wp_enqueue_style( 'pr-dhl-jquery-ui-style', PR_DHL_PLUGIN_DIR_URL . '/assets/css/jquery-ui-tooltip.css', array(), '1.0' );
				wp_enqueue_script( 'jquery-effects-core' );
				wp_enqueue_script( 'jquery-ui-tooltip' );
			}

			if ( $is_parcelfinder_enabled && $this->is_map_enabled() ) {
				// Enqueue Fancybox
				wp_enqueue_script( 'pr-dhl-fancybox-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/jquery.fancybox.min.js', array( 'jquery' ) );
				wp_enqueue_style( 'pr-dhl-fancybox-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/jquery.fancybox.min.css', '', PR_DHL_VERSION );
				if ( 'osm' === $this->get_map_type() ) {
					// Enqueue Leaflet
					wp_enqueue_style( 'leaflet_css', 'https://unpkg.com/leaflet/dist/leaflet.css', '', PR_DHL_VERSION );
					wp_enqueue_script( 'leaflet_js', 'https://unpkg.com/leaflet/dist/leaflet.js', '', PR_DHL_VERSION );
				} else {
					// Enqueue Google Maps
					wp_enqueue_script( 'pr-dhl-google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . $this->shipping_dhl_settings['dhl_google_maps_api_key'] );
				}
			}
		}

		protected function validate_is_german_customer() {
			$customer_country 	= WC()->customer->get_billing_country();
			$base_country_code 	= PR_DHL()->get_base_country();

			$display_preferred = false;
			// Preferred options are only for Germany customers
			if ( $base_country_code == 'DE' && $customer_country == 'DE' ) {
				return true;
			} else {
				return false;
			}
		}

		public function validate_extra_services_available( $check_day_transfer = false ) {
			// woocommerce_form_field('pr_dhl_paket_preferred_location');
			$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
			$chosen_payment_method   = WC()->session->get( 'chosen_payment_method' );

			$display_preferred = false;
			// Preferred options are only for Germany customers
			if ( $this->validate_is_german_customer() ) {

				if ( $check_day_transfer ) {
					foreach ( WC()->cart->get_cart() as $cart_item_key => $values ) {
						$same_day_transfer = get_post_meta( $values['product_id'], '_dhl_no_same_day_transfer', true );

						// If one of the products cannot be transferred same day then don't show preferred services
						if ( $same_day_transfer == 'yes' ) {
							return false;
						}
					}
				}

				if ( ! isset( $this->shipping_dhl_settings ) || empty( $this->shipping_dhl_settings['dhl_shipping_methods'] ) ) {
					throw new Exception( esc_html__( 'No shipping method enabled.', 'dhl-for-woocommerce' ) );
				}

				$wc_methods_dhl = $this->shipping_dhl_settings['dhl_shipping_methods'];
				if ( isset( $chosen_shipping_methods ) ) {

					if ( is_array( $chosen_shipping_methods ) ) {

						foreach ( $chosen_shipping_methods as $key => $value ) {

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

				$wc_payment_dhl = $this->shipping_dhl_settings['dhl_payment_gateway'];
				if ( isset( $chosen_payment_method ) && ! empty( $wc_payment_dhl ) ) {
					if ( is_array( $chosen_payment_method ) ) {

						foreach ( $chosen_payment_method as $key => $value ) {
							// $ship_method_slug = $this->get_shipping_method_slug( $value );

							if ( in_array( $value, $wc_payment_dhl ) ) {
								throw new Exception( esc_html__( 'Payment gateway excluded.', 'dhl-for-woocommerce' ) );
							}
						}
					} else {
						// $ship_method_slug = $this->get_shipping_method_slug( $chosen_payment_method );
						if ( in_array( $chosen_payment_method, $wc_payment_dhl ) ) {
							throw new Exception( esc_html__( 'Payment gateway excluded.', 'dhl-for-woocommerce' ) );
						}
					}
				}
			}

			// if reached here and not enabled then it's due to the selected shipping method not being set for DHL services
			if ( ! $display_preferred ) {
				throw new Exception( esc_html__( 'Not enabled for selected shipping method.', 'dhl-for-woocommerce' ) );
			}

			return $display_preferred;
		}

		protected function validate_cdp_available() {
			$shipping_country = WC()->customer->get_shipping_country();
			$valid_countries  = array( 'SE', 'FI', 'BE', 'AT' );

			if ( in_array( $shipping_country, $valid_countries ) ) {
				// Check if COD payment gateway selected
				$wc_payment_dhl        = $this->shipping_dhl_settings['dhl_payment_gateway'];
				$chosen_payment_method = WC()->session->get( 'chosen_payment_method' );

				if ( isset( $chosen_payment_method ) && ! empty( $wc_payment_dhl ) ) {

					if ( is_array( $chosen_payment_method ) ) {

						foreach ( $chosen_payment_method as $key => $value ) {
							if ( in_array( $value, $wc_payment_dhl ) ) {
								return false;
							}
						}
					} elseif ( in_array( $chosen_payment_method, $wc_payment_dhl ) ) {
						return false;
					}
				}

				return true;
			}

			return false;
		}

		public function add_preferred_fields() {
			try {

				$display_preferred = $this->validate_extra_services_available( true );

				if ( $display_preferred == true ) {
					$template_args = array();
					if ( isset( $_POST['post_data'] ) ) {
						parse_str( $_POST['post_data'], $post_data );

						foreach ( $this->preferred_services as $key => $value ) {
							if ( isset( $post_data[ $key ] ) ) {
								// array_push($template_args, $post_data[ $key ] );
								$template_args[ $key . '_selected' ] = wc_clean( $post_data[ $key ] );
							}
						}
					}

					if ( isset( $this->shipping_dhl_settings['dhl_preferred_day'] ) && ( $this->shipping_dhl_settings['dhl_preferred_day'] == 'yes' ) ) {

						if ( isset( $_POST['s_postcode'] ) ) {
							$shipping_postcode                   = wc_clean( $_POST['s_postcode'] );
							$template_args['preferred_day_time'] = PR_DHL()->get_dhl_preferred_day_time( $shipping_postcode );
							// Place preferred day and time in session, as to not call the API after placing an order unnecessarily
							WC()->session->set( 'dhl_preferred_day_time', $template_args['preferred_day_time'] );
						}
					}

					wc_get_template( 'checkout/dhl-preferred-services.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
				}
			} catch ( Exception $e ) {
				// do nothing
			}
		}

		public function add_cdp_fields() {
			if ( $this->validate_cdp_available() ) {
				wc_get_template( 'checkout/dhl-closest-drop-point.php', array(), '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
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

				if ( ! isset( $this->shipping_dhl_settings ) ) {
					return;
				}

				if ( ! empty( $post_data['pr_dhl_preferred_day'] ) ) {

					if ( ! empty( $this->shipping_dhl_settings['dhl_preferred_day_cost'] ) ) {
						$cart->add_fee( esc_html__( 'DHL Delivery Day', 'dhl-for-woocommerce' ), wc_format_decimal( $this->shipping_dhl_settings['dhl_preferred_day_cost'] ) );
					}
				}
				/*
				if( ! empty( $this->shipping_dhl_settings['dhl_cod_fee'] ) && $this->shipping_dhl_settings['dhl_cod_fee'] == 'yes' && isset( $post_data['payment_method'] ) && $post_data['payment_method'] == 'cod' ) {
				// Add €2 fee to COD usage (Euro is being assumed as currency)
				$cart->add_fee( esc_html__('DHL COD fee', 'dhl-for-woocommerce'), 2 );
				}*/

			} catch ( Exception $e ) {
				// do nothing
			}
		}

		public function verify_preferred_services_fields() {
			// save the posted preferences to the order so can be used when generating label
			$dhl_label_options = array();
			if ( ! isset( $_POST ) ) {
				return $dhl_label_options;
			}

			foreach ( $this->preferred_services as $key => $value ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					$dhl_label_options[ $key ] = wc_clean( $_POST[ $key ] );
				}
			}

			if ( isset( $dhl_label_options ) ) {

				if ( isset( $dhl_label_options['pr_dhl_preferred_location_neighbor'] ) ) {

					if ( ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'preferred_location' ) && ( ! isset( $dhl_label_options['pr_dhl_preferred_location'] ) ) ) {

						throw new Exception( esc_html__( 'Please enter the preferred location.', 'dhl-for-woocommerce' ) );
					}

					if ( ( $dhl_label_options['pr_dhl_preferred_location_neighbor'] == 'preferred_neighbor' ) && ( ! isset( $dhl_label_options['pr_dhl_preferred_neighbour_name'] ) || ! isset( $dhl_label_options['pr_dhl_preferred_neighbour_address'] ) ) ) {

						throw new Exception( esc_html__( 'Please enter the preferred neighbor name and address.', 'dhl-for-woocommerce' ) );
					}
				}
			}

			return $dhl_label_options;
		}

		public function process_dhl_preferred_fields( $order_id, $posted ) {
			// save the posted preferences to the order so can be used when generating label
			$dhl_label_options = $this->verify_preferred_services_fields();

			if ( ! empty( $dhl_label_options ) ) {
				PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order_id, $dhl_label_options );
			}
		}

		public function prepare_cdp_fields() {
			// save the posted preferences to the order so can be used when generating label
			$dhl_label_options = array();
			if ( ! isset( $_POST ) ) {
				return $dhl_label_options;
			}

			foreach ( $this->cdp_service as $key => $value ) {
				if ( ! empty( $_POST[ $key ] ) ) {
					$dhl_label_options[ $key ] = wc_clean( $_POST[ $key ] );
				}
			}

			return $dhl_label_options;
		}

		public function process_cdp_fields( $order_id, $posted ) {
			// save the posted preferences to the order so can be used when generating label
			$dhl_label_options = $this->prepare_cdp_fields();

			if ( ! empty( $dhl_label_options ) ) {
				PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order_id, $dhl_label_options );
			}
		}

		private function get_shipping_method_slug( $ship_method ) {

			if ( empty( $ship_method ) ) {
				return $ship_method;
			}

			// Assumes format 'name:id'
			$new_ship_method = explode( ':', $ship_method );
			$new_ship_method = isset( $new_ship_method[0] ) ? $new_ship_method[0] : $ship_method;

			return $new_ship_method;
		}

		public function display_dhl_preferred_free_services_values( $total_rows, $order ) {
			$order_id          = $order->get_id();
			$dhl_label_options = PR_DHL()->get_pr_dhl_wc_order()->get_dhl_label_items( $order_id );

			if ( isset( WC()->session ) ) {

				$preferred_day_time = WC()->session->get( 'dhl_preferred_day_time' );

				$new_rows = array();
				foreach ( $this->preferred_services as $key => $value ) {

					if ( ! empty( $dhl_label_options[ $key ] ) ) {
						// NEED TO PLACE THESE ROWS BEFORE THE PAYMENT METHOD
						$new_rows[ $key ]['label'] = $value . ':';

						if ( isset( $this->preferred_location_neighbor[ $dhl_label_options[ $key ] ] ) ) {
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
					if ( empty( $insert_before ) ) {
						$insert_before = array_search( 'order_total', array_keys( $total_rows ) );
					}
					if ( empty( $insert_before ) ) {
						$total_rows += $new_rows;
					} else {
						$this->array_insert( $total_rows, $insert_before, $new_rows );
					}
				}
			}

			return $total_rows;
		}

		private function array_insert( &$array, $position, $insert_array ) {
			$first_array = array_splice( $array, 0, $position );
			$array       = array_merge( $first_array, $insert_array, $array );
		}

		public function add_parcel_finder_btn() {
			if ( ! $this->is_map_enabled() ) {
				return;
			}

			$dhl_logo = PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png';
			echo '<a data-fancybox id="dhl_parcel_finder" class="button" data-src="#dhl_parcel_finder_form" href="javascript:;">' . $this->get_branch_location_text() . '<img src="' . esc_url( $dhl_logo ) . '" class="dhl-co-logo"></a>';
		}

		protected function get_branch_location_text() {
			$button_text = '';
			if ( $this->is_parcelshop_enabled() || $this->is_post_office_enabled() ) {
				// $button_text = esc_html__('Search Packstation / Branch', 'dhl-for-woocommerce')
				$button_text = esc_html__( 'Branch', 'dhl-for-woocommerce' );
			}

			if ( $this->is_packstation_enabled() ) {
				if ( ! empty( $button_text ) ) {
					$button_text = esc_html__( ' / ', 'dhl-for-woocommerce' ) . $button_text;
				}

				$button_text = esc_html__( 'Packstation', 'dhl-for-woocommerce' ) . $button_text;
			}

			$button_text = esc_html__( 'Search ', 'dhl-for-woocommerce' ) . $button_text;

			return $button_text;
		}

		public function add_parcel_finder_form() {
			$template_args = array();

			$template_args['packstation_img'] = PR_DHL_PLUGIN_DIR_URL . '/assets/img/packstation.png';
			$template_args['parcelshop_img']  = PR_DHL_PLUGIN_DIR_URL . '/assets/img/parcelshop.png';
			$template_args['post_office_img'] = PR_DHL_PLUGIN_DIR_URL . '/assets/img/post_office.png';

			$template_args['packstation_enabled'] = $this->is_packstation_enabled();
			$template_args['parcelshop_enabled']  = $this->is_parcelshop_enabled();
			$template_args['post_office_enabled'] = $this->is_post_office_enabled();

			wc_get_template( 'checkout/dhl-parcel-finder.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
		}

		public function call_parcel_finder() {
			check_ajax_referer( 'dhl_parcelfinder', 'security' );

			$parcelfinder_country  = isset( $_POST['parcelfinder_country'] ) ? wc_clean( wp_unslash( $_POST['parcelfinder_country'] ) ) : '';
			$parcelfinder_postcode = isset( $_POST['parcelfinder_postcode'] ) ? wc_clean( wp_unslash( $_POST['parcelfinder_postcode'] ) ) : '';
			$parcelfinder_city     = isset( $_POST['parcelfinder_city'] ) ? wc_clean( wp_unslash( $_POST['parcelfinder_city'] ) ) : '';
			$parcelfinder_address  = isset( $_POST['parcelfinder_address'] ) ? wc_clean( wp_unslash( $_POST['parcelfinder_address'] ) ) : '';
			$packstation_filter    = isset( $_POST['packstation_filter'] ) ? wc_clean( wp_unslash( $_POST['packstation_filter'] ) ) : '';
			$branch_filter         = isset( $_POST['branch_filter'] ) ? wc_clean( wp_unslash( $_POST['branch_filter'] ) ) : '';

			try {
				$dhl_obj                              = PR_DHL()->get_dhl_factory();
				$args['dhl_settings']['api_user']     = $this->shipping_dhl_settings['dhl_api_user'];
				$args['dhl_settings']['api_pwd']      = $this->shipping_dhl_settings['dhl_api_pwd'];
				$args['dhl_settings']['sandbox']      = $this->shipping_dhl_settings['dhl_sandbox'];
				$args['shipping_address']['address']  = $parcelfinder_address;
				$args['shipping_address']['postcode'] = $parcelfinder_postcode;
				$args['shipping_address']['city']     = $parcelfinder_city;
				$args['shipping_address']['country']  = $parcelfinder_country;
				$args['dhl_parcel_limit']             = $this->shipping_dhl_settings['dhl_parcel_limit'];
				$args['dhl_packstation_filter']       = $packstation_filter;
				$args['dhl_branch_filter']            = $branch_filter;

				$parcel_res = $dhl_obj->get_parcel_location( $args );

				if ( ! isset( $parcel_res->locations ) ) {
					throw new Exception( esc_html__( 'No parcel shops found', 'dhl-for-woocommerce' ) );
				}

				$res_count           = 0;
				$parcel_res_filtered = array();
				foreach ( $parcel_res->locations as $key => $value ) {

					if ( ( $this->is_packstation_enabled() &&
						( $packstation_filter == 'true' ) &&
						( $value->location->type == 'locker' ) ) ||
					( $this->is_parcelshop_enabled() &&
						( $branch_filter == 'true' ) &&
						( $value->location->type == 'servicepoint' ) ) ||
					( $this->is_post_office_enabled() &&
						( $branch_filter == 'true' ) &&
						( $value->location->type == 'postoffice' || $value->location->type == 'postbank' ) ) ) {

						if ( $value->serviceTypes ) {
							if ( is_array( $value->serviceTypes ) ) {
								foreach ( $value->serviceTypes as $service_type ) {
									// Only display shops that accept parcels.
									// Not needed 'parcelacceptance'
									if ( in_array( $service_type, array( 'parcel:pick-up', 'parcel:pick-up-unregistered', 'parcel:pick-up-registered' ) ) ) {
										array_push( $parcel_res_filtered, $value );
										++$res_count;
										break;
									}
								}
							} elseif ( in_array( $value->serviceTypes, array( 'parcel:pick-up', 'parcel:pick-up-unregistered', 'parcel:pick-up-registered' ) ) ) {
									array_push( $parcel_res_filtered, $value );
									++$res_count;
							}
						}
					}

					if ( $res_count == $this->shipping_dhl_settings['dhl_parcel_limit'] ) {
						break;
					}
				}

				if ( empty( $parcel_res_filtered ) ) {
					throw new Exception( esc_html__( 'No Parcel Shops found. Ensure "Packstation" or Branch" filter is checked ', 'dhl-for-woocommerce' ) );

				}

				wp_send_json(
					array(
						'parcel_res' => $parcel_res_filtered,
					// 'tracking_note'    => $tracking_note
					)
				);

			} catch ( Exception $e ) {
				wp_send_json( array( 'error' => $e->getMessage() ) );
			}

			wp_die();
		}

		protected function is_parcelfinder_enabled() {

			if ( $this->is_packstation_enabled() || $this->is_parcelshop_enabled() || $this->is_post_office_enabled() ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Is Location Finder map enabled?
		 *
		 * @return bool
		 */
		protected function is_map_enabled() {
			if ( ( isset( $this->shipping_dhl_settings['dhl_display_google_maps'] ) &&
				   ( $this->shipping_dhl_settings['dhl_display_google_maps'] == 'yes' ) ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Get Location Finder map type.
		 *
		 * @return string
		 */
		protected function get_map_type() {
			if ( ( isset( $this->shipping_dhl_settings['dhl_map_type'] ) &&
				   ( 'osm' === $this->shipping_dhl_settings['dhl_map_type'] ) ) ) {
				return 'osm';
			}

			return 'gmaps';
		}

		protected function is_packstation_enabled() {

			if ( ( isset( $this->shipping_dhl_settings['dhl_display_packstation'] ) &&
			( $this->shipping_dhl_settings['dhl_display_packstation'] == 'yes' ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		protected function is_parcelshop_enabled() {

			if ( ( isset( $this->shipping_dhl_settings['dhl_display_parcelshop'] ) &&
			( $this->shipping_dhl_settings['dhl_display_parcelshop'] == 'yes' ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		protected function is_post_office_enabled() {

			if ( ( isset( $this->shipping_dhl_settings['dhl_display_post_office'] ) &&
			( $this->shipping_dhl_settings['dhl_display_post_office'] == 'yes' ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		protected function is_email_notification_enabled() {

			if ( ( isset( $this->shipping_dhl_settings['dhl_email_notification'] ) &&
			( $this->shipping_dhl_settings['dhl_email_notification'] == 'yes' ) ) ) {
				return true;
			} else {
				return false;
			}
		}

		public function add_postnum_field( $checkout_fields ) {

			$types = array(
				'normal' => esc_html__( 'Regular Address', 'dhl-for-woocommerce' ),
			);

			if ( $this->is_packstation_enabled() ) {
				$types['dhl_packstation'] = esc_html__( 'DHL Packstation', 'dhl-for-woocommerce' );
			}

			if ( $this->is_post_office_enabled() ) {
				$types['dhl_branch'] = esc_html__( 'DHL Branch', 'dhl-for-woocommerce' );
			}

			$points = array(
				'' => esc_html__( 'Select a drop-off point', 'dhl-for-woocommerce' ),
			);

			$shipping_dhl_address_type = array(
				'label'    => esc_html__( 'Address Type', 'dhl-for-woocommerce' ),
				'required' => true,
				'type'     => 'select',
				'class'    => array( 'shipping-dhl-address-type' ),
				'clear'    => true,
				'default'  => 'normal',
				'options'  => $types,
			);

			$shipping_dhl_postnum_branch = array(
				'label'    => esc_html__( 'Post Number', 'dhl-for-woocommerce' ),
				'required' => false,
				'type'     => 'text',
				'class'    => array( 'shipping-dhl-postnum' ),
				'clear'    => true,
			);

			$shipping_dhl_drop_off = array(
				'label'    => __( 'Drop off points', 'dhl-for-woocommerce' ),
				'required' => false,
				'type'     => 'select',
				'class'    => array( 'shipping-dhl-drop-off-points' ),
				'clear'    => true,
				'default'  => 'normal',
				'options'  => $points,
			);

			$new_shipping_fields = array();

			foreach ( $checkout_fields['shipping'] as $key => $field ) {
				if ( 'shipping_first_name' === $key ) {
					$new_shipping_fields['shipping_dhl_address_type'] = $shipping_dhl_address_type;
					$new_shipping_fields['shipping_dhl_drop_off']     = $shipping_dhl_drop_off;
				}

				if ( 'shipping_address_1' === $key ) {
					$new_shipping_fields['shipping_dhl_postnum'] = $shipping_dhl_postnum_branch;
				}

				$new_shipping_fields[ $key ] = $field;
			}

			if ( empty( $new_shipping_fields ) ) {
				$new_shipping_fields['shipping_dhl_address_type'] = $shipping_dhl_address_type;
				$new_shipping_fields['shipping_dhl_drop_off']     = $shipping_dhl_drop_off;
				$new_shipping_fields['shipping_dhl_postnum']      = $shipping_dhl_postnum_branch;

				$new_shipping_fields = array_merge( $new_shipping_fields, $checkout_fields['shipping'] );
			}

			// Update the checkout fields array
			$checkout_fields['shipping'] = $new_shipping_fields;

			return $checkout_fields;
		}

		public function admin_order_add_postnum_field( $fields ) {

			$shipping_dhl_postnum_branch = array(
				'label'    => esc_html__( 'Post Number', 'dhl-for-woocommerce' ),
				'required' => false,
				'type'     => 'text',
				'class'    => 'shipping-dhl-postnum',
				'clear'    => true,
				'show'     => false,
			);

			if ( $new_shipping_fields = $this->array_insert_before( 'address_1', $fields, 'dhl_postnum', $shipping_dhl_postnum_branch ) ) {
				$fields = $new_shipping_fields;
			}

			return $fields;
		}

		public function add_email_notification_checkbox() {

			if ( $this->validate_is_german_customer() ) {
				woocommerce_form_field(
					'pr_dhl_email_notification',
					array(
						'type'  => 'checkbox',
						'class' => array( 'pr-dhl-email-notification form-row-wide' ),
						'label' => esc_html__( 'Activate Shipment Notification. When activated DHL will inform you via email about the shipment status of your order.', 'dhl-for-woocommerce' ),
					),
					'yes'
				);
			}
		}

		public function process_email_notification_fields( $order_id, $posted ) {

			$dhl_label_items = PR_DHL()->get_pr_dhl_wc_order()->get_dhl_label_items( $order_id );

			if ( isset( $_POST['pr_dhl_email_notification'] ) ) {

				if ( ! is_array( $dhl_label_items ) ) {
					$dhl_label_items = array();
				}

				$dhl_label_items['pr_dhl_email_notification'] = $_POST['pr_dhl_email_notification'];
				PR_DHL()->get_pr_dhl_wc_order()->save_dhl_label_items( $order_id, $dhl_label_items );

			}
		}

		/*
		 * Inserts a new key/value before the key in the array.
		 *
		 * @param $key
		 *   The key to insert before.
		 * @param $array
		 *   An array to insert in to.
		 * @param $new_key
		 *   The key to insert.
		 * @param $new_value
		 *   An value to insert.
		 *
		 * @return
		 *   The new array if the key exists, FALSE otherwise.
		 *
		 * @see array_insert_after()
		 */
		private function array_insert_before( $key, array &$array, $new_key, $new_value ) {
			if ( array_key_exists( $key, $array ) ) {
				$new = array();
				foreach ( $array as $k => $value ) {
					if ( $k === $key ) {
						$new[ $new_key ] = $new_value;
					}
					$new[ $k ] = $value;
				}
				return $new;
			}
			return false;
		}

		public function validate_post_number() {

			// Validate input only if "ship to different address" flag is set
			if ( ! isset( $_POST['ship_to_different_address'] ) ) {
				return;
			}

			$shipping_dhl_address_type = wc_clean( $_POST['shipping_dhl_address_type'] );
			$shipping_address_1        = wc_clean( $_POST['shipping_address_1'] );
			$shipping_dhl_postnum      = wc_clean( $_POST['shipping_dhl_postnum'] );

			$pos_ps = PR_DHL()->is_packstation( $shipping_address_1 );
			$pos_rs = PR_DHL()->is_parcelshop( $shipping_address_1 );
			$pos_po = PR_DHL()->is_post_office( $shipping_address_1 );

			if ( ! empty( $shipping_dhl_address_type ) && $shipping_dhl_address_type != 'normal' ) {
				// check shipping method and payment gateway first
				try {
					$this->validate_extra_services_available();
				} catch ( Exception $e ) {
					wc_add_notice( esc_html__( '"DHL Locations" cannot be used - ', 'dhl-for-woocommerce' ) . $e->getMessage(), 'error' );
					return;
				}
			}

			if ( $shipping_dhl_address_type == 'dhl_packstation' ) {

				if ( empty( $shipping_dhl_postnum ) ) {
					wc_add_notice( esc_html__( 'Post Number is mandatory for a Packstation location.', 'dhl-for-woocommerce' ), 'error' );
					return;
				}

				if ( ! $pos_ps ) {
					// Translators: %s is the text that must be included in the address (e.g., a specific keyword or phrase).
					wc_add_notice( sprintf( esc_html__( 'The text "%s" must be included in the address.', 'dhl-for-woocommerce' ), PR_DHL_PACKSTATION ), 'error' );
					return;
				}
			} elseif ( $shipping_dhl_address_type == 'dhl_branch' ) {
				if ( ! $pos_rs ) {
					// Translators: %s is the text that must be included in the address (e.g., a specific keyword or phrase).
					wc_add_notice( sprintf( esc_html__( 'The text "%s" must be included in the address.', 'dhl-for-woocommerce' ), PR_DHL_PARCELSHOP ), 'error' );
					return;
				}

				if ( ! $pos_po ) {
					// Translators: %s is the text that must be included in the address (e.g., a specific keyword or phrase).
					wc_add_notice( sprintf( esc_html__( 'The text "%s" must be included in the address.', 'dhl-for-woocommerce' ), PR_DHL_POST_OFFICE ), 'error' );
					return;
				}
			}

			if ( ! empty( $shipping_dhl_postnum ) ) {

				if ( ! is_numeric( $shipping_dhl_postnum ) ) {
					wc_add_notice( esc_html__( 'Post Number must be a number.', 'dhl-for-woocommerce' ), 'error' );
					return;
				}

				$post_num_len = strlen( $shipping_dhl_postnum );
				if ( $post_num_len < 6 || $post_num_len > 12 ) {
					wc_add_notice( esc_html__( 'The post number you entered is not valid. Please correct the number.', 'dhl-for-woocommerce' ), 'error' );
					return;
				}
			}
		}

		public function display_post_number( $address, $order ) {
			$pos_ps = PR_DHL()->is_packstation( $address['address_1'] );
			$pos_rs = PR_DHL()->is_parcelshop( $address['address_1'] );
			$pos_po = PR_DHL()->is_post_office( $address['address_1'] );

			if ( ( $pos_ps || $pos_rs || $pos_po ) &&
			( ! empty( $shipping_dhl_postnum = $order->get_meta( '_shipping_dhl_postnum' ) ) ) ) {
				$address['dhl_postnum'] = $shipping_dhl_postnum;
			}

			return $address;
		}

		public function set_format_post_number( $formats ) {
			foreach ( $formats as $key => $value ) {
				$count = 0;

				// use double quotes to find "\n" othewise treated as 2 chars
				$format_replaced = str_replace( "\n{address_1}", "\n{dhl_postnum}\n{address_1}", $value, $count );

				// Only change format if "address_1 found"
				if ( $count ) {
					$formats[ $key ] = $format_replaced;
				}
			}

			return $formats;
		}

		public function add_format_post_number( $address_format, $args ) {
			// $address_format['{dhl_postnum}'] must be set, even if empty as to not display on the frontend as '{dhl_postnum}'
			if ( isset( $args['dhl_postnum'] ) ) {
				$address_format['{dhl_postnum}'] = $args['dhl_postnum'];
			} else {
				$address_format['{dhl_postnum}'] = '';
			}

			return $address_format;
		}
	}

endif;
