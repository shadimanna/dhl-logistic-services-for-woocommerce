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
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'), 20 );

		// Invoice upload ajax request handler
		add_action( 'wp_ajax_wc_shipment_dhl_upload_invoice', array( $this, 'upload_invoice_ajax' ) );
	}

	public function enqueue_scripts() {
		wp_enqueue_script('media-upload');
	    wp_enqueue_script('thickbox');
	    wp_register_script('dhl-order-metabox-upload', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-order-upload.js', array('jquery','media-upload','thickbox'));
	    wp_enqueue_script('dhl-order-metabox-upload');

	    wp_enqueue_style('thickbox');
	}

	public function get_dhl_obj() {
		return PR_DHL()->get_dhl_factory( true );
	}

	public function get_shipping_dhl_settings() {
		return PR_DHL()->get_shipping_dhl_settings( true );
	}

	public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {
		
		$order = wc_get_order( $order_id );
		woocommerce_wp_text_input( array(
				'id'          		=> 'pr_dhl_ship_date',
				'label'       		=> __( 'Ship Date: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_ship_date'] ) ? $dhl_label_items['pr_dhl_ship_date'] : date('Y-m-d'),
				'custom_attributes'	=> array( $is_disabled => $is_disabled ),
				'class'				=> 'short date-picker'
		) );

		woocommerce_wp_checkbox( array(
			'id'          		=> 'pr_dhl_additional_insurance',
			'label'       		=> __( 'Additional Insurance:', 'pr-shipping-dhl' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $dhl_label_items['pr_dhl_additional_insurance'] ) ? $dhl_label_items['pr_dhl_additional_insurance'] : '',
			'custom_attributes'	=> array( $is_disabled => $is_disabled )
		) );

		woocommerce_wp_text_input( array(
			'id'          		=> 'pr_dhl_insured_value',
			'label'       		=> __( 'Insured Value: ', 'pr-shipping-dhl' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $dhl_label_items['pr_dhl_insured_value'] ) ? $dhl_label_items['pr_dhl_insured_value'] : $order->get_total(),
			'custom_attributes'	=> array( $is_disabled => $is_disabled ),
			'class'				=> 'wc_input_decimal'
		) );

		if( $this->is_crossborder_shipment( $order_id ) ) {
			woocommerce_wp_text_input( array(
				'id'          		=> 'pr_dhl_declared_value',
				'label'       		=> __( 'Declared Value: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_declared_value'] ) ? $dhl_label_items['pr_dhl_declared_value'] : $order->get_total(),
				'custom_attributes'	=> array( $is_disabled => $is_disabled ),
				'class'				=> 'wc_input_decimal'
			) );

			$duties_opt = $dhl_obj->get_dhl_duties();
			woocommerce_wp_select( array(
				'id'          		=> 'pr_dhl_duties',
				'label'       		=> __( 'Duties:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_duties'] ) ? $dhl_label_items['pr_dhl_duties'] : '',
				'options'			=> $duties_opt,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_paperless_trade',
				'label'       		=> __( 'Paperless Trade:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_paperless_trade'] ) ? $dhl_label_items['pr_dhl_paperless_trade'] : '',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			// echo '<p>';
			// echo '<input id="upload_image" type="text" size="36" name="upload_image" value="" />';
			// echo '<input id="upload_image_button" type="button" value="Upload Image" />';
			// echo '</p>';

			/*$commercial_invoice = PR_DHL_PLUGIN_DIR_URL . '/assets/pdf/commercial_invoice.pdf';
			woocommerce_wp_text_input( array(
					'id'	          	=> 'pr_dhl_invoice',
					'name'          	=> 'pr_dhl_invoice',
					'type'          	=> 'file',
					'label'       		=>  __( 'Upload invoice: ', 'pr-shipping-dhl' ),
					'placeholder' 		=> '',
					'description'		=> sprintf( __('Download template commercial invoice %shere%s.', 'pr-shipping-dhl'), '<a href="' . $commercial_invoice . '" target="_blank">', '</a>'),
					'custom_attributes'	=> array( $is_disabled => $is_disabled ),
					'class'				=> ''
				) );*/
		}
	}

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids( ) {
		return array('pr_dhl_ship_date', 'pr_dhl_additional_insurance', 'pr_dhl_insured_value', 'pr_dhl_declared_value','pr_dhl_duties', 'pr_dhl_paperless_trade', 'pr_dhl_invoice');
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
		// update_post_meta( $order_id, '_pr_shipment_dhl_express_label_tracking', $tracking_items );
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

		$setting_ids = array( 'dhl_api_user','dhl_api_pwd', 'dhl_account_num', 'dhl_shipper_name', 'dhl_shipper_company', 'dhl_shipper_address','dhl_shipper_address2', 'dhl_shipper_address_city', 'dhl_shipper_address_state', 'dhl_shipper_address_zip', 'dhl_shipper_phone', 'dhl_shipper_email', 'dhl_bank_holder', 'dhl_bank_name', 'dhl_bank_iban', 'dhl_bank_bic', 'dhl_bank_ref', 'dhl_bank_ref_2', 'dhl_cod_fee' );

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
