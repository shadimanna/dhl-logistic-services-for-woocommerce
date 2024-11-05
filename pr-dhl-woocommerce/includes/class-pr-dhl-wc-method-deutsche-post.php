<?php

// Exit if accessed directly or class already exists
if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_WC_Method_Deutsche_Post', false ) ) {
	return;
}

class PR_DHL_WC_Method_Deutsche_Post extends WC_Shipping_Method {
	/**
	 * Init and hook in the integration.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id           = 'pr_dhl_dp';
		$this->instance_id  = absint( $instance_id );
		$this->method_title = esc_html__( 'Deutsche Post International', 'dhl-for-woocommerce' );

		/* translators: %1$s is the link to the Deutsche Post contact page, %2$s is the closing HTML tag for the link */
		$this->method_description = sprintf(
			esc_html__( 'To start creating Deutsche Post shipping labels and return back a tracking number to your customers, please fill in your user credentials as provided by Deutsche Post. Not yet a customer? Please get a quote %1$shere%2$s.', 'dhl-for-woocommerce' ),
			'<a href="https://www.deutschepost.com/en/business-customers/contact.html" target="_blank">',
			'</a>'
		);
		$this->init();
	}

	/**
	 * Initializes the instance.
	 *
	 * @since [*next-version*]
	 */
	protected function init() {
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Get message
	 *
	 * @return string Error
	 */
	private function get_message( $message, $type = 'notice notice-error is-dismissible' ) {

		ob_start();
		?>
		<div class="<?php echo esc_html( $type ); ?>">
			<p><?php echo esc_html( $message ); ?></p>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Initialize integration settings form fields.
	 *
	 * @throws Exception
	 */
	public function init_form_fields() {

		$log_path = PR_DHL()->get_log_url();

		try {

			$dhl_obj                = PR_DHL()->get_dhl_factory();
			$select_dhl_product_int = $dhl_obj->get_dhl_products_international();
		} catch ( Exception $e ) {
			PR_DHL()->log_msg( esc_html__( 'Deutsche Post Products not displaying - ', 'dhl-for-woocommerce' ) . $e->getMessage() );
		}

		$weight_units = get_option( 'woocommerce_weight_unit' );

		$this->form_fields = array(
			'dhl_api'                    => array(
				'title'       => esc_html__( 'Account and API Settings', 'dhl-for-woocommerce' ),
				'type'        => 'title',
				'description' => esc_html__(
					'Please configure your account and API settings with Deutschepost International.',
					'dhl-for-woocommerce'
				),
				'class'       => '',
			),
			'dhl_account_num'            => array(
				'title'             => esc_html__( 'Account Number (EKP)', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'description'       => esc_html__( 'Your account number (9; 10 or 15 digits, numerical), also called "EKP“. This will be provided by your local Deutsche Post sales organization.', 'dhl-for-woocommerce' ),
				'desc_tip'          => true,
				'default'           => '',
				'placeholder'       => '1234567890',
				'custom_attributes' => array( 'maxlength' => '10' ),
			),
			'dhl_contact_name'           => array(
				'title'       => esc_html__( 'Contact Name', 'dhl-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Required for all customers. The name of the merchant, used as contact information on the Waybill.', 'dhl-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => 'Contact Name',
			),
			'dhl_contact_phone_number'   => array(
				'title'       => esc_html__( 'Contact Phone Number', 'dhl-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__( 'Required for DHL Express customers. The phone number of the merchant, used as contact information on the Waybill.', 'dhl-for-woocommerce' ),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => '+4935120681234',
			),
			'dhl_api_key'                => array(
				'title'       => esc_html__( 'Client Id', 'dhl-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__(
					'The client ID (a 36 digits alphanumerical string made from 5 blocks) is required for authentication and is provided to you within your contract.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => '',
			),
			'dhl_api_secret'             => array(
				'title'       => esc_html__( 'Client Secret', 'dhl-for-woocommerce' ),
				'type'        => 'text',
				'description' => esc_html__(
					'The client secret (also a 36 digits alphanumerical string made from 5 blocks) is required for authentication (together with the client ID) and creates the tokens needed to ensure secure access. It is part of your contract provided by your Deutsche Post sales partner.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => '',
			),
			'dhl_sandbox'                => array(
				'title'       => esc_html__( 'Sandbox Mode', 'dhl-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Enable Sandbox Mode', 'dhl-for-woocommerce' ),
				'default'     => 'no',
				'description' => esc_html__(
					'Please, tick here if you want to test the plug-in installation against the Deutsche Post Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
			),
			'dhl_test_connection_button' => array(
				'title'             => PR_DHL_BUTTON_TEST_CONNECTION,
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "dhlTestConnection('#woocommerce_pr_dhl_dp_dhl_test_connection_button');",
				),
				'description'       => esc_html__(
					'Press the button for testing the connection against our Deutsche Post (depending on the selected environment this test is being done against the Sandbox or the Production Environment).',
					'dhl-for-woocommerce'
				),
				'desc_tip'          => true,
			),
			'dhl_debug'                  => array(
				'title'       => esc_html__( 'Debug Log', 'dhl-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Enable logging', 'dhl-for-woocommerce' ),
				'default'     => 'yes',
				/* translators: %1$s is the link to the log file, %2$s is the closing HTML tag for the link */
				'description' => sprintf(
					esc_html__( 'A log file containing the communication to the Deutsche Post server will be maintained if this option is checked. This can be used in case of technical issues and can be found %1$shere%2$s.', 'dhl-for-woocommerce' ),
					'<a href="' . esc_url( $log_path ) . '" target="_blank">',
					'</a>'
				),
			),
		);

		$this->form_fields += array(
			'dhl_pickup_dist'         => array(
				'title'       => esc_html__( 'Shipping', 'dhl-for-woocommerce' ),
				'type'        => 'title',
				'description' => esc_html__( 'Please configure your shipping parameters underneath.', 'dhl-for-woocommerce' ),
				'class'       => '',
			),
			'dhl_default_product_int' => array(
				'title'       => esc_html__( 'International Default Service', 'dhl-for-woocommerce' ),
				'type'        => 'select',
				'description' => esc_html__(
					'Please select your default Deutsche Post shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
				'options'     => $select_dhl_product_int,
				'class'       => 'wc-enhanced-select',
			),
			'dhl_add_weight_type'     => array(
				'title'       => esc_html__( 'Additional Weight Type', 'dhl-for-woocommerce' ),
				'type'        => 'select',
				'description' => esc_html__(
					'Select whether to add an absolute weight amount or percentage amount to the total product weight.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
				'options'     => array(
					'absolute'   => 'Absolute',
					'percentage' => 'Percentage',
				),
				'class'       => 'wc-enhanced-select',
			),
			'dhl_add_weight'          => array(
				/* translators: %s is the unit of weight (e.g., kg, lbs) */
				'title'       => sprintf(
					esc_html__( 'Additional Weight (%s or %%)', 'dhl-for-woocommerce' ),
					$weight_units
				),
				'type'        => 'text',
				'description' => esc_html__(
					'Add extra weight in addition to the products. Either an absolute amount or percentage (e.g. 10 for 10%).',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
				'default'     => '',
				'placeholder' => '',
				'class'       => 'wc_input_decimal',
			),

			/*
			'dhl_packet_return'     => array(
				'title'       => esc_html__( 'Packet Return', 'dhl-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Enable', 'dhl-for-woocommerce' ),
				'default'     => 'no',
				'description' => esc_html__(
					'Please note that Packet Return needs to be activated by Deutsche Post. Please get in touch with your local DP Customer Service for more details.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
			),
			*/
			'dhl_tracking_note'       => array(
				'title'       => esc_html__( 'Tracking Note', 'dhl-for-woocommerce' ),
				'type'        => 'checkbox',
				'label'       => esc_html__( 'Make Private', 'dhl-for-woocommerce' ),
				'default'     => 'no',
				'description' => esc_html__(
					'Please, tick here to not send an email to the customer when the tracking number is added to the order.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => true,
			),
			'dhl_tracking_note_txt'   => array(
				'title'       => esc_html__( 'Tracking Note', 'dhl-for-woocommerce' ),
				'type'        => 'textarea',
				'description' => esc_html__(
					'Set the custom text when adding the tracking number to the order notes. {tracking-link} is where the tracking number will be set.',
					'dhl-for-woocommerce'
				),
				'desc_tip'    => false,
				'default'     => esc_html__( 'Deutsche Post Tracking Number: {tracking-link}', 'dhl-for-woocommerce' ),
			),
		);

		$this->form_fields += array(
			'dhl_label_section' => array(
				'title'       => esc_html__( 'Label options', 'dhl-for-woocommerce' ),
				'type'        => 'title',
				'description' => esc_html__( 'Options for configuring your label preferences', 'dhl-for-woocommerce' ),
			),
			'dhl_label_ref'     => array(
				'title'             => esc_html__( 'Label Reference', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'custom_attributes' => array( 'maxlength' => '35' ),
				/* translators: %s is the placeholder for the order ID and customer email */
				'description'       => sprintf(
					esc_html__( 'Use "%1$s" to send the order id as a reference and "%2$s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ),
					'{order_id}',
					'{email}'
				),
				'desc_tip'          => true,
				'default'           => '{order_id}',
			),
			'dhl_label_ref_2'   => array(
				'title'             => esc_html__( 'Label Reference 2', 'dhl-for-woocommerce' ),
				'type'              => 'text',
				'custom_attributes' => array( 'maxlength' => '35' ),
				/* translators: %s is the placeholder for the order ID and customer email */
				'description'       => sprintf(
					esc_html__( 'Use "%1$s" to send the order id as a reference and "%2$s" to send the customer email. This text is limited to 35 characters.', 'dhl-for-woocommerce' ),
					'{order_id}',
					'{email}'
				),
				'desc_tip'          => true,
				'default'           => '{email}',
			),
		);

		$this->form_fields += array();
	}

	/**
	 * Generate Button HTML.
	 *
	 * @access public
	 *
	 * @param mixed $key
	 * @param mixed $data
	 *
	 * @since  1.0.0
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
					<legend class="screen-reader-text"><span><?php echo esc_html( $data['title'] ); ?></span>
					</legend>
					<button class="<?php echo esc_attr( $data['class'] ); ?>" type="button"
							name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>"
							style="
							<?php
							echo esc_attr(
								$data['css']
							);
							?>
							" <?php echo $this->get_custom_attribute_html( $data ); ?>>
							<?php
							echo esc_html( $data['title'] );
							?>
						</button>
					<?php echo $this->get_description_html( $data ); ?>
				</fieldset>
			</td>
		</tr>
		<?php

		return ob_get_clean();
	}

	/**
	 * Validate the contact phone number field
	 *
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_contact_phone_number_field( $key, $value ) {

		$post_data   = $this->get_post_data();
		$account_num = $post_data[ $this->plugin_id . $this->id . '_' . 'dhl_account_num' ];

		$first_nums = array( '1', '3', '4' );

		foreach ( $first_nums as $num ) {
			if ( substr( $account_num, 0, 1 ) == $num ) {

				if ( empty( $value ) ) {

					$msg = esc_html__( 'Contact Phone Number required, please add in settings.', 'dhl-for-woocommerce' );
					echo $this->get_message( $msg );
					throw new Exception( esc_html( $msg ) );
					break;
				}
			}
		}

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
		} catch ( Exception $e ) {

			echo esc_html( $this->get_message( esc_html__( 'Could not reset connection: ', 'dhl-for-woocommerce' ) . esc_html( $e->getMessage() ) ) );
			// throw $e;
		}

		return parent::process_admin_options();
	}
}
