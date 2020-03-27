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
        $this->id = 'pr_dhl_dp';
        $this->instance_id = absint( $instance_id );
        $this->method_title = __( 'Deutsche Post', 'pr-shipping-dhl' );
        $this->method_description = sprintf(
            __(
                'To start creating Deutsche Post shipping labels and return back a tracking number to your customers, please fill in your user credentials as provided by Deutsche Post. Not yet a customer? Please get a quote %shere%s.',
                'pr-shipping-dhl'
            ),
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
        <div class="<?php echo $type ?>">
            <p><?php echo $message ?></p>
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

            $dhl_obj = PR_DHL()->get_dhl_factory();
            $select_dhl_product_int = $dhl_obj->get_dhl_products_international();
        } catch ( Exception $e ) {
            PR_DHL()->log_msg( __( 'Deutsche Post Products not displaying - ', 'pr-shipping-dhl' ) . $e->getMessage() );
        }

        $weight_units = get_option( 'woocommerce_weight_unit' );

        $this->form_fields = array(
            'dhl_api'                    => array(
                'title'       => __( 'Account and API Settings', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __(
                    'Please configure your account and API settings with Deutschepost International.',
                    'pr-shipping-dhl'
                ),
                'class'       => '',
            ),
            'dhl_account_num' => array(
	            'title'             => __( 'Account Number (EKP)', 'pr-shipping-dhl' ),
	            'type'              => 'text',
	            'description'       => __( 'Your account number (9; 10 or 15 digits, numerical), also called "EKP“. This will be provided by your local Deutsche Post sales organization.', 'pr-shipping-dhl' ),
	            'desc_tip'          => true,
	            'default'           => '',
	            'placeholder'		=> '1234567890',
	            'custom_attributes'	=> array( 'maxlength' => '10' )
            ),
	        'dhl_contact_name' => array(
		        'title'             => __( 'Contact Name', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'description'       => __( 'Required for all customers. The name of the merchant, used as contact information on the Waybill.', 'pr-shipping-dhl' ),
		        'desc_tip'          => true,
		        'default'           => '',
		        'placeholder'		=> 'Contact Name',
			),
			'dhl_contact_phone_number' => array(
		        'title'             => __( 'Contact Phone Number', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'description'       => __( 'Required for DHL Express customers. The phone number of the merchant, used as contact information on the Waybill.', 'pr-shipping-dhl' ),
		        'desc_tip'          => true,
		        'default'           => '',
		        'placeholder'		=> '+4935120681234',
	        ),
            'dhl_api_key'                => array(
                'title'       => __( 'Client Id', 'pr-shipping-dhl' ),
                'type'        => 'text',
                'description' => __(
                    'The client ID (a 36 digits alphanumerical string made from 5 blocks) is required for authentication and is provided to you within your contract.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'dhl_api_secret'             => array(
                'title'       => __( 'Client Secret', 'pr-shipping-dhl' ),
                'type'        => 'text',
                'description' => __(
                    'The client secret (also a 36 digits alphanumerical string made from 5 blocks) is required for authentication (together with the client ID) and creates the tokens needed to ensure secure access. It is part of your contract provided by your Deutsche Post sales partner.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'default'     => '',
            ),
            'dhl_sandbox'                => array(
                'title'       => __( 'Sandbox Mode', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable Sandbox Mode', 'pr-shipping-dhl' ),
                'default'     => 'no',
                'description' => __(
                    'Please, tick here if you want to test the plug-in installation against the Deutsche Post Sandbox Environment. Labels generated via Sandbox cannot be used for shipping and you need to enter your client ID and client secret for the Sandbox environment instead of the ones for production!',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
            ),
            'dhl_test_connection_button' => array(
                'title'             => PR_DHL_BUTTON_TEST_CONNECTION,
                'type'              => 'button',
                'custom_attributes' => array(
                    'onclick' => "dhlTestConnection('#woocommerce_pr_dhl_dp_dhl_test_connection_button');",
                ),
                'description'       => __(
                    'Press the button for testing the connection against our Deutsche Post (depending on the selected environment this test is being done against the Sandbox or the Production Environment).',
                    'pr-shipping-dhl'
                ),
                'desc_tip'          => true,
            ),
            'dhl_debug'                  => array(
                'title'       => __( 'Debug Log', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Enable logging', 'pr-shipping-dhl' ),
                'default'     => 'yes',
                'description' => sprintf(
                    __(
                        'A log file containing the communication to the Deutsche Post server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.',
                        'pr-shipping-dhl'
                    ),
                    '<a href="' . $log_path . '" target = "_blank">',
                    '</a>'
                ),
            ),
        );

        $this->form_fields += array(
            'dhl_pickup_dist'  => array(
                'title'       => __( 'Shipping', 'pr-shipping-dhl' ),
                'type'        => 'title',
                'description' => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
                'class'       => '',
            ),
            'dhl_default_product_int' => array(
                'title'       => __( 'International Default Service', 'pr-shipping-dhl' ),
                'type'        => 'select',
                'description' => __(
                    'Please select your default Deutsche Post shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'options'     => $select_dhl_product_int,
                'class'       => 'wc-enhanced-select',
            ),
            'dhl_add_weight_type'   => array(
                'title'       => __( 'Additional Weight Type', 'pr-shipping-dhl' ),
                'type'        => 'select',
                'description' => __(
                    'Select whether to add an absolute weight amount or percentage amount to the total product weight.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'options'     => array( 'absolute' => 'Absolute', 'percentage' => 'Percentage' ),
                'class'       => 'wc-enhanced-select',
            ),
            'dhl_add_weight'        => array(
                'title'       => sprintf( __( 'Additional Weight (%s or %%)', 'pr-shipping-dhl' ), $weight_units ),
                'type'        => 'text',
                'description' => __(
                    'Add extra weight in addition to the products.  Either an absolute amount or percentage (e.g. 10 for 10%).',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
                'default'     => '',
                'placeholder' => '',
                'class'       => 'wc_input_decimal',
            ),
            'dhl_tracking_note'     => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'checkbox',
                'label'       => __( 'Make Private', 'pr-shipping-dhl' ),
                'default'     => 'no',
                'description' => __(
                    'Please, tick here to not send an email to the customer when the tracking number is added to the order.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => true,
            ),
            'dhl_tracking_note_txt' => array(
                'title'       => __( 'Tracking Note', 'pr-shipping-dhl' ),
                'type'        => 'textarea',
                'description' => __(
                    'Set the custom text when adding the tracking number to the order notes. {tracking-link} is where the tracking number will be set.',
                    'pr-shipping-dhl'
                ),
                'desc_tip'    => false,
                'default'     => __( 'Deutsche Post Tracking Number: {tracking-link}', 'pr-shipping-dhl' ),
            ),
        );

	    $this->form_fields += array(
		    'dhl_label_section'   => array(
			    'title'           => __( 'Label options', 'pr-shipping-dhl' ),
			    'type'            => 'title',
			    'description'     => __( 'Options for configuring your label preferences', 'pr-shipping-dhl' ),
		    ),
	        'dhl_label_ref' => array(
		        'title'             => __( 'Label Reference', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'custom_attributes'	=> array( 'maxlength' => '35' ),
		        'description'       => sprintf( __( 'Use "%s" to send the order id as a reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}', '{user_id}' ),
		        'desc_tip'          => true,
		        'default'           => '{order_id}'
	        ),
	        'dhl_label_ref_2' => array(
		        'title'             => __( 'Label Reference 2', 'pr-shipping-dhl' ),
		        'type'              => 'text',
		        'custom_attributes'	=> array( 'maxlength' => '35' ),
		        'description'       => sprintf( __( 'Use "%s" to send the order id as a reference and "%s" to send the customer email. This text is limited to 35 characters.', 'pr-shipping-dhl' ), '{order_id}' , '{email}', '{user_id}' ),
		        'desc_tip'          => true,
		        'default'           => '{email}'
	        ),
        );

        $this->form_fields += array(

        );
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
        $field = $this->plugin_id . $this->id . '_' . $key;
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
                    <legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span>
                    </legend>
                    <button class="<?php echo esc_attr( $data['class'] ); ?>" type="button"
                            name="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>"
                            style="<?php echo esc_attr(
                                $data['css']
                            ); ?>" <?php echo $this->get_custom_attribute_html( $data ); ?>><?php echo wp_kses_post(
                            $data['title']
                        ); ?></button>
                    <?php echo $this->get_description_html( $data ); ?>
                </fieldset>
            </td>
        </tr>
        <?php

        return ob_get_clean();
	}
	
	/**
	 * Validate the contact phone number field
	 * @see validate_settings_fields()
	 */
	public function validate_dhl_contact_phone_number_field( $key, $value ) {
		
		$post_data 		= $this->get_post_data();
		$account_num 	= $post_data[ $this->plugin_id . $this->id . '_' . 'dhl_account_num' ];

		$first_nums = array( "1", "3", "4" );
		
		foreach( $first_nums as $num ){
			if( substr( $account_num, 0, 1 ) == $num ){

				if( empty( $value ) ){

					$msg = __( 'Contact Phone Number required, please add in settings.', 'pr-shipping-dhl' );
					echo $this->get_message( $msg );
					throw new Exception( $msg );
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

            echo $this->get_message( __( 'Could not reset connection: ', 'pr-shipping-dhl' ) . $e->getMessage() );
            // throw $e;
        }
		
        return parent::process_admin_options();
    }
}
