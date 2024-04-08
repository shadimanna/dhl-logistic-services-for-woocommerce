<?php
use PR\DHL\Utils\API_Utils;

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

	const DHL_DOWNLOAD_ENDPOINT = 'dhl_download_label';

	protected $shipping_dhl_settings = array();

    protected $service 	= 'DHL';

	protected $carrier 	= '';

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( ) {
		$this->define_constants();
		$this->init_hooks();

		$this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
	}

	protected function define_constants() {
	}

	public function init_hooks() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20 );
		add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 2 );

		// Order page metabox actions
		add_action( 'wp_ajax_wc_shipment_dhl_gen_label', array( $this, 'save_meta_box_ajax' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_delete_label', array( $this, 'delete_label_ajax' ) );

		$subs_version = class_exists( 'WC_Subscriptions' ) && ! empty( WC_Subscriptions::$version ) ? WC_Subscriptions::$version : null;

		// Prevent data being copied to subscriptions
		if ( null !== $subs_version && version_compare( $subs_version, '2.5.0', '>=' ) ) {
			add_filter( 'wcs_renewal_order_meta_query', array( $this, 'woocommerce_subscriptions_renewal_order_meta_query' ), 10 );
		} else {
			add_filter( 'woocommerce_subscriptions_renewal_order_meta_query', array( $this, 'woocommerce_subscriptions_renewal_order_meta_query' ), 10 );
		}

		// add bulk actions to the Orders screen table bulk action drop-downs
		add_action( 'admin_footer', array( $this, 'add_order_bulk_actions' ) );

		// process orders bulk actions
		// add_action( 'load-edit.php', array( $this, 'process_orders_bulk_actions' ) );
		//add_action( 'handle_bulk_actions-edit-shop_order', array( $this, 'process_orders_bulk_actions' ) );
		add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'process_orders_bulk_actions' ), 10, 3 );
		add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'process_orders_bulk_actions' ), 10, 3 );

		// display admin notices for bulk actions
		add_action( 'admin_notices', array( $this, 'render_messages' ) );

		add_action( 'init', array( $this, 'add_download_label_endpoint' ) );
		add_action( 'parse_query', array( $this, 'process_download_label' ) );

		// add {tracking_note} placeholder
		add_filter( 'woocommerce_email_format_string' , array( $this, 'add_tracking_note_email_placeholder' ), 10, 2 );

		add_shortcode( 'pr_dhl_tracking_note', array( $this, 'tracking_note_shortcode') );
		add_shortcode( 'pr_dhl_tracking_link', array( $this, 'tracking_link_shortcode') );
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function add_meta_box() {
		$screen = API_Utils::is_HPOS() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box( 'woocommerce-shipment-dhl-label', sprintf( __( '%s Label & Tracking', 'dhl-for-woocommerce' ), $this->service), array( $this, 'meta_box' ), $screen, 'side', 'high' );
	}

	/**
	 * Show the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function meta_box( $post_or_order_object ) {
		$order    = ( $post_or_order_object instanceof WC_Order ) ? $post_or_order_object : wc_get_order( $post_or_order_object );
		$order_id = $order->get_id();

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
			$selected_dhl_product = $this->get_default_dhl_product( $order_id );
		}

		// Get the list of domestic and international DHL services
		try {
			$dhl_obj = PR_DHL()->get_dhl_factory();

			if( $this->is_shipping_domestic( $order_id ) ) {
				$dhl_product_list = $dhl_obj->get_dhl_products_domestic();
			} else {
				$dhl_product_list = $dhl_obj->get_dhl_products_international();
			}

		} catch (Exception $e) {

			echo '<p class="wc_dhl_error">' . $e->getMessage() . '</p>';
		}

		$delete_label = '';
		if ($this->can_delete_label($order_id)) {
			$delete_label = '<span class="wc_dhl_delete"><a href="#" id="dhl_delete_label">' . __('Delete Label', 'dhl-for-woocommerce') . '</a></span>';
		}

		$main_button = '<button id="dhl-label-button" class="button button-primary button-save-form">' . __( 'Generate Label', 'dhl-for-woocommerce' ) . '</button>';

		// Get tracking info if it exists
		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		// Check whether the label has already been created or not
		if( empty( $label_tracking_info ) ) {
			$is_disabled = '';

			$print_button = '<a href="#" id="dhl-label-print" class="button button-primary" download target="_blank">' . __( 'Download Label', 'dhl-for-woocommerce' ) . '</a>';

		} else {
			$is_disabled = 'disabled';

			$print_button = '<a href="'. $this->get_download_label_url( $order_id ) .'" id="dhl-label-print" class="button button-primary" download target="_blank">' .__( 'Download Label', 'dhl-for-woocommerce' ) . '</a>';
		}

		$dhl_label_data = array(
			'main_button' => $main_button,
			'delete_label' => $delete_label,
			'print_button' => $print_button
		);


		echo '<div id="shipment-dhl-label-form">';

		if( !empty( $dhl_product_list ) ) {

			woocommerce_wp_hidden_input( array(
				'id'    => 'pr_dhl_label_nonce',
				'value' => wp_create_nonce( 'create-dhl-label' )
			) );
			
			echo '<div class="shipment-dhl-row-container shipment-dhl-row-service">';
				echo '<div class="shipment-dhl-icon-container"><span class="shipment-dhl-icon shipment-dhl-icon-service"></span> ' . __( 'Service', 'dhl-for-woocommerce' ) . '</div>';
				woocommerce_wp_select ( array(
					'id'          		=> 'pr_dhl_product',
					'label'       		=> __( 'Service selected:', 'dhl-for-woocommerce' ),
					'description'		=> '',
					'value'       		=> $selected_dhl_product,
					'options'			=> $dhl_product_list,
					'custom_attributes'	=> array( $is_disabled => $is_disabled )
				) );
			echo '</div>';
			
			echo '<div class="shipment-dhl-row-container shipment-dhl-row-weight">';

				$weight_units = get_option( 'woocommerce_weight_unit' );

				// Get weight UoM and add in label
				echo '<div class="shipment-dhl-icon-container"><span class="shipment-dhl-icon shipment-dhl-icon-weight"></span> ' . __( 'Weight', 'dhl-for-woocommerce' ) . '</div>';			
				woocommerce_wp_text_input( array(
					'id'          		=> 'pr_dhl_weight',
					'label'       		=> sprintf( __( 'Estimated shipment weight (%s) based on items ordered: ', 'dhl-for-woocommerce' ), $weight_units),
					'placeholder' 		=> '',
					'description'		=> '',
					'value'       		=> $selected_weight_val,
					'custom_attributes'	=> array( $is_disabled => $is_disabled ),
					'class'				=> 'wc_input_decimal' // adds JS to validate input is in price format
				) );
			echo '</div>';

			$this->additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );


			// A label has been generated already, allow to delete
			if( empty( $label_tracking_info ) ) {
				echo $main_button;
			} else {
				echo $print_button;
				echo $delete_label;
			}

			wp_enqueue_script( 'wc-shipment-dhl-label-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl.js', array('jquery'), PR_DHL_VERSION );
			wp_localize_script( 'wc-shipment-dhl-label-js', 'dhl_label_data', $dhl_label_data );

		} else {
			echo '<p class="wc_dhl_error">' . __('There are no DHL services available for the destination country!', 'dhl-for-woocommerce') . '</p>';
		}

		echo '</div>';

	}

	protected function can_delete_label($order_id) {
		return true;
	}

	abstract public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );


	public function save_meta_box( $post_id, $post = null ) {

		// Do nothing if it's not an order.
		$screen = get_current_screen();
		if ( ! empty( $screen ) ) {
			if ( 'shop_order' !== $screen->post_type ) {
				return;
			}
		}

		// loop through inputs within id 'shipment-dhl-label-form'
		$meta_box_ids = array( 'pr_dhl_product', 'pr_dhl_weight');

		$additional_meta_box_ids = $this->get_additional_meta_ids( );
		$meta_box_ids = array_merge( $meta_box_ids, $additional_meta_box_ids );
		foreach ($meta_box_ids as $key => $value) {
			// Save value if it exists
			if ( isset( $_POST[ $value ] ) ) {
				$args[ $value ]	 = wc_clean( $_POST[ $value ] );
			} else {
                $args[ $value ]	 = '';
            }
		}

		if( isset( $args ) ) {
			$this->save_dhl_label_items( $post_id, $args );
			return $args;
		}
	}

		/**
		 * Delete label in process
		 *
		 * @param Int $order_id ID of the order object.
		 *
		 * @return String.
		 */
		public function processing_delete_label( $order_id ) {
			$args = $this->delete_label_args( $order_id );

			// If no tracking number, just continue. We cannot delete the tracking.
			if ( empty( $args['tracking_number'] ) ) {
				return '';
			}

			$dhl_obj = PR_DHL()->get_dhl_factory();

			// Delete meta data first in case there is an error with the API call.
			$this->delete_dhl_label_tracking( $order_id );

			if ( is_array( $args['tracking_number'] ) ) {
				foreach ( $args['tracking_number'] as $tracking_number ) {
					$del_label_args                    = $args;
					$del_label_args['tracking_number'] = $tracking_number;
					$dhl_obj->delete_dhl_label( $del_label_args );
				}
			} else {
				$dhl_obj->delete_dhl_label( $args );
			}

			$tracking_number_to_delete_note = is_array( $args['tracking_number'] ) ? $args['tracking_number'][0] : $args['tracking_number'];

			return $tracking_number_to_delete_note;
		}

	abstract public function get_additional_meta_ids();
	/**
	 * Order Tracking Save AJAX
	 *
	 * Function for saving tracking items
	 */
	public function save_meta_box_ajax( ) {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		// Save inputted data first
		$this->save_meta_box( $order_id );

		try {

			// Gather args for DHL API call
			$args = $this->get_label_args( $order_id );

			// Allow third parties to modify the args to the DHL APIs
			$args = apply_filters('pr_shipping_dhl_label_args', $args, $order_id );

			$dhl_obj = PR_DHL()->get_dhl_factory();
			$label_tracking_info = $dhl_obj->get_dhl_label( $args );

			$this->save_dhl_label_tracking( $order_id, $label_tracking_info );
			$tracking_note = $this->get_tracking_note( $order_id );
			$tracking_note_type = $this->get_tracking_note_type();
			$label_url = $this->get_download_label_url( $order_id );

			do_action( 'pr_shipping_dhl_label_created', $order_id );

			wp_send_json( array(
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'dhl-for-woocommerce'),
				'button_txt' => __( 'Download Label', 'dhl-for-woocommerce' ),
				'label_url' => $label_url,
				'tracking_note'	  => $tracking_note,
				'tracking_note_type' => $tracking_note_type,
				) );

		} catch ( Exception $e ) {

			wp_send_json( array( 'error' => $e->getMessage() ) );
		}

		wp_die();
	}

	public function delete_label_ajax( ) {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		try {

			$tracking_num = $this->processing_delete_label( $order_id );

			if ( empty( $tracking_num ) ) {
				throw new Exception( esc_html__( 'There are no tracking number to delete.', 'dhl-for-woocommerce' ) );
			}

			wp_send_json( array(
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'dhl-for-woocommerce'),
				'button_txt' => __( 'Generate Label', 'dhl-for-woocommerce' ),
				'dhl_tracking_num'	  => $tracking_num
				) );

		} catch (Exception $e) {

			wp_send_json( array( 'error' => $e->getMessage() ) );
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
		if ( ! isset( $label_tracking_info['label_path'] ) && isset( $label_tracking_info['label_url'] ) ){
			return $label_tracking_info['label_url'];
		}

		// Override URL with our solution's download label endpoint:
		return $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/' . $order_id );
	}

	protected function get_tracking_note( $order_id ) {

		if( ! empty( $this->shipping_dhl_settings['dhl_tracking_note_txt'] ) ) {
			$tracking_note = $this->shipping_dhl_settings['dhl_tracking_note_txt'];
		} else {
			$tracking_note = sprintf( __( '%s Tracking Number: {tracking-link}', 'dhl-for-woocommerce' ), $this->service);
		}

		$tracking_link = $this->get_tracking_link( $order_id );

		if( empty( $tracking_link ) ) {
		    return '';
        }

		$tracking_note_new = str_replace('{tracking-link}', $tracking_link, $tracking_note, $count);

		if( $count == 0 ) {
			$tracking_note_new = $tracking_note . ' ' . $tracking_link;
		}

		$return_label_number = $this->get_return_label_number( $order_id );
		if ( $return_label_number ) {
			if ( is_array($return_label_number) ) {
				$return_label_number = implode(', ', $return_label_number);
			}
			$tracking_note_return_label = sprintf( __( "\n Return Label Number: %s", 'dhl-for-woocommerce' ), $return_label_number);
			$tracking_note_new  = $tracking_note_new . $tracking_note_return_label;
		}

		return $tracking_note_new;
	}

	protected function get_tracking_link( $order_id ) {

		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		if( empty( $label_tracking_info['tracking_number'] ) ) {
			return '';
		}

		return sprintf( __( '<a href="%s%s" target="_blank">%s</a>', 'dhl-for-woocommerce' ), $this->get_tracking_url(), $label_tracking_info['tracking_number'], $label_tracking_info['tracking_number']);
	}

	protected function get_return_label_number( $order_id ) {

		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		if( empty( $label_tracking_info['return_label_number'] ) ) {
			return '';
		}

		return $label_tracking_info['return_label_number'];
	}

	abstract protected function get_tracking_url();

	protected function get_tracking_note_type() {
		if( isset( $this->shipping_dhl_settings['dhl_tracking_note'] ) && ( $this->shipping_dhl_settings['dhl_tracking_note'] == 'yes' ) ) {
			return '';
		} else {
			return 'customer';
		}
	}

	public function add_tracking_note_email_placeholder( $string, $email ) {

		$placeholder = '{pr_dhl_tracking_note}'; // The corresponding placeholder to be used

    	$order = $email->object; // Get the instance of the WC_Order Object

		// Ensure the object is an order and not another type
		if ( ! ( $order instanceof WC_Order ) ) {
    		return $string;
    	}

		$tracking_note = $this->get_tracking_note( $order->get_id() );

    	// Return the clean replacement tracking_note string for "{tracking_note}" placeholder
    	return str_replace( $placeholder, $tracking_note, $string );
	}

	public function tracking_note_shortcode( $atts, $content ) {

		extract(shortcode_atts(array(
			'order_id' => ''
		), $atts));

		if( $order = wc_get_order( $order_id ) ){

			return $this->get_tracking_note( $order->get_id() );

		}

    	return '';
	}

	public function tracking_link_shortcode( $atts, $content ) {

		extract(shortcode_atts(array(
			'order_id' => ''
		), $atts));

		if( $order = wc_get_order( $order_id ) ){

			return $this->get_tracking_link( $order->get_id() );

		}

    	return '';
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

		if( isset( $tracking_items['label_path'] ) && validate_file( $tracking_items['label_path'] ) === 2 ){
			$tracking_items['label_path'] = wp_slash( $tracking_items['label_path'] );
		}

		$order = wc_get_order( $order_id );
		$order->update_meta_data( '_pr_shipment_dhl_label_tracking', $tracking_items );
		$order->save();

		$tracking_details = array(
			'carrier' 			=> $this->carrier,
			'tracking_number' 	=> $tracking_items['tracking_number'],
			'ship_date' 		=> date( "Y-m-d", time() )
		);

		// Primarily added for "Advanced Tracking" plugin integration
		do_action( 'pr_save_dhl_label_tracking', $order_id, $tracking_details );
	}

	/*
	 * Gets all tracking items fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return tracking items
	 */
	public function get_dhl_label_tracking( $order_id ) {
		$order = wc_get_order( $order_id );
		return $order->get_meta('_pr_shipment_dhl_label_tracking' );
	}

	/**
	 * Delete the tracking items array to post_meta.
	 *
	 * @param int   $order_id       Order ID
	 *
	 * @return void
	 */
	public function delete_dhl_label_tracking( $order_id ) {
		$order = wc_get_order( $order_id );
		$order->delete_meta_data( '_pr_shipment_dhl_label_tracking' );
		$order->save();
		do_action( 'pr_delete_dhl_label_tracking', $order_id );
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
		$order = wc_get_order( $order_id );

		$dhl_label_items = $order->get_meta( '_pr_shipment_dhl_label_items' );

        if( is_array( $dhl_label_items ) ){
            $dhl_label_items = array_merge( $dhl_label_items, $tracking_items );
        } else {
            $dhl_label_items = $tracking_items;
        }

		$order->update_meta_data( '_pr_shipment_dhl_label_items', $dhl_label_items );
		$order->save();
	}

	/*
	 * Gets all label items fron the post meta array for an order
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return label items
	 */
	public function get_dhl_label_items( $order_id ) {
		$order = wc_get_order( $order_id );
		return $order->get_meta('_pr_shipment_dhl_label_items' );
	}

	/*
	 * Save default fields, used by bulk create label
	 *
	 * @param int  $order_id  Order ID
	 *
	 * @return default label items
	 */
	protected function save_default_dhl_label_items( $order_id ) {
		$dhl_label_items = $this->get_dhl_label_items( $order_id );

		if( empty( $dhl_label_items ) ) {
			$dhl_label_items = array();
		}

		if( empty( $dhl_label_items['pr_dhl_weight'] ) ) {
			// Set default weight
			$dhl_label_items['pr_dhl_weight'] = $this->calculate_order_weight( $order_id );
		}

		if( empty( $dhl_label_items['pr_dhl_product'] ) ) {
			// Set default DHL product
			$dhl_label_items['pr_dhl_product'] = $this->get_default_dhl_product( $order_id );
		}

		// Save default items
		$this->save_dhl_label_items( $order_id, $dhl_label_items );
	}

	protected function get_default_dhl_product( $order_id ) {
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		if( $this->is_shipping_domestic( $order_id ) ) {
			return $this->shipping_dhl_settings['dhl_default_product_dom'];
		} else {
			return $this->shipping_dhl_settings['dhl_default_product_int'];
		}
	}

	protected function calculate_order_weight( $order_id ) {

		$total_weight 	= 0;
		$order 			= wc_get_order( $order_id );

		if( false === $order ){
			return apply_filters('pr_shipping_dhl_order_weight', $total_weight, $order_id );
		}

		$ordered_items = $order->get_items( );

		if( is_array( $ordered_items ) && count( $ordered_items ) > 0 ){

			foreach ($ordered_items as $key => $item) {

				if( ! empty( $item['variation_id'] ) ) {
					$product = wc_get_product($item['variation_id']);
				} else {
					$product = wc_get_product( $item['product_id'] );
				}

				if ( $product ) {
					$product_weight = $product->get_weight();
					if( $product_weight ) {
						$total_weight += ( $item['qty'] * $product_weight );
					}
				}
			}

		}

		if ( ! empty( $this->shipping_dhl_settings['dhl_add_weight'] ) ) {

			if ( $this->shipping_dhl_settings['dhl_add_weight_type'] == 'absolute' ) {
				$total_weight += wc_format_decimal( $this->shipping_dhl_settings['dhl_add_weight'] );
			} elseif ( $this->shipping_dhl_settings['dhl_add_weight_type'] == 'percentage' ) {
				$total_weight += $total_weight * ( $this->shipping_dhl_settings['dhl_add_weight'] / 100 );
			}
		}

		return apply_filters('pr_shipping_dhl_order_weight', $total_weight, $order_id );
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

		if ( PR_DHL()->is_crossborder_shipment( $shipping_address ) ) {
			return true;
		} else {
			return false;
		}
	}

	// This function gathers all of the data from WC to send to DHL API
	protected function get_label_args( $order_id ) {

		$dhl_label_items = $this->get_dhl_label_items( $order_id );

		// Get settings from child implementation
		$args = $this->get_label_args_settings( $order_id, $dhl_label_items );

		$order = wc_get_order( $order_id );
		// Get DHL service product
		$args['order_details']['dhl_product'] = $dhl_label_items['pr_dhl_product'];
		// $args['order_details']['duties'] = $dhl_label_items['shipping_dhl_duties'];
		$args['order_details']['weight'] = $dhl_label_items['pr_dhl_weight'];

		// Get WC specific details; order id, currency, units of measure, COD amount (if COD used)
		$args['order_details']['order_id'] = $order_id;
		// $args['order_details']['currency'] = get_woocommerce_currency();
		$args['order_details']['currency'] = $this->get_wc_currency( $order_id );
		$weight_units = get_option( 'woocommerce_weight_unit' );

		switch ( $weight_units ) {
			case 'lbs':
				$args['order_details']['weightUom'] = 'lb';
				break;
			default:
				$args['order_details']['weightUom'] = $weight_units;
				break;
		}

		$args['order_details']['dimUom'] = get_option( 'woocommerce_dimension_unit' );

		if( $this->is_cod_payment_method( $order_id ) ) {
			$args['order_details']['cod_value']	= $order->get_total();
		}

		// calculate the additional fee
		$additional_fees = 0;
		if( count( $order->get_fees() ) > 0 ){
			foreach( $order->get_fees() as $fee ){

				if( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ){

					$additional_fees += floatval( $fee->get_total() );

				}else{

					$additional_fees += floatval( $fee['line_total'] );

				}

			}
		}

		$args['order_details']['additional_fee'] 	= $additional_fees;

		if( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0.0', '>=' ) ){

			$args['order_details']['shipping_fee'] 		= $order->get_shipping_total();

		}else{

			$args['order_details']['shipping_fee'] 		= $order->get_total_shipping();

		}


		$args['order_details']['total_value'] = $order->get_total();

		// Get address related information
		$billing_address = $order->get_address();
		$shipping_address = $order->get_address( 'shipping' );

		// If shipping phone number doesn't exist, try to get billing phone number
		if( empty( $shipping_address['phone'] ) && ! empty( $billing_address['phone'] ) ) {
			$shipping_address['phone'] = $billing_address['phone'];
		}

		// If shipping email doesn't exist, try to get billing email
		if( empty( $shipping_address['email'] ) && ! empty( $billing_address['email'] ) ) {
			$shipping_address['email'] = $billing_address['email'];
		}

		// Merge first and last name into "name"
		$shipping_address['name'] = '';
		if ( isset( $shipping_address['first_name'] ) ) {
			$shipping_address['name'] = $shipping_address['first_name'];
			// unset( $shipping_address['first_name'] );
		}

		if ( isset( $shipping_address['last_name'] ) ) {
			if( ! empty( $shipping_address['name'] ) ) {
				$shipping_address['name'] .= ' ';
			}

			$shipping_address['name'] .= $shipping_address['last_name'];
			// unset( $shipping_address['last_name'] );
		}

		// If not USA, Australia or Germany, then change state from ISO code to name
		if ( 'US' !== $shipping_address['country'] && 'AU' !== $shipping_address['country'] && 'DE' !== $shipping_address['country'] ) {
			// Get all states for a country
			$states = WC()->countries->get_states( $shipping_address['country'] );

			// If the state is empty, it was entered as free text
			if ( ! empty($states) && ! empty( $shipping_address['state'] ) ) {
				// Change the state to be the name and not the code
				$shipping_address['state'] = $states[ $shipping_address['state'] ];

				// Remove anything in parentheses (e.g. TH)
				$ind = strpos($shipping_address['state'], " (");
				if( false !== $ind ) {
					$shipping_address['state'] = substr( $shipping_address['state'], 0, $ind );
				}
			}
		}

		if ( 'DE' === $shipping_address['country'] ) {
			$shipping_address['state'] = trim( $shipping_address['state'], 'DE-' );
		}

		// Check if post number exists then send over
		if( $shipping_dhl_postnum = $order->get_meta('_shipping_dhl_postnum' ) ) {
			$shipping_address['dhl_postnum'] = $shipping_dhl_postnum;
		}

		$args['shipping_address'] = $shipping_address;

		// Get order item specific data
		$ordered_items = $order->get_items();
		$args['items'] = array();
		// Sum value of ordered items
		$args['order_details']['items_value'] = 0;
		foreach ($ordered_items as $key => $item) {
            // Reset array
            $new_item = array();

			$refunded_qty    = $order->get_qty_refunded_for_item( $key );

            // Deduct refunded items
			$new_item['qty'] = intval( $item['qty'] ) - abs( $refunded_qty );

            // If its fully refunded item, skip it.
			if ( 0 === $new_item['qty'] ) {
				continue;
			}

			// Get 1 item value not total items, based on ordered items in case currency is different that set product price
			$new_item['item_value'] = ( $item['line_total'] / $item['qty'] );
			// Sum 'line_total' to get items total value w/ discounts!
			$args['order_details']['items_value'] += $item['line_total'];

			$product = wc_get_product( $item['product_id'] );

			// If product does not exist (i.e. was deleted) OR is virtual, skip it
			if ( empty( $product ) || $product->is_virtual() ) {
				continue;
			}

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

				// If product variation does not exist (i.e. was deleted) OR is virtual, skip it
				if ( empty( $product_variation ) || $product_variation->is_virtual() ) {
					continue;
				}

				// place 'sku' in a variable before validating using 'empty' to be compatible with PHP v5.4
				$product_sku = $product_variation->get_sku();
				// Ensure id is string and not int
				$new_item['product_id'] = intval( $item['variation_id'] );
				$new_item['sku'] = empty( $product_sku ) ? strval( $item['variation_id'] ) : $product_sku;

				// If value is empty due to discounts, set variation price instead
				if ( empty( $new_item['item_value'] ) ) {
					$new_item['item_value'] = $product_variation->get_price();
				}

				$new_item['item_weight'] = $product_variation->get_weight();

				$product_attribute = wc_get_product_variation_attributes($item['variation_id']);
				$new_item['item_description'] .= ' : ' . current( $product_attribute );

			} else {
				// place 'sku' in a variable before validating using 'empty' to be compatible with PHP v5.4
				$product_sku = $product->get_sku();
				// Ensure id is string and not int
				$new_item['product_id'] = intval( $item['product_id'] );
				$new_item['sku'] = empty( $product_sku ) ? strval( $item['product_id'] ) : $product_sku;

				// If value is empty due to discounts, set product price instead
				if ( empty( $new_item['item_value'] ) ) {
					$new_item['item_value'] = $product->get_price();
				}

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

	abstract protected function get_label_args_settings( $order_id, $dhl_label_items );

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

	protected function get_wc_currency( $order_id ) {
		$order = wc_get_order( $order_id );
		// WC 3.0 comaptibilty
		if ( defined( 'WOOCOMMERCE_VERSION' ) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) ) {
			$order_currency = $order->get_currency();
		}
		else {
			$order_currency = $order->get_order_currency();
		}
		return $order_currency;
	}

	/**
	 * Prevents data being copied to subscription renewals
	 */
	public function woocommerce_subscriptions_renewal_order_meta_query( $order_meta_query ) {
		$order_meta_query .= " AND `meta_key` NOT IN ( '_pr_shipment_dhl_label_tracking' )";

		return $order_meta_query;
	}

	/**
	 * Bulk functions
	 */
	public function add_order_bulk_actions() {
		global $typenow, $pagenow, $current_screen;

		$is_orders_list = API_Utils::is_HPOS()
				? ( wc_get_page_screen_id( 'shop-order' ) === $current_screen->id && 'admin.php' === $pagenow )
				: ( 'shop_order' === $typenow && 'edit.php' === $pagenow );

		if ( ! $is_orders_list ) {
			return;
		}

		?>
		<script type="text/javascript">
            jQuery( document ).ready( function ( $ ) {
                $( 'select[name^=action]' ).append(
					<?php $index = count( $actions = $this->get_bulk_actions() ); ?>
					<?php foreach ( $actions as $action => $name ) : ?>
                    $( '<option>' ).val( '<?php echo esc_js( $action ); ?>' ).text( '<?php echo esc_js( $name ); ?>' )
					<?php --$index; ?>
					<?php if ( $index ) { echo ','; } ?>
					<?php endforeach; ?>
                );
            } );
		</script>
		<?php
	}

	public function process_orders_bulk_actions( $redirect, $doaction, $object_ids ) {

		if( ! array_key_exists( $doaction, $this->get_bulk_actions() ) ) {
			return $redirect;
		}

		$array_messages = array( 'msg_user_id' => get_current_user_id() );

		$message = $this->validate_bulk_actions( $doaction, $object_ids );

		if ( ! empty( $message ) ) {
			array_push($array_messages, array(
				'message' => $message,
				'type' => 'error',
			));
		} else {
			try {
				$array_messages += $this->process_bulk_actions( $doaction, $object_ids );
			} catch (Exception $e) {
				array_push($array_messages, array(
					'message' => $e->getMessage(),
					'type' => 'error',
				));
			}
		}

		/* @see render_messages() */
		// update_option( '_pr_dhl_bulk_action_confirmation', array( get_current_user_id() => $message, 'is_error' => $is_error ) );
		update_option( '_pr_dhl_bulk_action_confirmation', $array_messages );

		return $redirect;

	}
	/*
	public function render_messages( $current_screen = null ) {
		if ( ! $current_screen instanceof WP_Screen ) {
			$current_screen = get_current_screen();
		}

		if ( isset( $current_screen->id ) && in_array( $current_screen->id, array( 'shop_order', 'edit-shop_order' ), true ) ) {

			$bulk_action_message_opt = get_option( '_pr_dhl_bulk_action_confirmation' );

			if ( ( $bulk_action_message_opt ) && is_array( $bulk_action_message_opt ) ) {

				$user_id = key( $bulk_action_message_opt );

				if ( get_current_user_id() !== (int) $user_id ) {
					return;
				}

				$message = wp_kses_post( current( $bulk_action_message_opt ) );
				$is_error = wp_kses_post( next( $bulk_action_message_opt ) );

				if( $is_error ) {
					echo '<div class="error"><ul><li>' . $message . '</li></ul></div>';
				} else {
					echo '<div id="wp-admin-message-handler-message"  class="updated"><ul><li><strong>' . $message . '</strong></li></ul></div>';
				}

				delete_option( '_pr_dhl_bulk_action_confirmation' );
			}
		}
	}*/

	/**
	 * Display messages on order view screen
	 */
	public function render_messages( ) {
		global $current_screen;

		$screens = array( 'shop_order', 'edit-shop_order' );
		if ( API_Utils::is_HPOS() ) {
			$screens[] = wc_get_page_screen_id( 'shop-order' );
		}

		if ( isset( $current_screen->id ) && in_array( $current_screen->id, $screens ) ) {
			$bulk_action_message_opt = get_option( '_pr_dhl_bulk_action_confirmation' );

			if ( ( $bulk_action_message_opt ) && is_array( $bulk_action_message_opt ) ) {
				// $user_id = key( $bulk_action_message_opt );
				// remove first element from array and verify if it is the user id
				$user_id = array_shift( $bulk_action_message_opt );
				if ( get_current_user_id() !== (int) $user_id ) {
					return;
				}

				foreach ($bulk_action_message_opt as $key => $value) {
					$message = wp_kses_post( $value['message'] );
					$type = wp_kses_post( $value['type'] );

					switch ($type) {
                        case 'error':
                            echo '<div class="notice notice-error"><ul><li>' . $message . '</li></ul></div>';
                            break;
                        case 'success':
                            echo '<div class="notice notice-success"><ul><li><strong>' . $message . '</strong></li></ul></div>';
                            break;
                        default:
                            echo '<div class="notice notice-warning"><ul><li><strong>' . $message . '</strong></li></ul></div>';
                    }
				}

				delete_option( '_pr_dhl_bulk_action_confirmation' );
			}
		}
	}


	abstract public function get_bulk_actions();

	public function validate_bulk_actions( $action, $order_ids ) {
		return '';
	}

	public function process_bulk_actions( $action, $order_ids, $dhl_force_product = false, $is_force_product_dom = false ) {
		$label_count    = 0;
		$merge_files    = array();
		$array_messages = array();
		$orders_args    = array();

		if ( 'pr_dhl_create_labels' === $action ) {

			$dhl_obj = PR_DHL()->get_dhl_factory();

			foreach ( $order_ids as $order_id ) {
				$order = wc_get_order( $order_id );

				try {
					// Create label if one has not been created before
					if ( empty( $label_tracking_info = $this->get_dhl_label_tracking( $order_id ) ) ) {

						$this->save_default_dhl_label_items( $order_id );

						// $dhl_label_items = $this->get_dhl_label_items( $order_id );

						// Gather args for DHL API call
						$args = $this->get_label_args( $order_id );

						// Force the use of this DHL Product for all bulk label creation
						if ( $dhl_force_product ) {

							// If forced product is domestic AND order is domestic
							if ( $is_force_product_dom && $this->is_shipping_domestic( $order_id ) ) {
								$args['order_details']['dhl_product'] = $dhl_force_product;
							}

							// If forced product is international AND order is international
							if ( ! $is_force_product_dom && ! $this->is_shipping_domestic( $order_id ) ) {
								$args['order_details']['dhl_product'] = $dhl_force_product;
							}
						}

						// Allow settings to override saved order data, ONLY for bulk action
						$args = $this->get_bulk_settings_override( $args );

						// Allow third parties to modify the args to the DHL APIs
						$args = apply_filters( 'pr_shipping_dhl_label_args', $args, $order_id );

						
							// SOAP API request.
							$label_tracking_info = $dhl_obj->get_dhl_label( $args );
							$this->save_dhl_label_tracking( $order_id, $label_tracking_info );
							$tracking_note = $this->get_tracking_note( $order_id );

							$tracking_note_type = $this->get_tracking_note_type();
							$tracking_note_type = empty( $tracking_note_type ) ? 0 : 1;

							$order->add_order_note( $tracking_note, $tracking_note_type, true );

							++ $label_count;

							$array_messages[] = array(
								'message' => sprintf( __( 'Order #%s: DHL label Created', 'dhl-for-woocommerce' ),
									$order->get_order_number() ),
								'type'    => 'success',
							);

							// if ( ! empty( $label_tracking_info['label_path'] ) ) {
							// 	$merge_files[] = $label_tracking_info['label_path'];
							// }

							do_action( 'pr_shipping_dhl_label_created', $order_id );
						
					}

					if( ! empty( $label_tracking_info['label_path'] ) ) {
						array_push($merge_files, $label_tracking_info['label_path']);
					}

				} catch ( Exception $e ) {
					$array_messages[] = array(
						'message' => sprintf( __( 'Order #%s: %s', 'dhl-for-woocommerce' ), $order->get_order_number(),
							$e->getMessage() ),
						'type'    => 'error',
					);
				}
			}
/*
			if ( API_Utils::is_new_merchant() || API_Utils::is_rest_api_enabled() ) {
				$labels_tracking_info = $dhl_obj->get_dhl_labels( $orders_args );

				foreach ( $labels_tracking_info['labels'] as $label_tracking_info ) {
					$this->save_dhl_label_tracking( $label_tracking_info['order_id'], $label_tracking_info );

					if ( ! empty( $label_tracking_info['label_path'] ) ) {
						$merge_files[] = $label_tracking_info['label_path'];
					}

					$tracking_note      = $this->get_tracking_note( $label_tracking_info['order_id'] );
					$tracking_note_type = $this->get_tracking_note_type();
					$tracking_note_type = empty( $tracking_note_type ) ? 0 : 1;

					$order = wc_get_order( $label_tracking_info['order_id'] );
					$order->add_order_note( $tracking_note, $tracking_note_type, true );

					++ $label_count;

					$array_messages[] = array(
						'message' => sprintf( __( 'Order #%s: DHL label Created', 'dhl-for-woocommerce' ),
							$order->get_order_number() ),
						'type'    => 'success',
					);

					do_action( 'pr_shipping_dhl_label_created', $order->get_order_number() );
				}

				if ( isset( $labels_tracking_info['errors'] ) ) {
					foreach ( $labels_tracking_info['errors'] as $label_tracking_info ) {
						$array_messages[] = array(
							'message' => sprintf( __( 'Order #%s: %s', 'dhl-for-woocommerce' ),
								$label_tracking_info['order_id'], $label_tracking_info['message'] ),
							'type'    => 'error',
						);
					}
				}
			}
*/
			try {

				$file_bulk = $this->merge_label_files( $merge_files );

				// $message = sprintf( __( 'DHL label created for %1$s out of %2$s selected order(s).', 'dhl-for-woocommerce' ), $label_count , sizeof($order_ids) );

				if ( file_exists( $file_bulk['file_bulk_path'] ) ) {
					// $message .= sprintf( __( ' - %sdownload labels file%s', 'dhl-for-woocommerce' ), '<a href="' . $file_bulk['file_bulk_url'] . '" target="_blank">', '</a>' );

					// We're saving the bulk file path temporarily and access it later during the download process.
					// This information expires in 3 minutes (180 seconds), just enough for the user to see the
					// displayed link and click it if he or she wishes to download the bulk labels
					set_transient( '_dhl_bulk_download_labels_file_' . get_current_user_id(),
						$file_bulk['file_bulk_path'], 180 );

					// Construct URL pointing to the download label endpoint (with bulk param):
					$bulk_download_label_url = $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/bulk' );

					array_push( $array_messages, array(
						'message' => sprintf( __( 'Bulk DHL labels file created - %sdownload file%s',
							'dhl-for-woocommerce' ), '<a href="' . $bulk_download_label_url . '" download>', '</a>' ),
						'type'    => 'success',
					) );

				} else {
					// $message .= __( '. Could not create bulk DHL label file, download individually.', 'dhl-for-woocommerce' );

					array_push( $array_messages, array(
						'message' => __( 'Could not create bulk DHL label file, download individually.',
							'dhl-for-woocommerce' ),
						'type'    => 'error',
					) );
				}

			} catch ( Exception $e ) {
				array_push( $array_messages, array(
					'message' => $e->getMessage(),
					'type'    => 'error',
				) );
			}
		} elseif ( 'pr_dhl_delete_labels' === $action ) {
			$array_messages = $this->delete_label_in_bulk( $order_ids );
		}

		return $array_messages;
	}

	/**
	 * Delete DHL in bulk.
	 *
	 * @param Array<Int> $order_ids List of Order IDs.
	 *
	 * @return Array.
	 */
	protected function delete_label_in_bulk( $order_ids ) {
		if ( empty( $order_ids ) || ! is_array( $order_ids ) ) {
			return array();
		}

		$array_messages = array();

		foreach( $order_ids as $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				continue;
			}

			try {
				$label_tracking = $this->get_dhl_label_tracking( $order_id );

				if ( empty( $label_tracking ) ) {
					continue;
				}

				$tracking_number = $this->processing_delete_label( $order_id );

				// If no tracking number, just continue. We cannot delete the order notes anyway.
				if ( empty( $tracking_number ) ) {
					continue;
				}

				$order_notes = wc_get_order_notes(
					array(
						'order_id' => $order_id,
						'limit'    => -1,
						'type'     => 'customer',
					)
				);

				$order_notes = array_map(
					function( $order_note ) use ( $tracking_number ) {
						if ( empty( $order_note->content ) ) {
							return $order_note;
						}

						if ( false !== strpos( $order_note->content, $tracking_number ) ) {
							wc_delete_order_note( $order_note->id );
						}
						return $order_note;
					},
					$order_notes
				);

				array_push(
					$array_messages,
					array(
						'message' => sprintf( esc_html__( 'Order #%s: DHL Label Deleted', 'dhl-for-woocommerce' ), $order->get_order_number() ),
						'type'    => 'success',
					)
				);
			} catch ( Exception $e ) {
				array_push(
					$array_messages,
					array(
						'message' => sprintf( __( 'Order #%s: %s', 'dhl-for-woocommerce'), $order->get_order_number(), $e->getMessage() ),
						'type' => 'error',
					)
				);
			}
		}

		return $array_messages;
	}

	/**
	 * Generates the download label URL
	 *
	 * @param string $endpoint_path
	 * @return string - The download URL for the label
	 */
	public function generate_download_url( $endpoint_path ) {

		// If we get a different URL addresses from the General settings then we're going to
		// construct the expected endpoint url for the download label feature manually
		if ( site_url() != home_url() ) {

			// You can use home_url() here as well, it really doesn't matter
			// as we're only after for the "scheme" and "host" info.
			$result = parse_url( site_url() );

			if ( !empty( $result['scheme'] ) && !empty( $result['host'] ) ) {
				return $result['scheme'] . '://' . $result['host'] . $endpoint_path;
			}
		}

		// Defaults to the "Site Address URL" from the general settings along
		// with the the custom endpoint path (with parameters)
		return home_url( $endpoint_path );
	}

	protected function get_bulk_settings_override( $args ) {
		return $args;
	}

	protected function merge_label_files( $files ) {

		if( empty( $files ) ) {
			throw new Exception( __('There are no files to merge.', 'dhl-for-woocommerce') );
		}

		if( ! empty( $files[0] ) ) {
			$base_ext = pathinfo($files[0], PATHINFO_EXTENSION);
		} else {
			throw new Exception( __('The first file is empty.', 'dhl-for-woocommerce') );
		}

		if ( method_exists( $this, 'merge_label_files_' . $base_ext ) ) {
			return call_user_func( array( $this, 'merge_label_files_' . $base_ext ), $files );
		} else {
			throw new Exception( __('File format not supported.', 'dhl-for-woocommerce') );
		}
	}

	protected function merge_label_files_pdf( $files ) {

		if( empty( $files ) ) {
			throw new Exception( __('There are no files to merge.', 'dhl-for-woocommerce') );
		}

		$loader = PR_DHL_Libraryloader::instance();
		$pdfMerger = $loader->get_pdf_merger();

		if( $pdfMerger === null ){

			throw new Exception( __('Library conflict, could not merge PDF files. Please download PDF files individually.', 'dhl-for-woocommerce') );
		}

		foreach ($files as $key => $value) {

			if ( ! file_exists( $value ) ) {
				// throw new Exception( __('File does not exist', 'dhl-for-woocommerce') );
				continue;
			}

			$ext = pathinfo($value, PATHINFO_EXTENSION);
			// if ( strncasecmp('pdf', $ext, strlen($ext) ) == 0 ) {
			if ( stripos($ext, 'pdf') === false) {
				throw new Exception( __('Not all the file formats are the same.', 'dhl-for-woocommerce') );
			}

			$pdfMerger->addPDF( $value, 'all' );
		}

		$filename = 'dhl-label-bulk-' . time() . '.pdf';
		$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
		$file_bulk_url = PR_DHL()->get_dhl_label_folder_url() . $filename;
		$pdfMerger->merge( 'file',  $file_bulk_path );

		return array( 'file_bulk_path' => $file_bulk_path, 'file_bulk_url' => $file_bulk_url);
	}

	/**
	 * Creates a custom endpoint to download the label
	 */
	public function add_download_label_endpoint() {
		add_rewrite_endpoint(  self::DHL_DOWNLOAD_ENDPOINT, EP_ROOT );

		//Flush permalink if it is not flushed yet.
		if( !get_option( 'dhl_permalinks_flushed') ){
			flush_rewrite_rules();
			update_option('dhl_permalinks_flushed', 1);
		}
	}

	/**
	 * Processes the download label request
	 *
	 * @return void
	 */
	public function process_download_label() {
	    global $wp_query;

	    if ( ! current_user_can( 'edit_shop_orders' ) ) {
  			return;
  		}

		if ( ! isset($wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ] ) ) {
			return;
		}

	    // If we fail to add the "DHL_DOWNLOAD_ENDPOINT" then we bail, otherwise, we
	    // will continue with the process below.
	    $endpoint_param = $wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ];
	    if ( ! isset( $endpoint_param ) ) {
	    	return;
	    }

	    $array_messages = get_option( '_pr_dhl_bulk_action_confirmation' );
    	if ( empty( $array_messages ) || !is_array( $array_messages ) ) {
    		$array_messages = array( 'msg_user_id' => get_current_user_id() );
		}

	    if ( $endpoint_param == 'bulk' ) {

	    	$bulk_file_path = get_transient( '_dhl_bulk_download_labels_file_' . get_current_user_id() );

	    	if ( false == $this->download_label( $bulk_file_path ) ) {
	    		array_push($array_messages, array(
                    'message' => __( 'There are currently no bulk DHL label file to download or the download link for the bulk DHL label file has already expired. Please try again.', 'dhl-for-woocommerce' ),
                    'type' => 'error'
                ));
			}

			$redirect_url  = admin_url( 'edit.php?post_type=shop_order' );
	    } else {
	    	$order_id = $endpoint_param;

	    	// Get tracking info if it exists
			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			// Check whether the label has already been created or not
			if( empty( $label_tracking_info ) ) {
				return;
			}

			$label_path = $label_tracking_info['label_path'];

			if ( false == $this->download_label( $label_path ) ) {
	    		array_push($array_messages, array(
                    'message' => __( 'Unable to download file. Label appears to be invalid or is missing. Please try again.', 'dhl-for-woocommerce' ),
                    'type' => 'error'
                ));
			}

			$redirect_url  = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
	    }

	    update_option( '_pr_dhl_bulk_action_confirmation', $array_messages );

	    // If there are errors redirect to the shop_orders and display error
	    if ( $this->has_error_message( $array_messages ) ) {
            wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $redirect_url ) );
            exit;
		}
	}

	/**
	 * Checks whether the current "messages" collection has an
	 * error message waiting to be rendered.
	 *
	 * @param array $messages
	 * @return boolean
	 */
	protected function has_error_message( $messages ) {
		$has_error = false;

		foreach ( $messages as $key => $value ) {
			if ( $value['type'] == 'error' ) {
				$has_error = true;
				break;
			}
		}

		return $has_error;
	}

	/**
	 * Downloads the generated label file
	 *
	 * @param string $file_path
	 * @return boolean|void
	 */
	protected function download_label( $file_path ) {
		if ( !empty( $file_path ) && is_string( $file_path ) && file_exists( $file_path ) ) {
			// Check if buffer exists, then flush any buffered output to prevent it from being included in the file's content
			if ( ob_get_contents() ) {
				ob_clean();
			}

			$filename = basename( $file_path );

		    header( 'Content-Description: File Transfer' );
		    header( 'Content-Type: application/octet-stream' );
		    header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		    header( 'Expires: 0' );
		    header( 'Cache-Control: must-revalidate' );
		    header( 'Pragma: public' );
		    header( 'Content-Length: ' . filesize( $file_path ) );

		    readfile( $file_path );
		    exit;
		} else {
			return false;
		}
	}
}

endif;
