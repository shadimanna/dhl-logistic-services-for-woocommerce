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

if ( ! class_exists( 'PR_DHL_WC_Order_eCS_Asia' ) ) :

class PR_DHL_WC_Order_eCS_Asia extends PR_DHL_WC_Order {
	
	protected $carrier = 'DHL eCS Asia';

	public function init_hooks() {
		parent::init_hooks();

		// add 'Label Created' orders page column header
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_label_column_header' ), 30 );

		// add 'Label Created' orders page column content
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_label_column_content' ) );

		// print DHL handover document
		add_action( 'admin_init', array( $this, 'print_document_action' ), 1 );

		// add bulk order filter for printed / non-printed orders
		add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_label_created') , 20 );
		add_filter( 'request',               array( $this, 'filter_orders_by_label_created_query' ) );
	}

	public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {

		$order 	= wc_get_order( $order_id );
		
		if( $this->is_crossborder_shipment( $order_id ) ) {
			
			// Duties drop down
			$duties_opt = $dhl_obj->get_dhl_duties();
			woocommerce_wp_select( array(
					'id'          		=> 'pr_dhl_duties',
					'label'       		=> __( 'Incoterms:', 'pr-shipping-dhl' ),
					'description'		=> '',
					'value'       		=> isset( $dhl_label_items['pr_dhl_duties'] ) ? $dhl_label_items['pr_dhl_duties'] : $this->shipping_dhl_settings['dhl_duties_default'],
					'options'			=> $duties_opt,
					'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );



		}

        // Get saved package description, otherwise generate the text based on settings
        if( ! empty( $dhl_label_items['pr_dhl_description'] ) ) {
            $selected_dhl_desc = $dhl_label_items['pr_dhl_description'];
        } else {
            $selected_dhl_desc = $this->get_package_description( $order_id );
        }

        woocommerce_wp_textarea_input( array(
            'id'          		=> 'pr_dhl_description',
            'label'       		=> __( 'Package description for customs (50 characters max): ', 'pr-shipping-dhl' ),
            'placeholder' 		=> '',
            'description'		=> '',
            'value'       		=> $selected_dhl_desc,
            'custom_attributes'	=> array( $is_disabled => $is_disabled, 'maxlength' => '50' )
        ) );

        if ( $this->is_shipping_domestic( $order_id ) ) {

            if( $this->is_cod_payment_method( $order_id ) ) {

                woocommerce_wp_checkbox( array(
                    'id'          		=> 'pr_dhl_is_cod',
                    'label'       		=> __( 'COD Enabled:', 'pr-shipping-dhl' ),
                    'placeholder' 		=> '',
                    'description'		=> '',
                    'value'       		=> isset( $dhl_label_items['pr_dhl_is_cod'] ) ? $dhl_label_items['pr_dhl_is_cod'] : 'yes',
                    'custom_attributes'	=> array( $is_disabled => $is_disabled )
                ) );
            }
        }

		woocommerce_wp_checkbox( array(
			'id'          		=> 'pr_dhl_additional_insurance',
			'label'       		=> __( 'Additional Insurance:', 'pr-shipping-dhl' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $dhl_label_items['pr_dhl_additional_insurance'] ) ? $dhl_label_items['pr_dhl_additional_insurance'] : $this->shipping_dhl_settings['dhl_default_additional_insurance'],
			'custom_attributes'	=> array( $is_disabled => $is_disabled )
		) );

		woocommerce_wp_text_input( array(
			'id'          		=> 'pr_dhl_insurance_value',
			'class'          	=> 'wc_input_decimal',
			'label'       		=> __( 'Insurance Value:', 'pr-shipping-dhl' ),
			'placeholder' 		=> '',
			'description'		=> '',
			'value'       		=> isset( $dhl_label_items['pr_dhl_insurance_value'] ) ? $dhl_label_items['pr_dhl_insurance_value'] : $order->get_subtotal(),
			'custom_attributes'	=> array( $is_disabled => $is_disabled )
	) );
		
		if( $this->is_shipping_domestic( $order_id ) ) {

			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_obox_service',
				'label'       		=> __( 'Open Box Service:', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_obox_service'] ) ? $dhl_label_items['pr_dhl_obox_service'] : $this->shipping_dhl_settings['dhl_default_obox_service'],
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

		}

	}
	

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids( ) {

		return array( 'pr_dhl_duties', 'pr_dhl_description', 'pr_dhl_is_cod', 'pr_dhl_additional_insurance', 'pr_dhl_insurance_value', 'pr_dhl_obox_service' );

	}
	
	protected function get_tracking_url() {
		return PR_DHL_ECS_ASIA_TRACKING_URL;
	}

	protected function get_package_description( $order_id ) {
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		$dhl_desc_default = $this->shipping_dhl_settings['dhl_desc_default'];
		$order = wc_get_order( $order_id );
		$ordered_items = $order->get_items();

		$desc_array = array();
		foreach ($ordered_items as $key => $item) {
			$product_id = $item['product_id'];
			$product = wc_get_product( $product_id );
			
			// If product does not exist, i.e. deleted go to next one
			if ( empty( $product ) ) {
				continue;
			}

			switch ($dhl_desc_default) {
				case 'product_cat':
					$product_terms = get_the_terms( $product_id, 'product_cat' );
					if ( $product_terms ) {
						foreach ($product_terms as $key => $product_term) {
							array_push( $desc_array, $product_term->name );
						}
					}
					break;
				case 'product_tag':
					$product_terms = get_the_terms( $product_id, 'product_tag' );
					if ( $product_terms ) {
						foreach ($product_terms as $key => $product_term) {
							array_push( $desc_array, $product_term->name );
						}
					}
					break;
				case 'product_name':
					array_push( $desc_array, $product->get_title() );
					break;
				case 'product_export':
					$export_desc = get_post_meta( $product_id, '_dhl_export_description', true );
					array_push( $desc_array, $export_desc );
					break;
			}
		}

		// Make sure there are no duplicate taxonomies
		$desc_array = array_unique($desc_array);
		$desc_text = implode(', ', $desc_array);
		$desc_text = mb_substr( $desc_text, 0, 50, 'UTF-8' );
		
		return $desc_text;
	}

	protected function get_label_args_settings( $order_id, $dhl_label_items ) {
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		$order = wc_get_order( $order_id );

		// Get DHL pickup and distribution center
		$args['dhl_settings']['dhl_api_key'] = $this->shipping_dhl_settings['dhl_api_key'];
		$args['dhl_settings']['dhl_api_secret'] = $this->shipping_dhl_settings['dhl_api_secret'];
		$args['dhl_settings']['pickup_id'] = $this->shipping_dhl_settings['dhl_pickup_id'];
		$args['dhl_settings']['soldto_id'] = $this->shipping_dhl_settings['dhl_soldto_id'];
//		$args['dhl_settings']['handover'] = $this->get_label_handover_num();
		$args['dhl_settings']['label_format'] = $this->shipping_dhl_settings['dhl_label_format'];
		$args['dhl_settings']['label_layout'] = $this->shipping_dhl_settings['dhl_label_layout'];

		// Get DHL Pickup Address.
		$args[ 'dhl_settings' ]['dhl_contact_name'] 	= $this->shipping_dhl_settings['dhl_contact_name'];
		$args[ 'dhl_settings' ]['dhl_address_1'] 		= $this->shipping_dhl_settings['dhl_shipper_address_1'];
		$args[ 'dhl_settings' ]['dhl_address_2'] 		= $this->shipping_dhl_settings['dhl_shipper_address_2'];
		$args[ 'dhl_settings' ]['dhl_city'] 			= $this->shipping_dhl_settings['dhl_shipper_address_city'];
		$args[ 'dhl_settings' ]['dhl_state'] 			= $this->shipping_dhl_settings['dhl_shipper_address_state'];
		$args[ 'dhl_settings' ]['dhl_district'] 		= $this->shipping_dhl_settings['dhl_shipper_address_district'];
		$args[ 'dhl_settings' ]['dhl_country'] 			= $this->shipping_dhl_settings['dhl_shipper_address_country'];
		$args[ 'dhl_settings' ]['dhl_postcode'] 		= $this->shipping_dhl_settings['dhl_shipper_address_postcode'];
		$args[ 'dhl_settings' ]['dhl_phone'] 			= $this->shipping_dhl_settings['dhl_phone'];
		$args[ 'dhl_settings' ]['dhl_email'] 			= $this->shipping_dhl_settings['dhl_email'];

		// Get package prefix
		$args['order_details']['prefix'] = $this->shipping_dhl_settings['dhl_prefix'];
		
		// Get package prefix
		if ( ! empty( $dhl_label_items['pr_dhl_description'] ) ) {
			$args['order_details']['description'] = $dhl_label_items['pr_dhl_description'];
		} else {
			// If description is empty and it is an international shipment throw an error
			if ( $this->is_crossborder_shipment( $order_id ) ) {
				throw new Exception( __('The package description cannot be empty!', 'pr-shipping-dhl') );
				
			}			
		}

		if ( isset( $this->shipping_dhl_settings['dhl_order_note'] ) && $this->shipping_dhl_settings['dhl_order_note'] == 'yes' ) {

			if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
				$args['order_details']['order_note'] = $order->get_customer_note();
			} else {
				$args['order_details']['order_note'] = $order->customer_note;
			}
		}

		if ( ! empty( $dhl_label_items['pr_dhl_duties'] ) ) {
			$args['order_details']['duties'] = $dhl_label_items['pr_dhl_duties'];
		}

		if ( ! empty( $dhl_label_items['pr_dhl_is_cod'] ) ) {
			$args['order_details']['is_cod'] = $dhl_label_items['pr_dhl_is_cod'];
		}

		if ( ! empty( $dhl_label_items['pr_dhl_additional_insurance'] ) ) {
			$args['order_details']['additional_insurance'] = $dhl_label_items['pr_dhl_additional_insurance'];
		}

		if ( ! empty( $dhl_label_items['pr_dhl_insurance_value'] ) ) { 
			$args['order_details']['insurance_value'] = $dhl_label_items['pr_dhl_insurance_value'];
		}

		if ( ! empty( $dhl_label_items['pr_dhl_obox_service'] ) ) {
			$args['order_details']['obox_service'] = $dhl_label_items['pr_dhl_obox_service'];
		}

		return $args;
	}
	
	// Pass args by reference to modify DG if needed
	protected function get_label_item_args( $product_id, &$args ) {

		$new_item = array();
		$dangerous_goods = get_post_meta( $product_id, '_dhl_dangerous_goods', true );

        if( ! empty( $dangerous_goods ) ) {

	    	if ( isset( $args['order_details']['dangerous_goods'] ) ) {
	    		// if more than one item id DG, make sure to take the minimum value
	    		$args['order_details']['dangerous_goods'] = min( $args['order_details']['dangerous_goods'], $dangerous_goods );
	    	} else {
	    		$args['order_details']['dangerous_goods'] = $dangerous_goods;
	    	}
	    	
		}

		$new_item['item_export'] 		= get_post_meta( $product_id, '_dhl_export_description', true );

		return $new_item;
	}

	protected function save_default_dhl_label_items( $order_id ) {

        $order = wc_get_order( $order_id );

		parent::save_default_dhl_label_items( $order_id );

		$dhl_label_items = $this->get_dhl_label_items( $order_id );
		
		if( empty( $dhl_label_items['pr_dhl_description'] ) ) {
			$dhl_label_items['pr_dhl_description'] = $this->get_package_description( $order_id );
		}

		if( empty( $dhl_label_items['pr_dhl_duties'] ) ) {
			$dhl_label_items['pr_dhl_duties'] = $this->shipping_dhl_settings['dhl_duties_default'];
		}

		if( empty( $dhl_label_items['pr_dhl_is_cod'] ) ) {
			$dhl_label_items['pr_dhl_is_cod'] = $this->is_cod_payment_method( $order_id ) ? 'yes' : 'no';
		}

		$settings_default_ids = array(
			'pr_dhl_additional_insurance',
			'pr_dhl_obox_service'
		);

		foreach ($settings_default_ids as $default_id) {
			$id_name = str_replace('pr_dhl_', '', $default_id);

			if ( !isset($dhl_label_items[$default_id]) ) {
				$dhl_label_items[$default_id] = isset( $this->shipping_dhl_settings['dhl_default_' . $id_name] ) ? $this->shipping_dhl_settings['dhl_default_' . $id_name] : '';
			}
		}

        if( empty( $dhl_label_items['pr_dhl_insurance_value'] ) ) {
            $dhl_label_items['pr_dhl_insurance_value'] = $order->get_subtotal();
        }

		$this->save_dhl_label_items( $order_id, $dhl_label_items );
	}

	// Used by label API to pass handover number
	protected function get_label_handover_num() {
		// If handover exists, use it...
		$handover_num = get_option('woocommerce_pr_dhl_handover');

		// ... otherwise generate a new one
		if( empty( $handover_num ) ) {
			$handover_num = $this->generate_handover();
			add_option( 'woocommerce_pr_dhl_handover', $handover_num );
		}

		return $handover_num;
	}

	// Used by Handover note creation
	protected function get_handover_num() {
		// If handover exists, use it...
		$handover_num = get_option('woocommerce_pr_dhl_handover');

		// If don't exist create a new one (but don't save it for future use)
		if( empty( $handover_num ) ) {
			$handover_num = $this->generate_handover();
		} else {
			// ...and delete it
			delete_option('woocommerce_pr_dhl_handover');
		}

		return $handover_num;
	}


	protected function generate_handover() {
		return '8' . mt_rand( 1000000000, 9999999999 );
	}
	/*
	*
	* HANDOVER CODE
	*
	*/

	public function add_order_label_column_header( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['dhl_label_created']      = __( 'DHL Label Created', 'pr-shipping-dhl' );
				$new_columns['dhl_tracking_number']    = __( 'DHL Tracking Number', 'pr-shipping-dhl' );
				$new_columns['dhl_handover_note']      = __( 'DHL Handover Created', 'pr-shipping-dhl' );
			}
		}

		return $new_columns;
	}

	public function add_order_label_column_content( $column ) {
		global $post;

		$order_id = $post->ID;

		if ( $order_id ) {
			if( 'dhl_label_created' === $column ) {
				echo $this->get_print_status( $order_id );
			}

			if( 'dhl_tracking_number' === $column ) {
				$tracking_link = $this->get_tracking_link( $order_id );
				echo empty($tracking_link) ? '<strong>&ndash;</strong>' : $tracking_link;
			}

			if( 'dhl_handover_note' === $column ) {
				echo $this->get_hangover_status( $order_id );
			}
		}
	}

	protected function get_download_label_url( $order_id ) {
		
		if( empty( $order_id ) ) {
			return '';
		}

		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		// Check whether the label has already been created or not
		if( empty( $label_tracking_info ) ) {
			return '';
		}
		
		// If no 'label_path' isset but a 'label_url' is set them return it...
		// ... this indicates an old download style label!
		if ( isset( $label_tracking_info['label_url'] ) ){
			return $label_tracking_info['label_url'];
		}

		// Override URL with our solution's download label endpoint:
		return $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/' . $order_id );
	}

	private function get_print_status( $order_id ) {
		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );

		if( empty( $label_tracking_info ) ) {
			return '<strong>&ndash;</strong>';
		} else {
			return '&#10004';
		}
	}

	private function get_hangover_status( $order_id ) {
		$handover = get_post_meta( $order_id, '_pr_shipment_dhl_handover_note', true );

		if( empty( $handover ) ) {
			return '<strong>&ndash;</strong>';
		} else {
			return '&#10004';
		}
	}

	public function get_bulk_actions() {

		$shop_manager_actions = array(
			'pr_dhl_create_labels'      => __( 'DHL Create Labels', 'pr-shipping-dhl' ),
            'pr_dhl_handover'      => __( 'DHL Print Handover', 'pr-shipping-dhl' )
		);

		return $shop_manager_actions;
	}

	public function validate_bulk_actions( $action, $order_ids ) {
		$message = '';
		if ( 'pr_dhl_handover' === $action ) {
			// Ensure the selected orders have a label created, otherwise don't create handover
			foreach ( $order_ids as $order_id ) {
				$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
				if( empty( $label_tracking_info ) ) {
					$message = __( 'One or more orders do not have a DHL label created, please ensure all DHL labels are created for each order before creating a handoff document.', 'pr-shipping-dhl' );
				}
			}
		}

		return $message;
	}

	public function process_bulk_actions( $action, $order_ids, $orders_count, $dhl_force_product = false, $is_force_product_dom = false ) {

		$array_messages = array();
		
		$action_arr = explode(':', $action);
		if ( ! empty( $action_arr ) ) {
			$action = $action_arr[0];

			if ( isset( $action_arr[1] ) && ($action_arr[1] == 'dom') ) {
				$is_force_product_dom = true;
			} else {
				$is_force_product_dom = false;
			}

			if ( isset( $action_arr[2] ) ) {
				$dhl_force_product = $action_arr[2];
			}
		}

		$array_messages += parent::process_bulk_actions( $action, $order_ids, $orders_count, $dhl_force_product, $is_force_product_dom );

		if ( 'pr_dhl_handover' === $action ) {
			$redirect_url  = admin_url( 'edit.php?post_type=shop_order' );
			$order_ids_hash = md5( json_encode( $order_ids ) );
			// Save the order IDs in a option.
			// Initially we were using a transient, but this seemed to cause issues
			// on some hosts (mainly GoDaddy) that had difficulty in implementing a
			// proper object cache override.
			update_option( 'pr_dhl_handover_order_ids_' . $order_ids_hash, $order_ids );

			$action_url = wp_nonce_url(
				add_query_arg(
					array(
						'pr_dhl_action'   => 'print',
						'order_id'        => $order_ids[0],
						'order_ids'       => $order_ids_hash,
					),
					'' !== $redirect_url ? $redirect_url : admin_url()
				),
				'pr_dhl_handover'
			);

			$print_link = '<a href="' . $action_url .'" target="_blank">' . __( 'Print DHL handover.', 'pr-shipping-dhl' ) . '</a>';

			$message = sprintf( __( 'DHL handover for %1$s order(s) created. %2$s', 'pr-shipping-dhl' ), $orders_count, $print_link );

			array_push($array_messages, array(
                'message' => $message,
                'type' => 'success',
            ));
		}

		return $array_messages;
	}

	protected function get_bulk_settings_override( $args ) {
		// Override duties to take default settings value for bulk only
		$args['order_details']['duties'] = $this->shipping_dhl_settings['dhl_duties_default'];
		return $args;
	}

	public function print_document_action() {

		// listen for 'print' action query string
		if ( isset( $_GET['pr_dhl_action'] ) && 'print' === $_GET['pr_dhl_action'] ) {

			$nonce = isset( $_REQUEST['_wpnonce'] ) ? $_REQUEST['_wpnonce'] : '';

			// security admin/frontend checks
			if ( ! $nonce || ! wp_verify_nonce( $nonce, 'pr_dhl_handover' ) ) {
				die( __( 'You are not allowed to view this page.', 'pr-shipping-dhl' ) );
			}

			$order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;

			// Get order IDs temporary option.
			$order_ids_hash = isset( $_GET['order_ids'] ) ? $_GET['order_ids'] : '';
			$order_ids      = empty( $order_ids_hash )    ? array()            : get_option( 'pr_dhl_handover_order_ids_' . $order_ids_hash );
			$order_ids      = false === $order_ids        ? array()            : $order_ids;

			if ( empty( $order_ids ) ) {
				die( __( 'The DHL handover is not valid, please regenerate anothor one!', 'pr-shipping-dhl' ) );
			}

			// Since this is not a transient, we delete it manually.
			delete_option( 'pr_dhl_handover_order_ids_' . $order_ids_hash );

			// Generate the handover id random number (10 digits) with prefix '8'
			$handover_id = $this->get_handover_num();
			$total_weight = 0;
			$dhl_products = array();
			$items_qty = sizeof($order_ids);

			try {
				
				// Get list of all DHL products and change key to name
				$dhl_obj = PR_DHL()->get_dhl_factory();
				$dhl_product_list = $dhl_obj->get_dhl_products_domestic() + $dhl_obj->get_dhl_products_international();
			} catch (Exception $e) {
				die( sprintf( __( 'Cannot generate handover %s', 'pr-shipping-dhl' ), $e->getMessage() ) );
			}

			foreach ($order_ids as $order_id) {
				$dhl_label_items = $this->get_dhl_label_items( $order_id );

				if ( empty( $dhl_label_items ) ) {
					continue;
				}
				
				// Add all weights
				$total_weight += $dhl_label_items['pr_dhl_weight'];

				$dhl_label_product = $dhl_product_list[ $dhl_label_items['pr_dhl_product'] ];

				array_push( $dhl_products, $dhl_label_product );

				// Add post meta to identify if added to handover or not
				update_post_meta( $order_id, '_pr_shipment_dhl_handover_note', 1 );
			}
			// There should a unique list of products listed not one for each order!
			$dhl_products = array_unique($dhl_products);

			$args =  array(
 				'handover_id' => $handover_id,
 				'items_qty' => $items_qty,
 				'total_weight' => $total_weight,
 				'dhl_products' => $dhl_products
			);

			$args = apply_filters( 'pr_shipping_dhl_handover_args', $args, $order_ids );

			$this->print_document( $args );

			exit;
		}
	}

	public function print_document( $template_args ) {

		if ( empty( $template_args ) ) {
			die( __( 'The DHL handover cannot be generated, arguments missing.', 'woocommerce-pip' ) );
		}

		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );

		wc_get_template( 'dhl-handover/styles.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
		wc_get_template( 'dhl-handover/head.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
		wc_get_template( 'dhl-handover/body.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
		wc_get_template( 'dhl-handover/foot.php', $template_args, '', PR_DHL_PLUGIN_DIR_PATH . '/templates/' );
	}

	// Add filters for created (or not) labels
	public function filter_orders_by_label_created() {
		global $typenow;

		if ( 'shop_order' === $typenow ) :

			$options  = array(
				'dhl_label_not_created'    => __( 'DHL Label Not Created', 'pr-shipping-dhl' ),
				'dhl_label_created'        => __( 'DHL Label Created', 'pr-shipping-dhl' ),
			);

			$selected = isset( $_GET['_shop_order_dhl_label_created'] ) ? $_GET['_shop_order_dhl_label_created'] : '';

			?>
			<select name="_shop_order_dhl_label_created" id="dropdown_shop_order_dhl_label_created">
				<option value=""><?php esc_html_e( 'Show all DHL label statuses', 'pr-shipping-dhl' ); ?></option>
				<?php foreach ( $options as $option_value => $option_name ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $selected, $option_value ); ?>><?php echo esc_html( $option_name ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php

		endif;
	}

	// Filter orders by created labels
	public function filter_orders_by_label_created_query( $vars ) {
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_dhl_label_created'] ) ) {

			$meta    = '';
			$compare = '';
			$value   = '';

			switch ( $_GET['_shop_order_dhl_label_created'] ) {
				case 'dhl_label_not_created' :
					$meta    = '_pr_shipment_dhl_label_tracking';
					$compare = 'NOT EXISTS';
				break;
				case 'dhl_label_created' :
					$meta    = '_pr_shipment_dhl_label_tracking';
					$compare = '>';
					$value   = '0';
				break;
			}

			if ( $meta && $compare ) {
				$vars['meta_key']     = $meta;
				$vars['meta_value']   = $value;
				$vars['meta_compare'] = $compare;
			}
		}

		return $vars;
	}

	protected function merge_label_files_png( $files ) {

		if( empty( $files ) ) {
			throw new Exception( __('There are no files to merge.', 'pr-shipping-dhl') );
		}

		if( ! class_exists('Imagick') ) {
			throw new Exception( __('"Imagick" must be installed on the server to merge png files.', 'pr-shipping-dhl') );
		}

		$all = new Imagick();
		foreach ($files as $key => $value) {

			if ( ! file_exists( $value ) ) {
				// throw new Exception( __('File does not exist', 'pr-shipping-dhl') );
				continue;
			}

			$ext = pathinfo($value, PATHINFO_EXTENSION);
			if ( stripos($ext, 'png') === false) {
				throw new Exception( __('Not all the file formats are the same.', 'pr-shipping-dhl') );
			}

			$im = new Imagick($value);       
    		$all->addImage( $im );
		}

		$filename = 'dhl-label-bulk-' . time() . '.png';
		$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
		$file_bulk_url = PR_DHL()->get_dhl_label_folder_url() . $filename;

		/* Append the images into one */
		$all->resetIterator();
		$combined = $all->appendImages(true);
		// $ima = $im1->appendImages(true); 
		$combined->setImageFormat('png');
		$combined->writeimage( $file_bulk_path );
		
		return array( 'file_bulk_path' => $file_bulk_path, 'file_bulk_url' => $file_bulk_url);
	}

	protected function merge_label_files_zpl( $files ) {

		if( empty( $files ) ) {
			throw new Exception( __('There are no files to merge.', 'pr-shipping-dhl') );
		}

		$files_content = '';
		foreach ($files as $key => $value) {

			if ( ! file_exists( $value ) ) {
				// throw new Exception( __('File does not exist', 'pr-shipping-dhl') );
				continue;
			}

			$ext = pathinfo($value, PATHINFO_EXTENSION);
			if ( stripos($ext, 'zpl') === false) {
				throw new Exception( __('Not all the file formats are the same.', 'pr-shipping-dhl') );
			}

			$files_content .= file_get_contents( $value );
		}

		$filename = 'dhl-label-bulk-' . time() . '.zpl';
		$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
		$file_bulk_url = PR_DHL()->get_dhl_label_folder_url() . $filename;
		
		$fp1 = fopen($file_bulk_path, 'a+');
		fwrite($fp1, $files_content);

		return array( 'file_bulk_path' => $file_bulk_path, 'file_bulk_url' => $file_bulk_url);
	}
}

endif;
