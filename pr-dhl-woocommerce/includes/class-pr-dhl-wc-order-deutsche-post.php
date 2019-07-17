<?php

if ( ! defined( 'ABSPATH' ) || class_exists( 'PR_DHL_WC_Order_Ecomm', false ) ) {
	exit;
}

/**
 * Integration for Deutsche Post label creation with WooCommerce orders.
 *
 * @since [*next-version*]
 */
class PR_DHL_WC_Order_Deutsche_Post extends PR_DHL_WC_Order {
	/**
	 * The order tracking URL printf-style pattern where "%s" tokens are replaced with the item barcode.
	 *
	 * @since [*next-version*]
	 */
	const TRACKING_URL_PATTERN = 'https://www.packet.deutschepost.com/web/portal-europe/packet_traceit?barcode=%s';

	/**
	 * The endpoint for download AWB labels.
	 *
	 * @since [*next-version*]
	 */
	const DHL_DOWNLOAD_AWB_LABEL_ENDPOINT = 'dhl_download_awb_label';

	/**
	 * Sets up the WordPress and WooCommerce hooks.
	 *
	 * @since [*next-version*]
	 */
	public function init_hooks() {
		parent::init_hooks();

		// add 'Status' orders page column header
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_status_column_header' ), 30 );
		// add 'Status Created' orders page column content
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_status_column_content' ) );

		// print DHL handover document
//		add_action( 'admin_init', array( $this, 'print_document_action' ), 1 );

		// add bulk order filter for printed / non-printed orders
//		add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_label_created' ), 20 );
//		add_filter( 'request', array( $this, 'filter_orders_by_label_created_query' ) );

		// Add the DHL order meta box
		add_action( 'add_meta_boxes', array( $this, 'add_dhl_order_meta_box' ), 20 );

		// AJAX handlers for the DHL order meta box
		add_action( 'wp_ajax_wc_shipment_dhl_get_order_items', array( $this, 'ajax_get_order_items' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_add_order_item', array( $this, 'ajax_add_order_item' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_remove_order_item', array( $this, 'ajax_remove_order_item' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_reset_order', array( $this, 'ajax_reset_order' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_get_awb_label', array( $this, 'ajax_generate_awb_label') );

		// The AWB label download endpoint
		add_action( 'init', array( $this, 'add_download_awb_label_endpoint' ) );
		add_action( 'parse_query', array( $this, 'process_download_awb_label' ) );
	}

	public function add_download_awb_label_endpoint() {
		add_rewrite_endpoint( self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT, EP_ROOT );
	}

	/**
	 * Cannot delete labels once an order is created.
	 *
	 * @since [*next-version*]
	 *
	 * @param int $order_id The ID of the order.
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	protected function can_delete_label($order_id) {
		$dhl_obj = PR_DHL()->get_dhl_factory();
		$dhl_order_id = get_post_meta( $order_id, 'pr_dhl_dp_order', true );
		$dhl_order = $dhl_obj->api_client->get_order($dhl_order_id);
		$dhl_order_created = !empty($dhl_order['shipments']);

		return parent::can_delete_label($order_id) && !$dhl_order_created;
	}

	/**
	 * Adds the DHL order info meta box to the WooCommerce order page.
	 */
	public function add_dhl_order_meta_box() {
		add_meta_box(
			'woocommerce-dhl-dp-order',
			__( 'DHL Order', 'pr-shipping-dhl' ),
			array( $this, 'dhl_order_meta_box' ),
			'shop_order',
			'side',
			'low'
		);
	}

	/**
	 * {@inheritdoc}
	 *
	 * Additional Deutsche Post specific fields for the "Label & Tracking" meta box in the WC order details page.
	 *
	 * @since [*next-version*]
	 *
	 * @param $order_id
	 * @param $is_disabled
	 * @param $dhl_label_items
	 * @param $dhl_obj
	 */
	public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {
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

		} else {
			woocommerce_wp_checkbox( array(
				'id'          		=> 'pr_dhl_return_item_wanted',
				'label'       		=> __( 'Allow return item: ', 'pr-shipping-dhl' ),
				'placeholder' 		=> '',
				'description'		=> '',
				'value'       		=> isset( $dhl_label_items['pr_dhl_return_item_wanted'] ) ? $dhl_label_items['pr_dhl_return_item_wanted'] : 'no',
				'custom_attributes'	=> array( $is_disabled => $is_disabled )
			) );
		}

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

	/**
	 * The meta box for managing the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 */
	public function dhl_order_meta_box() {
		echo $this->dhl_order_meta_box_table();

		wp_enqueue_script(
			'wc-shipment-dhl-dp-label-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-dp.js',
			array(),
			PR_DHL_VERSION
		);

		wp_localize_script(
			'wc-shipment-dhl-dp-label-js',
			'PR_DHL_DP',
			array(
				'create_order_confirmation' => __('Creating an order is final and cannot be undone, so make sure you have added all the desired items before continuing.', 'pr-shipping-dhl')
			)
		);
	}

	/**
	 * Creates the table of DHL items for the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @param int $wc_order_id The ID of the WooCommerce order where the meta box is shown.
	 *
	 * @return string The rendered HTML table.
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function dhl_order_meta_box_table( $wc_order_id = null ) {
		// If no WC order ID is given
		if ( $wc_order_id === null ) {
			// Try to get it from the request if doing an AJAX response
			if ( defined('DOING_AJAX') && DOING_AJAX ) {
				$wc_order_id = filter_input( INPUT_POST, 'order_id', FILTER_VALIDATE_INT );
			}
			// Otherwise get the current post ID
			else {
				global $post;
				$wc_order_id = $post->ID;
			}
		}

		$nonce = wp_create_nonce( 'pr_dhl_order_ajax' );


		// Get the DHL order that this WooCommerce order was submitted in
		$dhl_obj = PR_DHL()->get_dhl_factory();
		$dhl_order_id = get_post_meta( $wc_order_id, 'pr_dhl_dp_order', true );
		$dhl_order = $dhl_obj->api_client->get_order($dhl_order_id);

		$dhl_items = $dhl_order['items'];
		$dhl_shipments = $dhl_order['shipments'];

		// If no shipments have been created yet, show the items table.
		// If there are shipments, show the shipments table
		$table = ( empty($dhl_shipments) )
			? $this->order_items_table( $dhl_items, $wc_order_id )
			: $this->locked_order_items_table( $dhl_items, $dhl_order_id, $wc_order_id );

		ob_start();
		?>
		<p id="pr_dhl_dp_error"></p>

		<input type="hidden" id="pr_dhl_order_nonce" value="<?php echo $nonce; ?>" />
		<?php

		$buttons = ob_get_clean();

		return $table . $buttons;
	}

	/**
	 * Renders the table that shows order items.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $items The items to show.
	 * @param int|null $current_wc_order The ID of the current WC order.
	 *
	 * @return string The rendered table.
	 */
	public function order_items_table( $items, $current_wc_order = null ) {
		$table_rows = array();

		if (empty($items)) {
			$table_rows[] = sprintf(
				'<tr id="pr_dhl_no_items_msg"><td colspan="2"><i>%s</i></td></tr>',
				__( 'There are no items in your DHL order', 'pr-shipping-dhl' )
			);
		} else {
			foreach ( $items as $barcode => $wc_order ) {
				$order_url = get_edit_post_link( $wc_order );
				$order_text = sprintf( __( 'Order #%d', 'pr-shipping-dhl' ), $wc_order );
				$order_text = ( (int) $wc_order === (int) $current_wc_order )
					? sprintf('<b>%s</b>', $order_text)
					: $order_text;
				$order_link = sprintf('<a href="%s" target="_blank">%s</a>', $order_url, $order_text);

				$barcode_input = sprintf( '<input type="hidden" class="pr_dhl_item_barcode" value="%s">', $barcode );

				$remove_link = sprintf(
					'<a href="javascript:void(0)" class="pr_dhl_order_remove_item">%s</a>',
					__( 'Remove', 'pr-shipping-dhl' )
				);

				$table_rows[] = sprintf(
					'<tr><td>%s %s</td><td>%s</td></tr>',
					$order_link, $barcode_input, $remove_link
				);
			}
		}

		ob_start();

		?>
		<table class="widefat striped" id="pr_dhl_order_items_table">
			<thead>
			<tr>
				<th><?php _e( 'Item', 'pr-shipping-dhl' ) ?></th>
				<th><?php _e( 'Actions', 'pr-shipping-dhl' ) ?></th>
			</tr>
			</thead>
			<tbody>
			<?php echo implode( '', $table_rows ) ?>
			</tbody>
		</table>
		<p>
			<button id="pr_dhl_add_to_order" class="button button-secondary disabled" type="button">
				<?php _e( 'Add item to order', 'pr-shipping-dhl' ); ?>
			</button>

			<button id="pr_dhl_create_order" class="button button-primary" type="button">
				<?php _e( 'Create order', 'pr-shipping-dhl' ) ?>
			</button>
		</p>
		<p id="pr_dhl_order_gen_label_message">
			<?php _e( 'Please generate a label before adding the item to the DHL order', 'pr-shipping-dhl' ); ?>
		</p>
		<?php

		return ob_get_clean();
	}

	/**
	 * Renders the table that shows order items.
	 *
	 * @since [*next-version*]
	 *
	 * @param array $items The items to show.
	 * @param int $dhl_order_id The DHL order ID to show.
	 * @param int|null $current_wc_order The ID of the current WC order.
	 *
	 * @return string The rendered table.
	 */
	public function locked_order_items_table( $items, $dhl_order_id, $current_wc_order = null ) {
		$table_rows = array();
		foreach ( $items as $barcode => $wc_order ) {
			$order_url = get_edit_post_link( $wc_order );
			$order_text = sprintf( __( 'Order #%d', 'pr-shipping-dhl' ), $wc_order );
			$order_text = ( (int) $wc_order === (int) $current_wc_order )
				? sprintf('<b>%s</b>', $order_text)
				: $order_text;
			$order_link = sprintf('<a href="%s" target="_blank">%s</a>', $order_url, $order_text);

			$table_rows[] = sprintf('<tr><td>%s</td><td>%s</td></tr>', $order_link, $barcode);
		}

		$label_url = $this->generate_download_url( '/' . self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT . '/' . $dhl_order_id );

		ob_start();

		?>
		<p>
			<strong><?php _e('DHL Order ID:', 'pr-shipping-dhl'); ?></strong>
			<?php echo $dhl_order_id ?>
		</p>
		<table class="widefat striped" id="pr_dhl_order_items_table">
			<thead>
			<tr>
				<th><?php _e( 'Item', 'pr-shipping-dhl' ) ?></th>
				<th><?php _e( 'Barcode', 'pr-shipping-dhl' ) ?></th>
			</tr>
			</thead>
			<tbody>
			<?php echo implode( '', $table_rows ) ?>
			</tbody>
		</table>
		<p>
			<a id="pr_dhl_dp_download_awb_label" href="<?php echo $label_url ?>" class="button button-primary" target="_blank">
				<?php _e( 'Download Labels', 'pr-shipping-dhl' ); ?>
			</a>
		</p>

		<?php

		return ob_get_clean();
	}

	/**
	 * Ajax handler that responds with the order items table.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception
	 */
	public function ajax_get_order_items()
	{
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		wp_send_json( array(
			'html' => $this->dhl_order_meta_box_table(),
		) );
	}

	/**
	 * The AJAX handler for adding an item to the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_add_order_item()
	{
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		$item_barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );

		if ( ! empty( $item_barcode ) ) {
			PR_DHL()->get_dhl_factory()->api_client->add_item_to_order( $item_barcode, $order_id );
		}

		wp_send_json( array(
			'html' => $this->dhl_order_meta_box_table(),
		) );
	}

	/**
	 * The AJAX handler for removing an item from the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_remove_order_item()
	{
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		$item_barcode = wc_clean( $_POST[ 'item_barcode' ] );

		if ( ! empty( $item_barcode ) ) {
			PR_DHL()->get_dhl_factory()->api_client->remove_item_from_order( $item_barcode );
		}

		wp_send_json( array(
			'html' => $this->dhl_order_meta_box_table(),
		) );
	}

	/**
	 * The AJAX handler for finalizing the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_create_order()
	{
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		// Get the WC order
		$wc_order_id = wc_clean( $_POST[ 'order_id' ] );

		try {
			// Create the order
			PR_DHL()->get_dhl_factory()->create_order();

			// Send the new metabox HTML and the AWB (from the meta we just saved) as a tracking note
			wp_send_json( array(
				'html' => $this->dhl_order_meta_box_table( $wc_order_id ),
				'tracking' => array(
					'note' => $this->get_tracking_note( $wc_order_id ),
					'type' => $this->get_tracking_note_type(),
				),
			) );
		} catch (Exception $e) {
			wp_send_json( array (
				'error' => $e->getMessage(),
			) );
		}
	}

	/**
	 * The AJAX handler for resetting the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_reset_order()
	{
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		// Get the API client and reset the order
		PR_DHL()->get_dhl_factory()->api_client->reset_current_order();

		wp_send_json( array(
			'html' => $this->dhl_order_meta_box_table(),
		) );
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
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
			$label_info = $dhl_obj->get_dhl_label( $args );

			$this->save_dhl_label_tracking( $order_id, $label_info );
			$label_url = $this->get_download_label_url( $order_id );

			wp_send_json( array(
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'pr-shipping-dhl'),
				'button_txt' => __( 'Download Label', 'pr-shipping-dhl' ),
				'label_url' => $label_url,
				'tracking_note'	  => $this->get_tracking_note( $order_id ),
				'tracking_note_type' => $this->get_tracking_note_type()
			) );

			do_action( 'pr_shipping_dhl_label_created', $order_id );

		} catch ( Exception $e ) {
			wp_send_json( array( 'error' => $e->getMessage() ) );
		}

		wp_die();
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	public function delete_label_ajax( ) {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST[ 'order_id' ] );

		$api_client = PR_DHL()->get_dhl_factory()->api_client;

		// If the order has an associated DHL item ...
		$dhl_item_id = get_post_meta( $order_id, 'pr_dhl_dp_item_id', true);
		$item_barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true);

		if ( ! empty( $dhl_item_id ) ) {
			// Delete it from the API
			try {
				$api_client->delete_item( $dhl_item_id );
			} catch (Exception $e) {
			}
		}

		$api_client->remove_item_from_order( $item_barcode );

		delete_post_meta( $order_id, 'pr_dhl_dp_item_barcode' );
		delete_post_meta( $order_id, 'pr_dhl_dp_item_id' );

		// continue as usual
		parent::delete_label_ajax();
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	protected function get_download_label_url( $order_id ) {
		if ( empty( $order_id ) ) {
			return '';
		}

		$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
		if( empty( $label_tracking_info ) ) {
			return '';
		}

		// Override URL with our solution's download label endpoint:
		return $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/' . $order_id );
	}

	/**
	 * Order Tracking Save
	 *
	 * Function for saving tracking items
	 */
	public function get_additional_meta_ids() {
		return array();
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	protected function get_tracking_link( $order_id )
	{
		// Get the item barcode
		$barcode = get_post_meta( $order_id,'pr_dhl_dp_item_barcode', true );
		if ( empty( $barcode ) ) {
			return '';
		}

		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$this->get_item_tracking_url($barcode),
			$barcode
		);
	}

	/**
	 * Retrieves the tracking URL for an item.
	 *
	 * @since [*next-version*]
	 *
	 * @param string $barcode The item's barcode.
	 *
	 * @return string The tracking URL.
	 */
	protected function get_item_tracking_url($barcode )
	{
		return sprintf( $this->get_tracking_url(), $barcode );
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	protected function get_tracking_url() {
		return static::TRACKING_URL_PATTERN;
	}

	protected function get_package_description( $order_id ) {
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		$dhl_desc_default = $this->shipping_dhl_settings['dhl_desc_default'];
		$order = wc_get_order( $order_id );
		$ordered_items = $order->get_items();

		$desc_array = array();
		foreach ( $ordered_items as $key => $item ) {
			$product_id = $item['product_id'];
			$product = wc_get_product( $product_id );

			// If product does not exist, i.e. deleted go to next one
			if ( empty( $product ) ) {
				continue;
			}

			switch ( $dhl_desc_default ) {
				case 'product_cat':
					$product_terms = get_the_terms( $product_id, 'product_cat' );
					if ( $product_terms ) {
						foreach ( $product_terms as $key => $product_term ) {
							array_push( $desc_array, $product_term->name );
						}
					}
					break;
				case 'product_tag':
					$product_terms = get_the_terms( $product_id, 'product_tag' );
					if ( $product_terms ) {
						foreach ( $product_terms as $key => $product_term ) {
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
		$desc_array = array_unique( $desc_array );
		$desc_text = implode( ', ', $desc_array );
		$desc_text = mb_substr( $desc_text, 0, 50, 'UTF-8' );

		return $desc_text;
	}

	protected function get_label_args_settings( $order_id, $dhl_label_items ) {
		// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		$order = wc_get_order( $order_id );

		// Get DHL pickup and distribution center
		$args['dhl_settings']['dhl_api_key'] = $this->shipping_dhl_settings['dhl_api_key'];
		$args['dhl_settings']['dhl_api_secret'] = $this->shipping_dhl_settings['dhl_api_secret'];
		$args['dhl_settings']['pickup'] = $this->shipping_dhl_settings['dhl_pickup'];
		$args['dhl_settings']['distribution'] = $this->shipping_dhl_settings['dhl_distribution'];
		$args['dhl_settings']['handover'] = $this->get_label_handover_num();
		$args['dhl_settings']['label_format'] = $this->shipping_dhl_settings['dhl_label_format'];
		$args['dhl_settings']['label_size'] = $this->shipping_dhl_settings['dhl_label_size'];
		$args['dhl_settings']['label_page'] = $this->shipping_dhl_settings['dhl_label_page'];

		// Get package prefix
		$args['order_details']['prefix'] = $this->shipping_dhl_settings['dhl_prefix'];

		if ( ! empty( $dhl_label_items['pr_dhl_description'] ) ) {
			$args['order_details']['description'] = $dhl_label_items['pr_dhl_description'];
		} else {
			// If description is empty and it is an international shipment throw an error
			if ( $this->is_crossborder_shipment( $order_id ) ) {
				throw new Exception( __( 'The package description cannot be empty!', 'pr-shipping-dhl' ) );
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

		return $args;
	}

	// Pass args by reference to modify DG if needed
	protected function get_label_item_args( $product_id, &$args ) {

		$new_item = array();
		$dangerous_goods = get_post_meta( $product_id, '_dhl_dangerous_goods', true );
		if ( ! empty( $dangerous_goods ) ) {

			if ( isset( $args['order_details']['dangerous_goods'] ) ) {
				// if more than one item id DG, make sure to take the minimum value
				$args['order_details']['dangerous_goods'] = min(
					$args['order_details']['dangerous_goods'],
					$dangerous_goods
				);
			} else {
				$args['order_details']['dangerous_goods'] = $dangerous_goods;
			}
		}

		$new_item['item_export'] = get_post_meta( $product_id, '_dhl_export_description', true );

		return $new_item;
	}

	/**
	 * Saves the default data for DHL labels, needed for bulk actions.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|string $order_id The order ID.
	 */
	protected function save_default_dhl_label_items( $order_id ) {
		parent::save_default_dhl_label_items( $order_id );

		$dhl_label_items = $this->get_dhl_label_items( $order_id );

		if ( empty( $dhl_label_items['pr_dhl_description'] ) ) {
			$dhl_label_items['pr_dhl_description'] = $this->get_package_description( $order_id );
		}

		if ( empty( $dhl_label_items['pr_dhl_duties'] ) ) {
			$dhl_label_items['pr_dhl_duties'] = $this->shipping_dhl_settings['dhl_duties_default'];
		}

		if ( empty( $dhl_label_items['pr_dhl_is_cod'] ) ) {
			$dhl_label_items['pr_dhl_is_cod'] = $this->is_cod_payment_method( $order_id ) ? 'yes' : 'no';
		}

		$this->save_dhl_label_items( $order_id, $dhl_label_items );
	} // note to self: stop here

	// Used by label API to pass handover number
	protected function get_label_handover_num() {
		// If handover exists, use it...
		$handover_num = get_option( 'woocommerce_pr_dhl_handover' );

		// ... otherwise generate a new one
		if ( empty( $handover_num ) ) {
			$handover_num = $this->generate_handover();
			add_option( 'woocommerce_pr_dhl_handover', $handover_num );
		}

		return $handover_num;
	}

	// Used by Handover note creation
	protected function get_handover_num() {
		// If handover exists, use it...
		$handover_num = get_option( 'woocommerce_pr_dhl_handover' );

		// If don't exist create a new one (but don't save it for future use)
		if ( empty( $handover_num ) ) {
			$handover_num = $this->generate_handover();
		} else {
			// ...and delete it
			delete_option( 'woocommerce_pr_dhl_handover' );
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

	public function add_order_status_column_header( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['dhl_dp_status'] = __( 'DHL Order Status', 'pr-shipping-dhl' );
				$new_columns['dhl_tracking_number'] = __( 'DHL Tracking Number', 'pr-shipping-dhl' );
			}
		}

		return $new_columns;
	}

	public function add_order_status_column_content( $column ) {
		global $post;

		$order_id = $post->ID;

		if ( !$order_id ) {
			return;
		}

		if ( 'dhl_dp_status' === $column ) {
			echo $this->get_order_status( $order_id );
		}

		if ( 'dhl_tracking_number' === $column ) {
			$tracking_link = $this->get_tracking_link( $order_id );
			echo empty( $tracking_link ) ? '<strong>&ndash;</strong>' : $tracking_link;
		}
	}

	private function get_order_status( $order_id ) {
		$barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );
		$awb = get_post_meta( $order_id, 'pr_dhl_dp_awb', true );

		if ( !empty( $awb ) ) {
			return sprintf( __( 'Added to shipment %s', 'pr-shipping-dhl' ), $awb );
		}

		$api_client = PR_DHL()->get_dhl_factory()->api_client;
		$order = $api_client->get_order();
		$order_items = $order['items'];

		if ( in_array( $order_id, $order_items ) ) {
			return __('Added to order', 'pr-shipping-dhl');
		}

		if ( ! empty( $barcode ) ) {
			return __( 'DHL item created', 'pr-shipping-dhl' );
		}

		return '';
	}

	private function get_hangover_status( $order_id ) {
		$handover = get_post_meta( $order_id, '_pr_shipment_dhl_handover_note', true );

		if ( empty( $handover ) ) {
			return '<strong>&ndash;</strong>';
		} else {
			return '&#10004';
		}
	}

	public function get_bulk_actions() {
		return array(
			'pr_dhl_create_labels' => __( 'Create DHL items', 'pr-shipping-dhl' ),
			'pr_dhl_create_orders' => __( 'Create DHL order', 'pr-shipping-dhl' ),
		);
	}

	public function validate_bulk_actions( $action, $order_ids ) {
		if ( 'pr_dhl_create_orders' === $action ) {
			// Ensure the selected orders have a label created, otherwise don't add them to the order
			foreach ( $order_ids as $order_id ) {
				$item_barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );
				// If item has no barcode, return the error message
				if ( empty( $item_barcode ) ) {
					return __(
						'One or more orders do not have a DHL item label created. Please ensure all DHL labels are created before adding them to the order.',
						'pr-shipping-dhl'
					);
				}
			}
		}

		return '';
	}

	public function process_bulk_actions(
		$action,
		$order_ids,
		$orders_count,
		$dhl_force_product = false,
		$is_force_product_dom = false
	) {

		$array_messages = array();

		$action_arr = explode( ':', $action );
		if ( ! empty( $action_arr ) ) {
			$action = $action_arr[0];

			if ( isset( $action_arr[1] ) && ( $action_arr[1] == 'dom' ) ) {
				$is_force_product_dom = true;
			} else {
				$is_force_product_dom = false;
			}

			if ( isset( $action_arr[2] ) ) {
				$dhl_force_product = $action_arr[2];
			}
		}

		$array_messages += parent::process_bulk_actions(
			$action,
			$order_ids,
			$orders_count,
			$dhl_force_product,
			$is_force_product_dom
		);

		if ( 'pr_dhl_create_orders' === $action ) {
			$instance = PR_DHL()->get_dhl_factory();
			$client = $instance->api_client;

			foreach ($order_ids as $order_id) {
				// Get the DHL item barcode for this WC order
				$item_barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );
				// Add the DHL item barcode to the current DHL order
				$client->add_item_to_order($item_barcode, $order_id);
			}

			try {
				$order = $instance->api_client->get_order();
				$items_count = count( $order['items'] );

				// Create the order
				$dhl_order_id = $instance->create_order();
				// Get the URL to download the order label file
				$label_url = $this->generate_download_url( '/' . self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT . '/' . $dhl_order_id );

				$print_link = sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					$label_url,
					__('Print order label', 'pr-shipping-dhl')
				);

				$message = sprintf(
					__( 'Created DHL order for %1$s items. %2$s', 'pr-shipping-dhl' ),
					$items_count,
					$print_link
				);

				array_push(
					$array_messages,
					array(
						'message' => $message,
						'type'    => 'success',
					)
				);
			} catch (Exception $exception) {
				array_push(
					$array_messages,
					array(
						'message' => $exception->getMessage(),
						'type'    => 'error',
					)
				);
			}
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
			$order_ids = empty( $order_ids_hash )
				? array()
				: get_option(
					"pr_dhl_handover_order_ids_{$order_ids_hash}"
				);
			$order_ids = false === $order_ids ? array() : $order_ids;

			if ( empty( $order_ids ) ) {
				die( __( 'The DHL handover is not valid, please regenerate anothor one!', 'pr-shipping-dhl' ) );
			}

			// Since this is not a transient, we delete it manually.
			delete_option( "pr_dhl_handover_order_ids_{$order_ids_hash}" );

			// Generate the handover id random number (10 digits) with prefix '8'
			$handover_id = $this->get_handover_num();
			$total_weight = 0;
			$dhl_products = array();
			$items_qty = sizeof( $order_ids );

			try {

				// Get list of all DHL products and change key to name
				$dhl_obj = PR_DHL()->get_dhl_factory();
				$dhl_product_list = $dhl_obj->get_dhl_products_domestic() + $dhl_obj->get_dhl_products_international();
			} catch ( Exception $e ) {
				die( sprintf( __( 'Cannot generate handover %s', 'pr-shipping-dhl' ), $e->getMessage() ) );
			}

			foreach ( $order_ids as $order_id ) {
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
			$dhl_products = array_unique( $dhl_products );

			$args = array(
				'handover_id'  => $handover_id,
				'items_qty'    => $items_qty,
				'total_weight' => $total_weight,
				'dhl_products' => $dhl_products,
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

			$options = array(
				'dhl_label_not_created' => __( 'DHL Label Not Created', 'pr-shipping-dhl' ),
				'dhl_label_created'     => __( 'DHL Label Created', 'pr-shipping-dhl' ),
			);

			$selected = isset( $_GET['_shop_order_dhl_label_created'] ) ? $_GET['_shop_order_dhl_label_created'] : '';

			?>
			<select name="_shop_order_dhl_label_created" id="dropdown_shop_order_dhl_label_created">
				<option value=""><?php esc_html_e( 'Show all DHL label statuses', 'pr-shipping-dhl' ); ?></option>
				<?php foreach ( $options as $option_value => $option_name ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected(
						$selected,
						$option_value
					); ?>><?php echo esc_html( $option_name ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php

		endif;
	}

	// Filter orders by created labels
	public function filter_orders_by_label_created_query( $vars ) {
		global $typenow;

		if ( 'shop_order' === $typenow && isset( $_GET['_shop_order_dhl_label_created'] ) ) {

			$meta = '';
			$compare = '';
			$value = '';

			switch ( $_GET['_shop_order_dhl_label_created'] ) {
				case 'dhl_label_not_created' :
					$meta = '_pr_shipment_dhl_label_tracking';
					$compare = 'NOT EXISTS';
					break;
				case 'dhl_label_created' :
					$meta = '_pr_shipment_dhl_label_tracking';
					$compare = '>';
					$value = '0';
					break;
			}

			if ( $meta && $compare ) {
				$vars['meta_key'] = $meta;
				$vars['meta_value'] = $value;
				$vars['meta_compare'] = $compare;
			}
		}

		return $vars;
	}

	protected function merge_label_files_png( $files ) {

		if ( empty( $files ) ) {
			throw new Exception( __( 'There are no files to merge.', 'pr-shipping-dhl' ) );
		}

		if ( ! class_exists( 'Imagick' ) ) {
			throw new Exception(
				__( '"Imagick" must be installed on the server to merge png files.', 'pr-shipping-dhl' )
			);
		}

		$all = new Imagick();
		foreach ( $files as $key => $value ) {

			if ( ! file_exists( $value ) ) {
				// throw new Exception( __('File does not exist', 'pr-shipping-dhl') );
				continue;
			}

			$ext = pathinfo( $value, PATHINFO_EXTENSION );
			// error_log($ext);
			if ( stripos( $ext, 'png' ) === false ) {
				throw new Exception( __( 'Not all the file formats are the same.', 'pr-shipping-dhl' ) );
			}

			$im = new Imagick( $value );
			$all->addImage( $im );
		}

		$filename = 'dhl-label-bulk-' . time() . '.png';
		$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
		$file_bulk_url = PR_DHL()->get_dhl_label_folder_url() . $filename;

		/* Append the images into one */
		$all->resetIterator();
		$combined = $all->appendImages( true );
		// $ima = $im1->appendImages(true); 
		$combined->setImageFormat( 'png' );
		$combined->writeimage( $file_bulk_path );

		return array( 'file_bulk_path' => $file_bulk_path, 'file_bulk_url' => $file_bulk_url );
	}

	protected function merge_label_files_zpl( $files ) {

		if ( empty( $files ) ) {
			throw new Exception( __( 'There are no files to merge.', 'pr-shipping-dhl' ) );
		}

		$files_content = '';
		foreach ( $files as $key => $value ) {

			if ( ! file_exists( $value ) ) {
				// throw new Exception( __('File does not exist', 'pr-shipping-dhl') );
				continue;
			}

			$ext = pathinfo( $value, PATHINFO_EXTENSION );
			// error_log($ext);
			if ( stripos( $ext, 'zpl' ) === false ) {
				throw new Exception( __( 'Not all the file formats are the same.', 'pr-shipping-dhl' ) );
			}

			$files_content .= file_get_contents( $value );
		}

		$filename = 'dhl-label-bulk-' . time() . '.zpl';
		$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
		$file_bulk_url = PR_DHL()->get_dhl_label_folder_url() . $filename;

		$fp1 = fopen( $file_bulk_path, 'a+' );
		fwrite( $fp1, $files_content );

		return array( 'file_bulk_path' => $file_bulk_path, 'file_bulk_url' => $file_bulk_url );
	}

	public function process_download_awb_label() {
		global $wp_query;

		$dhl_order_id = isset($wp_query->query_vars[ self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT ] )
			? $wp_query->query_vars[ self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT ]
			: null;

		// If the endpoint param (aka the DHL order ID) is not in the query, we bail
		if ( $dhl_order_id === null ) {
			return;
		}

		$dhl_obj = PR_DHL()->get_dhl_factory();
		$label_path = $dhl_obj->get_dhl_order_label_file_info( $dhl_order_id )->path;

		$array_messages = get_option( '_pr_dhl_bulk_action_confirmation' );
		if ( empty( $array_messages ) || !is_array( $array_messages ) ) {
			$array_messages = array( 'msg_user_id' => get_current_user_id() );
		}

		if ( false == $this->download_label( $label_path ) ) {
			array_push($array_messages, array(
				'message' => __( 'Unable to download file. Label appears to be invalid or is missing. Please try again.', 'pr-shipping-dhl' ),
				'type' => 'error'
			));
		}

		update_option( '_pr_dhl_bulk_action_confirmation', $array_messages );

		$redirect_url = isset($wp_query->query_vars[ 'referer' ])
			? $wp_query->query_vars[ 'referer' ]
			: admin_url('edit.php?post_type=shop_order');

		// If there are errors redirect to the shop_orders and display error
		if ( $this->has_error_message( $array_messages ) ) {
			wp_redirect( $redirect_url );
			exit;
		}
	}
}
