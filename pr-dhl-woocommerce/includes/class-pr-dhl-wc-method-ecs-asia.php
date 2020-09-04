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

if ( ! class_exists( 'PR_DHL_WC_Method_eCS_Asia' ) ) :

class PR_DHL_WC_Method_eCS_Asia extends WC_Shipping_Method {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id = 'pr_dhl_ecs_asia';
		$this->instance_id = absint( $instance_id );
		$this->method_title = __( 'DHL eCS Asia', 'pr-shipping-dhl' );
		$this->method_description = sprintf( __( 'To start creating DHL eCommerce shipping labels and return back a DHL Tracking number to your customers, please fill in your user credentials as shown in your contracts provided by DHL. Not yet a customer? Please get a quote %shere%s or find out more on how to set up this plugin and get some more support %shere%s.', 'pr-shipping-dhl' ), '<a href="https://www.logistics.dhl/global-en/home/our-divisions/ecommerce/integration/contact-ecommerce-integration-get-a-quote.html?cid=referrer_3pv-signup_woocommerce_ecommerce-integration&SFL=v_signup-woocommerce" target="_blank">', '</a>', '<a href="https://www.logistics.dhl/global-en/home/our-divisions/ecommerce/integration/integration-channels/third-party-solutions/woocommerce.html?cid=referrer_docu_woocommerce_ecommerce-integration&SFL=v_woocommerce" target="_blank">', '</a>' );

		$this->init();
	}

	/**
	 * init function.
	 */
	private function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
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

		$log_path = PR_DHL()->get_log_url();

		$select_dhl_product = array( '0' => __( '- Select DHL Product -', 'pr-shipping-dhl' ) );

		$select_dhl_desc_default = array( 
				'product_cat' => __('Product Categories', 'pr-shipping-dhl'), 
				'product_tag' => __('Product Tags', 'pr-shipping-dhl'), 
				'product_name' => __('Product Name', 'pr-shipping-dhl'), 
				'product_export' => __('Product Export Description', 'pr-shipping-dhl')
		);

		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$select_dhl_product_int = $dhl_obj->get_dhl_products_international();
			$select_dhl_product_dom = $dhl_obj->get_dhl_products_domestic();
			$select_dhl_duties = $dhl_obj->get_dhl_duties();

		} catch (Exception $e) {
			PR_DHL()->log_msg( __('DHL Products not displaying - ', 'pr-shipping-dhl') . $e->getMessage() );
		}

		$weight_units = get_option( 'woocommerce_weight_unit' );

		$this->form_fields = array(
			'dhl_pickup_dist'     => array(
				'title'           => __( 'Account', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please enter your account information underneath.', 'pr-shipping-dhl' ),
				'class'			  => '',
			),
			'dhl_pickup_id' => array(
				'title'             => __( 'Pickup Account ID', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'The "Pickup Account" id will be provided by your local DHL sales organization and tells us where to pick up your shipments.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '0000500000'
			),
            'dhl_soldto_id' => array(
				'title'             => __( 'Soldto Account ID', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'The "Soldto Account" id will be provided by your local DHL sales organization and tells us where to pick up your shipments.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '0000500000'
			),
			'dhl_label_title'     => array(
				'title'           => __( 'Label', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your label parameters underneath.', 'pr-shipping-dhl' ),
				'class'			  => '',
			),
			'dhl_default_product_int' => array(
				'title'             => __( 'International Default Service', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL eCommerce shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_int,
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_default_product_dom' => array(
				'title'             => __( 'Domestic Default Service', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Please select your default DHL eCommerce shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_product_dom,
				'class'				=> 'wc-enhanced-select'
			),
            'dhl_prefix' => array(
                'title'             => __( 'Customer Prefix', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'The package prefix is added to identify the package is coming from your shop. This value is limited to 5 charaters.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => '',
                'placeholder'		=> '',
                'custom_attributes'	=> array( 'maxlength' => '5' )
            ),
			'dhl_desc_default' => array(
				'title'             => __( 'Package Description', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Prefill the package description with one of the options.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_desc_default,
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_label_format' => array(
				'title'             => __( 'Label Format', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select one of the formats to generate the shipping label in.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => array( 'PDF' => 'PDF', 'PNG' => 'PNG', 'ZPL' => 'ZPL' ),
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_label_layout' => array(
				'title'             => __( 'Label Layout', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select the shipping label size.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => array( '1x1' => '1x1', '4x1' => '4x1' ),
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_add_weight_type' => array(
				'title'             => __( 'Additional Weight Type', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select whether to add an absolute weight amount or percentage amount to the total product weight.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => array( 'absolute' => 'Absolute', 'percentage' => 'Percentage'),
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_add_weight' => array(
				'title'             => sprintf( __( 'Additional Weight (%s or %%)', 'pr-shipping-dhl' ), $weight_units),
				'type'              => 'text',
				'description'       => __( 'Add extra weight in addition to the products.  Either an absolute amount or percentage (e.g. 10 for 10%).', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'		=> '',
				'class'				=> 'wc_input_decimal'
			),
			'dhl_duties_default' => array(
				'title'             => __( 'Incoterms', 'pr-shipping-dhl' ),
				'type'              => 'select',
				'description'       => __( 'Select default for duties.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'options'           => $select_dhl_duties,
				'class'				=> 'wc-enhanced-select'
			),
			'dhl_order_note' => array(
				'title'             => __( 'Order Notes', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Include Order Notes', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want to send the customer "Order Notes" to be added to the label.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
            'dhl_default_additional_insurance' => array(
                'title'             => __( 'Additional Insurance default', 'pr-shipping-dhl' ),
                'type'              => 'checkbox',
                'label'             => __( 'Enabled', 'pr-shipping-dhl' ),
                'default'           => 'no',
                'description'       => __( 'Please, tick here if you want "Additional Insurance" service to be checked by default.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
            ),
            'dhl_default_obox_service' => array(
                'title'             => __( 'Open Box Service default', 'pr-shipping-dhl' ),
                'type'              => 'checkbox',
                'label'             => __( 'Enabled', 'pr-shipping-dhl' ),
                'default'           => 'no',
                'description'       => __( 'Please, tick here if you want "Open Box" service to be checked by default.', 'pr-shipping-dhl' ),
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
			'dhl_tracking_note_txt' => array(
				'title'             => __( 'Tracking Note', 'pr-shipping-dhl' ),
				'type'              => 'textarea',
				'description'       => __( 'Set the custom text when adding the tracking number to the order notes. {tracking-link} is where the tracking number will be set.', 'pr-shipping-dhl' ),
				'desc_tip'          => false,
				'default'           => __( 'DHL Tracking Number: {tracking-link}', 'pr-shipping-dhl')
			),
			'dhl_api'           => array(
				'title'           => __( 'API Settings', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your access towards the DHL eCommerce APIs by means of authentication.', 'pr-shipping-dhl' ),
				'class'			  => '',
			),
			'dhl_api_key' => array(
				'title'             => __( 'Client Id', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'The client ID (a 36 digits alphanumerical string made from 5 blocks) is required for authentication and is provided to you within your contract.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_api_secret' => array(
				'title'             => __( 'Client Secret', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'The client secret (also a 36 digits alphanumerical string made from 5 blocks) is required for authentication (together with the client ID) and creates the tokens needed to ensure secure access. It is part of your contract provided by your DHL sales partner.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_sandbox' => array(
				'title'             => __( 'Sandbox Mode', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable Sandbox Mode', 'pr-shipping-dhl' ),
				'default'           => 'no',
				'description'       => __( 'Please, tick here if you want to test the plug-in installation against the DHL Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_test_connection_button' => array(
				'title'             => PR_DHL_BUTTON_TEST_CONNECTION,
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "dhlTestConnection('#woocommerce_pr_dhl_ecs_asia_dhl_test_connection_button');",
				),
				'description'       => __( 'Press the button for testing the connection against our DHL eCommerce Gateways (depending on the selected environment this test is being done against the Sandbox or the Production Environment).', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),
			'dhl_debug' => array(
				'title'             => __( 'Debug Log', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'pr-shipping-dhl' ),
				'default'           => 'yes',
				'description'       => sprintf( __( 'A log file containing the communication to the DHL server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.', 'pr-shipping-dhl' ), '<a href="' . $log_path . '" target = "_blank">', '</a>' )
			),
            'dhl_shipper'           => array(
                'title'           => __( 'Pickup Address', 'pr-shipping-dhl' ),
                'type'            => 'title',
                'description'     => __( 'Enter Pickup Address below.  This is used for the "DHL Parcel Metro" product only.', 'pr-shipping-dhl' ),
            ),
            'dhl_contact_name' => array(
                'title'             => __( 'Name', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'The account name will be provided by your local DHL sales organization and tells us where to pick up your shipments.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_email' => array(
                'title'             => __( 'Email', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'The email.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => '',
                'placeholder'		=> 'name@email.com'
            ),
            'dhl_phone' => array(
                'title'             => __( 'Phone', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'The phone.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => '',
                'placeholder'		=> '0214543433'
            ),
            'dhl_shipper_company' => array(
                'title'             => __( 'Company', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup Company.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_1' => array(
                'title'             => __( 'Address 1', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup Address.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_2' => array(
                'title'             => __( 'Address 2', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup Address 2.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_city' => array(
                'title'             => __( 'City', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup City.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_state' => array(
                'title'             => __( 'State', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup County.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_district' => array(
                'title'             => __( 'District', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup District.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
            'dhl_shipper_address_country' => array(
                'title'             => __( 'Country', 'pr-shipping-dhl' ),
                'type'              => 'select',
                'description'       => __( 'Enter Pickup Country.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'options'           => WC()->countries->get_countries(),
                'class'				=> 'wc-enhanced-select',
                'default'           => ''
            ),
            'dhl_shipper_address_postcode' => array(
                'title'             => __( 'Postcode', 'pr-shipping-dhl' ),
                'type'              => 'text',
                'description'       => __( 'Enter Pickup Postcode.', 'pr-shipping-dhl' ),
                'desc_tip'          => true,
                'default'           => ''
            ),
		);
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

	/**
	 * Validate the pickup field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_pickup_field( $key, $value ) {
		// $value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );

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
	 * Validate the distribution field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_distribution_field( $key, $value ) {
		// $value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );
		
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
	 * Validate the label format field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_label_format_field( $key, $value ) {
		// $value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );
		$post_data = $this->get_post_data();
		/*
		$label_page = $post_data[ $this->plugin_id . $this->id . '_' . 'dhl_label_page' ];
		if( ( $value == 'PNG' || $value == 'ZPL' ) && ( $label_page == 'A4' ) ) {
			$msg = __('The selected format does not support "A4"', 'pr-shipping-dhl');
			echo $this->get_message($msg);
			throw new Exception( $msg );
		}
		*/
		return $value;
	}

	/**
	 * Validate the label size field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_label_size_field( $key, $value ) {
		// $value = wc_clean( $_POST[ $this->plugin_id . $this->id . '_' . $key ] );
		$distribution_centers = array('HKHKG1', 'CNSHA1', 'CNSZX1');
		$post_data = $this->get_post_data();

		$distribution = $post_data[ $this->plugin_id . $this->id . '_' . 'dhl_distribution' ];
		/*
		$label_page = $post_data[ $this->plugin_id . $this->id . '_' . 'dhl_label_page' ];

		if( ! in_array( $distribution, $distribution_centers )  && ( $value == '4x4' ) ) {
			$msg = __('Your distribution center does not support "4x4" label size.', 'pr-shipping-dhl');
			echo $this->get_message( $msg );
			throw new Exception( $msg );
		}

		if( ( $value == '4x4' ) && ( $label_page == '400x600' ) ) {
			$msg = __('You cannot have a Label Size of "4x4" and Page Size of "400x600".', 'pr-shipping-dhl');
			echo $this->get_message( $msg );
			throw new Exception( $msg );
		}

		if( ( $value == '4x6' ) && ( $label_page == '400x400' ) ) {
			$msg = __('You cannot have a Label Size of "4x6" and Page Size of "400x400".', 'pr-shipping-dhl');
			echo $this->get_message( $msg );
			throw new Exception( $msg );
		}
		*/
		return $value;
	}

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 */
	public function process_admin_options() {
		
		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$dhl_obj->dhl_reset_connection();

		} catch (Exception $e) {

			echo $this->get_message( __('Could not reset connection: ', 'pr-shipping-dhl') . $e->getMessage() );
			// throw $e;
		}

		return parent::process_admin_options();
	}
}

endif;
