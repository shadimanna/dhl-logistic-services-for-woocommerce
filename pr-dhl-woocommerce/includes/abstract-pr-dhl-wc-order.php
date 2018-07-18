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

if ( ! class_exists( 'PR_DHL_WC_Order' ) ) :

abstract class PR_DHL_WC_Order {
	
	protected $id = '';
	protected $title = '';

	/**
	 * Init and hook in the integration.
	 */
	public function __construct() {
		// error_log('order contrutor');
		// error_log(__CLASS__);
		$this->id = 'woocommerce-shipment-dhl-label';
		$this->title = __( 'DHL Label & Tracking', 'pr-shipping-dhl' );

		$this->define_constants();
		$this->init_hooks();
	}

	protected function define_constants() {
		PR_DHL()->define( 'PR_DHL_BUTTON_LABEL_GEN', __( 'Generate Label', 'pr-shipping-dhl' ) );
		PR_DHL()->define( 'PR_DHL_BUTTON_LABEL_PRINT', __( 'Download Label', 'pr-shipping-dhl' ) );
	}

	public function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 2 );

		$subs_version = class_exists( 'WC_Subscriptions' ) && ! empty( WC_Subscriptions::$version ) ? WC_Subscriptions::$version : null;

		// Prevent data being copied to subscriptions
		if ( null !== $subs_version && version_compare( $subs_version, '2.0.0', '>=' ) ) {
			add_filter( 'wcs_renewal_order_meta_query', array( $this, 'woocommerce_subscriptions_renewal_order_meta_query' ), 10 );
		} else {
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'woocommerce_subscriptions_renewal_order_meta_query' ), 10 );
		}

	}

	public function get_dhl_obj() {
		return PR_DHL()->get_dhl_factory();
	}

	public function get_shipping_dhl_settings() {
		return PR_DHL()->get_shipping_dhl_settings();
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function add_meta_box() {
		error_log($this->id);
		error_log($this->title);
		add_meta_box( $this->id, $this->title, array( $this, 'meta_box' ), 'shop_order', 'side', 'high' );

		// Create a new meta box for the DHL Invoice Uploader widget
		add_meta_box( 'woocommerce-shipment-dhl-invoice-upload', 'DHL Invoice Upload', array( $this, 'invoice_upload' ), 'shop_order', 'side', 'high' );
	}

	/**
	 * Renders the meta box content for the invoice uploader feature
	 *
	 * @access public
	 */
	public function invoice_upload() {

		$commercial_invoice = PR_DHL_PLUGIN_DIR_URL . '/assets/pdf/commercial_invoice.pdf';
		woocommerce_wp_text_input( array(
				'id'	          	=> 'pr_dhl_invoice',
				'name'          	=> 'pr_dhl_invoice',
				'type'          	=> 'file',
				'label'       		=>  __( 'Select an invoice to upload: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> sprintf( __('Download a sample template for commercial invoice %shere%s.', 'pr-shipping-dhl'), '<a href="' . $commercial_invoice . '" target="_blank">', '</a>'),
				'custom_attributes'	=> array( $is_disabled => $is_disabled ),
				'class'				=> ''
			) );

		$main_button = '<button class="upload-invoice-button button button-primary button-upload-form">' . __('Upload', 'pr-shipping-dhl') . '</button><div class="dhl-invoice-upload-spinner-container"><div class="spinner"></div><div class="dhl-invoice-upload-message" style="font-size: 11px;">'.sprintf( __('Upload complete. Preview %shere%s.', 'pr-shipping-dhl'), '<a id="dhl-invoice-upload-url" href="#" target="_blank">', '</a>').'</div></div>';

		echo $main_button;
	}

	/**
	 * Validates and processes the submitted invoice for upload from an ajax request
	 *
	 * @access public
	 */
	public function upload_invoice_ajax() {

	    $file = $_FILES[ 'file' ];
	    $order_id = $_REQUEST[ 'order_id' ];
	    $supported_types = [ 'image/png', 'image/jpg', 'image/jpeg', 'application/pdf' ];

	    // Extract the actual mime content type of the uploaded file.
	    $file_info = finfo_open( FILEINFO_MIME_TYPE );
    	$uploaded_file_type = finfo_file( $file_info, $file[ 'tmp_name' ] );
    	finfo_close( $file_info );

    	// Check whether the user uploaded a supported file types for the invoice
	    if ( in_array( $uploaded_file_type, $supported_types ) ) {
	    	$info = pathinfo( $file[ 'name' ] );
	    	$file_name = !empty( $info[ 'filename' ] ) ? $info[ 'filename' ] : explode( $file[ 'name' ], '.' )[0];
	    	$extension = $info[ 'extension' ];

	    	// If you want to change the new file name (uploaded name) format, you can do it here.
	    	// Currently, it has the following format "order{ORDER_ID}_{FILENAME}_{TIME_IN_EPOCH_FORMAT}.{FILE_EXTENSION}".
            $upload_file_name = 'order'.$order_id.'_'.$file_name.'_'.time();
            if ( !empty( $extension ) ) {
            	$upload_file_name .= '.'.$extension;
            }

            $upload_dir = wp_upload_dir();
            if ( move_uploaded_file( $file[ 'tmp_name' ], $upload_dir[ 'path' ] . '/' . $upload_file_name ) ) {

            	// Wrap some information that will be useful for others to consume and apply with their
            	// own custom actions.
                $uploaded_file[ 'orig_file_name' ] = $file[ 'name' ];
                $uploaded_file[ 'size' ] = $file[ 'size' ];
                $uploaded_file[ 'type' ] = $uploaded_file_type;
                $uploaded_file[ 'upload_url' ] = $upload_dir[ 'url' ] . '/' . $upload_file_name;
                $uploaded_file[ 'upload_path' ] = $upload_dir[ 'path' ] . '/' . $upload_file_name;
                $uploaded_file[ 'upload_datetime' ] = date( 'Y-m-d H:i:s' );

                $attachment = array(
                    'guid'           => $uploaded_file[ 'upload_url' ],
                    'post_mime_type' => $file[ 'type' ],
                    'post_title'     => $upload_file_name,
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                );

                // For now, our invoice will serve as a non-binding attachment... meaning, we're not setting its parent
                // currently. But if you wish to bind this to a certain post type, you can add an additional 3rd parameter
                // to the "wp_insert_attachment" API.
                $uploaded_file[ 'attach_id' ] = wp_insert_attachment( $attachment , $uploaded_file[ 'upload_path' ] );
                if ( 'application/pdf' !== $uploaded_file_type ) {
	                require_once( ABSPATH . 'wp-admin/includes/image.php' );

	                //Generate the metadata for the attachment, and update the database record.
	                $attach_data = wp_generate_attachment_metadata( $uploaded_file[ 'attach_id' ] , $uploaded_file[ 'upload_path' ] );
	                wp_update_attachment_metadata( $uploaded_file[ 'attach_id' ], $attach_data );
	            } else {
	            	// For non-image invoice we go straight to adding the meta data directly
	            	add_post_meta( $uploaded_file[ 'attach_id' ], '_wp_attachment_metadata', $attach_data );
	            }

                // Let others do anything as they wish with the newly uploaded invoice
                do_action( 'dhl_invoice_uploaded', $uploaded_file, $order_id );

                // The action hook above will be enough if we're going to supply additional actions after
                // a successful invoice upload. Nevertheless, we're returning the "upload_url" to the originator
                // of the request (e.g. client) in case we want to have a preview of the uploaded document/invoice
                // and present it to the user. 
                $result = array(
                	'code' => 'upload_complete',
                	'upload_url' => $uploaded_file[ 'upload_url' ]
                );
            } else {
            	$result = array(
		        	'code' => 'upload_failed',
		        	'error_message' => __( 'An error has occurred while processing your submitted invoice. Please kindly check your permission when uploading files to the server and try again.', 'pr-shipping-dhl' )
		        );
            }
	    } else {
	        $result = array(
	        	'code' => 'unsupported_file_types',
	        	'error_message' => __( 'Sorry, it appears that you have submitted an unsupported invoice file. Supported invoice file types are png, jpeg, jpg and pdf files.', 'pr-shipping-dhl' )
	        );
	    }

	    wp_send_json( $result );
	}

	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function meta_box() {
		global $woocommerce, $post;
		
		$order_id = $post->ID;	
		// Get saved label input fields or set default values
		$dhl_label_items = $this->get_dhl_label_items( $order_id );

		// Get saved weight, otherwise calculate it from the item weights
		if( ! empty( $dhl_label_items['pr_dhl_weight'] ) ) {
			$selected_weight_val = $dhl_label_items['pr_dhl_weight'];
		} else {
			$selected_weight_val = $this->calculate_order_weight( $order_id );
		}

		// Get saved product, otherwise get the default product in settings
		if( ! empty( $dhl_label_items['pr_dhl_product'] ) ) {
			$selected_dhl_product = $dhl_label_items['pr_dhl_product'];
		} else {
			$shipping_dhl_settings = $this->get_shipping_dhl_settings();

			if( $this->is_shipping_domestic( $order_id ) ) {
				$selected_dhl_product = $shipping_dhl_settings['dhl_default_product_dom'];
			} else {
				$selected_dhl_product = $shipping_dhl_settings['dhl_default_product_int'];
			}
		}

		// Get the list of domestic and international DHL services
		try {		
			$dhl_obj = $this->get_dhl_obj();

			if( $this->is_shipping_domestic( $order_id ) ) {
				$dhl_product_list = $dhl_obj->get_dhl_products_domestic();
			} else {
				$dhl_product_list = $dhl_obj->get_dhl_products_international();
			}

		} catch (Exception $e) {

			echo '<p class="wc_dhl_error">' . $e->getMessage() . '</p>';
		}
		
		$delete_label = '<span class="wc_dhl_delete"><a href="#" class="dhl_delete_label">' . __('Delete Label', 'pr-shipping-dhl') . '</a></span>';

		$main_button = '<button class="dhl-label-button button button-primary button-save-form">' . PR_DHL_BUTTON_LABEL_GEN . '</button>';

		// Get tracking info if it exists
		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		// Check whether the label has already been created or not
		if( empty( $label_tracking_info ) ) {
			$is_disabled = '';
			
			$print_button = '<a href="#" class="dhl-label-print button button-primary" download>' .PR_DHL_BUTTON_LABEL_PRINT . '</a>';

		} else {
			$is_disabled = 'disabled';

			$print_button = '<a href="'. $label_tracking_info['label_url'] .'" class=" dhl-label-print button button-primary" download>' .PR_DHL_BUTTON_LABEL_PRINT . '</a>';
		}

		$dhl_label_data = array(
			'main_button' => $main_button,
			'delete_label' => $delete_label,
			'print_button' => $print_button
		);


		echo '<div class="shipment-dhl-label-form">';

		if( !empty( $dhl_product_list ) ) {
			
			woocommerce_wp_hidden_input( array(
				'id'    => 'pr_dhl_label_nonce',
				'value' => wp_create_nonce( 'create-dhl-label' )
			) );

			woocommerce_wp_select ( array(
				'id'	          	=> 'pr_dhl_product',
				'name'          	=> 'pr_dhl_product',
				'label'       		=> __( 'DHL service selected:', 'pr-shipping-dhl' ),
				'description'		=> '',
				'value'       		=> $selected_dhl_product,
				'options'			=> $dhl_product_list,
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );

			$weight_units = get_option( 'woocommerce_weight_unit' );
			// Get weight UoM and add in label
			woocommerce_wp_text_input( array(
				'id'	          	=> 'pr_dhl_weight',
				'name'          	=> 'pr_dhl_weight',
				'label'       		=> sprintf( __( 'Estimated shipment weight (%s) based on items ordered: ', 'pr-shipping-dhl' ), $weight_units),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> $selected_weight_val,
				'custom_attributes'	=> array( $is_disabled => $is_disabled ),
				'class'				=> 'wc_input_decimal' // adds JS to validate input is in price format
			) );

			$this->additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );


			// A label has been generated already, allow to delete
			if( empty( $label_tracking_info ) ) {
				echo $main_button;
			} else {
				echo $print_button;
				echo $delete_label;
			}
			
			wp_enqueue_script( 'wc-shipment-dhl-label-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl.js', array(), PR_DHL_VERSION );
			wp_localize_script( 'wc-shipment-dhl-label-js', 'dhl_label_data', $dhl_label_data );
			
		} else {
			echo '<p class="wc_dhl_error">' . __('There are no DHL services available for the destination country!', 'pr-shipping-dhl') . '</p>';
		}
		
		echo '</div>';
		
	}
	
	abstract public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );


	public function save_meta_box( $post_id, $post = null ) {
		// loop through inputs within id 'shipment-dhl-label-form'
		$meta_box_ids = array( 'pr_dhl_product', 'pr_dhl_weight');
		
		$additional_meta_box_ids = $this->get_additional_meta_ids( );

		// $meta_box_ids += $additional_meta_box_ids;
		$meta_box_ids = array_merge( $meta_box_ids, $additional_meta_box_ids );
		foreach ($meta_box_ids as $key => $value) {
			// Save value if it exists
			if ( isset( $_POST[ $value ] ) ) {
				$args[ $value ]	 = wc_clean( $_POST[ $value ] );
			}
		}		

		$this->save_dhl_label_items( $post_id, $args );

		return $args;
	}

	abstract public function get_additional_meta_ids();
	/**
	 * Order Tracking Save AJAX
	 *
	 * Function for saving tracking items
	 */
	public function save_meta_box_ajax() {
		error_log('save_meta_box_ajax');
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		// Save inputted data first
		$dhl_label_args = $this->save_meta_box( $order_id );
		
		try {

			// Gather args for DHL API call
			$args = $this->get_label_args( $order_id, $dhl_label_args );

			// Allow third parties to modify the args to the DHL APIs
			$args = apply_filters('pr_shipping_dhl_label_args', $args, $order_id );

			$dhl_obj = $this->get_dhl_obj();
			$label_tracking_info = $dhl_obj->get_dhl_label( $args );

			$this->save_dhl_label_tracking( $order_id, $label_tracking_info );
			$tracking_note = $this->get_tracking_link( $label_tracking_info['tracking_number'] );
			$label_url = $label_tracking_info['label_url'];

			wp_send_json( array( 
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'pr-shipping-dhl'),
				'button_txt' => PR_DHL_BUTTON_LABEL_PRINT,
				'label_url' => $label_url,
				'tracking_note'	  => $tracking_note
				) );

			do_action( 'pr_shipping_dhl_label_created', $order_id );

		} catch ( Exception $e ) {

			wp_send_json( array( 'error' => $e->getMessage() ) );
		}
		
		wp_die();
	}

	public function delete_label_ajax( ) {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		try {

			$args = $this->delete_label_args( $order_id );
			$dhl_obj = $this->get_dhl_obj();
			
			$dhl_obj->delete_dhl_label( $args );
			$this->delete_dhl_label_tracking( $order_id );
			
			$tracking_num = $args['tracking_number'];

			wp_send_json( array( 
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'pr-shipping-dhl'), 
				'button_txt' => PR_DHL_BUTTON_LABEL_GEN, 
				'dhl_tracking_num'	  => $tracking_num
				) );

		} catch (Exception $e) {

			wp_send_json( array( 'error' => $e->getMessage() ) );
		}
	}

	protected function get_label_url( $label_url ) {
		
		if( empty( $label_url ) ) {
			return '';
		}

		$ext = pathinfo($label_url, PATHINFO_EXTENSION);
		$ext = strtoupper($ext);
		$download_ext = array( 'ZPL' );

		if( in_array($ext, $download_ext) ) {
			$nonce = wp_create_nonce( 'download-dhl-label' );

			$new_label_url = PR_DHL_PLUGIN_DIR_URL . '/lib/download.php';
			$upload_path = wp_upload_dir();
			$label_url = str_replace($upload_path['url'], $upload_path['path'], $label_url);

			$new_label_url .= '?path=' . $label_url . '&nonce=' . $nonce;

			$label_url = $new_label_url;
		}		
		
		return $label_url;
	}

	protected function get_tracking_link( $tracking_num ) {
		if( empty( $tracking_num ) ) {
			return '';
		}

		$tracking_note = sprintf( __( '<label>DHL Tracking Number: </label><a href="%s%s" target="_blank">%s</a>', 'pr-shipping-dhl' ), PR_DHL_ECOMM_TRACKING_URL, $tracking_num, $tracking_num);
		
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
		update_post_meta( $order_id, '_pr_shipment_dhl_label_tracking', $tracking_items );
	}

	/*
	 * Gets all tracking items fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return tracking items
	 */
	public function get_dhl_label_tracking( $order_id ) {
		return get_post_meta( $order_id, '_pr_shipment_dhl_label_tracking', true );
	}

	/**
	 * Delete the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 *
	 * @return void
	 */
	public function delete_dhl_label_tracking( $order_id ) {
		delete_post_meta( $order_id, '_pr_shipment_dhl_label_tracking' );
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
		update_post_meta( $order_id, '_pr_shipment_dhl_label_items', $tracking_items );
	}

	/*
	 * Gets all label itesm fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return label items
	 */
	public function get_dhl_label_items( $order_id ) {
		return get_post_meta( $order_id, '_pr_shipment_dhl_label_items', true );
	}

	protected function calculate_order_weight( $order_id ) {
		$order = wc_get_order( $order_id );

		$ordered_items = $order->get_items( );

		$total_weight = 0;
		foreach ($ordered_items as $key => $item) {
					
			if( ! empty( $item['variation_id'] ) ) {
				$product = wc_get_product($item['variation_id']);
			} else {
				$product = wc_get_product( $item['product_id'] );
			}
			
			$product_weight = $product->get_weight();
			if( $product_weight ) {
				$total_weight += ( $item['qty'] * $product_weight );
			}
		}

		return $total_weight;
	}

	protected function is_shipping_domestic( $order_id ) {   	 
		$order = wc_get_order( $order_id );
		$shipping_address = $order->get_address( 'shipping' );
		$shipping_country = $shipping_address['country'];

		if( PR_DHL()->is_shipping_domestic( $shipping_country ) ) {
			return true;
		} else {
			return false;
		}
	}

	protected function is_crossborder_shipment( $order_id ) {   	 
		$order = wc_get_order( $order_id );
		$shipping_address = $order->get_address( 'shipping' );
		$shipping_country = $shipping_address['country'];

		if( PR_DHL()->is_crossborder_shipment( $shipping_country ) ) {
			return true;
		} else {
			return false;
		}
	}

	// This function gathers all of the data from WC to send to DHL API
	protected function get_label_args( $order_id, $dhl_label_args ) {
		// Get settings from child implementation
		$args = $this->get_label_args_settings( $order_id, $dhl_label_args );		
		
		$order = wc_get_order( $order_id );
		// Get DHL service product
		$args['order_details']['dhl_product'] = $dhl_label_args['pr_dhl_product'];
		// $args['order_details']['duties'] = $dhl_label_args['shipping_dhl_duties'];
		$args['order_details']['weight'] = $dhl_label_args['pr_dhl_weight'];

		// Get WC specific details; order id, currency, units of measure, COD amount (if COD used)
		$args['order_details']['order_id'] = $order_id;
		$args['order_details']['currency'] = get_woocommerce_currency();
		$weight_units = get_option( 'woocommerce_weight_unit' );
		
		switch ( $weight_units ) {
			case 'lbs':
				$args['order_details']['weightUom'] = 'lb';
				break;
			default:
				$args['order_details']['weightUom'] = $weight_units;
				break;
		}

		if( $this->is_cod_payment_method( $order_id ) ) {
			$args['order_details']['cod_value']	= $order->get_total();			
		}
		
		$args['order_details']['total_value'] = $order->get_total();			
		// Value of ordered items only
		$args['order_details']['items_value'] = $order->get_subtotal();

		// Get address related information 
		$billing_address = $order->get_address( );
		$shipping_address = $order->get_address( 'shipping' );

		// If shipping phone number doesn't exist, try to get billing phone number
		if( ! isset( $shipping_address['phone'] ) && isset( $billing_address['phone'] ) ) {
			$shipping_address['phone'] = $billing_address['phone'];			
		}

		// If shipping email doesn't exist, try to get billing email
		if( ! isset( $shipping_address['email'] ) && isset( $billing_address['email'] ) ) {
			$shipping_address['email'] = $billing_address['email'];
		}

		// Merge first and last name into "name"
		$shipping_address['name'] = '';
		if ( isset( $shipping_address['first_name'] ) ) {
			$shipping_address['name'] = $shipping_address['first_name'];
			unset( $shipping_address['first_name'] );
		}

		if ( isset( $shipping_address['last_name'] ) ) {
			if( ! empty( $shipping_address['name'] ) ) {
				$shipping_address['name'] .= ' ';
			}

			$shipping_address['name'] .= $shipping_address['last_name'];
			unset( $shipping_address['last_name'] );
		}
		
		// If not USA, then change state from ISO code to name
		if ( $shipping_address['country'] != 'US' ) {
			// Get all states for a country
			$states = WC()->countries->get_states( $shipping_address['country'] );

			// If the state is empty, it was entered as free text
			if ( ! empty($states) ) {
				// Change the state to be the name and not the code
				$shipping_address['state'] = $states[ $shipping_address['state'] ];
				
				// Remove anything in parentheses (e.g. TH)
				$ind = strpos($shipping_address['state'], " (");
				if( false !== $ind ) {
					$shipping_address['state'] = substr( $shipping_address['state'], 0, $ind );
				}
			}
		}

		$args['shipping_address'] = $shipping_address;

		// Get order item specific data
		$ordered_items = $order->get_items( );
		$args['items'] = array();
		foreach ($ordered_items as $key => $item) {
			$new_item['qty'] = $item['qty'];
			$product = wc_get_product( $item['product_id'] );

		    $country_value = get_post_meta( $item['product_id'], '_dhl_manufacture_country', true );
		    if( ! empty( $country_value ) ) {
		    	$new_item['country_origin'] = $country_value;
		    }

			$hs_code = get_post_meta( $item['product_id'], '_dhl_hs_code', true );
			if( ! empty( $hs_code ) ) {
				$new_item['hs_code'] = $hs_code;
			}

			$new_item['item_description'] = $product->get_title();
			// $new_item['line_total'] = $item['line_total'];

			if( ! empty( $item['variation_id'] ) ) {
				$product_variation = wc_get_product($item['variation_id']);
				// Ensure id is string and not int
				$new_item['sku'] = empty( $product_variation->get_sku() ) ? strval( $item['variation_id'] ) : $product_variation->get_sku();
				$new_item['item_value'] = $product_variation->get_price();
				$new_item['item_weight'] = $product_variation->get_weight();

				$product_attribute = wc_get_product_variation_attributes($item['variation_id']);
				$new_item['item_description'] .= ' : ' . current( $product_attribute );

			} else {
				// Ensure id is string and not int
				$new_item['sku'] = empty( $product->get_sku() ) ? strval( $item['product_id'] ) : $product->get_sku();
				$new_item['item_value'] = $product->get_price();
				$new_item['item_weight'] = $product->get_weight();
			}

			$new_item += $this->get_label_item_args( $item['product_id'], $args );
			// if( ! empty( $product->post->post_excerpt ) ) {
			// 	$new_item['item_description'] = $product->post->post_excerpt;
			// } elseif ( ! empty( $product->post->post_content ) ) {
			// 	$new_item['item_description'] = $product->post->post_content;
			// }

			array_push($args['items'], $new_item);
		}

		return $args;
	}

	abstract protected function get_label_args_settings( $order_id, $dhl_label_args );

	protected function delete_label_args( $order_id ) {
		return $this->get_dhl_label_tracking( $order_id );
	}

	// Pass args by reference to modify DG if needed
	protected function get_label_item_args( $product_id, &$args ) {
		$new_item = array();
		return $new_item;
	}

	protected function is_cod_payment_method( $order_id ) {
		$is_code = false;
		$order = wc_get_order( $order_id );
		// WC 3.0 comaptibilty
		if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			$payment_method = $order->get_payment_method();
			if ( $payment_method == 'cod' ) {
				$is_code = true;
			}
		}
		else {
			if ( isset( $order->payment_method ) && ( $order->payment_method == 'cod' ) ) {
				$is_code = true;
			}
		}

		return $is_code;
	}

	/**
	 * Prevents data being copied to subscription renewals
	 */
	public function woocommerce_subscriptions_renewal_order_meta_query( $order_meta_query ) {
		$order_meta_query .= " AND `meta_key` NOT IN ( '_pr_shipment_dhl_label_tracking' )";

		return $order_meta_query;
	}
}

endif;
