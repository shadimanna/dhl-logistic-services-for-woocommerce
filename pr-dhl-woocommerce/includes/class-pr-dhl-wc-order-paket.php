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

if ( ! class_exists( 'PR_DHL_WC_Order_Paket' ) ) :

class PR_DHL_WC_Order_Paket extends PR_DHL_WC_Order {

	protected $carrier = 'DHL Paket';

	public function init_hooks(){

		parent::init_hooks();

		add_action( 'pr_shipping_dhl_label_created', array( $this, 'change_order_status' ), 10, 1 );
		add_action( 'woocommerce_email_order_details', array( $this, 'add_tracking_info'), 10, 4 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'create_label_on_status_changed' ), 10, 4 );
	}
	
	public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {

		$order 				= wc_get_order( $order_id );
		$base_country_code 	= PR_DHL()->get_base_country();

		if( $this->is_crossborder_shipment( $order_id ) ) {

			// Duties drop down
			$duties_opt = $dhl_obj->get_dhl_duties();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_duties',
				'label'       		=> __( 'Duties:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_duties'] ) ? $dhl_label_items['pr_dhl_duties'] : '',
				'options'			=> $duties_opt,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
		}

		// Preferred options for Germany only
		if( ( $base_country_code == 'DE' ) && ( $this->is_shipping_domestic( $order_id ) ) ) {

			if( $this->is_cod_payment_method( $order_id ) ) {

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_cod_value',
						'class'          	=> 'wc_input_decimal',
						'label'       		=> __( 'COD Amount:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_cod_value'] ) ? $dhl_label_items['pr_dhl_cod_value'] : $order->get_total(),
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );
			}

			if( ! empty( $this->shipping_dhl_settings['dhl_participation_return'] ) ) {

				echo '<hr/>';

				woocommerce_wp_checkbox( array(
					'id'          		=> 'pr_dhl_return_address_enabled',
					'label'       		=> __( 'Create return label: ', 'pr-shipping-dhl' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'       		=> isset( $dhl_label_items['pr_dhl_return_address_enabled'] ) ? $dhl_label_items['pr_dhl_return_address_enabled'] : $this->shipping_dhl_settings['dhl_default_return_address_enabled'],
					'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );
				
				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_name',
						'label'       		=> __( 'Name:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_name'] ) ? $dhl_label_items['pr_dhl_return_name'] : $this->shipping_dhl_settings['dhl_return_name'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_company',
						'label'       		=> __( 'Company:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_company'] ) ? $dhl_label_items['pr_dhl_return_company'] : $this->shipping_dhl_settings['dhl_return_company'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_address',
						'label'       		=> __( 'Street Address:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_address'] ) ? $dhl_label_items['pr_dhl_return_address'] : $this->shipping_dhl_settings['dhl_return_address'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_address_no',
						'label'       		=> __( 'Street Address Number:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_address_no'] ) ? $dhl_label_items['pr_dhl_return_address_no'] : $this->shipping_dhl_settings['dhl_return_address_no'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_address_city',
						'label'       		=> __( 'City:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_address_city'] ) ? $dhl_label_items['pr_dhl_return_address_city'] : $this->shipping_dhl_settings['dhl_return_address_city'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_address_state',
						'label'       		=> __( 'State:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_address_state'] ) ? $dhl_label_items['pr_dhl_return_address_state'] : $this->shipping_dhl_settings['dhl_return_address_state'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_address_zip',
						'label'       		=> __( 'Postcode:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['dhl_return_address_zip'] ) ? $dhl_label_items['dhl_return_address_zip'] : $this->shipping_dhl_settings['dhl_return_address_zip'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_phone',
						'label'       		=> __( 'Phone:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_phone'] ) ? $dhl_label_items['pr_dhl_return_phone'] : $this->shipping_dhl_settings['dhl_return_phone'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				woocommerce_wp_text_input( array(
						'id'          		=> 'pr_dhl_return_email',
						'label'       		=> __( 'Email:', 'pr-shipping-dhl' ),
						'placeholder' 		=> '',
						'description'		=> '',
						'value'       		=> isset( $dhl_label_items['pr_dhl_return_email'] ) ? $dhl_label_items['pr_dhl_return_email'] : $this->shipping_dhl_settings['dhl_return_email'],
						'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );

				echo '<hr/>';
			}

			try {
				$shipping_address = $order->get_address( 'shipping' );

				$preferred_day_time = PR_DHL()->get_dhl_preferred_day_time( $shipping_address['postcode'] );

				if ( $preferred_day_time ) {
					$preferred_days = $preferred_day_time['preferred_day'];
					$preferred_days = array_keys($preferred_days);
					$preferred_days = array_combine($preferred_days, $preferred_days);
				}
			} catch (Exception $e) {
				// catch exception
			}
			
			$preferred_days[0] = __( 'none', 'pr-shipping-dhl' );

			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_preferred_day',
				'label'       		=> __( 'Preferred Day:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_preferred_day'] ) ? $dhl_label_items['pr_dhl_preferred_day'] : '',
				'options'			=> $preferred_days,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			if( isset( $dhl_label_items['pr_dhl_preferred_location'] ) ) {

				woocommerce_wp_text_input( array(
					'id'          		=> 'pr_dhl_preferred_location',
					'label'       		=> __( 'Preferred Location (80 characters max): ', 'pr-shipping-dhl' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'       		=> $dhl_label_items['pr_dhl_preferred_location'],
					'custom_attributes'	=> array( $is_disabled => $is_disabled, 'maxlength' => '80' )
				) );
			}

			if( isset( $dhl_label_items['pr_dhl_preferred_neighbour_name'] )  && isset( $dhl_label_items['pr_dhl_preferred_neighbour_address'] ) ) {

				$neighbor_info = $dhl_label_items['pr_dhl_preferred_neighbour_name'] . ', ' . $dhl_label_items['pr_dhl_preferred_neighbour_address'];
				woocommerce_wp_text_input( array(
					'id'          		=> 'pr_dhl_preferred_neighbor',
					'label'       		=> __( 'Preferred Neighbor (80 characters max): ', 'pr-shipping-dhl' ),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'       		=> $neighbor_info,
					'custom_attributes'	=> array( $is_disabled => $is_disabled, 'maxlength' => '80' )
				) );
			} 

			woocommerce_wp_hidden_input( array(
				'id'          		=> 'pr_dhl_email_notification',
				'label'       		=> __( 'Email Notification:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_email_notification'] ) ? $dhl_label_items['pr_dhl_email_notification'] : false,
			) );

			// Visual age, need 16 or 18, drop down
			$visual_age = $dhl_obj->get_dhl_visual_age();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_age_visual',
				'label'       		=> __( 'Visual Age Check:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_age_visual'] ) ? $dhl_label_items['pr_dhl_age_visual'] : $this->shipping_dhl_settings['dhl_default_age_visual'],
				'options'			=> $visual_age,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_additional_insurance',
				'label'       		=> __( 'Additional Insurance:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_additional_insurance'] ) ? $dhl_label_items['pr_dhl_additional_insurance'] : $this->shipping_dhl_settings['dhl_default_additional_insurance'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
/*
			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_personally',
				'label'       		=> __( 'Personally: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_personally'] ) ? $dhl_label_items['pr_dhl_personally'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
*/
			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_no_neighbor',
				'label'       		=> __( 'No Neighbour Delivery: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_no_neighbor'] ) ? $dhl_label_items['pr_dhl_no_neighbor'] : $this->shipping_dhl_settings['dhl_default_no_neighbor'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_named_person',
				'label'       		=> __( 'Named Person Only: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_named_person'] ) ? $dhl_label_items['pr_dhl_named_person'] : $this->shipping_dhl_settings['dhl_default_named_person'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_premium',
				'label'       		=> __( 'Premium: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_premium'] ) ? $dhl_label_items['pr_dhl_premium'] : $this->shipping_dhl_settings['dhl_default_premium'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			// COD logic 

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_bulky_goods',
				'label'       		=> __( 'Bulky Goods: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_bulky_goods'] ) ? $dhl_label_items['pr_dhl_bulky_goods'] : $this->shipping_dhl_settings['dhl_default_bulky_goods'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			echo '<hr/>';

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_identcheck',
				'label'       		=> __( 'Ident-Check: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck'] ) ? $dhl_label_items['pr_dhl_identcheck'] : $this->shipping_dhl_settings['dhl_default_identcheck'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
/*
			woocommerce_wp_text_input( array(
				'id'          		=> 'pr_dhl_identcheck_fname',
				'label'       		=> __( 'Identity Check - First Name: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck_fname'] ) ? $dhl_label_items['pr_dhl_identcheck_fname'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_text_input( array(
				'id'          		=> 'pr_dhl_identcheck_lname',
				'label'       		=> __( 'Identity Check - Last Name: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck_lname'] ) ? $dhl_label_items['pr_dhl_identcheck_lname'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
*/
			woocommerce_wp_text_input( array(
				'id'          		=> 'pr_dhl_identcheck_dob',
				'label'       		=> __( 'Ident-Check - Date of Birth: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck_dob'] ) ? $dhl_label_items['pr_dhl_identcheck_dob'] : $this->shipping_dhl_settings['dhl_default_identcheck_dob'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled ),
				'class'				=> 'short date-picker'
			) );

			// $visual_age = $dhl_obj->get_dhl_visual_age();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_identcheck_age',
				'label'       		=> __( 'Ident-Check - Minimum Age: ', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck_age'] ) ? $dhl_label_items['pr_dhl_identcheck_age'] : '',
				'options'			=> $visual_age,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			echo '<hr/>';

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_is_codeable',
				'label'       		=> __( 'Print Only If Codeable: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_is_codeable'] ) ? $dhl_label_items['pr_dhl_is_codeable'] : $this->shipping_dhl_settings['dhl_default_is_codeable'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			echo '<hr/>';

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_routing',
				'label'       		=> __( 'Parcel Outlet Routing: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_routing'] ) ? $dhl_label_items['pr_dhl_routing'] : $this->shipping_dhl_settings['dhl_default_routing'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

            woocommerce_wp_text_input( array(
                'id'          		=> 'pr_dhl_routing_email',
                'label'       		=> __( 'Parcel Outlet Routing - Email: ', 'pr-shipping-dhl' ),
                'placeholder' 		=> '',
                'description'		=> '',
                'value'       		=> isset( $dhl_label_items['pr_dhl_routing_email'] ) ? $dhl_label_items['pr_dhl_routing_email'] : $this->get_default_dhl_rounting_email( $order_id ),
                'custom_attributes'	=> array( $is_disabled => $is_disabled ),
            ) );

		}
		
	}

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids( ) {
		return array( 'pr_dhl_cod_value', 'pr_dhl_preferred_day', 'pr_dhl_preferred_location', 'pr_dhl_preferred_neighbor', 'pr_dhl_duties', 'pr_dhl_age_visual', 'pr_dhl_email_notification', 'pr_dhl_additional_insurance', 'pr_dhl_personally', 'pr_dhl_no_neighbor', 'pr_dhl_named_person', 'pr_dhl_premium', 'pr_dhl_bulky_goods', 'pr_dhl_is_codeable', 'pr_dhl_identcheck', 'pr_dhl_identcheck_dob', 'pr_dhl_identcheck_age', 'pr_dhl_return_address_enabled', 'pr_dhl_return_name', 'pr_dhl_return_company', 'pr_dhl_return_address','pr_dhl_return_address_no', 'pr_dhl_return_address_city', 'pr_dhl_return_address_state', 'pr_dhl_return_address_zip', 'pr_dhl_return_phone', 'pr_dhl_return_email', 'pr_dhl_routing', 'pr_dhl_routing_email' );
	}

	protected function get_tracking_url() {
		return PR_DHL_PAKET_TRACKING_URL;
	}

	protected function get_label_args_settings( $order_id, $dhl_label_items ) {

		$order = wc_get_order( $order_id );
		$billing_address = $order->get_address( );
		$shipping_address = $order->get_address( 'shipping' );

		$shipping_address_email = '';
		// If shipping email doesn't exist, try to get billing email
		if( ! isset( $shipping_address['email'] ) && isset( $billing_address['email'] ) ) {
			$shipping_address_email = $billing_address['email'];
		} else {
            $shipping_address_email = $shipping_address['email'];
        }

		// Get services etc.
		$meta_box_ids = $this->get_additional_meta_ids();
		
		foreach ($meta_box_ids as $value) {
			$api_key = str_replace('pr_dhl_', '', $value);
			if ( isset( $dhl_label_items[ $value ] ) ) {
				$args['order_details'][ $api_key ] = $dhl_label_items[ $value ];
			}
		}

		// Get settings
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

		$setting_ids = array( 'dhl_api_user','dhl_api_pwd', 'dhl_add_logo', 'dhl_shipper_reference', 'dhl_account_num', 'dhl_shipper_name', 'dhl_shipper_company', 'dhl_shipper_address','dhl_shipper_address_no', 'dhl_shipper_address_city', 'dhl_shipper_address_state', 'dhl_shipper_address_zip', 'dhl_shipper_phone', 'dhl_shipper_email', 'dhl_shipper_reference', 'dhl_bank_holder', 'dhl_bank_name', 'dhl_bank_iban', 'dhl_bank_bic', 'dhl_bank_ref', 'dhl_bank_ref_2', 'dhl_participation_return', 'dhl_pass_email', 'dhl_pass_phone' );

		foreach ($setting_ids as $value) {
			$api_key = str_replace('dhl_', '', $value);
			if ( isset( $this->shipping_dhl_settings[ $value ] ) ) {
				$args['dhl_settings'][ $api_key ] = htmlspecialchars_decode( $this->shipping_dhl_settings[ $value ] );

				if( stripos($value, 'bank_ref') !== false ) {

					$args['dhl_settings'][ $api_key ] = str_replace( '{order_id}', $order_id, $args['dhl_settings'][ $api_key ] );
					
					$args['dhl_settings'][ $api_key ] = str_replace( '{email}', $shipping_address_email, $args['dhl_settings'][ $api_key ] );
				}
			}
		}
		
		$args['dhl_settings'][ 'shipper_country' ] = PR_DHL()->get_base_country();
		$args['dhl_settings'][ 'return_country' ] = PR_DHL()->get_base_country();
		$args['dhl_settings'][ 'participation' ] = $this->shipping_dhl_settings[ 'dhl_participation_' . $dhl_label_items['pr_dhl_product'] ];
		$args['dhl_settings'][ 'label_format' ] = $this->shipping_dhl_settings['dhl_label_format'];

		return $args;
	}

	protected function delete_label_args( $order_id ) {
		$args = $this->get_dhl_label_tracking( $order_id );

		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();

		$args['api_user'] = $this->shipping_dhl_settings['dhl_api_user'];
		$args['api_pwd'] = $this->shipping_dhl_settings['dhl_api_pwd'];
		
		return $args;
	}

	protected function save_default_dhl_label_items( $order_id ) {

	    parent::save_default_dhl_label_items( $order_id );

        $base_country_code 	= PR_DHL()->get_base_country();
	    // Services and COD only for Germany
        if( ( $base_country_code == 'DE' ) && ( $this->is_shipping_domestic( $order_id ) ) ) {
            $dhl_label_items = $this->get_dhl_label_items($order_id);

            $settings_default_ids = array(
                'pr_dhl_is_codeable',
                'pr_dhl_return_address_enabled',
                'pr_dhl_age_visual',
                'pr_dhl_additional_insurance',
                'pr_dhl_no_neighbor',
                'pr_dhl_named_person',
                'pr_dhl_premium',
                'pr_dhl_bulky_goods',
                'pr_dhl_identcheck',
                'pr_dhl_identcheck_age',
                'pr_dhl_identcheck_dob',
                'pr_dhl_routing'
            );

            foreach ($settings_default_ids as $default_id) {
                $id_name = str_replace("pr_dhl_", '', $default_id);

                if ( !isset($dhl_label_items[$default_id]) ) {
                    $dhl_label_items[$default_id] = isset( $this->shipping_dhl_settings['dhl_default_' . $id_name] ) ? $this->shipping_dhl_settings['dhl_default_' . $id_name] : '';
                }
            }

            $order = wc_get_order($order_id);
            if ($this->is_cod_payment_method($order_id) && empty($dhl_label_items['pr_dhl_cod_value'])) {
                $dhl_label_items['pr_dhl_cod_value'] = $order->get_total();
            }

            $this->save_dhl_label_items($order_id, $dhl_label_items);
        }

	}

	protected function get_default_dhl_rounting_email( $order_id ) {
        $order = wc_get_order( $order_id );
        $billing_address = $order->get_address();
		return $billing_address['email'];
	}

	public function get_bulk_actions() {

		$shop_manager_actions = array();

		$shop_manager_actions = array(
			'pr_dhl_create_labels'      => __( 'DHL Create Labels', 'pr-shipping-dhl' )
		);

		return $shop_manager_actions;
	}

	protected function is_cod_payment_method( $order_id ) {
		$base_country_code 	= PR_DHL()->get_base_country();

		if( ( $base_country_code == 'DE' ) && ( $this->is_shipping_domestic( $order_id ) ) ) {
			return parent::is_cod_payment_method( $order_id );
		} else {
		    return false;
        }
	}
	
	public function change_order_status( $order_id ){
		
		if( isset( $this->shipping_dhl_settings['dhl_change_order_status_completed'] ) && ( $this->shipping_dhl_settings['dhl_change_order_status_completed'] == 'yes' ) ) {
			$order = wc_get_order( $order_id );
			$order->update_status('completed');

		}
	}

	public function add_tracking_info( $order, $sent_to_admin, $plain_text, $email ){

		if( $email->id != 'customer_completed_order' ){
			return;
		}
		
		if( isset( $this->shipping_dhl_settings['dhl_add_tracking_info_completed'] ) && ( $this->shipping_dhl_settings['dhl_add_tracking_info_completed'] == 'yes' ) ) {

            if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
                $order_id = $order->get_id();
            } else {
                $order_id = $order->id;
            }

            $tracking_note = $this->get_tracking_note( $order_id );

            if( ! empty( $tracking_note ) ) {
			    echo '<p>' . $tracking_note . '</p>';
            }
		}

	}

	public function create_label_on_status_changed($order_id, $status_from, $status_to, $order ){

		$status_setting = str_replace('wc-', '', $this->shipping_dhl_settings['dhl_create_label_on_status'] );
		if( $status_setting == $status_to ){
			$this->process_bulk_actions( 'pr_dhl_create_labels', array( $order_id ), 1 );
		}
	}
}

endif;
