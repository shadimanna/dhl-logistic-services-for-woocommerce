<?php
use PR\DHL\Utils\API_Utils;

if ( ! defined( 'ABSPATH' ) ) {
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

	protected $service = 'Deutsche Post';

	protected $carrier = 'Deutsche Post DHL';

	/**
	 * Sets up the WordPress and WooCommerce hooks.
	 *
	 * @since [*next-version*]
	 */
	public function init_hooks() {
		parent::init_hooks();

		// add AWB Copy Count
		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_shop_order_awb_copy' ), 1 );
		add_action( 'woocommerce_order_list_table_extra_tablenav', array( $this, 'add_shop_order_awb_copy' ) );

		// add_action('restrict_manage_posts', array( $this, 'add_shop_order_awb_copy' ), 1 );
		add_action( 'admin_enqueue_scripts', array( $this, 'order_list_awb_script' ), 10 );
		// add 'Status' orders page column header
		add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_status_column_header' ), 30 );
		add_filter( 'manage_woocommerce_page_wc-orders_columns', array( $this, 'add_order_status_column_header' ), 30 );
		// add 'Status Created' orders page column content
		add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_status_column_content' ), 10, 2 );
		add_action( 'manage_woocommerce_page_wc-orders_custom_column', array( $this, 'add_order_status_column_content' ), 10, 2 );

		// Add the DHL order meta box
		add_action( 'add_meta_boxes', array( $this, 'add_dhl_order_meta_box' ), 21 );

		// AJAX handlers for the DHL order meta box
		add_action( 'wp_ajax_wc_shipment_dhl_get_order_items', array( $this, 'ajax_get_order_items' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_add_order_item', array( $this, 'ajax_add_order_item' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_remove_order_item', array( $this, 'ajax_remove_order_item' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_create_order', array( $this, 'ajax_create_order' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_reset_order', array( $this, 'ajax_reset_order' ) );
		add_action( 'wp_ajax_wc_shipment_dhl_get_awb_label', array( $this, 'ajax_generate_awb_label' ) );

		// The AWB label download endpoint
		add_action( 'init', array( $this, 'add_download_awb_label_endpoint' ) );
		add_action( 'parse_query', array( $this, 'process_download_awb_label' ) );

		add_filter( 'gettext', array( $this, 'change_meta_box_title' ) );
	}

	public function add_shop_order_awb_copy( $which ) {
		global $typenow, $pagenow, $current_screen;

		$is_orders_list = API_Utils::is_HPOS()
			? ( wc_get_page_screen_id( 'shop-order' ) === $current_screen->id && 'admin.php' === $pagenow )
			: ( 'shop_order' === $typenow && 'edit.php' === $pagenow );

		if ( ! $is_orders_list ) {
			return;
		}

		// Get available countries codes with their states code/name pairs
		$country_states = WC()->countries->get_allowed_country_states();

		// Initializing
		$filter_id = 'dhl_awb_copy_count';
		$selected  = isset( $_GET[ $filter_id ] ) ? $_GET[ $filter_id ] : '';

		echo '<div class="alignleft actions dhl-awb-filter-container">';
		echo '<select name="' . esc_attr( $filter_id ) . '" class="dhl-awb-copy-count">';
		echo '<option value="">' . esc_html__( 'AWB Copy Count', 'dhl-for-woocommerce' ) . '</option>';
		// Loop through shipping zones locations array
		for ( $i = 1; $i < 51; $i++ ) {
			echo '<option value="' . esc_attr( $i ) . '" ' . selected( $selected, $i, false ) . '>' . esc_attr( $i ) . '</option>';
		}

		echo '</select>';
		// echo '<input type="submit" name="awb_copy_submit" class="button" value="Submit" />';
		echo '</div>';
	}

	public function order_list_awb_script( $hook ) {
		global $typenow, $pagenow, $current_screen;

		$is_orders_list = API_Utils::is_HPOS()
			? ( wc_get_page_screen_id( 'shop-order' ) === $current_screen->id && 'admin.php' === $pagenow )
			: ( 'shop_order' === $typenow && 'edit.php' === $pagenow );

		if ( ! $is_orders_list ) {
			return;
		}

		wp_enqueue_script(
			'wc-shipment-dhl-dp-orderlist-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-dp-orderlist.js',
			array(),
			PR_DHL_VERSION,
			true
		);
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
	protected function can_delete_label( $order_id ) {
		$dhl_obj           = PR_DHL()->get_dhl_factory();
		$order             = wc_get_order( $order_id );
		$dhl_order_id      = $order->get_meta( 'pr_dhl_dp_order' );
		$dhl_order         = $dhl_obj->api_client->get_order( $dhl_order_id );
		$dhl_order_created = ! empty( $dhl_order['shipments'] );

		return parent::can_delete_label( $order_id ) && ! $dhl_order_created;
	}

	/**
	 * Adds the DHL order info meta box to the WooCommerce order page.
	 */
	public function add_dhl_order_meta_box() {
		$screen = API_Utils::is_HPOS() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
		add_meta_box(
			'woocommerce-dhl-dp-order',
			/* translators: %s is the name of the service (e.g., DHL Express, DHL Parcel) */
			sprintf( esc_html__( '%s Waybill', 'dhl-for-woocommerce' ), $this->service ),
			array( $this, 'dhl_order_meta_box' ),
			$screen,
			'side',
			'high'
		);
	}

	public function change_meta_box_title( $text ) {

		global $pagenow, $theorder;

		if ( ( $pagenow == 'post.php' ) && ( get_post_type() == 'shop_order' ) || ( $theorder instanceof WC_Order ) ) {
			if ( $text == '%s Label & Tracking' ) {
				$text = '%s Label';
			}
		}

		return $text;
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

		woocommerce_wp_text_input(
			array(
				'id'                => 'pr_dhl_sender_taxid',
				'label'             => esc_html__( 'Sender customs reference: ', 'dhl-for-woocommerce' ),
				'placeholder'       => '',
				'description'       => '',
				'value'             => isset( $dhl_label_items['pr_dhl_sender_taxid'] ) ? $dhl_label_items['pr_dhl_sender_taxid'] : '',
				'custom_attributes' => array( $is_disabled => $is_disabled ),
				'class'             => '', // adds JS to validate input is in price format
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => 'pr_dhl_importer_taxid',
				'label'             => esc_html__( 'Importer customs reference: ', 'dhl-for-woocommerce' ),
				'placeholder'       => '',
				'description'       => '',
				'value'             => isset( $dhl_label_items['pr_dhl_importer_taxid'] ) ? $dhl_label_items['pr_dhl_importer_taxid'] : '',
				'custom_attributes' => array( $is_disabled => $is_disabled ),
				'class'             => '', // adds JS to validate input is in price format
			)
		);

		if ( $this->is_crossborder_shipment( $order_id ) ) {
			$dhl_obj = PR_DHL()->get_dhl_factory();

			$nature_type = $dhl_obj->get_dhl_nature_type();

			woocommerce_wp_select(
				array(
					'id'                => 'pr_dhl_nature_type',
					'label'             => esc_html__( 'Contents Type:', 'dhl-for-woocommerce' ),
					'description'       => '',
					'value'             => isset( $dhl_label_items['pr_dhl_nature_type'] ) ? $dhl_label_items['pr_dhl_nature_type'] : '',
					'options'           => $nature_type,
					'custom_attributes' => array( $is_disabled => $is_disabled ),
				)
			);
		}
		/*
		if( !$this->is_packet_return_available( $order_id ) ){

			echo '<p>'. esc_html__( 'Please note that the destination country does <b>not</b> support Packet Return', 'dhl-for-woocommerce' ) . '</p>';
		}*/
	}

	public function is_packet_return_available( $order_id ) {

		$can_return = false;
		/*
		$order      = wc_get_order( $order_id );

		if( in_array( $order->get_shipping_country(), $this->country_available_packet_return() ) ){
			$can_return = true;
		}
		*/
		return $can_return;
	}

	public function product_can_return() {
		return array(
			'GMP',
			'GPP',
			'GPT',
		);
	}

	public function country_available_packet_return() {
		return array(
			'AT',
			'BE',
			'CZ',
			'DE',
			'DK',
			'ES',
			'FI',
			'FR',
			'GB',
			'GR',
			'HR',
			'HU',
			'IT',
			'LT',
			'LU',
			'LV',
			'NL',
			'PL',
			'PT',
			'RO',
			'SE',
			'SI',
		);
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
				'create_order_confirmation' => esc_html__( 'Finalizing an order cannot be undone, so make sure you have added all the desired items before continuing.', 'dhl-for-woocommerce' ),
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
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
				$wc_order_id = filter_input( INPUT_POST, 'order_id', FILTER_VALIDATE_INT );
			}
			// Otherwise get the current post ID
			else {
				global $post, $theorder;
				$wc_order_id = API_Utils::is_HPOS() ? $theorder->get_id() : $post->ID;
			}
		}

		$nonce = wp_create_nonce( 'pr_dhl_order_ajax' );

		// Get the DHL order that this WooCommerce order was submitted in
		$dhl_obj = PR_DHL()->get_dhl_factory();

		$order        = $theorder ?? wc_get_order( $wc_order_id );
		$dhl_order_id = $order->get_meta( 'pr_dhl_dp_order' );
		$dhl_order    = $dhl_obj->api_client->get_order( $dhl_order_id );

		$dhl_items     = $dhl_order['items'];
		$dhl_shipments = $dhl_order['shipments'];

		// If no shipments have been created yet, show the items table.
		// If there are shipments, show the shipments table
		$table = ( empty( $dhl_shipments ) )
			? $this->order_items_table( $dhl_items, $wc_order_id )
			: $this->locked_order_items_table( $dhl_items, $dhl_order_id, $wc_order_id );

		ob_start();
		?>
		<p id="pr_dhl_dp_error"></p>

		<input type="hidden" id="pr_dhl_order_nonce" value="<?php echo esc_attr( $nonce ); ?>" />
		<?php

		$buttons = ob_get_clean();

		return $table . $buttons;
	}

	/**
	 * Renders the table that shows order items.
	 *
	 * @since [*next-version*]
	 *
	 * @param array    $items The items to show.
	 * @param int|null $current_wc_order The ID of the current WC order.
	 *
	 * @return string The rendered table.
	 */
	public function order_items_table( $items, $current_wc_order = null ) {
		$table_rows = array();

		if ( empty( $items ) ) {
			$table_rows[] = sprintf(
				'<tr id="pr_dhl_no_items_msg"><td colspan="2"><i>%s</i></td></tr>',
				esc_html__( 'There are no items in your Waybill', 'dhl-for-woocommerce' )
			);
		} else {
			foreach ( $items as $barcode => $wc_order ) {
				$order_url = get_edit_post_link( $wc_order );

				/* translators: %s is the order number */
				$order_text = sprintf( esc_html__( 'Order #%d', 'dhl-for-woocommerce' ), $wc_order );

				/* translators: %s is the order text that will be displayed in bold if it matches the current order */
				$order_text = ( (int) $wc_order === (int) $current_wc_order )
					? sprintf( '<b>%s</b>', $order_text )
					: $order_text;

				/* translators: %s is the URL of the order and %s is the text displayed for the link */
				$order_link = sprintf( '<a href="%s" target="_blank">%s</a>', $order_url, $order_text );

				/* translators: %s is the barcode value for the DHL item */
				$barcode_input = sprintf( '<input type="hidden" class="pr_dhl_item_barcode" value="%s">', $barcode );

				/* translators: %s is the text for the remove link */
				$remove_link = sprintf(
					'<a href="javascript:void(0)" class="pr_dhl_order_remove_item">%s</a>',
					esc_html__( 'Remove', 'dhl-for-woocommerce' )
				);

				/* translators: %1$s is the order link, %2$s is the barcode input, %3$s is the remove link */
				$table_rows[] = sprintf(
					'<tr><td>%1$s %2$s</td><td>%3$s</td></tr>',
					$order_link,
					$barcode_input,
					$remove_link
				);
			}
		}

		ob_start();

		?>
		<table class="widefat striped" id="pr_dhl_order_items_table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Item', 'dhl-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'dhl-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php echo implode( '', $table_rows ); ?>
			</tbody>
		</table>
		<p class="form-field pr_dhl_awb_copy_count_field">
			<label for="dhl_awb_copy_count"><?php esc_html_e( 'AWB Copy Count:', 'dhl-for-woocommerce' ); ?></label>
			<select name="dhl_awb_copy_count" id="pr_dhl_awb_copy_count" class="select long dhl-awb-copy-count">
				<?php
				// Loop through shipping zones locations array
				for ( $i = 1; $i < 51; $i++ ) {
					echo '<option value="' . esc_attr( $i ) . '">' . esc_attr( $i ) . '</option>';
				}
				?>
			</select>
		</p>
		<p>
			<button id="pr_dhl_add_to_order" class="button button-secondary disabled" type="button">
				<?php esc_html_e( 'Add to Waybill', 'dhl-for-woocommerce' ); ?>
			</button>

			<button id="pr_dhl_create_order" class="button button-primary" type="button">
				<?php esc_html_e( 'Finalize Waybill', 'dhl-for-woocommerce' ); ?>
			</button>
		</p>
		<p id="pr_dhl_order_gen_label_message">
			<?php esc_html_e( 'Please generate a label before adding the item to the Waybill', 'dhl-for-woocommerce' ); ?>
		</p>
		<?php

		return ob_get_clean();
	}

	/**
	 * Renders the table that shows order items.
	 *
	 * @since [*next-version*]
	 *
	 * @param array    $items The items to show.
	 * @param int      $dhl_order_id The DHL order ID to show.
	 * @param int|null $current_wc_order The ID of the current WC order.
	 *
	 * @return string The rendered table.
	 */
	public function locked_order_items_table( $items, $dhl_order_id, $current_wc_order = null ) {
		$table_rows = array();
		foreach ( $items as $barcode => $wc_order ) {
			$order_url = get_edit_post_link( $wc_order );

			/* translators: %s is the order number */
			$order_text = sprintf( esc_html__( 'Order #%d', 'dhl-for-woocommerce' ), $wc_order );

			/* translators: %s is the order text that will be bolded if it matches the current order */
			$order_text = ( (int) $wc_order === (int) $current_wc_order )
			? sprintf( '<b>%s</b>', $order_text )
			: $order_text;

			/* translators: %1$s is the order URL, %2$s is the order text */
			$order_link = sprintf( '<a href="%1$s" target="_blank">%2$s</a>', $order_url, $order_text );

			/* translators: %1$s is the order link, %2$s is the barcode */
			$table_rows[] = sprintf( '<tr><td>%1$s</td><td>%2$s</td></tr>', $order_link, $barcode );

		}

		$label_url = $this->generate_download_url( '/' . self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT . '/' . $dhl_order_id );

		ob_start();

		?>
		<table class="widefat striped" id="pr_dhl_order_items_table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Item', 'dhl-for-woocommerce' ); ?></th>
				<th><?php esc_html_e( 'Barcode', 'dhl-for-woocommerce' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php echo implode( '', $table_rows ); ?>
			</tbody>
		</table>
		<p>
			<a id="pr_dhl_dp_download_awb_label" href="<?php echo esc_url( $label_url ); ?>" class="button button-primary" target="_blank">
				<?php esc_html_e( 'Download Waybill', 'dhl-for-woocommerce' ); ?>
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
	public function ajax_get_order_items() {
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		wp_send_json(
			array(
				'html' => $this->dhl_order_meta_box_table(),
			)
		);
	}

	/**
	 * The AJAX handler for adding an item to the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_add_order_item() {
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );
		$order_id = wc_clean( $_POST['order_id'] );

		$order        = wc_get_order( $order_id );
		$item_barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );

		if ( ! empty( $item_barcode ) ) {
			PR_DHL()->get_dhl_factory()->api_client->add_item_to_order( $item_barcode, $order_id );
		}

		wp_send_json(
			array(
				'html' => $this->dhl_order_meta_box_table(),
			)
		);
	}

	/**
	 * The AJAX handler for removing an item from the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_remove_order_item() {
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		$item_barcode = wc_clean( $_POST['item_barcode'] );

		if ( ! empty( $item_barcode ) ) {
			PR_DHL()->get_dhl_factory()->api_client->remove_item_from_order( $item_barcode );
		}

		wp_send_json(
			array(
				'html' => $this->dhl_order_meta_box_table(),
			)
		);
	}

	/**
	 * The AJAX handler for finalizing the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_create_order() {
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		// Get the WC order
		$wc_order_id = wc_clean( $_POST['order_id'] );

		// Get AWB copy count
		if ( ! empty( $_POST['awb_copy_count'] ) && is_numeric( $_POST['awb_copy_count'] ) ) {
			$dhl_awb_copy_count = wc_clean( $_POST['awb_copy_count'] );
		} else {
			$dhl_awb_copy_count = 1;
		}

		try {
			// Create the order
			PR_DHL()->get_dhl_factory()->create_order( $dhl_awb_copy_count );

			// Send the new metabox HTML and the AWB (from the meta we just saved) as a tracking note
			// 'type' should alwasys be private for AWB
			wp_send_json(
				array(
					'html'     => $this->dhl_order_meta_box_table( $wc_order_id ),
					'tracking' => array(
						'note' => $this->get_tracking_note( $wc_order_id ),
						'type' => '',
					),
				)
			);
		} catch ( Exception $e ) {
			wp_send_json(
				array(
					'error' => $e->getMessage(),
				)
			);
		}
	}

	/**
	 * The AJAX handler for resetting the current Deutsche Post order.
	 *
	 * @since [*next-version*]
	 *
	 * @throws Exception If an error occurred while creating the DHL object from the factory.
	 */
	public function ajax_reset_order() {
		check_ajax_referer( 'pr_dhl_order_ajax', 'pr_dhl_order_nonce' );

		// Get the API client and reset the order
		PR_DHL()->get_dhl_factory()->api_client->reset_current_order();

		wp_send_json(
			array(
				'html' => $this->dhl_order_meta_box_table(),
			)
		);
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	public function save_meta_box_ajax() {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST['order_id'] );

		// Save inputted data first
		$this->save_meta_box( $order_id );

		try {
			// Gather args for DHL API call
			$args = $this->get_label_args( $order_id );

			// Allow third parties to modify the args to the DHL APIs
			$args       = apply_filters( 'pr_shipping_dhl_label_args', $args, $order_id );
			$dhl_obj    = PR_DHL()->get_dhl_factory();
			$label_info = $dhl_obj->get_dhl_label( $args );

			$this->save_dhl_label_tracking( $order_id, $label_info );
			$label_url = $this->get_download_label_url( $order_id );

			do_action( 'pr_shipping_dhl_label_created', $order_id );

			wp_send_json(
				array(
					'download_msg'       => esc_html__( 'Your DHL label is ready to download, click the "Download Label" button above"', 'dhl-for-woocommerce' ),
					'button_txt'         => esc_html__( 'Download Label', 'dhl-for-woocommerce' ),
					'label_url'          => $label_url,
					'tracking_note'      => $this->get_tracking_note( $order_id ),
					'tracking_note_type' => $this->get_tracking_note_type(),
				)
			);

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
	public function delete_label_ajax() {
		check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
		$order_id = wc_clean( $_POST['order_id'] );

		$api_client = PR_DHL()->get_dhl_factory()->api_client;

		// If the order has an associated DHL item ...
		$order        = wc_get_order( $order_id );
		$dhl_item_id  = $order->get_meta( 'pr_dhl_dp_item_id' );
		$item_barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );

		if ( ! empty( $dhl_item_id ) ) {
			// Delete it from the API
			try {
				$api_client->delete_item( $dhl_item_id );
			} catch ( Exception $e ) {
			}
		}

		$api_client->remove_item_from_order( $item_barcode );
		$order->delete_meta_data( 'pr_dhl_dp_item_barcode' );
		$order->delete_meta_data( 'pr_dhl_dp_item_id' );
		$order->save();

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
		if ( empty( $label_tracking_info ) ) {
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
		return array( 'pr_dhl_nature_type', 'pr_dhl_packet_return', 'pr_dhl_importer_taxid', 'pr_dhl_sender_taxid' );
	}

	/**
	 * @inheritdoc
	 *
	 * @since [*next-version*]
	 */
	protected function get_tracking_link( $order_id ) {
		// Get the item barcode
		$order   = wc_get_order( $order_id );

	  if ( ! is_a( $order, 'WC_Order' ) ) {
		  return '';
	  }

		$barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );
		if ( empty( $barcode ) ) {
			return '';
		}

		return sprintf(
			'<a href="%s" target="_blank">%s</a>',
			$this->get_item_tracking_url( $barcode ),
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
	protected function get_item_tracking_url( $barcode ) {
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
		$args['dhl_settings']['dhl_api_key']    = $this->shipping_dhl_settings['dhl_api_key'];
		$args['dhl_settings']['dhl_api_secret'] = $this->shipping_dhl_settings['dhl_api_secret'];

		$args['dhl_settings']['dhl_label_ref']   = $this->replace_references( $this->shipping_dhl_settings['dhl_label_ref'], $order_id );
		$args['dhl_settings']['dhl_label_ref_2'] = $this->replace_references( $this->shipping_dhl_settings['dhl_label_ref_2'], $order_id );

		if ( $this->is_packet_return_available( $order_id ) && in_array( $dhl_label_items['pr_dhl_product'], $this->product_can_return() ) ) {
			$args['dhl_settings']['packet_return'] = $this->shipping_dhl_settings['dhl_packet_return'];
		}

		if ( $dhl_label_items['pr_dhl_nature_type'] ) {
			$args['order_details']['nature_type'] = $dhl_label_items['pr_dhl_nature_type'];
		}

		if ( isset( $dhl_label_items['pr_dhl_importer_taxid'] ) ) {
			$args['order_details']['importer_taxid'] = $dhl_label_items['pr_dhl_importer_taxid'];
		}

		if ( isset( $dhl_label_items['pr_dhl_sender_taxid'] ) ) {
			$args['order_details']['sender_taxid'] = $dhl_label_items['pr_dhl_sender_taxid'];
		}

		$dhl_product  = $dhl_label_items['pr_dhl_product'];
		$product_info = explode( '-', $dhl_product );
		$product      = $product_info[0];
		if ( isset( $product_info[1] ) ) {
			$args['order_details']['dhl_service_level'] = $product_info[1]; // get service level
		}

		return $args;
	}

	// Pass args by reference to add item export if needed
	protected function get_label_item_args( $product_id, &$args ) {

		$new_item = array();

		$new_item['item_export'] = get_post_meta( $product_id, '_dhl_export_description', true );

		return $new_item;
	}

	protected function replace_references( $reference, $order_id ) {
		$order            = wc_get_order( $order_id );
		$billing_address  = $order->get_address();
		$shipping_address = $order->get_address( 'shipping' );

		$shipping_address_email = '';
		// If shipping email doesn't exist, try to get billing email
		if ( ! isset( $shipping_address['email'] ) && isset( $billing_address['email'] ) ) {
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
				$new_columns['dhl_dp_status']       = esc_html__( 'DHL Status', 'dhl-for-woocommerce' );
				$new_columns['dhl_tracking_number'] = esc_html__( 'DHL Tracking', 'dhl-for-woocommerce' );
			}
		}

		return $new_columns;
	}

	public function add_order_status_column_content( $column, $post_id_or_order ) {
		$order = ( $post_id_or_order instanceof WC_Order ) ? $post_id_or_order : wc_get_order( $post_id_or_order );

		if ( ! ( $order instanceof WC_Order ) ) {
			return;
		}

		$order_id = $order->get_id();

		if ( 'dhl_dp_status' === $column ) {
			$status = $this->get_order_status( $order_id );
			echo esc_html( $this->get_order_status_text( $order_id, esc_html( $status ) ) );
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
		$order   = wc_get_order( $order_id );
		$barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );
		$awb     = $order->get_meta( 'pr_dhl_dp_awb' );

		if ( ! empty( $awb ) ) {
			return self::STATUS_IN_SHIPMENT;
		}

		$api_client  = PR_DHL()->get_dhl_factory()->api_client;
		$order       = $api_client->get_order();
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
	 * @param string     $status The status.
	 *
	 * @return string The human-friendly text for the status.
	 */
	private function get_order_status_text( $order_id, $status ) {
		switch ( $status ) {
			case 'in_shipment':
				return esc_html__( 'Waybill created', 'dhl-for-woocommerce' );

			case 'in_order':
				return esc_html__( 'Added to waybill', 'dhl-for-woocommerce' );

			case 'has_item':
				return esc_html__( 'Label created', 'dhl-for-woocommerce' );
		}

		return '';
	}

	public function get_bulk_actions() {
		return array(
			'pr_dhl_create_labels' => esc_html__( 'Create DHL Label', 'dhl-for-woocommerce' ),
			'pr_dhl_create_orders' => esc_html__( 'Finalize DHL Waybill', 'dhl-for-woocommerce' ),
		);
	}

	public function validate_bulk_actions( $action, $order_ids ) {

		$orders_count = count( $order_ids );
		if ( 'pr_dhl_create_labels' === $action ) {

			if ( $orders_count < 1 ) {

				return esc_html__( 'No orders selected for the DHL bulk action, please select orders before performing the DHL action.', 'dhl-for-woocommerce' );

			}
		} elseif ( 'pr_dhl_create_orders' === $action ) {

			if ( $orders_count < 1 ) {

				return esc_html__( 'No orders selected for the DHL bulk action, please select orders before performing the DHL action.', 'dhl-for-woocommerce' );

			}

			// Ensure the selected orders have a label created, otherwise don't add them to the order

			foreach ( $order_ids as $order_id ) {
				$order        = wc_get_order( $order_id );
				$item_barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );
				// If item has no barcode, return the error message
				if ( empty( $item_barcode ) ) {
					return esc_html__(
						'One or more orders do not have a DHL item label created. Please ensure all DHL labels are created before adding them to the order.',
						'dhl-for-woocommerce'
					);
				}
			}

			$get_copy_count = $_REQUEST['dhl_awb_copy_count'];

			if ( empty( $get_copy_count ) ) {

				return esc_html__( 'Copy count must not be empty.', 'dhl-for-woocommerce' );

			} elseif ( ! is_numeric( $get_copy_count ) ) {

				return esc_html__( 'Copy count must be numeric.', 'dhl-for-woocommerce' );
			} elseif ( 50 <= intval( $get_copy_count ) ) {

				return esc_html__( 'Copy count must not be more than 50.', 'dhl-for-woocommerce' );
			}
		}

		return '';
	}

	public function process_bulk_actions(
		$action,
		$order_ids,
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
			$dhl_force_product,
			$is_force_product_dom
		);

		if ( 'pr_dhl_create_orders' === $action ) {
			$instance = PR_DHL()->get_dhl_factory();
			$client   = $instance->api_client;

			foreach ( $order_ids as $order_id ) {
				$status = $this->get_order_status( $order_id );

				// Only continue if the WC order is still at the item label creation phase
				if ( $status !== self::STATUS_HAS_ITEM ) {
					continue;
				}

				// Get the DHL item barcode for this WC order
				$order        = wc_get_order( $order_id );
				$item_barcode = $order->get_meta( 'pr_dhl_dp_item_barcode' );
				// Add the DHL item barcode to the current DHL order
				$client->add_item_to_order( $item_barcode, $order_id );
			}

			try {
				$order          = $instance->api_client->get_order();
				$items_count    = count( $order['items'] );
				$get_copy_count = $_GET['dhl_awb_copy_count'];

				if ( ! empty( $get_copy_count ) && is_numeric( $get_copy_count ) ) {
					$copy_count = $get_copy_count;
				} else {
					$copy_count = 1;
				}

				// Create the order
				$dhl_order_id = $instance->create_order( $copy_count );
				// Get the URL to download the order label file
				$label_url = $this->generate_download_url( '/' . self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT . '/' . $dhl_order_id );

				/* translators: %1$s is the URL for the label file, %2$s is the text for the download link */
				$print_link = sprintf(
					'<a href="%1$s" target="_blank">%2$s</a>',
					$label_url,
					esc_html__( 'download file', 'dhl-for-woocommerce' )
				);

				$message = sprintf(
					/* translators: %1$s is the order type (e.g., finalized or pending), %2$s is the date or other relevant detail */
					esc_html__( 'Finalized DHL Waybill for %1$s orders - %2$s', 'dhl-for-woocommerce' ),
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
			} catch ( Exception $exception ) {
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

		$dhl_order_id = isset( $wp_query->query_vars[ self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT ] )
			? $wp_query->query_vars[ self::DHL_DOWNLOAD_AWB_LABEL_ENDPOINT ]
			: null;

		// If the endpoint param (aka the DHL order ID) is not in the query, we bail
		if ( $dhl_order_id === null ) {
			return;
		}

		$dhl_obj    = PR_DHL()->get_dhl_factory();
		$label_path = $dhl_obj->get_dhl_order_label_file_info( $dhl_order_id )->path;

		$array_messages = get_option( '_pr_dhl_bulk_action_confirmation' );
		if ( empty( $array_messages ) || ! is_array( $array_messages ) ) {
			$array_messages = array( 'msg_user_id' => get_current_user_id() );
		}

		if ( false == $this->download_label( $label_path ) ) {
			array_push(
				$array_messages,
				array(
					'message' => esc_html__( 'Unable to download file. Label appears to be invalid or is missing. Please try again.', 'dhl-for-woocommerce' ),
					'type'    => 'error',
				)
			);
		}

		update_option( '_pr_dhl_bulk_action_confirmation', $array_messages );

		$redirect_url = isset( $wp_query->query_vars['referer'] )
			? $wp_query->query_vars['referer']
			: admin_url( 'edit.php?post_type=shop_order' );

		// If there are errors redirect to the shop_orders and display error
		if ( $this->has_error_message( $array_messages ) ) {
			wp_redirect( remove_query_arg( array( '_wp_http_referer', '_wpnonce' ), $redirect_url ) );
			exit;
		}
	}
}
