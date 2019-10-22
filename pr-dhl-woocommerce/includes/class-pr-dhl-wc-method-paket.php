<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * DHL Shipping Method.
 *
 * @package  PR_DHL_Method
 * @category Shipping Method
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Method_Paket' ) ) :

class PR_DHL_WC_Method_Paket extends WC_Shipping_Method {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id = 'pr_dhl_paket';
		$this->instance_id = absint( $instance_id );
		$this->method_title = __( 'DHL Paket', 'pr-shipping-dhl' );
		$this->method_description = sprintf( __( 'Below you will find all functions for controlling, preparing and processing your shipment with DHL Paket. Prerequisite is a valid DHL business customer contract. If you are not yet a DHL business customer, you can request a quote %shere%s.', 'pr-shipping-dhl' ), '<a href="https://www.dhl.de/de/geschaeftskunden/paket/kunde-werden/angebot-dhl-geschaeftskunden-online.html" target="_blank">', '</a>' );

		$this->init();
	}

	/**
	 * init function.
	 */
	public function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );
		
		add_action('admin_footer', array( $this, 'add_datepicker' ), 10);
		
	}

	public function load_admin_scripts( $hook ) {
	    if( 'woocommerce_page_wc-settings' != $hook ) {
			// Only applies to WC Settings panel
			return;
	    }

	    $test_con_data = array( 
	    					'ajax_url' => admin_url( 'admin-ajax.php' ),
						    'loader_image'   => admin_url( 'images/loading.gif' ),
	    					'test_con_nonce' => wp_create_nonce( 'pr-dhl-test-con' ) 
	    				);

		// wp_enqueue_style( 'wc-shipment-dhl-label-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-admin.css' );		
		wp_enqueue_script( 'wc-shipment-dhl-testcon-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-test-connection.js', array('jquery'), PR_DHL_VERSION );
		// in JavaScript, object properties are accessed as ajax_object.ajax_url, ajax_object.we_value
		wp_localize_script( 'wc-shipment-dhl-testcon-js', 'dhl_test_con_obj', $test_con_data );
	}

	/**
	 * Get message
	 * @return string Error
	 */
	private function get_message( $message, $type = 'notice notice-error is-dismissible' ) {

		ob_start();
		?>
		<div class="<?php echo $type ?>">
			<p><?php echo $message ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$wc_shipping_methods = WC()->shipping->get_shipping_methods();
		$wc_shipping_titles = wp_list_pluck($wc_shipping_methods, 'method_title', 'id');
		
		$payment_gateway_titles = PR_DHL()->get_payment_gateways();

		$log_path = PR_DHL()->get_log_url();

		$select_dhl_product = array( '0' => __( '- Select DHL Product -', 'pr-shipping-dhl' ) );

		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$select_dhl_product_int = $dhl_obj->get_dhl_products_international();
			$select_dhl_product_dom = $dhl_obj->get_dhl_products_domestic();
			$select_dhl_visual_age 	= $dhl_obj->get_dhl_visual_age();
			
		} catch (Exception $e) {
			PR_DHL()->log_msg( __('DHL Products not displaying - ', 'pr-shipping-dhl') . $e->getMessage() );
		}

		$this->form_fields = array(
			'dhl_pickup_dist'     => array(
				'title'           => __( 'Shipping and Pickup', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
			),
			'dhl_account_num' => array(
				'title'             => __( 'Account Number (EKP)', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Your DHL account number (10 digits - numerical), also called "EKP“. This will be provided by your local DHL sales organization.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '1234567890',
				'custom_attributes'	=> array( 'maxlength' => '10' )
			),
			'dhl_participation_title'     => array(
				'title'           => __( 'DHL Products and Participation Number', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'For each DHL product that you would like to use, please enter your participation number here. The participation number consists of the last two characters of the respective accounting number, which you will find in your DHL contract data (for example, 01).', 'pr-shipping-dhl' ),
			),
		);

		foreach ($select_dhl_product_dom as $key => $value) {

			$this->form_fields += array(
				'dhl_participation_' . $key => array(
					'title'             => $value,
					'type'              => 'text',
					'placeholder'		=> '',
					'custom_attributes'	=> array( 'maxlength' => '2' ),
				)
			);
		}

		foreach ($select_dhl_product_int as $key => $value) {

			$this->form_fields += array(
				'dhl_participation_' . $key => array(
					'title'             => $value,
					'type'              => 'text',
					'placeholder'		=> '',
					'custom_attributes'	=> array( 'maxlength' => '2' )
				)
			);
		}


		$this->form_fields += array(
			'dhl_participation_return' => array(
				'title'             => __('DHL Retoure', 'pr-shipping-dhl'),
				'type'              => 'text',
				'placeholder'		=> '',
				'custom_attributes'	=> array( 'maxlength' => '2' )
			),
			'dhl_general'     => array(
				'title'           => __( 'Shipping Label Settings', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure the shipping label settings.', 'pr-shipping-dhl' ),
			),
			'dhl_default_product_dom' => array(
				'title'             => __( 'Domestic Default Service', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL Paket shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_dom,
				'class'          => 'wc-enhanced-select',
			),
			'dhl_default_product_int' => array(
				'title'             => __( 'International Default Service', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL Paket shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_int,
				'class'          => 'wc-enhanced-select',
			),
			'dhl_email_notification' => array(
				'title'             => __( 'Email Notification', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enabled', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Email Notification" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_age_visual' => array(
				'title'             => __( 'Visual Age Check default', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'options' 			=> $select_dhl_visual_age,
				'description'       => __( 'Please, tick here if you want the "Visual Age Check" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'class'          	=> 'wc-enhanced-select',
			),
			'dhl_default_additional_insurance' => array(
				'title'             => __( 'Additional Insurance default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Additional Insurance" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_no_neighbor' => array(
				'title'             => __( 'No Neighbor Delivery default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "No Neighbor Delivery" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_named_person' => array(
				'title'             => __( 'Named Person Only default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Named Person Only" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_premium' => array(
				'title'             => __( 'Premium default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Premium" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_bulky_goods' => array(
				'title'             => __( 'Bulky Goods default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Bulky Goods" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_identcheck' => array(
				'title'             => __( 'Ident Check default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_identcheck_dob' => array(
				'title'             => __( 'Ident Check Date of Birth default', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'description'       => __( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'class' 			=> 'short pr-dhl-date-picker'
			),
			'dhl_default_identcheck_age' => array(
				'title'             => __( 'Ident Check Age default', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => '',
				'options' 			=> $select_dhl_visual_age,
				'description'       => __( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_default_is_codeable' => array(
				'title'             => __( 'Print Only If Codeable default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Print Only If Codeable" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_tracking_note' => array(
				'title'             => __( 'Tracking Note', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Make Private', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here to not send an email to the customer when the tracking number is added to the order.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_api'           => array(
				'title'           => __( 'API Settings', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your access towards the DHL Paket APIs by means of authentication.', 'pr-shipping-dhl' ),
			),
			'dhl_api_user' => array(
				'title'             => __( 'Username', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Your username for the DHL business customer portal. Please note the lower case and test your access data in advance at %shere%s.', 'pr-shipping-dhl' ), '<a href="' . PR_DHL_PAKET_BUSSINESS_PORTAL . '" target = "_blank">', '</a>' ),
				'desc_tip'          => false,
				'default'           => ''
			),
			'dhl_api_pwd' => array(
				'title'             => __( 'Password', 'pr-shipping-dhl' ),
				'type'              => 'password',
				'description'       => sprintf( __( 'Your password for the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and test your access data in advance at %shere%s', 'pr-shipping-dhl' ), '<a href="' . PR_DHL_PAKET_BUSSINESS_PORTAL . '" target = "_blank">', '</a>' ),
				'desc_tip'          => false,
				'default'           => ''
			),/*
			'dhl_sandbox' => array(
				'title'             => __( 'Sandbox Mode', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable Sandbox Mode', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want to test the plug-in installation against the DHL Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),*/
			'dhl_debug' => array(
				'title'             => __( 'Debug Log', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => sprintf( __( 'A log file containing the communication to the DHL server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.', 'pr-shipping-dhl' ), '<a href="' . $log_path . '" target = "_blank">', '</a>' )
			),
			'dhl_pixel_tracking' => array(
				'title'             => __( 'Tracking', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable tracking', 'pr-shipping-dhl' ),
				'default'           => 'yes',
				'description'       => __( 'This allows DHL to anomously track whether the preferred services are being used on the shop or not.' )
			),
		);

		$base_country_code = PR_DHL()->get_base_country();
		// Preferred options for Germany only
		// IF USING PREFERRED OPTIONS AND COD IS ENABLED DISPALY A WARNING MESSAGE OR DON'T ALLOW IT TO BE USED?
		if( $base_country_code == 'DE' ) {

			$this->form_fields += array(
				'dhl_preferred'           => array(
					'title'           => __( 'Preferred Service', 'pr-shipping-dhl' ),
					'type'            => 'title',
					'description'     => __( 'Preferred service options.', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_day' => array(
					'title'             => __( 'Preferred Day', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Day', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred day of delivery.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_day_cost' => array(
					'title'             => __( 'Preferred Day Price', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'Insert gross value as surcharge for the preferred day. Insert 0 to offer service for free.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '1.2',
					'class'				=> 'wc_input_decimal' // adds JS to validate input is in price format
				),
				'dhl_preferred_day_cutoff' => array(
					'title'             => __( 'Cut Off Time', 'pr-shipping-dhl' ),
					'type'              => 'time',
					'description'       => __( 'The cut-off time is the latest possible order time up to which the minimum preferred day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest preferred day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
 					'default'           => '12:00',
				),
				'dhl_preferred_exclusion_mon' => array(
					'title'             => __( 'Exclusion of transfer days', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Monday', 'pr-shipping-dhl' ),
					'description'       => __( 'Exclude days to transfer packages to DHL.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_exclusion_tue' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Tuesday', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_exclusion_wed' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Wednesday', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_exclusion_thu' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Thursday', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_exclusion_fri' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Friday', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_exclusion_sat' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Saturday', 'pr-shipping-dhl' ),
				),
				'dhl_preferred_time' => array(
					'title'             => __( 'Preferred Time', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Time', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred time of delivery.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_time_cost' => array(
					'title'             => __( 'Preferred Time Price', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'Insert gross value as surcharge for the preferred time. Insert 0 to offer service for free.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '4.8',
					'class'				=> 'wc_input_decimal'
				),
				'dhl_preferred_day_time_cost' => array(
					'title'             => __( 'Preferred Day and Time Price', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => __( 'Insert gross value as surcharge for the combination of preferred day and time. Insert 0 to offer service for free.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'default'           => '4.8',
					'class'				=> 'wc_input_decimal'
				),
				'dhl_preferred_location' => array(
					'title'             => __( 'Preferred Location', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Location', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred location.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_neighbour' => array(
					'title'             => __( 'Preferred Neighbour', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Neighbour', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred neighbour.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_shipping_methods' => array(
					'title'             => __( 'Shipping Methods', 'pr-shipping-dhl' ),
					'type'              => 'multiselect',
					'description'       => __( 'Select the Shipping Methods to display the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'options'           => $wc_shipping_titles,
					'class'          => 'wc-enhanced-select',
				),
				'dhl_payment_gateway' => array(
					'title'             => __( 'Exclude Payment Gateways', 'pr-shipping-dhl' ),
					'type'              => 'multiselect',
					'description'       => __( 'Select the Payment Gateways to hide the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'options'           => $payment_gateway_titles,
					'class'          => 'wc-enhanced-select',
				),
				'dhl_parcel_finder'           => array(
					'title'           => __( 'Location Finder', 'pr-shipping-dhl' ),
					'type'            => 'title',
					'description'     => __( 'Please define the parameters for the display of dhl locations in the shop frontend.', 'pr-shipping-dhl' ),
				),
				'dhl_display_packstation' => array(
					'title'             => __( 'Packstation', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Packstation', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display Packstation locations on Google Maps when searching for drop off locations on the front-end.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_display_parcelshop' => array(
					'title'             => __( 'Parcelshop', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Parcelshop', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display Parcelshop locations on Google Maps when searching for drop off locations on the front-end.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_display_post_office' => array(
					'title'             => __( 'Post Office', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Post Office', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display Post Office locations on Google Maps when searching for drop off locations on the front-end.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_display_google_maps' => array(
					'title'             => __( 'Google Maps', 'pr-shipping-dhl' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Google Maps', 'pr-shipping-dhl' ),
					'default'           => 'yes',
					'description'       => __( 'Enabling this will display Google Maps on the front-end.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
				),
				'dhl_parcel_limit' => array(
					'title'             => __( 'Limit Results', 'pr-shipping-dhl' ),
					'type'              => 'number',
					'description'       => __( 'Limit displayed results, from 1 to at most 50.', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'class'				=> '',
					'default'           => '20',
					'custom_attributes'	=> array( 'min' => '1', 'max' => '50' )
				),
				'dhl_google_maps_api_key' => array(
					'title'             => __( 'API Key', 'pr-shipping-dhl' ),
					'type'              => 'text',
					'description'       => sprintf( __( 'The Google Maps API Key is necassary to display the DHL Locations on a google map.<br/>Get a free Google Maps API key %shere%s.', 'pr-shipping-dhl' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target = "_blank">', '</a>' ),
					'desc_tip'          => false,
					'class'				=> ''
				),
			);
		}

		$this->form_fields += array(
			'dhl_shipper'           => array(
				'title'           => __( 'Shipper Address', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Enter Shipper Address below.', 'pr-shipping-dhl' ),
			),
			'dhl_shipper_name' => array(
				'title'             => __( 'Name', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Name.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_company' => array(
				'title'             => __( 'Company', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Company.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address' => array(
				'title'             => __( 'Street Address', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Street Address.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_no' => array(
				'title'             => __( 'Street Address Number', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Street Address Number.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_city' => array(
				'title'             => __( 'City', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper City.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_state' => array(
				'title'             => __( 'State', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper County.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_zip' => array(
				'title'             => __( 'Postcode', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Postcode.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_phone' => array(
				'title'             => __( 'Phone Number', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Phone Number.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_email' => array(
				'title'             => __( 'Email', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Email.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return'           => array(
				'title'           => __( 'Return Address', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Enter Return Address below.', 'pr-shipping-dhl' ),
			),
			'dhl_default_return_address_enabled' => array(
				'title'             => __( 'Create Return Label default', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Create Return Label" option to be checked in the "Edit Order" before printing a label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_return_name' => array(
				'title'             => __( 'Name', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Name.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_company' => array(
				'title'             => __( 'Company', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Company.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address' => array(
				'title'             => __( 'Street Address', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Street Address.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_no' => array(
				'title'             => __( 'Street Address Number', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Street Address Number.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_city' => array(
				'title'             => __( 'City', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return City.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_state' => array(
				'title'             => __( 'State', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return County.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_zip' => array(
				'title'             => __( 'Postcode', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Postcode.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_phone' => array(
				'title'             => __( 'Phone Number', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Phone Number.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_email' => array(
				'title'             => __( 'Email', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Email.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_bank'           => array(
				'title'           => __( 'Bank Details', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Enter your bank details needed for services that use COD.', 'pr-shipping-dhl' ),
			),
			'dhl_bank_holder' => array(
				'title'             => __( 'Account Owner', 'pr-shipping-dhl' ),
				'type'              => 'text',
			),
			'dhl_bank_name' => array(
				'title'             => __( 'Bank Name', 'pr-shipping-dhl' ),
				'type'              => 'text',
			),
			'dhl_bank_iban' => array(
				'title'             => __( 'IBAN', 'pr-shipping-dhl' ),
				'type'              => 'text',
			),
			'dhl_bank_bic' => array(
				'title'             => __( 'BIC', 'pr-shipping-dhl' ),
				'default'           => ''
			),
			'dhl_bank_ref' => array(
				'title'             => __( 'Payment Reference', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'description'       => sprintf( __( 'Use "%s" to send the order id as a bank reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}' ),
				'desc_tip'          => true,
				'default'           => '{order_id}'
			),
			'dhl_bank_ref_2' => array(
				'title'             => __( 'Payment Reference 2', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'description'       => sprintf( __( 'Use "%s" to send the order id as a bank reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}' ),
				'desc_tip'          => true,
				'default'           => '{email}'
			),/*
			'dhl_cod_fee' => array(
				'title'             => __( 'Add COD Fee', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'description'       => __( 'Add €2 fee for users using COD.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes'	=> array( 'maxlength' => '35' )
			),*/
		);
	}

	public function add_datepicker(){ 
		?>
		<script type="text/javascript">
		jQuery(document).ready(function(){
			jQuery('.pr-dhl-date-picker').datepicker({
				dateFormat: 'yy-mm-dd'
			});
		});
		</script>
		<?php
	}

	/**
	 * Generate Button HTML.
	 *
	 * @access public
	 * @param mixed $key
	 * @param mixed $data
	 * @since 1.0.0
	 * @return string
	 */
	public function generate_button_html( $key, $data ) {
		$field    = $this->plugin_id . $this->id . '_' . $key;
		$defaults = array(
			'class'             => 'button-secondary',
			'css'               => '',
			'custom_attributes' => array(),
			'desc_tip'          => false,
			'description'       => '',
			'title'             => '',
		);

		$data = wp_parse_args( $data, $defaults );

		ob_start();
		?>
		<tr valign="top">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo wp_kses_post( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
					<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	public function init_instance_form_fields() {
		$this->instance_form_fields = array(
			'title'            => array(
				'title'           => __( 'Method Title', 'pr-shipping-dhl' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'pr-shipping-dhl' ),
				'default'         => __( 'DHL Paket', 'pr-shipping-dhl' ),
				'desc_tip'        => true
			)
		);
	}

	/**
	 * Validate the API key
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_pickup_field( $key ) {
		$value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$dhl_obj->dhl_validate_field( 'pickup', $value );

		} catch (Exception $e) {
			
			echo $this->get_message( __('Pickup Account Number: ', 'pr-shipping-dhl') . $e->getMessage() );
			throw $e;

		}

		return $value;
	}

	/**
	 * Validate the API secret
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_distribution_field( $key ) {
		$value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );
		
		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$dhl_obj->dhl_validate_field( 'distribution', $value );

		} catch (Exception $e) {

			echo $this->get_message( __('Distribution Center: ', 'pr-shipping-dhl') . $e->getMessage() );
			throw $e;
		}

		return $value;
	}

	/**
	 * Validate the Google API Key
	 * @see validate_settings_fields()
	 */
	/*
	public function validate_dhl_google_maps_api_key_field( $key ) {
		$google_maps_api = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

		if ( empty( $google_maps_api ) ) {

			if ( isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_packstation' ] ) || 
				 isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_parcelshop' ] ) || 
				 isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_post_office' ] ) ) {

					$error_message = __('In order to show the dhl locations on a map, you need to insert a Google API Key. Otherwise, please deactivate the locations.', 'pr-shipping-dhl');
					echo $this->get_message( $error_message );
					throw new Exception( $error_message );
				
			}
		}

		return $google_maps_api;
	}*/

	/**
	 * Validate the Packstation enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_packstation_field( $key ) {
		return $this->validate_location_enabled_field( $key, PR_DHL_PACKSTATION );
	}

	/**
	 * Validate the Parcelshop enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_parcelshop_field( $key ) {
		return $this->validate_location_enabled_field( $key, __( 'Parcelshop', 'pr-shipping-dhl' ) );
	}

	/**
	 * Validate the Post Office enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_post_office_field( $key ) {
		return $this->validate_location_enabled_field( $key, __( 'Post Office', 'pr-shipping-dhl' ) );
	}

	/**
	 * Validate the Google Maps enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_google_maps_field( $key ) {
		return $this->validate_location_enabled_field( $key, __( 'Google Maps', 'pr-shipping-dhl' ) );
	}

	/**
	 * Validate the any location enabled field
	 * @see validate_settings_fields()
	 * @return return 'no' or 'yes' (not exception) to 'disable' locations as opposed to NOT save them
	 */
	protected function validate_location_enabled_field( $key, $location_type ) {
		if ( ! isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
			return 'no';
		}

		// Verify whether Google API key set
		$google_maps_api_key = $_POST[ $this->plugin_id . $this->id . '_dhl_google_maps_api_key' ];

		// If not return 'no'
		if ( empty( $google_maps_api_key ) ) {
			
			if( $key == 'dhl_display_google_maps' ){ 
				$error_message = sprintf( __('In order to show %s, you need to set a Google API Key first.', 'pr-shipping-dhl'), $location_type );	
			}else{
				$error_message = sprintf( __('In order to show %s on a map, you need to set a Google API Key first.', 'pr-shipping-dhl'), $location_type );
			}
			
			echo $this->get_message( $error_message );
			
			return 'no';
		}

		return 'yes';
	}

}

endif;
