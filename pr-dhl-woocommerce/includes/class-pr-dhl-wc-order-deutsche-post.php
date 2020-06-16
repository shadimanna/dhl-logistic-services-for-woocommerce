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
	 * Status for WC orders that have an item label created.
     *
     * @since [*next-version*]
	 */
	const STATUS_HAS_ITEM = 'has_item';

	/**
	 * Status for WC orders that have been added to the current DHL order.
     *
     * @since [*next-version*]
	 */
	const STATUS_IN_ORDER = 'in_order';

	/**
	 * Status for WC orders that have been submitted in an order and are part of a shipment.
	 *
	 * @since [*next-version*]
	 */
	const STATUS_IN_SHIPMENT = 'in_shipment';

    protected $service 	= 'Deutsche Post';

	protected $carrier 	= 'Deutsche Post DHL';

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

		// Add the DHL order meta box
		add_action( 'add_meta_boxes', array( $this, 'add_dhl_order_meta_box' ), 21 );

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
            sprintf( __( '%s Waybill', 'pr-shipping-dhl' ), $this->service),
			array( $this, 'dhl_order_meta_box' ),
			'shop_order',
			'side',
			'high'
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
            $dhl_obj = PR_DHL()->get_dhl_factory();

            $nature_type = $dhl_obj->get_dhl_nature_type();

            woocommerce_wp_select(array(
                'id' => 'pr_dhl_nature_type',
                'label' => __('Contents Type:', 'pr-shipping-dhl'),
                'description' => '',
                'value' => isset($dhl_label_items['pr_dhl_nature_type']) ? $dhl_label_items['pr_dhl_nature_type'] : '',
                'options' => $nature_type,
                'custom_attributes' => array($is_disabled => $is_disabled)
            ));
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
				'create_order_confirmation' => __('Finalizing an order cannot be undone, so make sure you have added all the desired items before continuing.', 'pr-shipping-dhl')
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
				__( 'There are no items in your Waybill', 'pr-shipping-dhl' )
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
				<?php _e( 'Add to Waybill', 'pr-shipping-dhl' ); ?>
			</button>

			<button id="pr_dhl_create_order" class="button button-primary" type="button">
				<?php _e( 'Finalize Waybill', 'pr-shipping-dhl' ) ?>
			</button>
		</p>
		<p id="pr_dhl_order_gen_label_message">
			<?php _e( 'Please generate a label before adding the item to the Waybill', 'pr-shipping-dhl' ); ?>
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
				<?php _e( 'Download Waybill', 'pr-shipping-dhl' ); ?>
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
			// 'type' should alwasys be private for AWB
			wp_send_json( array(
				'html' => $this->dhl_order_meta_box_table( $wc_order_id ),
				'tracking' => array(
					'note' => $this->get_tracking_note( $wc_order_id ),
					'type' => '',
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

			do_action( 'pr_shipping_dhl_label_created', $order_id );

			wp_send_json( array(
				'download_msg' => __('Your DHL label is ready to download, click the "Download Label" button above"', 'pr-shipping-dhl'),
				'button_txt' => __( 'Download Label', 'pr-shipping-dhl' ),
				'label_url' => $label_url,
				'tracking_note'	  => $this->get_tracking_note( $order_id ),
				'tracking_note_type' => $this->get_tracking_note_type()
			) );

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
		return array( 'pr_dhl_nature_type' );
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

	protected function get_label_args_settings( $order_id, $dhl_label_items ) {

		// Get DPI API keys
		$args['dhl_settings']['dhl_api_key'] = $this->shipping_dhl_settings['dhl_api_key'];
		$args['dhl_settings']['dhl_api_secret'] = $this->shipping_dhl_settings['dhl_api_secret'];

		$args['dhl_settings']['dhl_label_ref'] = $this->replace_references( $this->shipping_dhl_settings['dhl_label_ref'], $order_id );
		$args['dhl_settings']['dhl_label_ref_2'] = $this->replace_references( $this->shipping_dhl_settings['dhl_label_ref_2'], $order_id );

		if ($dhl_label_items['pr_dhl_nature_type']) {
            $args['order_details']['nature_type'] = $dhl_label_items['pr_dhl_nature_type'];
        }

		return $args;
	}

	protected function replace_references( $reference, $order_id ) {
        $order = wc_get_order( $order_id );
        $billing_address = $order->get_address( );
        $shipping_address = $order->get_address( 'shipping' );

        $shipping_address_email = '';
        // If shipping email doesn't exist, try to get billing email
        if( ! isset( $shipping_address['email'] ) && isset( $billing_address['email'] ) ) {
            $shipping_address_email = $billing_address['email'];
        } else {
            $shipping_address_email = $shipping_address['email'];
        }

        $reference = str_replace( '{order_id}', $order_id, $reference );
        $reference = str_replace( '{email}', $shipping_address_email, $reference );

        return $reference;
    }

	public function add_order_status_column_header( $columns ) {

		$new_columns = array();

		foreach ( $columns as $column_name => $column_info ) {
			$new_columns[ $column_name ] = $column_info;

			if ( 'order_total' === $column_name ) {
				$new_columns['dhl_dp_status'] = __( 'DHL Status', 'pr-shipping-dhl' );
				$new_columns['dhl_tracking_number'] = __( 'DHL Tracking', 'pr-shipping-dhl' );
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
			$status = $this->get_order_status( $order_id );
			echo $this->get_order_status_text( $order_id, $status );
		}

		if ( 'dhl_tracking_number' === $column ) {
			$tracking_link = $this->get_tracking_link( $order_id );
			echo empty( $tracking_link ) ? '<strong>&ndash;</strong>' : $tracking_link;
		}
	}

	/**
	 * Retrieves an WC order's status.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|string $order_id The ID of the WC order.
	 *
	 * @return string The status string. See the `STATUS_*` constants in this class.
	 *
	 * @throws Exception If failed to get the DHL object from the factory.
	 */
	private function get_order_status( $order_id ) {
		$barcode = get_post_meta( $order_id, 'pr_dhl_dp_item_barcode', true );
		$awb = get_post_meta( $order_id, 'pr_dhl_dp_awb', true );

		if ( !empty( $awb ) ) {
			return self::STATUS_IN_SHIPMENT;
		}

		$api_client = PR_DHL()->get_dhl_factory()->api_client;
		$order = $api_client->get_order();
		$order_items = $order['items'];

		if ( in_array( $order_id, $order_items ) ) {
			return self::STATUS_IN_ORDER;
		}

		if ( ! empty( $barcode ) ) {
		return self::STATUS_HAS_ITEM;
		}

		return '';
	}

	/**
	 * Retrieves the human-friendly text for an WC order's status.
	 *
	 * @since [*next-version*]
	 *
	 * @param int|string $order_id The ID of the WC order.
	 * @param string $status The status.
	 *
	 * @return string The human-friendly text for the status.
	 */
	private function get_order_status_text( $order_id, $status ) {
		switch ($status) {
			case 'in_shipment':
				return __( 'Waybill created', 'pr-shipping-dhl' );

			case 'in_order':
				return __('Added to waybill', 'pr-shipping-dhl');

			case 'has_item':
				return __( 'Label created', 'pr-shipping-dhl' );
		}

		return '';
	}

	public function get_bulk_actions() {
		return array(
			'pr_dhl_create_labels' => __( 'Create DHL Label', 'pr-shipping-dhl' ),
			'pr_dhl_create_orders' => __( 'Finalize DHL Waybill', 'pr-shipping-dhl' ),
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
				$status = $this->get_order_status($order_id);

				// Only continue if the WC order is still at the item label creation phase
				if ($status !== self::STATUS_HAS_ITEM) {
					continue;
				}

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
					__('download file', 'pr-shipping-dhl')
				);

				$message = sprintf(
					__( 'Finalized DHL Waybill for %1$s orders - %2$s', 'pr-shipping-dhl' ),
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
            wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $redirect_url ) );
			exit;
		}
	}
}
