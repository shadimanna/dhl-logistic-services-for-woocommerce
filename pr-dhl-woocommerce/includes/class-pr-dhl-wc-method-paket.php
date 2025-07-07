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
			$this->id           = 'pr_dhl_paket';
			$this->instance_id  = absint( $instance_id );
			$this->method_title = esc_html__( 'DHL Paket', 'dhl-for-woocommerce' );

			/* translators: %s: link to request a quote for becoming a DHL business customer */
			$this->method_description = sprintf( esc_html__( 'Below you will find all functions for controlling, preparing and processing your shipment with DHL Paket. Prerequisite is a valid DHL business customer contract. If you are not yet a DHL business customer, you can request a quote %1$shere%2$s.', 'dhl-for-woocommerce' ), '<a href="https://www.dhl.de/dhl-kundewerden?source=woocommerce&cid=c_dhloka_de_woocommerce" target="_blank">', '</a>' );

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

			add_filter( 'woocommerce_settings_api_form_fields_' . $this->id, array( $this, 'after_init_set_field_options' ) );

			if ( API_Utils::is_new_merchant() ) {
				new PR_DHL_WC_Wizard_Paket();
			}
		}

		/**
		 * Get message
		 *
		 * @return string Error
		 */
		private function get_message( $message, $type = 'notice notice-error is-dismissible' ) {

			ob_start();
			?>
		<div class="<?php echo $type; ?>" >
			<p><?php echo $message; ?></p>
		</div>
			<?php
			return ob_get_clean();
		}

		public function excluded_order_statuses() {

			return array(
				'wc-failed',
				'wc-refunded',
				'wc-cancelled',
			);
		}

		public function get_order_statuses() {

			$wc_order_statuses = wc_get_order_statuses();

			foreach ( $this->excluded_order_statuses() as $status ) {
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
			$wc_shipping_methods  = WC()->shipping->get_shipping_methods();
			$wc_shipping_titles   = wp_list_pluck( $wc_shipping_methods, 'method_title', 'id' );
			$order_status_options = array(
				'none' => esc_html__( 'None', 'dhl-for-woocommerce' ),
			);

			$order_status_options = array_merge( $order_status_options, $this->get_order_statuses() );

			$log_path = PR_DHL()->get_log_url();

			$select_dhl_product = array( '0' => esc_html__( '- Select DHL Product -', 'dhl-for-woocommerce' ) );

			$select_dhl_desc_default = array(
				'product_name' => esc_html__( 'Product Name', 'dhl-for-woocommerce' ),
				'product_cat'  => esc_html__( 'Product Categories', 'dhl-for-woocommerce' ),
				'product_tag'  => esc_html__( 'Product Tags', 'dhl-for-woocommerce' ),
			);

			try {
				$dhl_obj                  = PR_DHL()->get_dhl_factory();
				$select_dhl_product_int   = $dhl_obj->get_dhl_products_international();
				$select_dhl_product_dom   = $dhl_obj->get_dhl_products_domestic();
				$select_dhl_visual_age    = $dhl_obj->get_dhl_visual_age();
				$myaccount_pwd_expiration = $dhl_obj->get_dhl_myaccount_pwd_expiration();
			} catch ( Exception $e ) {
				PR_DHL()->log_msg( esc_html__( 'DHL Products not displaying - ', 'dhl-for-woocommerce' ) . esc_html( $e->getMessage() ) );
			}

			$weight_units = get_option( 'woocommerce_weight_unit' );
			if ( $myaccount_pwd_expiration == '7days' ) {
				/* Translators: %s is the URL for the business portal login. */
				$password_expiration_message = sprintf(
					esc_html__( '<p style="color: red;">Your password will expire in less than 7 days, please go to your <a href="%s" target="_blank">business portal</a> and reset your password then click the "Get Account Settings" button below.</p>', 'dhl-for-woocommerce' ),
					esc_url( PR_DHL_PAKET_BUSSINESS_PORTAL_LOGIN )
				);
			} elseif ( $myaccount_pwd_expiration == '30days' ) {
				/* Translators: %s is the URL for the business portal login. */
				$password_expiration_message = sprintf(
					esc_html__( '<p style="color: red;">Your password will expire in less than 30 days, please go to your <a href="%s" target="_blank">business portal</a> and reset your password then click the "Get Account Settings" button below.</p>', 'dhl-for-woocommerce' ),
					esc_url( PR_DHL_PAKET_BUSSINESS_PORTAL_LOGIN )
				);
			} else {
				$password_expiration_message = '';
			}

			$this->form_fields = array(
				'dhl_pickup_api_dist'        => array(
					'title'       => esc_html__( 'Account and API Settings', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'Please configure your shipping parameters and your access towards the DHL Paket APIs by means of authentication.', 'dhl-for-woocommerce' ),
				),
				'dhl_api_user'               => array(
					'title'       => esc_html__( 'Username', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => sprintf(
					/* Translators: %s is the URL for the DHL business customer portal. */
						esc_html__( 'Your username for the DHL business customer portal. Please note the lower case and test your access data in advance at %1$shere%2$s.', 'dhl-for-woocommerce' ),
						'<a href="' . esc_url( PR_DHL_PAKET_BUSSINESS_PORTAL ) . '" target="_blank">',
						'</a>'
					),
					'desc_tip'    => false,
					'default'     => '',
				),
				'dhl_api_pwd'                => array(
					'title'       => esc_html__( 'Password', 'dhl-for-woocommerce' ),
					'type'        => 'password',
					'description' => sprintf(
					                 /* Translators: %s is the URL for the DHL business customer portal. */
						                 esc_html__( 'Your password for the DHL business customer portal. Please note the new assignment of the password to 3 (Standard User) or 12 (System User) months and test your access data in advance at %1$shere%2$s.', 'dhl-for-woocommerce' ),
						                 '<a href="' . esc_url( PR_DHL_PAKET_BUSSINESS_PORTAL ) . '" target="_blank">', // Use esc_url() for security
						                 '</a>'
					                 ) . $password_expiration_message,
					'desc_tip'    => false,
					'default'     => '',
				),
				'dhl_my_account_button'      => array(
					'title'             => PR_DHL_BUTTON_MY_ACCOUNT,
					'type'              => 'button',
					'custom_attributes' => array(
						'onclick' => "dhlMyAccount('#woocommerce_pr_dhl_paket_dhl_my_account_button');",
					),
					'description'       => esc_html__( 'Press the button to read your DHL Business Account settings into the DHL for WooCommerce plugin.', 'dhl-for-woocommerce' ),
					'desc_tip'          => false,
				),
				'dhl_account_num'            => array(
					'title'             => esc_html__( 'Account Number (EKP)', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'description'       => esc_html__( 'Your DHL account number (10 digits - numerical), also called "EKP“. This will be provided by your local DHL sales organization.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'default'           => '',
					'placeholder'       => '1234567890',
					'custom_attributes' => array( 'maxlength' => '10' ),
				),
				'dhl_default_api'            => array(
					'title'       => esc_html__( 'API Protocol', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Select the API protocol to use for creating shipping labels.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => array(
						'soap'     => 'SOAP',
						'rest-api' => 'REST',
					),
					'class'       => 'wc-enhanced-select',
					'default'     => API_Utils::is_new_merchant() ? 'rest-api' : 'soap',
				),
				'dhl_sandbox'                => array(
					'title'       => esc_html__( 'Sandbox Mode', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Enable Sandbox Mode', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want to test the plug-in installation against the DHL Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_api_sandbox_user'       => array(
					'title'       => esc_html__( 'Sandbox Username', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => sprintf(
					/* Translators: %s is the URL for creating a DHL developer portal account. */
						esc_html__( 'Your sandbox username is the same as for the DHL developer portal. You can create an account %1$shere%2$s.', 'dhl-for-woocommerce' ),
						'<a href="' . esc_url( PR_DHL_PAKET_DEVELOPER_PORTAL ) . '" target="_blank">',
						'</a>'
					),
					'desc_tip'    => false,
					'default'     => '',
				),
				'dhl_api_sandbox_pwd'        => array(
					'title'       => esc_html__( 'Sandbox Password', 'dhl-for-woocommerce' ),
					'type'        => 'password',
					'description' => sprintf(
					/* Translators: %s is the URL for creating a DHL developer portal account. */
						esc_html__( 'Your sandbox password is the same as for the DHL developer portal. You can create an account %1$shere%2$s.', 'dhl-for-woocommerce' ),
						'<a href="' . esc_url( PR_DHL_PAKET_DEVELOPER_PORTAL ) . '" target="_blank">',
						'</a>'
					),
					'desc_tip'    => false,
					'default'     => '',
				),
				'dhl_debug'                  => array(
					'title'       => esc_html__( 'Debug Log', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Enable logging', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => sprintf(
					/* Translators: %s is the URL to the log file. */
						esc_html__( 'A log file containing the communication to the DHL server will be maintained if this option is checked. This can be used in case of technical issues and can be found %1$shere%2$s.', 'dhl-for-woocommerce' ),
						'<a href="' . esc_url( $log_path ) . '" target="_blank">',
						'</a>'
					),
				),
				'dhl_participation_title'    => array(
					'title'       => esc_html__( 'DHL Products and Participation Number', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'For each DHL product that you would like to use, please enter your participation number here. The participation number consists of the last two characters of the respective accounting number, which you will find in your DHL contract data (for example, 01).', 'dhl-for-woocommerce' ),
				),
				'dhl_my_account_button_prod' => array(
					'title'             => PR_DHL_BUTTON_MY_ACCOUNT,
					'type'              => 'button',
					'custom_attributes' => array(
						'onclick' => "dhlMyAccount('#woocommerce_pr_dhl_paket_dhl_my_account_button_prod');",
					),
					'description'       => esc_html__( 'Press the button to read your DHL Business Account settings into the DHL for WooCommerce plugin.', 'dhl-for-woocommerce' ),
					'desc_tip'          => false,
				),
			);

			// $booking_text_array = unserialize(get_option('booking_text_option'));
			$booking_text_array = $dhl_obj->get_dhl_booking_text();

			foreach ( $select_dhl_product_dom as $key => $value ) {
				$description = '';

				// Check if the product key exists in the booking_text array
				if ( isset( $booking_text_array[ $key ] ) ) {
					$description = $booking_text_array[ $key ];
				}

				$this->form_fields += array(
					'dhl_participation_' . $key => array(
						'title'             => $value,
						'type'              => 'text',
						'placeholder'       => '',
						'custom_attributes' => array( 'maxlength' => '2' ),
						'description'       => $description, // Set the description
					),
				);
			}

			foreach ( $select_dhl_product_int as $key => $value ) {
				$description = '';

				// Check if the product key exists in the booking_text array
				if ( isset( $booking_text_array[ $key ] ) ) {
					$description = $booking_text_array[ $key ];
				}

				$this->form_fields += array(
					'dhl_participation_' . $key => array(
						'title'             => $value,
						'type'              => 'text',
						'placeholder'       => '',
						'custom_attributes' => array( 'maxlength' => '2' ),
						'description'       => $description, // Set the description
					),
				);
			}

			$this->form_fields += array(
				'dhl_participation_return'          => array(
					'title'             => esc_html__( 'DHL Retoure', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'placeholder'       => '',
					'custom_attributes' => array( 'maxlength' => '2' ),
				),
				'dhl_general'                       => array(
					'title'       => esc_html__( 'Shipping Label Settings', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					/* translators: %1$s: link to upload logo; %2$s: closing tag for link */
					'description' => sprintf( esc_html__( 'Would you like to customize the DHL shipment notification? You can now add your online shop’s name and logo and we will display it in the DHL shipment notification. To upload your logo please use the following %1$slink%2$s.', 'dhl-for-woocommerce' ), '<a href="' . esc_attr( PR_DHL_PAKET_NOTIFICATION_EMAIL ) . '" target = "_blank">', '</a>' ),
				),
				'dhl_default_product_dom'           => array(
					'title'       => esc_html__( 'Domestic Default Service', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Please select your default DHL Paket shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => $select_dhl_product_dom,
					'class'       => 'wc-enhanced-select',
				),
				'dhl_default_product_int'           => array(
					'title'       => esc_html__( 'International Default Service', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Please select your default DHL Paket shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => $select_dhl_product_int,
					'class'       => 'wc-enhanced-select',
				),
				'dhl_email_notification'            => array(
					'title'       => esc_html__( 'Send Customer Email', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Please select whether to send the customer\'s email to DHL or not. "Customer Confirmation" displays a confirmation on the checkout page and "Confirmed via terms & condition" assumes confirmation via the website terms & conditions.', 'dhl-for-woocommerce' ),
					'options'     => array(
						'no'        => esc_html__( 'Do not send', 'dhl-for-woocommerce' ),
						'yes'       => esc_html__( 'Customer confirmation', 'dhl-for-woocommerce' ),
						'sendviatc' => esc_html__( 'Confirmed via terms & condition', 'dhl-for-woocommerce' ),
					),
					'default'     => 'sendviatc',
					'desc_tip'    => true,
				),
				'dhl_phone_notification'            => array(
					'title'       => esc_html__( 'Send Customer Phone', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Please select whether to send the customer\'s phone to DHL or not. "Confirmed via terms & condition" assumes confirmation via the website terms & conditions.', 'dhl-for-woocommerce' ),
					'options'     => array(
						'no'        => esc_html__( 'Do not send', 'dhl-for-woocommerce' ),
						'sendviatc' => esc_html__( 'Confirmed via terms & condition', 'dhl-for-woocommerce' ),
					),
					'default'     => 'sendviatc',
					'desc_tip'    => true,
				),
				'dhl_default_age_visual'            => array(
					'title'       => esc_html__( 'Visual Age Check default', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'options'     => $select_dhl_visual_age,
					'description' => esc_html__( 'Please, tick here if you want the "Visual Age Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'class'       => 'wc-enhanced-select',
				),
				'dhl_default_additional_insurance'  => array(
					'title'       => esc_html__( 'Additional Insurance default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Additional Insurance" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_no_neighbor'           => array(
					'title'       => esc_html__( 'No Neighbor Delivery default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "No Neighbor Delivery" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_named_person'          => array(
					'title'       => esc_html__( 'Named Person Only default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Named Person Only" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_premium'               => array(
					'title'       => esc_html__( 'Premium default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Premium" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_bulky_goods'           => array(
					'title'       => esc_html__( 'Bulky Goods default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Bulky Goods" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_identcheck'            => array(
					'title'       => esc_html__( 'Ident Check default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_signature_service'     => array(
					'title'       => esc_html__( 'Signed for by recipient default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Signed for by recipient" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_identcheck_age'        => array(
					'title'       => esc_html__( 'Ident Check Age default', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => '',
					'options'     => $select_dhl_visual_age,
					'description' => esc_html__( 'Please, tick here if you want the "Ident Check" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_endorsement'           => array(
					'title'       => esc_html__( 'Endorsement type', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'default'     => '',
					'options'     => array(
						'IMMEDIATE'   => esc_html__( 'Sending back to sender', 'dhl-for-woocommerce' ),
						'ABANDONMENT' => esc_html__( 'Abandonment of parcel', 'dhl-for-woocommerce' ),
					),
					'description' => esc_html__( 'Please, tick here if you want the "Endorsement value" to be selected in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_is_codeable'           => array(
					'title'       => esc_html__( 'Print Only If Codeable default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Print Only If Codeable" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_routing'               => array(
					'title'       => esc_html__( 'Parcel Outlet Routing default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Parcel Outlet Routing" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_default_go_green_plus'         => array(
					'title'       => esc_html__( 'GoGreen Plus default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Check to request GoGreen Plus for every domestic shipment unless you turn it off per order.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_add_weight_type'              => array(
					'title'       => esc_html__( 'Additional Weight Type', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Select whether to add an absolute weight amount or percentage amount to the total product weight.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => array(
						'absolute'   => 'Absolute',
						'percentage' => 'Percentage',
					),
					'class'       => 'wc-enhanced-select',
				),
				'dhl_add_weight'                    => array(
					'title'       => sprintf(
					/* Translators: %s represents the weight unit (e.g., kg, lbs, %). */
						esc_html__( 'Additional Weight (%s or %%)', 'dhl-for-woocommerce' ),
						esc_html( $weight_units ) // Escaped for security
					),
					'type'        => 'text',
					'description' => esc_html__( 'Add extra weight in addition to the products.  Either an absolute amount or percentage (e.g. 10 for 10%).', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
					'placeholder' => '',
					'class'       => 'wc_input_decimal',
				),
				'dhl_desc_default'                  => array(
					'title'       => esc_html__( 'Package Description', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Prefill the customs package description with one of the options for cross-border packages.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => 'product_cat',
					'options'     => $select_dhl_desc_default,
					'class'       => 'wc-enhanced-select',
				),
				'dhl_label_format'                  => array(
					'title'       => esc_html__( 'Label Format', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'description' => esc_html__( 'Select one of the formats to generate the shipping label in.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => array(
						'A4'             => 'A4',
						'910-300-700'    => 'Laser printer 105 x 205 mm',
						'910-300-700-oZ' => 'Laser printer 105 x 205 mm (no info)',
						'910-300-600'    => 'Thermo printer 103 x 199 mm',
						'910-300-610'    => 'Thermo printer 103 x 202 mm',
						'910-300-710'    => 'Laser printer 105 x 208 mm',
						'910-300-410'    => 'Laser printer 103 x 150 mm',
						'910-300-300'    => 'Laser printer 105 x 148 mm',
						'910-300-300-oZ' => 'Laser printer 105 x 148 mm (without additional labels)',
						'100x70mm'       => '100 x 70 mm (only for Warenpost & Kleinpaket)',
					),
					'default'     => '910-300-700',
					'class'       => 'wc-enhanced-select',
				),
				'dhl_add_logo'                      => array(
					'title'       => esc_html__( 'Logo', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add Logo', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'The logo will be added from your DHL dashboard settings.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_shipper_reference'             => array(
					'title'       => sprintf( esc_html__( 'Shipper Reference', 'dhl-for-woocommerce' ), esc_html( $weight_units ) ),
					'type'        => 'text',
					'description' => esc_html__( 'Add shipper reference.', 'dhl-for-woocommerce' ),
					'desc_tip'    => false,
					'default'     => '',
					'placeholder' => '',
				),
				'dhl_tracking_note'                 => array(
					'title'       => esc_html__( 'Tracking Note', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Make Private', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here to not send an email to the customer when the tracking number is added to the order.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_tracking_note_txt'             => array(
					'title'       => esc_html__( 'Tracking Text', 'dhl-for-woocommerce' ),
					'type'        => 'textarea',
					'description' => esc_html__( 'Set the custom text when adding the tracking number to the order notes or completed email. {tracking-link} is where the tracking number will be set.', 'dhl-for-woocommerce' ),
					'desc_tip'    => false,
					'default'     => esc_html__( 'DHL Tracking Number: {tracking-link}', 'dhl-for-woocommerce' ),
				),
				'dhl_add_tracking_info_completed'   => array(
					'title'       => esc_html__( 'Tracking Email', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Add tracking text in completed email', 'dhl-for-woocommerce' ),
					'description' => esc_html__( 'Please, tick here to add tracking text when completed email is sent.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => 'no',
					'class'       => '',
				),
				'dhl_tracking_url_language'         => array(
					'title'       => esc_html__( 'Tracking URL Language', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'label'       => esc_html__( 'Select the tracking link language.', 'dhl-for-woocommerce' ),
					'description' => esc_html__( 'Select language of the tracking link page.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => array(
						'de' => 'German',
						'en' => 'English',
					),
					'class'       => 'wc-enhanced-select',
					'default'     => 'de',
				),
				'dhl_create_label_on_status'        => array(
					'title'       => esc_html__( 'Create Label on Status', 'dhl-for-woocommerce' ),
					'type'        => 'select',
					'label'       => esc_html__( 'Create label on specific status.', 'dhl-for-woocommerce' ),
					'description' => esc_html__( 'Select the order status.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'options'     => $order_status_options,
					'class'       => 'wc-enhanced-select',
					'default'     => 'no',
				),
				'dhl_change_order_status_completed' => array(
					'title'       => esc_html__( 'Order Status', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Change to Completed', 'dhl-for-woocommerce' ),
					'description' => esc_html__( 'Please, tick here to change the order status when label is generated.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => 'no',
					'class'       => '',
				),
			);

			$base_country_code = PR_DHL()->get_base_country();
			// Preferred options for Germany only
			// IF USING PREFERRED OPTIONS AND COD IS ENABLED DISPALY A WARNING MESSAGE OR DON'T ALLOW IT TO BE USED?
			if ( $base_country_code == 'DE' ) {
				$this->form_fields += array(
					'dhl_preferred'               => array(
						'title'       => esc_html__( 'Preferred Service', 'dhl-for-woocommerce' ),
						'type'        => 'title',
						'description' => esc_html__( 'Preferred service options.', 'dhl-for-woocommerce' ),
					),
					'dhl_closest_drop_point'      => array(
						'title'       => esc_html__( 'Closest Drop Point', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Closest Drop Point', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display a front-end option for the user to select delivery option (Home address or CDP delivery).', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_preferred_day'           => array(
						'title'       => esc_html__( 'Delivery Day', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Delivery Day', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display a front-end option for the user to select their preferred day of delivery.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_preferred_day_cost'      => array(
						'title'       => esc_html__( 'Delivery Day Price', 'dhl-for-woocommerce' ),
						'type'        => 'text',
						'description' => esc_html__( 'Insert gross value as surcharge for the preferred day. Insert 0 to offer service for free.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'default'     => '1.2',
						'class'       => 'wc_input_decimal', // adds JS to validate input is in price format
					),
					'dhl_preferred_day_cutoff'    => array(
						'title'       => esc_html__( 'Cut Off Time', 'dhl-for-woocommerce' ),
						'type'        => 'time',
						'description' => esc_html__( 'The cut-off time is the latest possible order time up to which the minimum preferred day (day of order + 2 working days) can be guaranteed. As soon as the time is exceeded, the earliest preferred day displayed in the frontend will be shifted to one day later (day of order + 3 working days).', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'default'     => '12:00',
					),
					'dhl_preferred_exclusion_mon' => array(
						'title'       => esc_html__( 'Exclusion of transfer days', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Monday', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Exclude days to transfer packages to DHL.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_preferred_exclusion_tue' => array(
						'type'  => 'checkbox',
						'label' => esc_html__( 'Tuesday', 'dhl-for-woocommerce' ),
					),
					'dhl_preferred_exclusion_wed' => array(
						'type'  => 'checkbox',
						'label' => esc_html__( 'Wednesday', 'dhl-for-woocommerce' ),
					),
					'dhl_preferred_exclusion_thu' => array(
						'type'  => 'checkbox',
						'label' => esc_html__( 'Thursday', 'dhl-for-woocommerce' ),
					),
					'dhl_preferred_exclusion_fri' => array(
						'type'  => 'checkbox',
						'label' => esc_html__( 'Friday', 'dhl-for-woocommerce' ),
					),
					'dhl_preferred_exclusion_sat' => array(
						'type'  => 'checkbox',
						'label' => esc_html__( 'Saturday', 'dhl-for-woocommerce' ),
					),
					'dhl_preferred_location'      => array(
						'title'       => esc_html__( 'Preferred Location', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Preferred Location', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display a front-end option for the user to select their preferred location.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_preferred_neighbour'     => array(
						'title'       => esc_html__( 'Preferred Neighbour', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Preferred Neighbour', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display a front-end option for the user to select their preferred neighbour.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_shipping_methods'        => array(
						'title'       => esc_html__( 'Shipping Methods', 'dhl-for-woocommerce' ),
						'type'        => 'multiselect',
						'description' => esc_html__( 'Select the Shipping Methods to display the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'options'     => $wc_shipping_titles,
						'class'       => 'wc-enhanced-select',
					),
					'dhl_payment_gateway'         => array(
						'title'       => esc_html__( 'Exclude Payment Gateways', 'dhl-for-woocommerce' ),
						'type'        => 'multiselect',
						'default'     => 'cod',
						'description' => esc_html__( 'Select the Payment Gateways to hide the enabled DHL Paket preferred services and Location Finder below. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'options'     => array(),
						'class'       => 'wc-enhanced-select',
					),
					'dhl_cod_payment_methods'     => array(
						'title'       => esc_html__( 'COD Payment Gateways', 'dhl-for-woocommerce' ),
						'type'        => 'multiselect',
						'default'     => 'cod',
						'description' => esc_html__( 'Select the Payment Gateways to use with DHL COD services. You can press "ctrl" to select multiple options or click on a selected option to deselect it.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'options'     => array(),
						'class'       => 'wc-enhanced-select',
					),
					'dhl_parcel_finder'           => array(
						'title'       => esc_html__( 'Location Finder', 'dhl-for-woocommerce' ),
						'type'        => 'title',
						'description' => esc_html__( 'Please define the parameters for the display of dhl locations in the shop frontend.', 'dhl-for-woocommerce' ),
					),
					'dhl_display_packstation'     => array(
						'title'       => esc_html__( 'Packstation', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Packstation', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display Packstation locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_display_parcelshop'      => array(
						'title'       => esc_html__( 'Parcelshop', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Parcelshop', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display Parcelshop locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_display_post_office'     => array(
						'title'       => esc_html__( 'Post Office', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => esc_html__( 'Enable Post Office', 'dhl-for-woocommerce' ),
						'description' => esc_html__( 'Enabling this will display Post Office locations on Google Maps when searching for drop off locations on the front-end.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_display_google_maps'     => array(
						'title'       => __( 'Map', 'dhl-for-woocommerce' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable Map', 'dhl-for-woocommerce' ),
						'description' => __( 'Enabling this will display the Map on the front-end.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
					),
					'dhl_map_type'                => array(
						'title'       => __( 'Map type', 'dhl-for-woocommerce' ),
						'type'        => 'select',
						'description' => __( 'Select the map type to show parcels shops.', 'dhl-for-woocommerce' ),
						'desc_tip'    => true,
						'options'     => array( 'gmaps' => 'Google Maps', 'osm' => 'Open Street Map' ),
						'class'       => 'wc-enhanced-select',
						'default'     => 'gmaps',
					),
					'dhl_parcel_limit'            => array(
						'title'             => esc_html__( 'Limit Results', 'dhl-for-woocommerce' ),
						'type'              => 'number',
						'description'       => esc_html__( 'Limit displayed results, from 1 to at most 50.', 'dhl-for-woocommerce' ),
						'desc_tip'          => true,
						'class'             => '',
						'default'           => '20',
						'custom_attributes' => array(
							'min' => '1',
							'max' => '50',
						),
					),
					'dhl_google_maps_api_key'     => array(
						'title'       => esc_html__( 'API Key', 'dhl-for-woocommerce' ),
						'type'        => 'text',
						/* Translators: %s represents a link to obtain a Google Maps API key. */
						'description' => wp_kses(
							sprintf(
								esc_html__( 'The Google Maps API Key is necessary to display the DHL Locations on a Google map.%1$sGet a free Google Maps API key %2$shere%3$s.', 'dhl-for-woocommerce' ),
								'<br/>',
								'<a href="' . esc_url( 'https://developers.google.com/maps/documentation/javascript/get-api-key' ) . '" target="_blank">',
								'</a>'
							),
							array(
								'br' => array(),
								'a'  => array(
									'href'   => array(),
									'target' => array(),
								),
							)
						),
						'desc_tip'    => false,
						'class'       => '',
					),
				);
			}

			$this->form_fields += array(
				'dhl_shipper'                        => array(
					'title'       => esc_html__( 'Shipper Address / Pickup Request Address', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'Enter Shipper Address. This address is also used for Pickup Requests.<br/>Note: For pickup requests to be accepted, this address must match a pickup address saved to your DHL Portal.', 'dhl-for-woocommerce' ),
				),
				'dhl_shipper_name'                   => array(
					'title'       => esc_html__( 'Name', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper Name.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_company'                => array(
					'title'       => esc_html__( 'Company', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper Company.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_address'                => array(
					'title'       => esc_html__( 'Street Address', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper Street Address.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_address_no'             => array(
					'title'       => esc_html__( 'Street Address Number', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper Street Address Number.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_address_city'           => array(
					'title'       => esc_html__( 'City', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper City.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_address_state'          => array(
					'title'       => esc_html__( 'State', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper County.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_address_zip'            => array(
					'title'       => esc_html__( 'Postcode', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Shipper Postcode.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_phone'                  => array(
					'title'       => esc_html__( 'Phone Number', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Phone Number.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_shipper_email'                  => array(
					'title'       => esc_html__( 'Email', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Email.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return'                         => array(
					'title'       => esc_html__( 'Return Address', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'Enter Return Address below.', 'dhl-for-woocommerce' ),
				),
				'dhl_default_return_address_enabled' => array(
					'title'       => esc_html__( 'Create Return Label default', 'dhl-for-woocommerce' ),
					'type'        => 'checkbox',
					'label'       => esc_html__( 'Checked', 'dhl-for-woocommerce' ),
					'default'     => 'no',
					'description' => esc_html__( 'Please, tick here if you want the "Create Return Label" option to be checked in the "Edit Order" before printing a label.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
				'dhl_return_name'                    => array(
					'title'       => esc_html__( 'Name', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return Name.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_company'                 => array(
					'title'       => esc_html__( 'Company', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return Company.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_address'                 => array(
					'title'       => esc_html__( 'Street Address', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return Street Address.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_address_no'              => array(
					'title'       => esc_html__( 'Street Address Number', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return Street Address Number.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_address_city'            => array(
					'title'       => esc_html__( 'City', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return City.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_address_state'           => array(
					'title'       => esc_html__( 'State', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return County.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_address_zip'             => array(
					'title'       => esc_html__( 'Postcode', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Return Postcode.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_phone'                   => array(
					'title'       => esc_html__( 'Phone Number', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Phone Number.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_return_email'                   => array(
					'title'       => esc_html__( 'Email', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'Enter Email.', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
					'default'     => '',
				),
				'dhl_bank'                           => array(
					'title'       => esc_html__( 'Bank Details', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'Enter your bank details needed for services that use COD.', 'dhl-for-woocommerce' ),
				),
				'dhl_bank_holder'                    => array(
					'title' => esc_html__( 'Account Owner', 'dhl-for-woocommerce' ),
					'type'  => 'text',
				),
				'dhl_bank_name'                      => array(
					'title' => esc_html__( 'Bank Name', 'dhl-for-woocommerce' ),
					'type'  => 'text',
				),
				'dhl_bank_iban'                      => array(
					'title' => esc_html__( 'IBAN', 'dhl-for-woocommerce' ),
					'type'  => 'text',
				),
				'dhl_bank_bic'                       => array(
					'title'   => esc_html__( 'BIC', 'dhl-for-woocommerce' ),
					'type'    => 'text',
					'default' => '',
				),
				'dhl_bank_ref'                       => array(
					'title'             => esc_html__( 'Payment Reference', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'custom_attributes' => array( 'maxlength' => '35' ),
					/* translators: %1$s: order ID placeholder, %2$s: customer email placeholder */
					'description'       => sprintf( esc_html__( 'Use "%1$s" to send the order id as a bank reference and "%2$s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ), '{order_id}', '{email}' ),
					'desc_tip'          => true,
					'default'           => '{order_id}',
				),
				'dhl_bank_ref_2'                     => array(
					'title'             => esc_html__( 'Payment Reference 2', 'dhl-for-woocommerce' ),
					'type'              => 'text',
					'custom_attributes' => array( 'maxlength' => '35' ),
					/* translators: %1$s: order ID placeholder, %2$s: customer email placeholder */
					'description'       => sprintf( esc_html__( 'Use "%1$s" to send the order id as a bank reference and "%2$s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ), '{order_id}', '{email}' ),
					'desc_tip'          => true,
					'default'           => '{email}',
				),
				/*
				'dhl_cod_fee' => array(
					'title'             => esc_html__( 'Add COD Fee', 'dhl-for-woocommerce' ),
					'type'              => 'checkbox',
					'description'       => esc_html__( 'Add €2 fee for users using COD.', 'dhl-for-woocommerce' ),
					'desc_tip'          => true,
					'default'           => '',
					'custom_attributes' => array( 'maxlength' => '35' )
				),*/
			);

			// Business Hours for DHL Pickkup Request
			$this->form_fields += array(
				'dhl_business_hours'         => array(
					'title'       => esc_html__( 'Business Hours (for DHL Pickup Request)', 'dhl-for-woocommerce' ),
					'type'        => 'title',
					'description' => esc_html__( 'The business hours available for DHL Pickup.', 'dhl-for-woocommerce' ),
				),
				'dhl_business_hours_1_start' => array(
					'title'   => esc_html__( 'From: ', 'dhl-for-woocommerce' ),
					'type'    => 'time',
					'default' => '08:00',
				),
				'dhl_business_hours_1_end'   => array(
					'title'   => esc_html__( 'To: ', 'dhl-for-woocommerce' ),
					'type'    => 'time',
					'default' => '17:00',
				),
				'dhl_business_hours_2_start' => array(
					'title'       => esc_html__( '(Additional Business Hours) From: ', 'dhl-for-woocommerce' ),
					'type'        => 'time',
					'description' => esc_html__( 'Optional, if additional business hours are needed.', 'dhl-for-woocommerce' ),
					'default'     => '',
				),
				'dhl_business_hours_2_end'   => array(
					'title'       => esc_html__( '(Additional Business Hours) To: ', 'dhl-for-woocommerce' ),
					'type'        => 'time',
					'description' => esc_html__( 'Optional, if additional business hours are needed.', 'dhl-for-woocommerce' ),
					'default'     => '',
				),
			);
		}

		// Set specific field options after initialization
		public function after_init_set_field_options( $fields ) {
			if ( isset( $fields['dhl_payment_gateway'] ) || isset( $fields['dhl_cod_payment_methods'] ) ) {
				$payment_gateway_titles = array();
				if ( WC()->payment_gateways ) {
					$wc_payment_gateways = WC()->payment_gateways->payment_gateways();
					foreach ( $wc_payment_gateways as $gatekey => $gateway ) {
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
				<label for="<?php echo esc_attr( $field ); ?>"><?php echo esc_html( $data['title'] ); ?></label>
				<?php echo $this->get_tooltip_html( $data ); ?>
			</th>
			<td class="forminp">
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $data['title'] ); ?></span></legend>
					<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button" name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo esc_html( $data['title'] ); ?></button>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
			<?php
			return ob_get_clean();
		}

		public function init_instance_form_fields() {
			$this->instance_form_fields = array(
				'title' => array(
					'title'       => esc_html__( 'Method Title', 'dhl-for-woocommerce' ),
					'type'        => 'text',
					'description' => esc_html__( 'This controls the title which the user sees during checkout.', 'dhl-for-woocommerce' ),
					'default'     => esc_html__( 'DHL Paket', 'dhl-for-woocommerce' ),
					'desc_tip'    => true,
				),
			);
		}

		/**
		 * Validate the API key
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_pickup_field( $key ) {
			$value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

			try {
				$dhl_obj = PR_DHL()->get_dhl_factory();
				$dhl_obj->dhl_validate_field( 'pickup', $value );

			} catch ( Exception $e ) {
				echo $this->get_message( esc_html__( 'Pickup Account Number: ', 'dhl-for-woocommerce' ) . esc_html( $e->getMessage() ) );
				throw $e;
			}

			return $value;
		}

		/**
		 * Validate the API secret
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_distribution_field( $key ) {
			$value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

			try {

				$dhl_obj = PR_DHL()->get_dhl_factory();
				$dhl_obj->dhl_validate_field( 'distribution', $value );

			} catch ( Exception $e ) {

				echo $this->get_message( esc_html__( 'Distribution Center: ', 'dhl-for-woocommerce' ) . esc_html( $e->getMessage() ) );
				throw $e;
			}

			return $value;
		}

		/**
		 * Validate the Google API Key
		 *
		 * @see validate_settings_fields()
		 */
		/*
		public function validate_dhl_google_maps_api_key_field( $key ) {
		$google_maps_api = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

		if ( empty( $google_maps_api ) ) {

			if ( isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_packstation' ] ) ||
				isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_parcelshop' ] ) ||
				isset( $_POST[ $this->plugin_id . $this->id . '_dhl_display_post_office' ] ) ) {

					$error_message = esc_html__( 'In order to show the dhl locations on a map, you need to insert a Google API Key. Otherwise, please deactivate the locations.', 'dhl-for-woocommerce' );
					echo $this->get_message( $error_message );
					throw new Exception( $error_message );

			}
		}

		return $google_maps_api;
		}*/

		/**
		 * Validate the Packstation enabled field
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_display_packstation_field( $key ) {
			return $this->validate_location_enabled_field( $key, PR_DHL_PACKSTATION );
		}

		/**
		 * Validate the Parcelshop enabled field
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_display_parcelshop_field( $key ) {
			return $this->validate_location_enabled_field( $key, esc_html__( 'Parcelshop', 'dhl-for-woocommerce' ) );
		}

		/**
		 * Validate the Post Office enabled field
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_display_post_office_field( $key ) {
			return $this->validate_location_enabled_field( $key, esc_html__( 'Post Office', 'dhl-for-woocommerce' ) );
		}

		/**
		 * Validate the Google Maps enabled field
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_display_google_maps_field( $key ) {
			return $this->validate_location_enabled_field( $key, esc_html__( 'Google Maps', 'dhl-for-woocommerce' ) );
		}

		/**
		 * Validate the logo enabled field
		 *
		 * @see validate_settings_fields()
		 */
		public function validate_dhl_add_logo_field( $key ) {

			if ( ! isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
				return 'no';
			}

			// Verify shipper reference set
			$shipper_reference = $_POST[ $this->plugin_id . $this->id . '_dhl_shipper_reference' ];

			if ( empty( $shipper_reference ) ) {
				$error_message = esc_html__( 'In order to use logo, you need to set a shipper reference first.', 'dhl-for-woocommerce' );
				echo $this->get_message( $error_message );
				return 'no';
			}

			return 'yes';
		}

		/**
		 * Validate the any location enabled field
		 *
		 * @see validate_settings_fields()
		 * @return string 'no' or 'yes' (not exception) to 'disable' locations as opposed to NOT save them
		 */
		protected function validate_location_enabled_field( $key, $location_type ) {
			if ( ! isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
				return 'no';
			}

			// Verify whether Google API key set
			$google_maps_api_key = $_POST[ $this->plugin_id . $this->id . '_dhl_google_maps_api_key' ];

			// If not return 'no'
			if ( empty( $google_maps_api_key ) ) {

				if ( $key == 'dhl_display_google_maps' ) {
					/* translators: %s: type of location (e.g., DHL locations) */
					$error_message = sprintf( esc_html__( 'In order to show %s, you need to set a Google API Key first.', 'dhl-for-woocommerce' ), $location_type );
				} else {
					/* translators: %s: type of location (e.g., DHL locations on a map) */
					$error_message = sprintf( esc_html__( 'In order to show %s on a map, you need to set a Google API Key first.', 'dhl-for-woocommerce' ), $location_type );
				}

				echo $this->get_message( $error_message );

				return 'no';
			}

			return 'yes';
		}
	}

endif;
