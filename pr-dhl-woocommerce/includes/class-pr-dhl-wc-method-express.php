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

if ( ! class_exists( 'PR_DHL_WC_Method_Express' ) ) :

class PR_DHL_WC_Method_Express extends WC_Shipping_Method {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( $instance_id = 0 ) {
		$this->id = 'pr_dhl_express';
		$this->instance_id = absint( $instance_id );
		$this->method_title = __( 'DHL Express', 'pr-shipping-dhl' );
		$this->method_description = __( 'Setup DHL Express rates and create DHL Express labels.', 'pr-shipping-dhl' );
		$this->supports           = array(
			'settings',
			'shipping-zones', // support shipping zones shipping method
			'instance-settings',
		);
		$this->init();
	}

	/**
	 * init function.
	 */
	private function init() {
		// Load the settings.
		$this->init_instance_form_fields();
		$this->init_form_fields();
		$this->init_settings();

		// Set title so can be viewed in zone screen
		$this->title = $this->get_option( 'title' );

		add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

	}

	public function load_admin_scripts( $hook ) {
	    if( 'woocommerce_page_wc-settings' != $hook ) {
			// Only applies to WC Settings panel
			return;
	    }
	    
      $test_con_data = array( 
    					'ajax_url' => admin_url( 'admin-ajax.php' ),
    					'test_con_nonce' => wp_create_nonce( 'pr-dhl-test-con' ) 
    				);

		wp_enqueue_script( 'wc-shipment-dhl-testcon-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-test-connection.js', array('jquery'), PR_DHL_VERSION );
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
	public function init_instance_form_fields() {
		$this->instance_form_fields = array(
			'title'            	=> array(
				'title'           	=> __( 'Method Title', 'smart-send-shipping' ),
				'type'            	=> 'text',
				'description'     	=> __( 'This controls the title which the user sees during checkout.', 'smart-send-shipping' ),
				'default'         	=> __( 'DHL Express', 'smart-send-shipping' ),
				'desc_tip'        	=> true
			),
			'tax_status'		=> array(
				'title' 			=> __( 'Tax status', 'smart-send-shipping' ),
				'type'	 			=> 'select',
				'class' 	        => 'wc-enhanced-select',
				'description'     => __( 'This controls the title which the user sees during checkout.', 'smart-send-shipping' ),
				'default' 			=> 'taxable',
				'desc_tip'        	=> true,
				'options'			=> array(
					'taxable' 		=> __( 'Taxable', 'smart-send-shipping' ),
					'none' 			=> _x( 'None', 'Tax status', 'smart-send-shipping' ),
				)
			),
			'express_products' => array(
				'type'        => 'express_products',
			),
		);
	}
	/**
	 * Initialize integration settings form fields.
	 *
	 * @return void
	 */
	public function init_form_fields() {

		$log_path = PR_DHL()->get_log_url();

		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory(true);
			$select_dhl_product_int = $dhl_obj->get_dhl_products_international();
			$select_dhl_product_dom = $dhl_obj->get_dhl_products_domestic();

		} catch (Exception $e) {
			PR_DHL()->log_msg( __('DHL Products not displaying - ', 'pr-shipping-dhl') . $e->getMessage() );
		}

		$this->form_fields = array(
			'dhl_pickup_dist'     => array(
				'title'           => __( 'Shipping and Pickup', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your shipping parameters underneath.', 'pr-shipping-dhl' ),
				'class'			  => '',
			),
			'dhl_account_num' => array(
				'title'             => __( 'Account Number', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'The pickup account number (10 digits - numerical) will be provided by your local DHL sales organization and tells us where to pick up your shipments.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => '',
				// 'placeholder'		=> '0000500000'
			)
		);

		if ( ! empty( $select_dhl_product_int ) ) {

			$this->form_fields += array(
				'dhl_default_product_int' => array(
					'title'             => __( 'International Default Service', 'pr-shipping-dhl' ),
					'type'              => 'select',
					'description'       => __( 'Please select your default DHL Express shipping service for cross-border shippments that you want to offer to your customers (you can always change this within each individual order afterwards).', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'options'           => $select_dhl_product_int
				)
			);
		}

		if ( ! empty( $select_dhl_product_dom ) ) {

			$this->form_fields += array(
				'dhl_default_product_dom' => array(
					'title'             => __( 'Domestic Default Service', 'pr-shipping-dhl' ),
					'type'              => 'select',
					'description'       => __( 'Please select your default DHL Express shipping service for domestic shippments that you want to offer to your customers (you can always change this within each individual order afterwards)', 'pr-shipping-dhl' ),
					'desc_tip'          => true,
					'options'           => $select_dhl_product_dom
				)
			);
		}

		$this->form_fields += array(
			'dhl_api'           => array(
				'title'           => __( 'API Settings', 'pr-shipping-dhl' ),
				'type'            => 'title',
				'description'     => __( 'Please configure your access towards the DHL Paket APIs by means of authentication.', 'pr-shipping-dhl' ),
			),
			'dhl_api_user' => array(
				'title'             => __( 'Username', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter DHL Paket username.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_api_pwd' => array(
				'title'             => __( 'Password', 'pr-shipping-dhl' ),
				'type'              => 'password',
				'description'       => __( 'Enter DHL Paket password.', 'pr-shipping-dhl' ),
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
			),/*
			'dhl_customize_button' => array(
				'title'             => PR_DHL_BUTTON_TEST_CONNECTION,
				'type'              => 'button',
				'custom_attributes' => array(
					'onclick' => "dhlTestConnection('#woocommerce_pr_dhl_express_dhl_customize_button');",
				),
				'description'       => __( 'Press the button for testing the connection against our DHL Express Gateways (depending on the selected environment this test is being done against the Sandbox or the Production Environment).', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
			),*/
			'dhl_debug' => array(
				'title'             => __( 'Debug Log', 'pr-shipping-dhl' ),
				'type'              => 'checkbox',
				'label'             => __( 'Enable logging', 'pr-shipping-dhl' ),
				'default'           => 'yes',
				'description'       => sprintf( __( 'A log file containing the communication to the DHL server will be maintained if this option is checked. This can be used in case of technical issues and can be found %shere%s.', 'pr-shipping-dhl' ), '<a href="' . $log_path . '" target = "_blank">', '</a>' )
			),
		);

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
				'title'             => __( 'Street Address 1', 'pr-shipping-dhl' ),
				'type'              => 'text',
				'description'       => __( 'Enter Shipper Street Address.', 'pr-shipping-dhl' ),
				'desc_tip'          => true,
				'default'           => ''
			),
			'dhl_shipper_address2' => array(
				'title'             => __( 'Street Address 2', 'pr-shipping-dhl' ),
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
	 * HTML for service option.
	 *
	 * @access public
	 * @return string HTML string.
	 */
	public function generate_express_products_html() {
		ob_start();
		?>
		<tr valign="top" id="service_options">
			<th scope="row" class="titledesc"><?php _e( 'Services', 'woocommerce-shipping-ups' ); ?></th>
			<td class="forminp">
				<table id="dhl_express_products" class="widefat wc_input_table sortable" cellspacing="0">
					<thead>
						<th class="sort">&nbsp;</th>
						<th class="short_text"><?php _e( 'Service Code', 'woocommerce-shipping-ups' ); ?></th>
						<th class="wide_text"><?php _e( 'Name', 'woocommerce-shipping-ups' ); ?></th>
						<th class="short_text"><?php _e( 'Enabled', 'woocommerce-shipping-ups' ); ?></th>
						<th><?php echo sprintf( __( 'Price Adjustment (%s)', 'woocommerce-shipping-ups' ), get_woocommerce_currency_symbol() ); ?></th>
						<th><?php _e( 'Price Adjustment (%)', 'woocommerce-shipping-ups' ); ?></th>
					</thead>
					<tfoot>
					<?php //if ( 'PL' !== $this->origin_country && ! in_array( $this->origin_country, $this->eu_array ) ) : ?>
						<tr>
							<th colspan="6">
								<small class="description"><?php //_e( '<strong>Domestic Rates</strong>: Next Day Air, 2nd Day Air, Ground, 3 Day Select, Next Day Air Saver, Next Day Air Early AM, 2nd Day Air AM', 'woocommerce-shipping-ups' ); ?></small><br/>
								<small class="description"><?php //_e( '<strong>International Rates</strong>: Worldwide Express, Worldwide Expedited, Standard, Worldwide Express Plus, UPS Saver', 'woocommerce-shipping-ups' ); ?></small>
							</th>
						</tr>
					<?php // endif ?>
					</tfoot>
					<tbody>
						<?php
						// $sort = 0;
						// $ordered_services = array();
						/*
						if ( 'PL' === $this->origin_country ) {
							$use_services = $this->polandservices;
						} elseif ( in_array( $this->origin_country, $this->eu_array ) ) {
							$use_services = $this->euservices;
						} else {
							$use_services = $this->services;
						}
						*/
						$dhl_express = PR_DHL()->get_dhl_factory( true );
						$use_services = $dhl_express->get_dhl_products_domestic();
						$use_services += $dhl_express->get_dhl_products_international();
						error_log(print_r($use_services,true));
						
						$custom_services = $this->get_option('express_products');
						error_log(print_r($custom_services,true));

						// If the saved servics are empty, means first use of plugin
						if( empty( $custom_services ) ) {
							$custom_services = $use_services;
						}
						
						// Loop through to add services that should be used in plugin
						foreach ( $use_services as $code => $name ) {

							// If the service does not exists, add it to the end of the existing ones
							if ( ! isset( $custom_services[ $code ] ) ) {
								$custom_services[ $code ] = array();
							}
						}

						// error_log(print_r($custom_services,true));

						foreach ( $custom_services as $code => $service ) {
							// If a saved service is not in use anymore, do not display it
							if ( !isset( $use_services[ $code ] ) ){
								continue;
							}

							?>
							<tr>
								<td class="sort"></td>
								<td><strong><?php echo $code; ?></strong></td>
								<td><input type="text" name="dhl_express_product[<?php echo $code; ?>][name]" placeholder="<?php echo $use_services[ $code ]; ?>" value="<?php echo isset( $service['name'] ) ? $service['name'] : ''; ?>" size="50" /></td>
								<td><input type="checkbox" name="dhl_express_product[<?php echo $code; ?>][enabled]" <?php checked( ( ! isset( $service['enabled'] ) || ! empty( $service['enabled'] ) ), true ); ?> /></td>
								<td><input class="wc_input_price" type="text" name="dhl_express_product[<?php echo $code; ?>][adjustment]" placeholder="N/A" value="<?php echo isset( $service['adjustment'] ) ? $service['adjustment'] : ''; ?>" size="4" /></td>
								<td><input class="wc_input_decimal" type="text" name="dhl_express_product[<?php echo $code; ?>][adjustment_percent]" placeholder="N/A" value="<?php echo isset( $service['adjustment_percent'] ) ? $custom_services[ $code ]['adjustment_percent'] : ''; ?>" size="4" /></td>
							</tr>
							<?php
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
		return ob_get_clean();
	}

	/**
	 * Validate services option.
	 *
	 * @param mixed $key Option's key.
	 *
	 * @return mixed Validated value.
	 */
	public function validate_express_products_field( $key ) {
		$services         = array();
		$posted_services  = $_POST['dhl_express_product'];
		error_log(print_r($posted_services,true));
		foreach ( $posted_services as $code => $settings ) {

			$services[ $code ] = array(
				'name'               => wc_clean( $settings['name'] ),
				// 'order'              => wc_clean( $settings['order'] ),
				'enabled'            => isset( $settings['enabled'] ) ? true : false,
				'adjustment'         => wc_clean( $settings['adjustment'] ),
				'adjustment_percent' => str_replace( '%', '', wc_clean( $settings['adjustment_percent'] ) ),
			);
		}

		return $services;
	}
	/**
	 * Validate the API key
	 * @see validate_settings_fields()
	 */
	/*
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
	}*/

	/**
	 * Validate the API secret
	 * @see validate_settings_fields()
	 */
	/*
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
	}*/

	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save and validate fields, but will leave the erroring field out.
	 */
	/*
	public function process_admin_options() {
		
		try {
			
			$dhl_obj = PR_DHL()->get_dhl_factory();
			$dhl_obj->dhl_reset_connection();

		} catch (Exception $e) {

			echo $this->get_message( __('Could not reset connection: ', 'pr-shipping-dhl') . $e->getMessage() );
			throw $e;
		}

		return parent::process_admin_options();
	}*/

	public function calculate_shipping( $package = array() ) {
		// error_log(print_r($package,true));
		$express_products = $this->get_option( 'express_products' );
		error_log(print_r($express_products,true));
		$args = $this->get_rates_args();
		$args['shipping_address'] = $package['destination'];

		error_log(print_r($args,true));
		try {
			$dhl_express = PR_DHL()->get_dhl_factory( true );
			$dhl_rates = $dhl_express->get_dhl_rates( $args );
			error_log(print_r($dhl_rates,true));

			foreach ($express_products as $express_key => $express_value) {

				// Rate is enabled and returned from the API, then add rate
				if( ! empty( $express_value['enabled'] ) && isset( $dhl_rates[ $express_key ] ) ) {

					if( ! empty( $express_value['name'] ) ) {
						$express_name = $express_value['name'];
					} else {
						$express_name = $this->format_dhl_method_name( $dhl_rates[ $express_key ]['name'] );
					}

					// Add filter to change text
					$express_name .= sprintf( __( ' %s(Est. arrival: %s)%s', 'pr-shipping-dhl' ), '<span class="dhl-express-estinated-arrival">', $this->format_dhl_method_time( $dhl_rates[ $express_key ]['delivery_time'] ), '</span>' );

					$express_amount = $dhl_rates[ $express_key ]['amount'];
					// Cost adjustment %
					if ( ! empty( $express_value['adjustment_percent'] ) ) {
						$express_amount += ( $express_amount * ( floatval( $express_value['adjustment_percent'] ) / 100 ) );
					}
					// Cost adjustment
					if( ! empty( $express_value['adjustment'] ) ) {
						$express_amount += floatval( $express_value['adjustment'] );
					}

					// Sort
					if ( isset( $express_value['order'] ) ) {
						$sort = $express_value['order'];
					} else {
						$sort = 999;
					}

					// Suffix for "id" is needed to display multiple rates on the frontend...however it is not saved whereas the "meta_data" below is, so BOTH needed.
					$rate = array(
						'id' 		=> $this->get_rate_id( $express_key ), 
						'label'   	=> $express_name,
						'cost'   	=> $express_amount,
						'sort'  	=> $sort,
						'package' 	=> $package,
						'meta_data' => array( 
								'dhl_express_product_key' => $express_key, 
							),
					);

					$this->add_rate( $rate ); // ADD FILTER BEFORE PASSING
				}
			}

		} catch (Exception $e) {
			error_log('calc ship exception');
			error_log($e->getMessage());
			// throw new Exception($e->getMessage());
		}

		/**
		 * Developers can add additional rates based on this one via this action
		 *
		 * This example shows how you can add an extra rate based on this flat rate via custom function:
		 *
		 * 		add_action( 'woocommerce_smart_send_shipping_shipping_add_rate', 'add_another_custom_rate', 10, 2 );
		 *
		 * 		function add_another_custom_rate( $method, $rate ) {
		 * 			$new_rate          = $rate;
		 * 			$new_rate['id']    .= ':' . 'custom_rate_name'; // Append a custom ID.
		 * 			$new_rate['label'] = 'Rushed Shipping'; // Rename to 'Rushed Shipping'.
		 * 			$new_rate['cost']  += 2; // Add $2 to the cost.
		 *
		 * 			// Add it to WC.
		 * 			$method->add_rate( $new_rate );
		 * 		}.
		 */
		// do_action( 'woocommerce_' . $this->id . '_shipping_add_rate', $this, $rate );
	}

	protected function format_dhl_method_name( $name ) {
		return ucwords( strtolower( $name ) );
	}

	protected function format_dhl_method_time( $date ) {
		$wp_date_format = get_option('date_format');
		$wp_time_format = get_option('time_format');
		return date( $wp_date_format, strtotime( $date ) ) . ', ' . date( $wp_time_format, strtotime( $date ) );
	}

	protected function get_rates_args()	{

		// $args['dhl_settings']['api_user'] = $this->get_option( 'dhl_api_user' );
		// $args['dhl_settings']['api_pwd'] = $this->get_option( 'dhl_api_pwd' );
		// $args['dhl_settings']['account_num'] = $this->get_option( 'dhl_account_num' );
		
		// Method settings
		$setting_ids = array( 'dhl_api_user','dhl_api_pwd', 'dhl_account_num', 'dhl_shipper_address','dhl_shipper_address2', 'dhl_shipper_address_city', 'dhl_shipper_address_state', 'dhl_shipper_address_zip' );

		foreach ($setting_ids as $value) {
			$api_key = str_replace('dhl_', '', $value);
			$setting_value = $this->get_option( $value );
			if ( isset( $setting_value ) ) {
				$args['dhl_settings'][ $api_key ] = htmlspecialchars_decode( $setting_value );
			}
		}

		$args['dhl_settings'][ 'shipper_country' ] = PR_DHL()->get_base_country();

		// Receiver address


		return $args;
	}
}

endif;
