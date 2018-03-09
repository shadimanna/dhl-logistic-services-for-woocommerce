<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Shipping Order Express.
 *
 * @package  PR_DHL_WC_Order_Express
 * @category Shipping
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Order_Express' ) ) :

class PR_DHL_WC_Order_Express extends PR_DHL_WC_Order {
	
	/**
	 * Init and hook in the integration, parent will automatically be called
	 */
	public function __construct( ) {
		parent::__construct();

		$this->id = 'woocommerce-shipment-dhl-label-express';
		$this->title = __( 'DHL Express Label & Tracking', 'pr-shipping-dhl' );
	}

	public function init_hooks() {
		parent::init_hooks();

		// Order page metabox actions
		add_action( 'wp_ajax_wc_shipment_dhl_gen_label_express', array( $this, 'save_meta_box_ajax' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_delete_label_express', array( $this, 'delete_label_ajax' ) );	
	}

	public function get_dhl_obj() {
		return PR_DHL()->get_dhl_factory( true );
	}

	public function get_shipping_dhl_settings() {
		return PR_DHL()->get_shipping_dhl_settings( true );
	}

	public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {
		// $dhl_label_items = $this->get_dhl_label_items( $order_id );

		// Get saved package description, otherwise generate the text based on settings
		// if( ! empty( $dhl_label_items['shipping_dhl_description'] ) ) {
		// 	$selected_dhl_desc = $dhl_label_items['shipping_dhl_description'];
		// } else {
		// 	$selected_dhl_desc = $this->get_package_description( $order_id );
		// }
		

		$base_country_code = PR_DHL()->get_base_country();
		
		// Preferred options for Germany only
		if( ( $base_country_code == 'DE' ) && ( $this->is_shipping_domestic( $order_id ) ) ) {
			$preferred_days = PR_DHL()->get_dhl_preferred_days();
			
			$preferred_days = array_keys($preferred_days);
			$preferred_days = array_combine($preferred_days, $preferred_days);
			$preferred_days[0] = __( 'none', 'pr-shipping-dhl' );

			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_preferred_day',
				'label'       		=> __( 'Preferred Day:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_preferred_day'] ) ? $dhl_label_items['pr_dhl_preferred_day'] : '',
				'options'			=> $preferred_days,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			$preferred_times = $dhl_obj->get_dhl_preferred_time();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_preferred_time',
				'label'       		=> __( 'Preferred Time:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_preferred_time'] ) ? $dhl_label_items['pr_dhl_preferred_time'] : '',
				'options'			=> $preferred_times,
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

			if( ! $this->is_shipping_domestic( $order_id ) ) {

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

			// Visual age, need 16 or 18, drop down
			$visual_age = $dhl_obj->get_dhl_visual_age();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_age_visual',
				'label'       		=> __( 'Visual Age Check:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_age_visual'] ) ? $dhl_label_items['pr_dhl_age_visual'] : '',
				'options'			=> $visual_age,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_email_notification',
				'label'       		=> __( 'Email Notification:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_email_notification'] ) ? $dhl_label_items['pr_dhl_email_notification'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_additional_insurance',
				'label'       		=> __( 'Additional Insurance:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_additional_insurance'] ) ? $dhl_label_items['pr_dhl_additional_insurance'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_personally',
				'label'       		=> __( 'Personally: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_personally'] ) ? $dhl_label_items['pr_dhl_personally'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_no_neighbor',
				'label'       		=> __( 'No Neighbour Delivery: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_no_neighbor'] ) ? $dhl_label_items['pr_dhl_no_neighbor'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_named_person',
				'label'       		=> __( 'Named Person Only: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_named_person'] ) ? $dhl_label_items['pr_dhl_named_person'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_premium',
				'label'       		=> __( 'Premium: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_premium'] ) ? $dhl_label_items['pr_dhl_premium'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			// COD logic 

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_bulky_goods',
				'label'       		=> __( 'Bulky Goods: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_bulky_goods'] ) ? $dhl_label_items['pr_dhl_bulky_goods'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
			/*
			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_identcheck',
				'label'       		=> __( 'IdentCheck: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_identcheck'] ) ? $dhl_label_items['pr_dhl_identcheck'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );*/
		}

	}

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids( ) {
		return array();
	}

	protected function get_tracking_link( $tracking_num ) {
		if( empty( $tracking_num ) ) {
			return '';
		}

		$tracking_note = sprintf( __( '<label>DHL Tracking Number: </label><a href="%s%s" target="_blank">%s</a>', 'my-text-domain' ), PR_DHL_PAKET_TRACKING_URL, $tracking_num, $tracking_num);
		
		return $tracking_note;
	}

	/**
	 * Saves the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 * @param array $tracking_items List of tracking item
	 *
	 * @return void
	 */
	public function save_dhl_label_tracking( $order_id, $tracking_items ) {
		update_post_meta( $order_id, '_pr_shipment_dhl_express_label_tracking', $tracking_items );
	}

	/*
	 * Gets all tracking items fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return tracking items
	 */
	public function get_dhl_label_tracking( $order_id ) {
		return get_post_meta( $order_id, '_pr_shipment_dhl_express_label_tracking', true );
	}

	/**
	 * Delete the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 *
	 * @return void
	 */
	public function delete_dhl_label_tracking( $order_id ) {
		delete_post_meta( $order_id, '_pr_shipment_dhl_express_label_tracking' );
	}

	/**
	 * Saves the label items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 * @param array $tracking_items List of tracking item
	 *
	 * @return void
	 */
	public function save_dhl_label_items( $order_id, $tracking_items ) {
		update_post_meta( $order_id, '_pr_shipment_dhl_express_label_items', $tracking_items );
	}

	/*
	 * Gets all label itesm fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return label items
	 */
	public function get_dhl_label_items( $order_id ) {
		return get_post_meta( $order_id, '_pr_shipment_dhl_express_label_items', true );
	}

	protected function get_label_args_settings( $order_id, $dhl_label_args ) {

		// Get services etc.
		$meta_box_ids = $this->get_additional_meta_ids();
		
		foreach ($meta_box_ids as $value) {
			$api_key = str_replace('pr_dhl_', '', $value);
			if ( isset( $dhl_label_args[ $value ] ) ) {
				$args['order_details'][ $api_key ] = $dhl_label_args[ $value ];
			}
		}

		// Get settings
		$shipping_dhl_settings = $this->get_shipping_dhl_settings();

		$setting_ids = array( 'dhl_api_user','dhl_api_pwd', 'dhl_account_num', 'dhl_shipper_name', 'dhl_shipper_company', 'dhl_shipper_address','dhl_shipper_address_no', 'dhl_shipper_address_city', 'dhl_shipper_address_state', 'dhl_shipper_address_zip', 'dhl_shipper_phone', 'dhl_shipper_email', 'dhl_bank_holder', 'dhl_bank_name', 'dhl_bank_iban', 'dhl_bank_bic', 'dhl_bank_ref', 'dhl_bank_ref_2', 'dhl_cod_fee' );

		foreach ($setting_ids as $value) {
			$api_key = str_replace('dhl_', '', $value);
			if ( isset( $shipping_dhl_settings[ $value ] ) ) {
				$args['dhl_settings'][ $api_key ] = htmlspecialchars_decode( $shipping_dhl_settings[ $value ] );
			}
		}
		
		$args['dhl_settings'][ 'shipper_country' ] = PR_DHL()->get_base_country();
		// $args['dhl_settings'][ 'participation' ] = $shipping_dhl_settings[ 'dhl_participation_' . $dhl_label_args['pr_dhl_product'] ];

		return $args;
	}

	protected function delete_label_args( $order_id ) {
		$args = $this->get_dhl_label_tracking( $order_id );

		$shipping_dhl_settings = $this->get_shipping_dhl_settings();

		$args['api_user'] = $shipping_dhl_settings['dhl_api_user'];
		$args['api_pwd'] = $shipping_dhl_settings['dhl_api_pwd'];
		
		return $args;
	}

}

endif;
