<?php

use PR\DHL\Utils\API_Utils;

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
		$this->method_title = __( 'DHL Paket', 'dhl-for-woocommerce' );
		$this->method_description = sprintf( __( 'Below you will find all functions for controlling, preparing and processing your shipment with DHL Paket. Prerequisite is a valid DHL business customer contract. If you are not yet a DHL business customer, you can request a quote %shere%s.', 'dhl-for-woocommerce' ), '<a href="https://www.dhl.de/dhl-kundewerden?source=woocommerce&cid=c_dhloka_de_woocommerce" target="_blank">', '</a>' );

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

		add_filter( 'woocommerce_settings_api_form_fields_' .$this->id, array( $this, 'after_init_set_field_options' ) );

		if ( API_Utils::is_new_merchant() ) {
			new PR_DHL_WC_Wizard_Paket();
		}
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

	public function excluded_order_statuses(){

		return array(
			'wc-failed',
			'wc-refunded',
			'wc-cancelled'
		);
	}

	public function get_order_statuses(){

		$wc_order_statuses = wc_get_order_statuses();

		foreach( $this->excluded_order_statuses() as $status ){
			unset( $wc_order_statuses[ $status ] );
		}

		return $wc_order_statuses;
	}

	/**
     * @inheritdoc
     */
    public function get_admin_options_html() {
        return '<div id="dhlpaket_shipping_method_settings"><div class="dhlpaket_tab_menu"></div><div class="dhlpaket_tab_content">' . parent::get_admin_options_html() . '</div></div>';
    }

	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {
		$wc_shipping_methods = WC()->shipping->get_shipping_methods();
		$wc_shipping_titles = wp_list_pluck($wc_shipping_methods, 'method_title', 'id');
		$order_status_options = array(
			'none' => __( 'None', 'dhl-for-woocommerce'),
		);

		$order_status_options = array_merge( $order_status_options, $this->get_order_statuses() );

		$log_path = PR_DHL()->get_log_url();

		$select_dhl_product = array( '0' => __( '- Select DHL Product -', 'dhl-for-woocommerce' ) );

		$select_dhl_desc_default = array(
			'product_name' => __('Product Name', 'dhl-for-woocommerce'),
			'product_cat' => __('Product Categories', 'dhl-for-woocommerce'),
			'product_tag' => __('Product Tags', 'dhl-for-woocommerce'),
		);

		try {

			$dhl_obj = PR_DHL()->get_dhl_factory();
			$select_dhl_product_int   = $dhl_obj->get_dhl_products_international();
			$select_dhl_product_dom   = $dhl_obj->get_dhl_products_domestic();
			$select_dhl_visual_age 	  = $dhl_obj->get_dhl_visual_age();
			$myaccount_pwd_expiration = $dhl_obj->get_dhl_myaccount_pwd_expiration();

		} catch (Exception $e) {
			PR_DHL()->log_msg( __('DHL Products not displaying - ', 'dhl-for-woocommerce') . $e->getMessage() );
		}

		$weight_units = get_option( 'woocommerce_weight_unit' );
		if ($myaccount_pwd_expiration == '7days') {
			$password_expiration_message = sprintf(
				__('<p style="color: red;">Your password will expire in less than 7 days, please go to your <a href="%s" target = "_blank">business portal</a> and reset your password then click the "Get Account Settings" button below.</p>', 'dhl-for-woocommerce'),
				PR_DHL_PAKET_BUSSINESS_PORTAL_LOGIN
			);
		} elseif ($myaccount_pwd_expiration == '30days') {
			$password_expiration_message = sprintf(
				__('<p style="color: red;">Your password will expire in less than 30 days, please go to your <a href="%s" target = "_blank">business portal</a> and reset your password then click the "Get Account Settings" button below.</p>', 'dhl-for-woocommerce'),
				PR_DHL_PAKET_BUSSINESS_PORTAL_LOGIN
			);
		} else {
			$password_expiration_message = '';
		}
		
		$this->form_fields = array(
			'dhl_pickup_api_dist'     => array(
				'title'           => __( 'Account and API Settings', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your shipping parameters and your access towards the DHL Paket APIs by means of authentication.', 'dhl-for-woocommerce' ),
			),
			'dhl_api_user' => array(
				'title'             => __( 'Username', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Your username for the DHL business customer portal. Please note the lower case and test your access data in advance at %shere%s.', 'dhl-for-woocommerce' ), '<a href="' . PR_DHL_PAKET_BUSSINESS_PORTAL . '" target = "_blank">', '</a>' ),
				'desc_tip'          => false,
				'default'           => ''
			),
			'dhl_api_pwd' => array(
				'title'             => __( 'Password', 'dhl-for-woocommerce' ),
				'type'              => 'password',
				'description'       => sprintf( __( 'Your password for the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and test your access data in advance at %shere%s', 'dhl-for-woocommerce' ), '<a href="' . PR_DHL_PAKET_BUSSINESS_PORTAL . '" target = "_blank">', '</a>' ) . $password_expiration_message,
				'desc_tip'          => false,
				'default'           => ''
			),
			'dhl_my_account_button' => array(
				'title'             => PR_DHL_BUTTON_MY_ACCOUNT,
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "dhlMyAccount('#woocommerce_pr_dhl_paket_dhl_my_account_button');",
				),
				'description'       => __( 'Press the button to read your DHL Business Account settings into the DHL for WooCommerce plugin.', 'dhl-for-woocommerce' ),
				'desc_tip'          => false,
			),
			'dhl_account_num' => array(
				'title'             => __( 'Account Number (EKP)', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Your DHL account number (10 digits - numerical), also called "EKP“. This will be provided by your local DHL sales organization.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '1234567890',
				'custom_attributes'	=> array( 'maxlength' => '10' )
			),
			'dhl_default_api' => array(
				'title'             => __( 'API Protocol', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Select the API protocol to use for creating shipping labels.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => array( 'soap' => 'SOAP', 'rest-api' => 'REST' ),
				'class'             => 'wc-enhanced-select',
				'default'           => API_Utils::is_new_merchant() ? 'rest-api' : 'soap',
			),
			'dhl_sandbox' => array(
				'title'             => __( 'Sandbox Mode', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable Sandbox Mode', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want to test the plug-in installation against the DHL Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_api_sandbox_user' => array(
				'title'             => __( 'Sandbox Username', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => sprintf( __( 'Your sandbox username is the same as for the DHL developer portal. You can create an account %shere%s.', 'dhl-for-woocommerce' ), '<a href="' . PR_DHL_PAKET_DEVELOPER_PORTAL . '" target = "_blank">', '</a>' ),
				'desc_tip'          => false,
				'default'           => ''
			),
			'dhl_api_sandbox_pwd' => array(
				'title'             => __( 'Sandbox Password', 'dhl-for-woocommerce' ),
				'type'              => 'password',
				'description'       => sprintf( __( 'Your sandbox password is the same as for the DHL developer portal. You can create an account %shere%s.', 'dhl-for-woocommerce' ), '<a href="' . PR_DHL_PAKET_DEVELOPER_PORTAL . '" target = "_blank">', '</a>' ),
				'desc_tip'          => false,
				'default'           => ''
			),
			'dhl_debug' => array(
				'title'             => __( 'Debug Log', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => sprintf( __( 'A log file containing the communication to the DHL server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.', 'dhl-for-woocommerce' ), '<a href="' . $log_path . '" target = "_blank">', '</a>' )
			),
			'dhl_participation_title'     => array(
				'title'           => __( 'DHL Products and Participation Number', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'For each DHL product that you would like to use, please enter your participation number here. The participation number consists of the last two characters of the respective accounting number, which you will find in your DHL contract data (for example, 01).', 'dhl-for-woocommerce' ),
			),
			'dhl_my_account_button_prod' => array(
				'title'             => PR_DHL_BUTTON_MY_ACCOUNT,
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "dhlMyAccount('#woocommerce_pr_dhl_paket_dhl_my_account_button_prod');",
				),
				'description'       => __( 'Press the button to read your DHL Business Account settings into the DHL for WooCommerce plugin.', 'dhl-for-woocommerce' ),
				'desc_tip'          => false,
			),
		);

		// $booking_text_array = unserialize(get_option('booking_text_option'));
		$booking_text_array = $dhl_obj->get_dhl_booking_text();

		foreach ($select_dhl_product_dom as $key => $value) {
			$description = '';

			// Check if the product key exists in the booking_text array
			if (isset($booking_text_array[ $key ])) {
				$description = $booking_text_array[ $key ];
			}

			$this->form_fields += array(
				'dhl_participation_' . $key => array(
					'title'             => $value,
					'type'              => 'text',
					'placeholder'		=> '',
					'custom_attributes'	=> array( 'maxlength' => '2' ),
					'description'       => $description, // Set the description
				)
			);
		}

		foreach ($select_dhl_product_int as $key => $value) {
			$description = '';
		
			// Check if the product key exists in the booking_text array
			if (isset($booking_text_array[ $key ])) {
				$description = $booking_text_array[ $key ];
			}
			
			$this->form_fields += array(
				'dhl_participation_' . $key => array(
					'title'             => $value,
					'type'              => 'text',
					'placeholder'       => '',
					'custom_attributes' => array('maxlength' => '2'),
					'description'       => $description, // Set the description
				)
			);
		}


		$this->form_fields += array(
			'dhl_participation_return' => array(
				'title'             => __('DHL Retoure', 'dhl-for-woocommerce'),
				'type'              => 'text',
				'placeholder'		=> '',
				'custom_attributes'	=> array( 'maxlength' => '2' )
			),
			'dhl_general'     => array(
				'title'           => __( 'Shipping Label Settings', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => sprintf( __( 'Would you like to customize the DHL shipment notification? You can now add your online shop’s name and logo and we will display it in the DHL shipment notification. To upload your logo please use the following %slink%s.', 'dhl-for-woocommerce' ), '<a href="' . PR_DHL_PAKET_NOTIFICATION_EMAIL . '" target = "_blank">', '</a>' ),
			),
			'dhl_default_product_dom' => array(
				'title'             => __( 'Domestic Default Service', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL Paket shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_dom,
				'class'          => 'wc-enhanced-select',
			),
			'dhl_default_product_int' => array(
				'title'             => __( 'International Default Service', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL Paket shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_int,
				'class'          => 'wc-enhanced-select',
			),
			'dhl_email_notification' => array(
				'title'             => __( 'Send Customer Email', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'             => __( 'Please select whether to send the customer\'s email to DHL or not. "Customer Confirmation" displays a confirmation on the checkout page and "Confirmed via terms & condition" assumes confirmation via the website terms & conditions.', 'dhl-for-woocommerce' ),
				'options'           => array(
					'no' 		=> __( 'Do not send', 'dhl-for-woocommerce'),
					'yes' 		=> __( 'Customer confirmation', 'dhl-for-woocommerce'),
					'sendviatc' => __( 'Confirmed via terms & condition', 'dhl-for-woocommerce'),
				),
				'default'           => 'sendviatc',
				'desc_tip'          => true,
			),
			'dhl_phone_notification' => array(
				'title'             => __( 'Send Customer Phone', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Please select whether to send the customer\'s phone to DHL or not. "Confirmed via terms & condition" assumes confirmation via the website terms & conditions.', 'dhl-for-woocommerce' ),
				'options'           => array(
					'no' 		=> __( 'Do not send', 'dhl-for-woocommerce'),
					'sendviatc' => __( 'Confirmed via terms & condition', 'dhl-for-woocommerce'),
				),
				'default'           => 'sendviatc',
				'desc_tip'          => true,
			),
			'dhl_default_age_visual' => array(
				'title'             => __( 'Visual Age Check default', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'options' 			=> $select_dhl_visual_age,
				'description'       => __( 'Please, tick here if you want the "Visual Age Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'class'          	=> 'wc-enhanced-select',
			),
			'dhl_default_additional_insurance' => array(
				'title'             => __( 'Additional Insurance default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Additional Insurance" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_no_neighbor' => array(
				'title'             => __( 'No Neighbor Delivery default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "No Neighbor Delivery" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_named_person' => array(
				'title'             => __( 'Named Person Only default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Named Person Only" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_premium' => array(
				'title'             => __( 'Premium default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Premium" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_bulky_goods' => array(
				'title'             => __( 'Bulky Goods default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Bulky Goods" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_identcheck' => array(
				'title'             => __( 'Ident Check default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_signature_service' => array(
					'title'             => __( 'Signed for by recipient default', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
					'default'           => 'no',
					'description'       => __( 'Please, tick here if you want the "Signed for by recipient" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
			),
			'dhl_default_identcheck_age' => array(
				'title'             => __( 'Ident Check Age default', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => '',
				'options' 			=> $select_dhl_visual_age,
				'description'       => __( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_endorsement' => array(
				'title'             => esc_html__( 'Endorsement type', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'default'           => '',
				'options'           => array(
					'IMMEDIATE'   	   => esc_html__( 'Sending back to sender', 'dhl-for-woocommerce' ),
					'ABANDONMENT' 	   => esc_html__( 'Abandonment of parcel', 'dhl-for-woocommerce' )
				),
				'description'       => esc_html__( 'Please, tick here if you want the "Endorsement value" to be selected in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_is_codeable' => array(
				'title'             => __( 'Print Only If Codeable default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Print Only If Codeable" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_default_routing' => array(
				'title'             => __( 'Parcel Outlet Routing default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Parcel Outlet Routing" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_add_weight_type' => array(
				'title'             => __( 'Additional Weight Type', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Select whether to add an absolute weight amount or percentage amount to the total product weight.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => array( 'absolute' => 'Absolute', 'percentage' => 'Percentage'),
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_add_weight' => array(
				'title'             => sprintf( __( 'Additional Weight (%s or %%)', 'dhl-for-woocommerce' ), $weight_units),
				'type'              => 'text',
				'description'       => __( 'Add extra weight in addition to the products.  Either an absolute amount or percentage (e.g. 10 for 10%).', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '',
				'class'				=> 'wc_input_decimal'
			),
			'dhl_desc_default' => array(
				'title'             => __( 'Package Description', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Prefill the customs package description with one of the options for cross-border packages.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'			=> 'product_cat',
				'options'           => $select_dhl_desc_default,
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_label_format' => array(
				'title'             => __( 'Label Format', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'description'       => __( 'Select one of the formats to generate the shipping label in.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => array(
					'A4' => 'A4',
					'910-300-700' => 'Laser printer 105 x 205 mm',
					'910-300-700-oZ' => 'Laser printer 105 x 205 mm (no info)',
					'910-300-600' => 'Thermo printer 103 x 199 mm',
					'910-300-610' => 'Thermo printer 103 x 202 mm',
					'910-300-710' => 'Laser printer 105 x 208 mm',
					'910-300-410' => 'Laser printer 103 x 150 mm',
					'910-300-300' => 'Laser printer 105 x 148 mm',
					'910-300-300-oZ' => 'Laser printer 105 x 148 mm (without additional labels)',
					'100x70mm'	  => '100 x 70 mm (only for Warenpost)'
				),
				'default' 			=> '910-300-700',
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_add_logo' => array(
				'title'             => __( 'Logo', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Add Logo', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'The logo will be added from your DHL dashboard settings.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_shipper_reference' => array(
				'title'             => sprintf( __( 'Shipper Reference', 'dhl-for-woocommerce' ), $weight_units),
				'type'              => 'text',
				'description'       => __( 'Add shipper reference.', 'dhl-for-woocommerce' ),
				'desc_tip'          => false,
				'default'           => '',
				'placeholder'		=> '',
			),
			'dhl_tracking_note' => array(
				'title'             => __( 'Tracking Note', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Make Private', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here to not send an email to the customer when the tracking number is added to the order.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_tracking_note_txt' => array(
				'title'             => __( 'Tracking Text', 'dhl-for-woocommerce' ),
				'type'              => 'textarea',
				'description'       => __( 'Set the custom text when adding the tracking number to the order notes or completed email. {tracking-link} is where the tracking number will be set.', 'dhl-for-woocommerce' ),
				'desc_tip'          => false,
				'default'           => __( 'DHL Tracking Number: {tracking-link}', 'dhl-for-woocommerce')
			),
			'dhl_add_tracking_info_completed' => array(
				'title'             => __( 'Tracking Email', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label' 			=> __( 'Add tracking text in completed email', 'dhl-for-woocommerce'),
				'description'       => __( 'Please, tick here to add tracking text when completed email is sent.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => 'no',
				'class'				=> ''
			),
			'dhl_tracking_url_language' => array(
				'title'             => __( 'Tracking URL Language', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'label' 			=> __( 'Select the tracking link language.', 'dhl-for-woocommerce'),
				'description'       => __( 'Select language of the tracking link page.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => [
					'de' => 'German',
					'en' => 'English'
				],
				'class'				=> 'wc-enhanced-select',
				'default'           => 'de',
			),
			'dhl_create_label_on_status' => array(
				'title'             => __( 'Create Label on Status', 'dhl-for-woocommerce' ),
				'type'              => 'select',
				'label' 			=> __( 'Create label on specific status.', 'dhl-for-woocommerce'),
				'description'       => __( 'Select the order status.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'options'           => $order_status_options,
				'class'				=> 'wc-enhanced-select',
				'default'           => 'no',
			),
			'dhl_change_order_status_completed' => array(
				'title'             => __( 'Order Status', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label' 			=> __( 'Change to Completed', 'dhl-for-woocommerce'),
				'description'       => __( 'Please, tick here to change the order status when label is generated.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => 'no',
				'class'				=> ''
			),
		);

		$base_country_code = PR_DHL()->get_base_country();
		// Preferred options for Germany only
		// IF USING PREFERRED OPTIONS AND COD IS ENABLED DISPALY A WARNING MESSAGE OR DON'T ALLOW IT TO BE USED?
		if( $base_country_code == 'DE' ) {

			$this->form_fields += array(
				'dhl_preferred'           => array(
					'title'           => __( 'Preferred Service', 'dhl-for-woocommerce' ),
					'type'            => 'title',
					'description'     => __( 'Preferred service options.', 'dhl-for-woocommerce' ),
				),
				'dhl_closest_drop_point' => array(
					'title'             => __( 'Closest Drop Point', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Closest Drop Point', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display a front-end option for the user to select delivery option (Home address or CDP delivery).', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_day' => array(
					'title'             => __( 'Delivery Day', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Delivery Day', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred day of delivery.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_day_cost' => array(
					'title'             => __( 'Delivery Day Price', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'description'       => __( 'Insert gross value as surcharge for the preferred day. Insert 0 to offer service for free.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'default'           => '1.2',
					'class'				=> 'wc_input_decimal' // adds JS to validate input is in price format
				),
				'dhl_preferred_day_cutoff' => array(
					'title'             => __( 'Cut Off Time', 'dhl-for-woocommerce' ),
					'type'              => 'time',
					'description'       => __( 'The cut-off time is the latest possible order time up to which the minimum preferred day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest preferred day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
 					'default'           => '12:00',
				),
				'dhl_preferred_exclusion_mon' => array(
					'title'             => __( 'Exclusion of transfer days', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Monday', 'dhl-for-woocommerce' ),
					'description'       => __( 'Exclude days to transfer packages to DHL.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_exclusion_tue' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Tuesday', 'dhl-for-woocommerce' ),
				),
				'dhl_preferred_exclusion_wed' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Wednesday', 'dhl-for-woocommerce' ),
				),
				'dhl_preferred_exclusion_thu' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Thursday', 'dhl-for-woocommerce' ),
				),
				'dhl_preferred_exclusion_fri' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Friday', 'dhl-for-woocommerce' ),
				),
				'dhl_preferred_exclusion_sat' => array(
					'type'              => 'checkbox',
					'label'             => __( 'Saturday', 'dhl-for-woocommerce' ),
				),
				'dhl_preferred_location' => array(
					'title'             => __( 'Preferred Location', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Location', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred location.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_preferred_neighbour' => array(
					'title'             => __( 'Preferred Neighbour', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Preferred Neighbour', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display a front-end option for the user to select their preferred neighbour.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_shipping_methods' => array(
					'title'             => __( 'Shipping Methods', 'dhl-for-woocommerce' ),
					'type'              => 'multiselect',
					'description'       => __( 'Select the Shipping Methods to display the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'options'           => $wc_shipping_titles,
					'class'          => 'wc-enhanced-select',
				),
				'dhl_payment_gateway' => array(
					'title'             => __( 'Exclude Payment Gateways', 'dhl-for-woocommerce' ),
					'type'              => 'multiselect',
					'default' 			=> 'cod',
					'description'       => __( 'Select the Payment Gateways to hide the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'options'           => [],
					'class'          => 'wc-enhanced-select',
				),
				'dhl_cod_payment_methods' => array(
					'title'             => __( 'COD Payment Gateways', 'dhl-for-woocommerce' ),
					'type'              => 'multiselect',
					'default' 			=> 'cod',
					'description'       => __( 'Select the Payment Gateways to use with DHL COD services. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'options'           => [],
					'class'          => 'wc-enhanced-select',
				),
				'dhl_parcel_finder'           => array(
					'title'           => __( 'Location Finder', 'dhl-for-woocommerce' ),
					'type'            => 'title',
					'description'     => __( 'Please define the parameters for the display of dhl locations in the shop frontend.', 'dhl-for-woocommerce' ),
				),
				'dhl_display_packstation' => array(
					'title'             => __( 'Packstation', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Packstation', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display Packstation locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_display_parcelshop' => array(
					'title'             => __( 'Parcelshop', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Parcelshop', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display Parcelshop locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_display_post_office' => array(
					'title'             => __( 'Post Office', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Post Office', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display Post Office locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_display_google_maps' => array(
					'title'             => __( 'Google Maps', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'label'             => __( 'Enable Google Maps', 'dhl-for-woocommerce' ),
					'description'       => __( 'Enabling this will display Google Maps on the front-end.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
				),
				'dhl_parcel_limit' => array(
					'title'             => __( 'Limit Results', 'dhl-for-woocommerce' ),
					'type'              => 'number',
					'description'       => __( 'Limit displayed results, from 1 to at most 50.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'class'				=> '',
					'default'           => '20',
					'custom_attributes'	=> array( 'min' => '1', 'max' => '50' )
				),
				'dhl_google_maps_api_key' => array(
					'title'             => __( 'API Key', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'description'       => sprintf( __( 'The Google Maps API Key is necassary to display the DHL Locations on a google map.<br/>Get a free Google Maps API key %shere%s.', 'dhl-for-woocommerce' ), '<a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target = "_blank">', '</a>' ),
					'desc_tip'          => false,
					'class'				=> ''
				),
			);
		}

		$this->form_fields += array(
			'dhl_shipper'           => array(
				'title'           => __( 'Shipper Address / Pickup Request Address', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'Enter Shipper Address. This address is also used for Pickup Requests.<br/>Note: For pickup requests to be accepted, this address must match a pickup address saved to your DHL Portal.', 'dhl-for-woocommerce' ),
			),
			'dhl_shipper_name' => array(
				'title'             => __( 'Name', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Name.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_company' => array(
				'title'             => __( 'Company', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Company.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address' => array(
				'title'             => __( 'Street Address', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Street Address.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_no' => array(
				'title'             => __( 'Street Address Number', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Street Address Number.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_city' => array(
				'title'             => __( 'City', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper City.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_state' => array(
				'title'             => __( 'State', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper County.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address_zip' => array(
				'title'             => __( 'Postcode', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Postcode.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_phone' => array(
				'title'             => __( 'Phone Number', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Phone Number.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_email' => array(
				'title'             => __( 'Email', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Email.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return'           => array(
				'title'           => __( 'Return Address', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'Enter Return Address below.', 'dhl-for-woocommerce' ),
			),
			'dhl_default_return_address_enabled' => array(
				'title'             => __( 'Create Return Label default', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'label'             => __( 'Checked', 'dhl-for-woocommerce' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want the "Create Return Label" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
			),
			'dhl_return_name' => array(
				'title'             => __( 'Name', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Name.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_company' => array(
				'title'             => __( 'Company', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Company.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address' => array(
				'title'             => __( 'Street Address', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Street Address.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_no' => array(
				'title'             => __( 'Street Address Number', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Street Address Number.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_city' => array(
				'title'             => __( 'City', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return City.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_state' => array(
				'title'             => __( 'State', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return County.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_address_zip' => array(
				'title'             => __( 'Postcode', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Return Postcode.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_phone' => array(
				'title'             => __( 'Phone Number', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Phone Number.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_return_email' => array(
				'title'             => __( 'Email', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => __( 'Enter Email.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_bank'           => array(
				'title'           => __( 'Bank Details', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'Enter your bank details needed for services that use COD.', 'dhl-for-woocommerce' ),
			),
			'dhl_bank_holder' => array(
				'title'             => __( 'Account Owner', 'dhl-for-woocommerce' ),
				'type'              => 'text',
			),
			'dhl_bank_name' => array(
				'title'             => __( 'Bank Name', 'dhl-for-woocommerce' ),
				'type'              => 'text',
			),
			'dhl_bank_iban' => array(
				'title'             => __( 'IBAN', 'dhl-for-woocommerce' ),
				'type'              => 'text',
			),
			'dhl_bank_bic' => array(
				'title'             => __( 'BIC', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'default'           => ''
			),
			'dhl_bank_ref' => array(
				'title'             => __( 'Payment Reference', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'description'       => sprintf( __( 'Use "%s" to send the order id as a bank reference and "%s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ), '{order_id}' , '{email}' ),
				'desc_tip'          => true,
				'default'           => '{order_id}'
			),
			'dhl_bank_ref_2' => array(
				'title'             => __( 'Payment Reference 2', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'custom_attributes'	=> array( 'maxlength' => '35' ),
				'description'       => sprintf( __( 'Use "%s" to send the order id as a bank reference and "%s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ), '{order_id}' , '{email}' ),
				'desc_tip'          => true,
				'default'           => '{email}'
			),/*
			'dhl_cod_fee' => array(
				'title'             => __( 'Add COD Fee', 'dhl-for-woocommerce' ),
				'type'              => 'checkbox',
				'description'       => __( 'Add €2 fee for users using COD.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '',
				'custom_attributes'	=> array( 'maxlength' => '35' )
			),*/
		);


		// Business Hours for DHL Pickkup Request
		$this->form_fields += array(
			'dhl_business_hours'           => array(
				'title'           => __( 'Business Hours (for DHL Pickup Request)', 'dhl-for-woocommerce' ),
				'type'            => 'title',
				'description'     => __( 'The business hours available for DHL Pickup.', 'dhl-for-woocommerce' ),
			),
			'dhl_business_hours_1_start'           => array(
				'title'           => __( 'From: ', 'dhl-for-woocommerce' ),
				'type'            => 'time',
				'default'       	  => '08:00',
			),
			'dhl_business_hours_1_end'           => array(
				'title'           => __( 'To: ', 'dhl-for-woocommerce' ),
				'type'            => 'time',
				'default'       	  => '17:00',
			),
			'dhl_business_hours_2_start'           => array(
				'title'           => __( '(Additional Business Hours) From: ', 'dhl-for-woocommerce' ),
				'type'            => 'time',
				'description'     => __( 'Optional, if additional business hours are needed.', 'dhl-for-woocommerce' ),
				'default'       	  => '',
			),
			'dhl_business_hours_2_end'           => array(
				'title'           => __( '(Additional Business Hours) To: ', 'dhl-for-woocommerce' ),
				'type'            => 'time',
				'description'     => __( 'Optional, if additional business hours are needed.', 'dhl-for-woocommerce' ),
				'default'       	  => '',
			),
		);


	}

	// Set specific field options after initialization
	public function after_init_set_field_options ( $fields ) {
		if ( isset( $fields['dhl_payment_gateway'] ) || isset( $fields['dhl_cod_payment_methods'] ) ) {
			$payment_gateway_titles = [];
			if ( WC()->payment_gateways ) {
				$wc_payment_gateways = WC()->payment_gateways->payment_gateways();
				foreach ($wc_payment_gateways as $gatekey => $gateway) {
					$payment_gateway_titles[ $gatekey ] = $gateway->get_method_title();
				}
				if ( isset( $fields['dhl_payment_gateway'] ) ) {
					$fields['dhl_payment_gateway']['options'] = $payment_gateway_titles;
				}
				if ( isset( $fields['dhl_cod_payment_methods'] ) ) {
					$fields['dhl_cod_payment_methods']['options'] = $payment_gateway_titles;
				}
			}
		}
		return $fields;
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
				'title'           => __( 'Method Title', 'dhl-for-woocommerce' ),
				'type'            => 'text',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'dhl-for-woocommerce' ),
				'default'         => __( 'DHL Paket', 'dhl-for-woocommerce' ),
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

			echo $this->get_message( __('Pickup Account Number: ', 'dhl-for-woocommerce') . $e->getMessage() );
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

			echo $this->get_message( __('Distribution Center: ', 'dhl-for-woocommerce') . $e->getMessage() );
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

					$error_message = __('In order to show the dhl locations on a map, you need to insert a Google API Key. Otherwise, please deactivate the locations.', 'dhl-for-woocommerce');
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
		return $this->validate_location_enabled_field( $key, __( 'Parcelshop', 'dhl-for-woocommerce' ) );
	}

	/**
	 * Validate the Post Office enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_post_office_field( $key ) {
		return $this->validate_location_enabled_field( $key, __( 'Post Office', 'dhl-for-woocommerce' ) );
	}

	/**
	 * Validate the Google Maps enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_display_google_maps_field( $key ) {
		return $this->validate_location_enabled_field( $key, __( 'Google Maps', 'dhl-for-woocommerce' ) );
	}

	/**
	 * Validate the logo enabled field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_add_logo_field( $key ) {

		if ( ! isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
			return 'no';
		}

		// Verify shipper reference set
		$shipper_reference = $_POST[ $this->plugin_id . $this->id . '_dhl_shipper_reference' ];

		if ( empty( $shipper_reference ) ) {

			$error_message = __('In order to use logo, you need to set a shipper reference first.', 'dhl-for-woocommerce');

			echo $this->get_message( $error_message );

			return 'no';
		}

		return 'yes';
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
				$error_message = sprintf( __('In order to show %s, you need to set a Google API Key first.', 'dhl-for-woocommerce'), $location_type );
			}else{
				$error_message = sprintf( __('In order to show %s on a map, you need to set a Google API Key first.', 'dhl-for-woocommerce'), $location_type );
			}

			echo $this->get_message( $error_message );

			return 'no';
		}

		return 'yes';
	}

}

endif;
