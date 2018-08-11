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
		// add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts'), 20 );

		// Invoice upload ajax request handler
		add_action( 'wp_ajax_wc_shipment_dhl_upload_invoice', array( $this, 'upload_invoice_ajax' ) );

		add_action( 'woocommerce_checkout_order_processed', array( $this, 'process_dhl_preferred_fields' ), 10, 2 );
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


			$contents_desc = isset( $dhl_label_items['pr_dhl_contents_description'] ) ? $dhl_label_items['pr_dhl_contents_description'] : '';
			woocommerce_wp_textarea_input( array(
				'id'          		=> 'pr_dhl_contents_description',
				'label'       		=> __( 'Contents Description: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> 'Briefly describe package contents',
				'description'		=> '',
				'value'       		=> $contents_desc,
				'rows'				=> 5,
				'custom_attributes'	=> array( $is_disabled => $is_disabled, 'maxlength' => '90' )
			) );

			echo '<div style="margin-top:-15px;margin-bottom:10px;"><small>90 chars max</small></div>';
			echo "<hr/>";

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

			$upload_file_button = '<button class="upload-invoice-button button button-upload-form">' . __('Upload Invoice', 'pr-shipping-dhl') . '</button><div class="dhl-invoice-upload-spinner-container"><div class="spinner"></div><div class="dhl-invoice-upload-message" style="font-size: 11px;">'.sprintf( __('Upload complete. Preview %shere%s.', 'pr-shipping-dhl'), '<a id="dhl-invoice-upload-url" href="#" target="_blank">', '</a>').'</div></div>';

			echo $upload_file_button;


		}
		
		echo '<hr style="clear:both;">';

		$total_packages = isset( $dhl_label_items['pr_dhl_total_packages'] ) ? $dhl_label_items['pr_dhl_total_packages'] : '1';
		$packages = isset( $dhl_label_items['pr_dhl_packages'] ) ? $dhl_label_items['pr_dhl_packages'] : array();

		// Fallback: for whatever reason the packages were not saved successfully then we make
		// sure that they are consistent with the total packages entry.
		$total_packages = empty( $packages ) ? 1 : $total_packages;

		$numbers = array();
		for ( $i = 1; $i <= 50; $i++ ) $numbers[$i] = $i;

		woocommerce_wp_select( array(
			'id'	          	=> 'pr_dhl_total_packages',
			'name'          	=> 'pr_dhl_total_packages',
			'label'       		=>  __( 'Total Packages:', 'pr-shipping-dhl' ),
			'value'				=> $total_packages,
			'options'			=> $numbers,
			'custom_attributes'	=> array( $is_disabled => $is_disabled, 'data-current' => $total_packages,  "autocomplete" => "off" ),
			'wrapper_class'		=> 'dhl-total-packages'
		) );

		echo '<div class="total_packages_container" style="margin-bottom:15px;">
				<div class="package_header">
					<div class="package_header_field first">Package</div>
					<div class="package_header_field">Weight</div>
					<div class="package_header_field">Length</div>
					<div class="package_header_field">Width</div>
					<div class="package_header_field">Height</div>
				</div>';

		if ( empty( $packages ) ) {
			echo '	<div class="package_item">
						<div class="package_item_field package_number first"><input type="text" name="pr_dhl_packages_number[]" data-sequence="1" value="1" maxlength="70" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_weight[]" placeholder="kg" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_length[]" placeholder="cm" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_width[]" placeholder="cm" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_height[]" placeholder="cm" /></div>
					</div>';
		} else {
			for ($i=0, $seq=1; $i<intval($total_packages); $i++, $seq++) {
				$number = !empty($packages[$i]) ? $packages[$i]['number'] : $seq;
				$weight = !empty($packages[$i]) ? $packages[$i]['weight'] : '';
				$length = !empty($packages[$i]) ? $packages[$i]['length'] : '';
				$width = !empty($packages[$i]) ? $packages[$i]['width'] : '';
				$height = !empty($packages[$i]) ? $packages[$i]['height'] : '';

				echo '	<div class="package_item">
						<div class="package_item_field package_number first"><input type="text" name="pr_dhl_packages_number[]" data-sequence="'.$seq.'" value="'.$number.'" maxlength="70" autocomplete="off" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_weight[]" value="'.$weight.'" placeholder="kg" autocomplete="off" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_length[]" value="'.$length.'" placeholder="cm" autocomplete="off" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_width[]" value="'.$width.'" placeholder="cm" autocomplete="off" /></div>
						<div class="package_item_field clearable"><input type="text" name="pr_dhl_packages_height[]" value="'.$height.'" placeholder="cm" autocomplete="off" /></div>
					</div>';
			}
		}

		echo '</div>';
		echo '<hr style="clear:both;">';
	}


	/**
	 * Validates and processes the submitted invoice for upload from an ajax request
	 *
	 * @access public
	 */
	public function upload_invoice_ajax() {

		// CHECK NONCE!

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
                // SHADI: SAVE PATH TO ITEMS ORDER META!
				
				$uploaded_file_path = $upload_dir[ 'path' ] . '/' . $upload_file_name;

				$this->save_dhl_label_invoice( $order_id, $uploaded_file_path );

				/*
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
				*/

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
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids( ) {
		return array('pr_dhl_ship_date', 'pr_dhl_additional_insurance', 'pr_dhl_insured_value', 'pr_dhl_declared_value','pr_dhl_duties', 'pr_dhl_paperless_trade', 'pr_dhl_total_packages', 'pr_dhl_packages', 'pr_dhl_contents_description' );
	}

	protected function get_tracking_link( $tracking_num ) {
		if( empty( $tracking_num ) ) {
			return '';
		}

		$tracking_note = sprintf( __( '<label>DHL Tracking Number: </label><a href="%s%s" target="_blank">%s</a>', 'my-text-domain' ), PR_DHL_EXPRESS_TRACKING_URL, $tracking_num, $tracking_num);
		
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
		update_post_meta( $order_id, '_pr_shipment_dhl_express_label_tracking', $tracking_items );
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

	/**
	 * Saves the label invoice array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 * @param array $tracking_invoice List of tracking item
	 *
	 * @return void
	 */
	public function save_dhl_label_invoice( $order_id, $tracking_invoice ) {
		update_post_meta( $order_id, '_pr_shipment_dhl_express_label_invoice', $tracking_invoice );
	}

	/*
	 * Gets all label itesm fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return label invoice
	 */
	public function get_dhl_label_invoice( $order_id ) {
		return get_post_meta( $order_id, '_pr_shipment_dhl_express_label_invoice', true );
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

		// Get invoice path
		$args['order_details']['invoice'] = $this->get_dhl_label_invoice( $order_id );

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
		// $dhl_label_args = $this->get_dhl_label_items( $order_id );
		// $args = $this->get_label_args( $order_id, $dhl_label_args );

		$args = $this->get_dhl_label_tracking( $order_id );

		$shipping_dhl_settings = $this->get_shipping_dhl_settings();

		$args['api_user'] = $shipping_dhl_settings['dhl_api_user'];
		$args['api_pwd'] = $shipping_dhl_settings['dhl_api_pwd'];
		
		return $args;
	}

	public function process_dhl_preferred_fields( $order_id, $posted ) {
		// save the posted preferences to the order so can be used when generating label
		
		// error_log($order_id);
		// error_log(print_r($posted,true));

		if (isset( $posted['shipping_method'] ) ) {
			foreach ($posted['shipping_method'] as $key => $value) {
				$shipping_method_arr = explode( ':', $value );
				if( $shipping_method_arr && $shipping_method_arr[0] == 'pr_dhl_express' && isset( $shipping_method_arr[2] ) ) {

					$dhl_label_options['pr_dhl_product'] = $shipping_method_arr[2];
					$this->save_dhl_label_items( $order_id, $dhl_label_options );
				}
			}
		}
	}
}

endif;
