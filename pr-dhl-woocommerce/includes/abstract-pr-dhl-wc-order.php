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

		// Action Scheduler hooks and group used for background label creation.
		const ACTION_CREATE_LABEL        = 'pr_dhl_create_label_async';
		const ACTION_CREATE_LABELS_BATCH = 'pr_dhl_create_labels_batch_async';
		const ACTION_GROUP               = 'pr-dhl-labels';

		// Per-order background label job state.
		const JOB_STATUS_META = '_pr_dhl_label_job';
		const JOB_PENDING     = 'pending';
		const JOB_CREATED     = 'created';
		const JOB_FAILED      = 'failed';

		protected $shipping_dhl_settings = array();

		protected $service = 'DHL';

		protected $carrier = '';

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {
			$this->define_constants();
			$this->init_hooks();

			$this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		}

		protected function define_constants() {
		}

		public function init_hooks() {
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 20, 2 );
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 2 );

			// Order page metabox actions
			add_action( 'wp_ajax_wc_shipment_dhl_gen_label', array( $this, 'save_meta_box_ajax' ) );
			add_action( 'wp_ajax_wc_shipment_dhl_delete_label', array( $this, 'delete_label_ajax' ) );

			// Background (Action Scheduler) label-creation callbacks.
			add_action( self::ACTION_CREATE_LABEL, array( $this, 'create_label_async' ), 10, 1 );
			add_action( self::ACTION_CREATE_LABELS_BATCH, array( $this, 'create_labels_batch_async' ), 10, 3 );

			// Live progress UI for background bulk-label jobs on the Orders screen, and its AJAX endpoints.
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_label_batch_assets' ) );
			add_action( 'admin_notices', array( $this, 'render_label_batch_progress' ) );
			add_action( 'wp_ajax_pr_dhl_label_batch_progress', array( $this, 'ajax_label_batch_progress' ) );
			add_action( 'wp_ajax_pr_dhl_label_batch_download', array( $this, 'ajax_label_batch_download' ) );
			add_action( 'wp_ajax_pr_dhl_label_batch_retry', array( $this, 'ajax_label_batch_retry' ) );
			add_action( 'wp_ajax_pr_dhl_label_batch_dismiss', array( $this, 'ajax_label_batch_dismiss' ) );

			// Prevent the DHL tracking number being copied from a subscription to its renewal and resubscribe orders.
			// Detect the capability rather than a version number: the data copier (and the array-based
			// wc_subscriptions_{type}_data filters) were introduced together in subscriptions-core 2.5.0.
			if ( class_exists( 'WC_Subscriptions_Data_Copier' ) ) {
				// subscriptions-core 2.5.0+: the data is passed as an array keyed by meta_key.
				add_filter( 'wc_subscriptions_renewal_order_data', array( $this, 'remove_dhl_tracking_meta' ), 10 );
				add_filter( 'wc_subscriptions_resubscribe_order_data', array( $this, 'remove_dhl_tracking_meta' ), 10 );
			} elseif ( class_exists( 'WC_Subscriptions' ) ) {
				// Older WooCommerce Subscriptions: the data is filtered as a SQL meta_query string.
				add_filter( 'wcs_renewal_order_meta_query', array( $this, 'remove_dhl_tracking_meta_query' ), 10 );
			}

			// add bulk actions to the Orders screen table bulk action drop-downs
			add_action( 'admin_footer', array( $this, 'add_order_bulk_actions' ) );

			// process orders bulk actions
			// add_action( 'load-edit.php', array( $this, 'process_orders_bulk_actions' ) );
			// add_action( 'handle_bulk_actions-edit-shop_order', array( $this, 'process_orders_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-woocommerce_page_wc-orders', array( $this, 'process_orders_bulk_actions' ), 10, 3 );
			add_filter( 'handle_bulk_actions-edit-shop_order', array( $this, 'process_orders_bulk_actions' ), 10, 3 );

			// display admin notices for bulk actions
			add_action( 'admin_notices', array( $this, 'render_messages' ) );

			add_action( 'init', array( $this, 'add_download_label_endpoint' ) );
			add_action( 'parse_query', array( $this, 'process_download_label' ) );

			// add {tracking_note} placeholder
			add_filter( 'woocommerce_email_format_string', array( $this, 'add_tracking_note_email_placeholder' ), 10, 2 );

			add_shortcode( 'pr_dhl_tracking_note', array( $this, 'tracking_note_shortcode' ) );
			add_shortcode( 'pr_dhl_tracking_link', array( $this, 'tracking_link_shortcode' ) );
		}

		/**
         * Init order object for meta box.
         *
         * @param WP_POST|WC_Order $metabox_object Either WP_Post or WC_Order object.
         */
		public function init_order_object( $metabox_object ) {
			if ( is_a( $metabox_object, 'WP_Post' ) ) {
				return wc_get_order( $metabox_object->ID );
			}

			if ( is_a( $metabox_object, 'WC_Order' ) ) {
				return $metabox_object;
			}

			return false;
		}

		/**
		 * Add the meta box for shipment info on the order page
		 *
		 * @access public
		 */
        public function add_meta_box( $post_type, $post_or_order_object ) {
			$order = $this->init_order_object( $post_or_order_object );

			if ( ! is_a( $order, 'WC_Order' ) || ! API_Utils::order_needs_shipping( $order ) ) {
				return;
			}

			$screen = API_Utils::is_HPOS() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';
			add_meta_box(
				'woocommerce-shipment-dhl-label',
				/* translators: %s is the name of the service (e.g., DHL) */
				sprintf( esc_html__( '%s Label & Tracking', 'dhl-for-woocommerce' ), $this->service ),
				array( $this, 'meta_box' ),
				$screen,
				'side',
				'high'
			);
		}

		/**
		 * Show the meta box for shipment info on the order page
		 *
		 * @access public
		 */
		public function meta_box( $post_or_order_object ) {
            $order    = $this->init_order_object( $post_or_order_object );
			$order_id = $order->get_id();

			// Get saved label input fields or set default values
			$dhl_label_items = $this->get_dhl_label_items( $order_id );

			// Get saved weight, otherwise calculate it from the item weights
			if ( ! empty( $dhl_label_items['pr_dhl_weight'] ) ) {
				$selected_weight_val = $dhl_label_items['pr_dhl_weight'];
			} else {
				$selected_weight_val = $this->calculate_order_weight( $order_id );
			}

			// Get saved product, otherwise get the default product in settings
			if ( ! empty( $dhl_label_items['pr_dhl_product'] ) ) {
				$selected_dhl_product = $dhl_label_items['pr_dhl_product'];
			} else {
				$selected_dhl_product = $this->get_default_dhl_product( $order_id );
			}

			// Get the list of domestic and international DHL services
			try {
				$dhl_obj = PR_DHL()->get_dhl_factory();

				if ( $this->is_shipping_domestic( $order_id ) ) {
					$dhl_product_list = $dhl_obj->get_dhl_products_domestic();
				} else {
					$dhl_product_list = $dhl_obj->get_dhl_products_international();
				}
			} catch ( Exception $e ) {
				echo '<p class="wc_dhl_error">' . esc_html( $e->getMessage() ) . '</p>';
			}

			$delete_label = '';
			if ( $this->can_delete_label( $order_id ) ) {
				$delete_label = '<span class="wc_dhl_delete"><a href="#" id="dhl_delete_label">' . esc_html__( 'Delete Label', 'dhl-for-woocommerce' ) . '</a></span>';
			}

			$main_button = '<button id="dhl-label-button" class="button button-primary button-save-form">' . esc_html__( 'Generate Label', 'dhl-for-woocommerce' ) . '</button>';

			$return_label_button = '';

			// Get tracking info if it exists
			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			// Check whether the label has already been created or not
			if ( empty( $label_tracking_info ) ) {
				$is_disabled = '';

				$print_button = '<a href="#" id="dhl-label-print" class="button button-primary" download target="_blank">' . esc_html__( 'Download Label', 'dhl-for-woocommerce' ) . '</a>';

			} else {
				$is_disabled = 'disabled';

				$print_button = '<a href="' . $this->get_download_label_url( $order_id ) . '" id="dhl-label-print" class="button button-primary" download target="_blank">' . esc_html__( 'Download Label', 'dhl-for-woocommerce' ) . '</a>';

				// Only show when the return label was saved as its own file (setting enabled + label has a return part).
				if ( ! empty( $label_tracking_info['return_label_path'] ) ) {
					$return_label_button = '<a href="' . esc_url( $this->get_download_return_label_url( $order_id ) ) . '" id="dhl-return-label-print" class="dhl-return-label-link" download target="_blank">' . esc_html__( 'Download Return Label', 'dhl-for-woocommerce' ) . '</a>';
				}
			}

			$dhl_label_data = array(
				'main_button'         => $main_button,
				'delete_label'        => $delete_label,
				'print_button'        => $print_button,
				'return_label_button' => $return_label_button,
				'return_label_text'   => esc_html__( 'Download Return Label', 'dhl-for-woocommerce' ),
			);

			echo '<div id="shipment-dhl-label-form">';

			echo $this->get_label_job_status_notice( $order_id, $label_tracking_info );

			if ( ! empty( $dhl_product_list ) ) {

				woocommerce_wp_hidden_input(
					array(
						'id'    => 'pr_dhl_label_nonce',
						'value' => wp_create_nonce( 'create-dhl-label' ),
					)
				);

				echo '<div class="shipment-dhl-row-container shipment-dhl-row-service">';
					echo '<div class="shipment-dhl-icon-container"><span class="shipment-dhl-icon shipment-dhl-icon-service"></span> ' . esc_html__( 'Service', 'dhl-for-woocommerce' ) . '</div>';
					woocommerce_wp_select(
						array(
							'id'                => 'pr_dhl_product',
							'label'             => esc_html__( 'Service selected:', 'dhl-for-woocommerce' ),
							'description'       => '',
							'value'             => $selected_dhl_product,
							'options'           => $dhl_product_list,
							'custom_attributes' => array( $is_disabled => $is_disabled ),
						)
					);
				echo '</div>';

				echo '<div class="shipment-dhl-row-container shipment-dhl-row-weight">';

					$weight_units = get_option( 'woocommerce_weight_unit' );

					// Get weight UoM and add in label
					echo '<div class="shipment-dhl-icon-container"><span class="shipment-dhl-icon shipment-dhl-icon-weight"></span> ' . esc_html__( 'Weight', 'dhl-for-woocommerce' ) . '</div>';

					woocommerce_wp_text_input(
						array(
							'id'                => 'pr_dhl_weight',
							/* translators: %s is the weight unit (e.g., kg or lbs) */
							'label'             => sprintf( esc_html__( 'Estimated shipment weight (%s) based on items ordered: ', 'dhl-for-woocommerce' ), $weight_units ),
							'placeholder'       => '',
							'description'       => '',
							'value'             => $selected_weight_val,
							'custom_attributes' => array( $is_disabled => $is_disabled ),
							'class'             => 'wc_input_decimal', // adds JS to validate input is in price format
						)
					);
				echo '</div>';

				$this->additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );

				// A label has been generated already, allow to delete
				if ( empty( $label_tracking_info ) ) {
					echo $main_button;
				} else {
					echo $print_button;
					echo $return_label_button;
					echo $delete_label;
				}

				wp_enqueue_script( 'wc-shipment-dhl-label-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl.js', array( 'jquery' ), PR_DHL_VERSION );
				wp_localize_script( 'wc-shipment-dhl-label-js', 'dhl_label_data', $dhl_label_data );

			} else {
				echo '<p class="wc_dhl_error">' . esc_html__( 'There are no DHL services available for the destination country!', 'dhl-for-woocommerce' ) . '</p>';
			}

			echo '</div>';
		}

		protected function can_delete_label( $order_id ) {
			return true;
		}

		abstract public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj );


		public function save_meta_box( $post_id, $post = null ) {

			$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
			if ( ! empty( $screen ) ) {
				if ( isset( $screen->post_type ) && 'shop_order' !== $screen->post_type ) {
					return;
				}
			}

			if ( empty( $_POST['pr_dhl_label_nonce'] ) ) {
				return;
			}

			$nonce = sanitize_text_field( wp_unslash( $_POST['pr_dhl_label_nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'create-dhl-label' ) ) {
				return;
			}

			$meta_box_ids = array( 'pr_dhl_product', 'pr_dhl_weight' );

			$additional_meta_box_ids = $this->get_additional_meta_ids();
			if ( is_array( $additional_meta_box_ids ) && ! empty( $additional_meta_box_ids ) ) {
				$meta_box_ids = array_merge( $meta_box_ids, $additional_meta_box_ids );
			}

			$args = array();

			foreach ( $meta_box_ids as $value ) {
				if ( isset( $_POST[ $value ] ) ) {
					$args[ $value ] = wc_clean( $_POST[ $value ] );
				}
			}

			if ( ! empty( $args ) ) {
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
		public function save_meta_box_ajax() {
			check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
			$order_id = wc_clean( $_POST['order_id'] );

			// Save inputted data first
			$this->save_meta_box( $order_id );

			try {

				// Gather args for DHL API call
				$args = $this->get_label_args( $order_id );

				$this->create_dhl_label( $order_id, $args );

				$tracking_note      = $this->get_tracking_note( $order_id );
				$tracking_note_type = $this->get_tracking_note_type();
				$label_url          = $this->get_download_label_url( $order_id );

				wp_send_json(
					array(
						'download_msg'       => esc_html__( 'Your DHL label is ready to download, click the "Download Label" button above"', 'dhl-for-woocommerce' ),
						'button_txt'         => esc_html__( 'Download Label', 'dhl-for-woocommerce' ),
						'label_url'          => $label_url,
						'return_label_url'   => $this->get_download_return_label_url( $order_id ),
						'tracking_note'      => $tracking_note,
						'tracking_note_type' => $tracking_note_type,
					)
				);

			} catch ( Exception $e ) {

				wp_send_json( array( 'error' => $e->getMessage() ) );
			}

			wp_die();
		}

		/**
		 * Creates a DHL label for a single order and persists its tracking data.
		 *
		 * Shared by the single-order and bulk label flows: applies the label-args
		 * filter, calls the DHL API, saves the returned tracking data and fires the
		 * label-created hook.
		 *
		 * @param int   $order_id Order ID.
		 * @param array $args     Label args already gathered via get_label_args().
		 *
		 * @return array The label tracking info returned by the DHL API.
		 * @throws Exception If the DHL API fails to create the label.
		 */
		protected function create_dhl_label( $order_id, $args ) {
			// Allow third parties to modify the args to the DHL APIs
			$args = apply_filters( 'pr_shipping_dhl_label_args', $args, $order_id );

			$dhl_obj             = PR_DHL()->get_dhl_factory();
			$label_tracking_info = $dhl_obj->get_dhl_label( $args );

			$this->save_dhl_label_tracking( $order_id, $label_tracking_info );

			do_action( 'pr_shipping_dhl_label_created', $order_id );

			return $label_tracking_info;
		}

		/**
		 * Whether WooCommerce Action Scheduler is available for background jobs.
		 *
		 * @return bool
		 */
		public function is_action_scheduler_available() {
			return function_exists( 'as_enqueue_async_action' ) && function_exists( 'as_has_scheduled_action' );
		}

		/**
		 * Queues background label creation for a single order.
		 *
		 * Runs the work in an Action Scheduler job when available, otherwise falls
		 * back to synchronous execution so the label is still created. A second job
		 * is not queued while one is already pending for the same order.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return bool True if a background job was queued, false if it ran synchronously or was already queued.
		 */
		public function schedule_label_creation( $order_id ) {
			if ( ! $this->is_action_scheduler_available() ) {
				// Graceful fallback: create the label in the current request.
				$this->create_label_async( $order_id );

				return false;
			}

			// Do not queue a second job while one is already pending for this order.
			if ( as_has_scheduled_action( self::ACTION_CREATE_LABEL, array( $order_id ), self::ACTION_GROUP ) ) {
				return false;
			}

			as_enqueue_async_action( self::ACTION_CREATE_LABEL, array( $order_id ), self::ACTION_GROUP );
			$this->set_label_job_status( $order_id, self::JOB_PENDING );

			return true;
		}

		/**
		 * Action Scheduler callback: create a DHL label for one order in the background.
		 *
		 * Rebuilds the label args from the stored order data rather than relying on a
		 * serialized job payload, skips orders that already have a label, and records
		 * any failure as an order note so it is never silently lost.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return void
		 */
		public function create_label_async( $order_id ) {
			// Skip if a label has already been created for this order.
			if ( ! empty( $this->get_dhl_label_tracking( $order_id ) ) ) {
				$this->set_label_job_status( $order_id, self::JOB_CREATED );

				return;
			}

			// An order deleted between queuing and running would otherwise fatal deep in get_label_args();
			// record it and move on instead.
			if ( ! wc_get_order( $order_id ) instanceof WC_Order ) {
				$this->set_label_job_status( $order_id, self::JOB_FAILED, array( 'message' => esc_html__( 'The order no longer exists.', 'dhl-for-woocommerce' ) ) );

				return;
			}

			// Build the request. A failure here is before any purchase, so the order is safe to retry.
			try {
				$this->save_default_dhl_label_items( $order_id );
				$args = $this->get_label_args( $order_id );
				$args = apply_filters( 'pr_shipping_dhl_label_args', $args, $order_id );
			} catch ( Throwable $e ) {
				$this->record_async_label_failure( $order_id, $e->getMessage() );

				return;
			}

			// Purchase the label. Still safe to retry if this throws — nothing was bought.
			try {
				$label_tracking_info = PR_DHL()->get_dhl_factory()->get_dhl_label( $args );
			} catch ( Throwable $e ) {
				$this->record_async_label_failure( $order_id, $e->getMessage() );

				return;
			}

			// The label is now bought at DHL. A storage failure past this point must NOT be blindly
			// retried (that would buy a second label), so flag it purchased — matching the batch path.
			try {
				$this->save_dhl_label_tracking( $order_id, $label_tracking_info );
				do_action( 'pr_shipping_dhl_label_created', $order_id );
				$this->set_label_job_status(
					$order_id,
					self::JOB_CREATED,
					array(
						'warnings' => isset( $label_tracking_info['dhl_label_warnings'] ) ? $label_tracking_info['dhl_label_warnings'] : array(),
					)
				);
			} catch ( Throwable $e ) {
				$this->record_async_label_failure(
					$order_id,
					sprintf(
						/* translators: %s is the storage error message */
						esc_html__( 'DHL created the label but it could not be saved (%s). A label may already exist at DHL — verify before retrying.', 'dhl-for-woocommerce' ),
						$e->getMessage()
					),
					array( 'purchased' => true )
				);
			}
		}

		/**
		 * Splits a bulk selection into background label-creation jobs, one Action Scheduler
		 * action per chunk, each of which creates its chunk in a single batched request.
		 *
		 * @param array $order_ids            Selected order IDs.
		 * @param mixed $dhl_force_product    Forced DHL product for the whole batch, or false.
		 * @param bool  $is_force_product_dom Whether the forced product is domestic.
		 *
		 * @return array Bulk-action feedback messages.
		 */
		protected function enqueue_bulk_label_jobs( $order_ids, $dhl_force_product = false, $is_force_product_dom = false ) {
			// Queue only orders that have no label yet and no job already in flight, so re-running
			// the bulk action (or a double-click) cannot enqueue a second job for the same order.
			$pending_orders = array();
			foreach ( $order_ids as $order_id ) {
				if ( ! empty( $this->get_dhl_label_tracking( $order_id ) ) ) {
					continue;
				}

				$job = $this->get_label_job_status( $order_id );
				if ( self::JOB_PENDING === $job['status'] ) {
					continue;
				}

				$pending_orders[] = $order_id;
			}

			if ( empty( $pending_orders ) ) {
				return array(
					array(
						'message' => esc_html__( 'No orders needed queuing — the selected orders already have a DHL label or a background job in progress.', 'dhl-for-woocommerce' ),
						'type'    => 'warning',
					),
				);
			}

			$chunk_size = max( 1, (int) apply_filters( 'pr_dhl_bulk_label_chunk_size', 10 ) );

			foreach ( array_chunk( $pending_orders, $chunk_size ) as $chunk ) {
				as_enqueue_async_action(
					self::ACTION_CREATE_LABELS_BATCH,
					array( $chunk, $dhl_force_product, $is_force_product_dom ),
					self::ACTION_GROUP
				);
			}

			foreach ( $pending_orders as $order_id ) {
				$this->set_label_job_status( $order_id, self::JOB_PENDING );
			}

			$this->start_label_batch( $pending_orders );

			return array(
				array(
					'message' => sprintf(
						/* translators: %d is the number of orders queued */
						esc_html( _n( '%d order queued for background DHL label creation.', '%d orders queued for background DHL label creation.', count( $pending_orders ), 'dhl-for-woocommerce' ) ),
						count( $pending_orders )
					),
					'type'    => 'success',
				),
			);
		}

		/**
		 * Queues one background label-creation job per order, for APIs without a batch endpoint
		 * (e.g. Deutsche Post). Orders that already have a label or a job in flight are skipped so
		 * re-running the bulk action cannot enqueue a duplicate job for the same order.
		 *
		 * @param array $order_ids Selected order IDs.
		 *
		 * @return array Bulk-action feedback messages.
		 */
		protected function enqueue_per_order_label_jobs( $order_ids ) {
			$pending_orders = array();
			foreach ( $order_ids as $order_id ) {
				if ( ! empty( $this->get_dhl_label_tracking( $order_id ) ) ) {
					continue;
				}

				if ( ! $this->schedule_label_creation( $order_id ) ) {
					continue;
				}

				$pending_orders[] = $order_id;
			}

			if ( empty( $pending_orders ) ) {
				return array(
					array(
						'message' => esc_html__( 'No orders needed queuing — the selected orders already have a DHL label or a background job in progress.', 'dhl-for-woocommerce' ),
						'type'    => 'warning',
					),
				);
			}

			$this->start_label_batch( $pending_orders );

			return array(
				array(
					'message' => sprintf(
						/* translators: %d is the number of orders queued */
						esc_html( _n( '%d order queued for background DHL label creation.', '%d orders queued for background DHL label creation.', count( $pending_orders ), 'dhl-for-woocommerce' ) ),
						count( $pending_orders )
					),
					'type'    => 'success',
				),
			);
		}

		/**
		 * Action Scheduler callback: create labels for a chunk of orders in one batched request.
		 *
		 * Orders that already have a label are skipped before the request is built, so a retried
		 * job never buys a second label for an order whose label was already saved.
		 *
		 * @param array $order_ids            Order IDs in this chunk.
		 * @param mixed $dhl_force_product    Forced DHL product for the whole batch, or false.
		 * @param bool  $is_force_product_dom Whether the forced product is domestic.
		 *
		 * @return void
		 */
		public function create_labels_batch_async( $order_ids, $dhl_force_product = false, $is_force_product_dom = false ) {
			$dhl_obj = PR_DHL()->get_dhl_factory();

			if ( ! method_exists( $dhl_obj, 'get_dhl_labels' ) ) {
				return;
			}

			$batch_args = array();

			foreach ( (array) $order_ids as $order_id ) {
				// Idempotency: never recreate a label that already exists (covers job retries).
				if ( ! empty( $this->get_dhl_label_tracking( $order_id ) ) ) {
					$this->set_label_job_status( $order_id, self::JOB_CREATED );
					continue;
				}

				try {
					$this->save_default_dhl_label_items( $order_id );

					$args = $this->get_label_args( $order_id );

					if ( $dhl_force_product ) {
						if ( $is_force_product_dom && $this->is_shipping_domestic( $order_id ) ) {
							$args['order_details']['dhl_product'] = $dhl_force_product;
						}

						if ( ! $is_force_product_dom && ! $this->is_shipping_domestic( $order_id ) ) {
							$args['order_details']['dhl_product'] = $dhl_force_product;
						}
					}

					$args = $this->get_bulk_settings_override( $args );
					$args = apply_filters( 'pr_shipping_dhl_label_args', $args, $order_id );

					$batch_args[ $order_id ] = $args;
				} catch ( Throwable $e ) {
					$this->record_async_label_failure( $order_id, $e->getMessage() );
				}
			}

			if ( empty( $batch_args ) ) {
				return;
			}

			try {
				$labels_result = $dhl_obj->get_dhl_labels( array_values( $batch_args ) );
			} catch ( Throwable $e ) {
				foreach ( array_keys( $batch_args ) as $order_id ) {
					$this->record_async_label_failure( $order_id, $e->getMessage() );
				}

				return;
			}

			$handled = array();

			foreach ( $labels_result['labels'] as $label_tracking_info ) {
				$order_id = $label_tracking_info['order_id'];

				// The label has already been purchased at DHL. Persist it, but never let a storage
				// failure escape this callback: an uncaught error would make Action Scheduler retry
				// the whole chunk and buy a second label for every order that was not yet saved.
				try {
					$this->save_created_dhl_label( $order_id, $label_tracking_info );
					$this->set_label_job_status(
						$order_id,
						self::JOB_CREATED,
						array(
							'warnings' => isset( $label_tracking_info['dhl_label_warnings'] ) ? $label_tracking_info['dhl_label_warnings'] : array(),
						)
					);
				} catch ( Throwable $e ) {
					// Flag the purchased-but-unsaved case distinctly so a later retry does not
					// blindly buy a duplicate for a label that already exists at DHL.
					$this->record_async_label_failure(
						$order_id,
						sprintf(
							/* translators: %s is the storage error message */
							esc_html__( 'DHL created the label but it could not be saved (%s). A label may already exist at DHL — verify before retrying.', 'dhl-for-woocommerce' ),
							$e->getMessage()
						),
						array( 'purchased' => true )
					);
				}

				$handled[ $order_id ] = true;
			}

			foreach ( $labels_result['errors'] as $error ) {
				if ( ! empty( $error['order_id'] ) ) {
					$this->record_async_label_failure( $error['order_id'], $error['message'] );
					$handled[ $error['order_id'] ] = true;
				}
			}

			// Any queued order the API neither created nor reported is a failure, so nothing is left pending.
			foreach ( array_keys( $batch_args ) as $order_id ) {
				if ( empty( $handled[ $order_id ] ) ) {
					$this->record_async_label_failure( $order_id, esc_html__( 'DHL did not return a label for this order.', 'dhl-for-woocommerce' ) );
				}
			}
		}

		/**
		 * Appends a plain-language hint to connection/timeout failures so a transient network problem
		 * reads as "reachability, use Retry" rather than a raw cURL string. Other errors pass through.
		 *
		 * @param string $message Raw failure message.
		 *
		 * @return string
		 */
		protected function humanize_label_error( $message ) {
			// Connection-level failures (timeouts, DNS, refused, SSL) are almost always transient network
			// issues rather than bad order data, so point the merchant at Retry instead of a raw cURL string.
			if ( preg_match( '/cURL error (7|28|35|56)|timed out|timeout|could not resolve host|failed to connect|ssl connection/i', $message ) ) {
				return $message . ' ' . esc_html__( '(The DHL API could not be reached — usually a temporary network issue; use Retry.)', 'dhl-for-woocommerce' );
			}

			return $message;
		}

		/**
		 * Records a failed background label creation on the order: job state plus an order note.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $message  Failure reason.
		 * @param array  $context  Optional extra job-state context, e.g. 'purchased' => true when the label
		 *                         was bought at DHL but could not be saved locally.
		 *
		 * @return void
		 */
		protected function record_async_label_failure( $order_id, $message, $context = array() ) {
			$message            = $this->humanize_label_error( $message );
			$context['message'] = $message;
			$this->set_label_job_status( $order_id, self::JOB_FAILED, $context );

			$order = wc_get_order( $order_id );

			if ( $order instanceof WC_Order ) {
				$order->add_order_note(
					sprintf(
						/* translators: %s is the error message returned by the DHL API */
						esc_html__( 'DHL label could not be created: %s', 'dhl-for-woocommerce' ),
						$message
					)
				);
			}
		}

		/**
		 * Records the background label job state for an order.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $status   One of self::JOB_PENDING, self::JOB_CREATED, self::JOB_FAILED.
		 * @param array  $context  Optional 'message' (failure reason), 'warnings' (string[]) and
		 *                         'purchased' (bool: the label was bought at DHL but could not be saved).
		 *
		 * @return void
		 */
		public function set_label_job_status( $order_id, $status, $context = array() ) {
			$order = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				return;
			}

			$order->update_meta_data(
				self::JOB_STATUS_META,
				array(
					'status'    => $status,
					'message'   => isset( $context['message'] ) ? $context['message'] : '',
					'warnings'  => isset( $context['warnings'] ) ? (array) $context['warnings'] : array(),
					'purchased' => ! empty( $context['purchased'] ),
				)
			);
			$order->save_meta_data();
		}

		/**
		 * Returns the background label job state for an order.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return array{status: string, message: string, warnings: array, purchased: bool} Empty status when no job has run.
		 */
		public function get_label_job_status( $order_id ) {
			$default = array(
				'status'    => '',
				'message'   => '',
				'warnings'  => array(),
				'purchased' => false,
			);

			$order = wc_get_order( $order_id );

			if ( ! $order instanceof WC_Order ) {
				return $default;
			}

			$status = $order->get_meta( self::JOB_STATUS_META );

			if ( ! is_array( $status ) ) {
				return $default;
			}

			return wp_parse_args( $status, $default );
		}

		/**
		 * Renders the background label job state for the order metabox: a "being created" notice while a
		 * job is pending and the failure reason when the last job failed. Returns an empty string once a
		 * label exists (the download button already makes that state obvious) or when no job has run.
		 *
		 * @param int        $order_id            Order ID.
		 * @param array|null $label_tracking_info Already-loaded tracking info, to avoid a second lookup.
		 *
		 * @return string HTML markup, or an empty string when there is nothing to show.
		 */
		protected function get_label_job_status_notice( $order_id, $label_tracking_info = null ) {
			if ( null === $label_tracking_info ) {
				$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			}

			if ( ! empty( $label_tracking_info ) ) {
				return '';
			}

			$job = $this->get_label_job_status( $order_id );

			if ( self::JOB_PENDING === $job['status'] ) {
				return '<p class="wc_dhl_label_job wc_dhl_label_job--pending">'
					. esc_html__( 'A DHL label is being created for this order in the background. Reload the page to see the result.', 'dhl-for-woocommerce' )
					. '</p>';
			}

			if ( self::JOB_FAILED === $job['status'] ) {
				$message = '' !== $job['message']
					? $job['message']
					: __( 'The DHL label could not be created.', 'dhl-for-woocommerce' );

				return '<p class="wc_dhl_error wc_dhl_label_job wc_dhl_label_job--failed">'
					. sprintf(
						/* translators: %s is the failure reason returned by DHL */
						esc_html__( 'Background DHL label creation failed: %s', 'dhl-for-woocommerce' ),
						esc_html( $message )
					)
					. '</p>';
			}

			return '';
		}

		public function delete_label_ajax() {
			check_ajax_referer( 'create-dhl-label', 'pr_dhl_label_nonce' );
			$order_id = wc_clean( $_POST['order_id'] );

			try {

				$tracking_num = $this->processing_delete_label( $order_id );

				if ( empty( $tracking_num ) ) {
					throw new Exception( esc_html__( 'There are no tracking number to delete.', 'dhl-for-woocommerce' ) );
				}

				wp_send_json(
					array(
						'download_msg'     => esc_html__( 'Your DHL label is ready to download, click the "Download Label" button above"', 'dhl-for-woocommerce' ),
						'button_txt'       => esc_html__( 'Generate Label', 'dhl-for-woocommerce' ),
						'dhl_tracking_num' => $tracking_num,
					)
				);

			} catch ( Exception $e ) {

				wp_send_json( array( 'error' => $e->getMessage() ) );
			}
		}

		protected function get_download_label_url( $order_id ) {

			if ( empty( $order_id ) ) {
				return '';
			}

			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			// Check whether the label has already been created or not
			if ( empty( $label_tracking_info ) ) {
				return '';
			}

			// Always serve through our download endpoint so the file is streamed from the
			// protected folder after a capability check, including old "download style"
			// labels that only stored a public 'label_url'.
			return $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/' . $order_id );
		}

		/**
		 * Builds the download URL for the separate return label, if one was saved.
		 *
		 * @param int $order_id The order ID.
		 *
		 * @return string The download URL, or an empty string when no separate return label exists.
		 */
		protected function get_download_return_label_url( $order_id ) {

			if ( empty( $order_id ) ) {
				return '';
			}

			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			// Only build the URL when a separate return label file exists.
			if ( empty( $label_tracking_info['return_label_path'] ) ) {
				return '';
			}

			return add_query_arg(
				'dhl_label_type',
				'return',
				$this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/' . $order_id )
			);
		}

		protected function get_tracking_note( $order_id ) {

			if ( ! empty( $this->shipping_dhl_settings['dhl_tracking_note_txt'] ) ) {
				$tracking_note = $this->shipping_dhl_settings['dhl_tracking_note_txt'];
			} else {
				/* translators: %s is the tracking link */
				$tracking_note = sprintf( esc_html__( '%s Tracking Number: {tracking-link}', 'dhl-for-woocommerce' ), $this->service );
			}

			$tracking_link = $this->get_tracking_link( $order_id );

			if ( empty( $tracking_link ) ) {
				return '';
			}

			$tracking_note_new = str_replace( '{tracking-link}', $tracking_link, $tracking_note, $count );

			if ( $count == 0 ) {
				$tracking_note_new = $tracking_note . ' ' . $tracking_link;
			}

			$return_label_number = $this->get_return_label_number( $order_id );
			if ( $return_label_number ) {
				if ( is_array( $return_label_number ) ) {
					$return_label_number = implode( ', ', $return_label_number );
				}

				/* translators: %s is the return label number */
				$tracking_note_return_label = sprintf( esc_html__( "\n Return Label Number: %s", 'dhl-for-woocommerce' ), $return_label_number );
				$tracking_note_new          = $tracking_note_new . $tracking_note_return_label;
			}

			return $tracking_note_new;
		}

		protected function get_tracking_link( $order_id ) {

			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			if ( empty( $label_tracking_info['tracking_number'] ) ) {
				return '';
			}

			return sprintf(
			/* translators: %1$s is the base tracking URL, %2$s is the tracking number, %3$s is the tracking number displayed as link text */
				'<a href="%1$s%2$s" target="_blank">%3$s</a>',
				esc_url( $this->get_tracking_url() ),
				esc_html( $label_tracking_info['tracking_number'] ),
				esc_html( $label_tracking_info['tracking_number'] )
			);
		}

		protected function get_return_label_number( $order_id ) {

			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
			if ( empty( $label_tracking_info['return_label_number'] ) ) {
				return '';
			}

			return $label_tracking_info['return_label_number'];
		}

		abstract protected function get_tracking_url();

		protected function get_tracking_note_type() {
			if ( isset( $this->shipping_dhl_settings['dhl_tracking_note'] ) && ( $this->shipping_dhl_settings['dhl_tracking_note'] == 'yes' ) ) {
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

			extract(
				shortcode_atts(
					array(
						'order_id' => '',
					),
					$atts
				)
			);

			if ( $order = wc_get_order( $order_id ) ) {

				return $this->get_tracking_note( $order->get_id() );

			}

			return '';
		}

		public function tracking_link_shortcode( $atts, $content ) {

			extract(
				shortcode_atts(
					array(
						'order_id' => '',
					),
					$atts
				)
			);

			if ( $order = wc_get_order( $order_id ) ) {

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

			if ( isset( $tracking_items['label_path'] ) && validate_file( $tracking_items['label_path'] ) === 2 ) {
				$tracking_items['label_path'] = wp_slash( $tracking_items['label_path'] );
			}

			// Protect the return label path's backslashes on Windows the same way as the label path.
			if ( isset( $tracking_items['return_label_path'] ) && validate_file( $tracking_items['return_label_path'] ) === 2 ) {
				$tracking_items['return_label_path'] = wp_slash( $tracking_items['return_label_path'] );
			}

			$order = wc_get_order( $order_id );
			$order->update_meta_data( '_pr_shipment_dhl_label_tracking', $tracking_items );
			$order->save_meta_data();

			$tracking_numbers = is_array( $tracking_items['tracking_number'] ) ? $tracking_items['tracking_number'] : array( $tracking_items['tracking_number'] );
			$ship_date        = gmdate( 'Y-m-d', time() );

			foreach ( $tracking_numbers as $tracking_number ) {
				if ( empty( $tracking_number ) ) {
					continue;
				}

				$tracking_details = array(
					'carrier'         => $this->carrier,
					'tracking_number' => $tracking_number,
					'ship_date'       => $ship_date,
					'tracking_url'    => $this->get_tracking_url() . $tracking_number,
				);

				// Primarily added for "Advanced Tracking" plugin integration.
				// Will be triggered for each ( Label ) tracking number.
				do_action( 'pr_save_dhl_label_tracking', $order_id, $tracking_details );

				// Add support for "WooCommerce Shipment Tracking" plugin.
				if ( function_exists( 'wc_st_add_tracking_number' ) ) {
					wc_st_add_tracking_number(
						$order_id,
						$tracking_details['tracking_number'],
						$tracking_details['carrier'],
						time(),
						$tracking_details['tracking_url']
					);
				}
			}
		}

		/**
		 * Gets all tracking items from the post meta array for an order.
		 *
		 * @param int  $order_id  Order ID
		 *
		 * @return array Tracking items
		 */
		public function get_dhl_label_tracking( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return array();
			}

			$label_tracking = $order->get_meta( '_pr_shipment_dhl_label_tracking' );

			return is_array( $label_tracking ) ? $label_tracking : array();
		}

		/**
		 * Delete the tracking items array to post_meta.
		 *
		 * @param int $order_id       Order ID
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

			if ( is_array( $dhl_label_items ) ) {
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

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return array();
			}
			
			return $order->get_meta( '_pr_shipment_dhl_label_items' );
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

			if ( empty( $dhl_label_items ) ) {
				$dhl_label_items = array();
			}

			if ( empty( $dhl_label_items['pr_dhl_weight'] ) ) {
				// Set default weight
				$dhl_label_items['pr_dhl_weight'] = $this->calculate_order_weight( $order_id );
			}

			if ( empty( $dhl_label_items['pr_dhl_product'] ) ) {
				// Set default DHL product
				$dhl_label_items['pr_dhl_product'] = $this->get_default_dhl_product( $order_id );
			}

			// Save default items
			$this->save_dhl_label_items( $order_id, $dhl_label_items );
		}

		protected function get_default_dhl_product( $order_id ) {
			// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
			if ( $this->is_shipping_domestic( $order_id ) ) {
				return $this->shipping_dhl_settings['dhl_default_product_dom'];
			} else {
				return $this->shipping_dhl_settings['dhl_default_product_int'];
			}
		}

		protected function calculate_order_weight( $order_id ) {

			$total_weight = 0;
			$order        = wc_get_order( $order_id );

			if ( false === $order ) {
				return apply_filters( 'pr_shipping_dhl_order_weight', $total_weight, $order_id );
			}

			$ordered_items = $order->get_items();

			if ( is_array( $ordered_items ) && count( $ordered_items ) > 0 ) {

				foreach ( $ordered_items as $key => $item ) {

					if ( ! empty( $item['variation_id'] ) ) {
						$product = wc_get_product( $item['variation_id'] );
					} else {
						$product = wc_get_product( $item['product_id'] );
					}

					if ( $product ) {
						$product_weight = $product->get_weight();
						if ( $product_weight ) {
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

			return apply_filters( 'pr_shipping_dhl_order_weight', $total_weight, $order_id );
		}

		protected function is_shipping_domestic( $order_id ) {
			$order            = wc_get_order( $order_id );
			$shipping_address = $order->get_address( 'shipping' );
			$shipping_country = $shipping_address['country'];

			if ( PR_DHL()->is_shipping_domestic( $shipping_country ) ) {
				return true;
			} else {
				return false;
			}
		}

		protected function is_crossborder_shipment( $order_id ) {
			$order            = wc_get_order( $order_id );
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
			$weight_units                      = get_option( 'woocommerce_weight_unit' );

			switch ( $weight_units ) {
				case 'lbs':
					$args['order_details']['weightUom'] = 'lb';
					break;
				default:
					$args['order_details']['weightUom'] = $weight_units;
					break;
			}

			$args['order_details']['dimUom'] = get_option( 'woocommerce_dimension_unit' );

			if ( $this->is_cod_payment_method( $order_id ) && empty( $args['order_details']['cod_value'] ) ) {
				$args['order_details']['cod_value'] = $order->get_total();
			}

			// calculate the additional fee
			$additional_fees = 0;
			if ( count( $order->get_fees() ) > 0 ) {
				foreach ( $order->get_fees() as $fee ) {
					$additional_fees += floatval( $fee->get_total() );
				}
			}

			$args['order_details']['additional_fee'] = $additional_fees;
			$args['order_details']['shipping_fee']   = $order->get_shipping_total();
			$args['order_details']['total_value']    = $order->get_total();

			// Get address related information
			$billing_address  = $order->get_address();
			$shipping_address = $order->get_address( 'shipping' );

			// If shipping phone number doesn't exist, try to get billing phone number
			if ( empty( $shipping_address['phone'] ) && ! empty( $billing_address['phone'] ) ) {
				$shipping_address['phone'] = $billing_address['phone'];
			}

			// If shipping email doesn't exist, try to get billing email
			if ( empty( $shipping_address['email'] ) && ! empty( $billing_address['email'] ) ) {
				$shipping_address['email'] = $billing_address['email'];
			}

			// Merge first and last name into "name"
			$shipping_address['name'] = '';
			if ( isset( $shipping_address['first_name'] ) ) {
				$shipping_address['name'] = $shipping_address['first_name'];
				// unset( $shipping_address['first_name'] );
			}

			if ( isset( $shipping_address['last_name'] ) ) {
				if ( ! empty( $shipping_address['name'] ) ) {
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
				if ( ! empty( $states ) && ! empty( $shipping_address['state'] ) ) {
					// Change the state to be the name and not the code
					$shipping_address['state'] = $states[ $shipping_address['state'] ];

					// Remove anything in parentheses (e.g. TH)
					$ind = strpos( $shipping_address['state'], ' (' );
					if ( false !== $ind ) {
						$shipping_address['state'] = substr( $shipping_address['state'], 0, $ind );
					}
				}
			}

			if ( 'DE' === $shipping_address['country'] ) {
				$shipping_address['state'] = trim( $shipping_address['state'], 'DE-' );
			}

			// Check if post number exists then send over
			if ( $shipping_dhl_postnum = $order->get_meta( '_shipping_dhl_postnum' ) ) {
				$shipping_address['dhl_postnum'] = $shipping_dhl_postnum;
			}

			$args['shipping_address'] = $shipping_address;

			// Get order item specific data
			$ordered_items = $order->get_items();
			$args['items'] = array();
			// Sum value of ordered items
			$args['order_details']['items_value'] = 0;
			foreach ( $ordered_items as $key => $item ) {
				// Reset array
				$new_item = array();

				$refunded_qty = $order->get_qty_refunded_for_item( $key );

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
				if ( ! empty( $country_value ) ) {
					$new_item['country_origin'] = $country_value;
				}

				$hs_code = get_post_meta( $item['product_id'], '_dhl_hs_code', true );
				if ( ! empty( $hs_code ) ) {
					$new_item['hs_code'] = $hs_code;
				}

				$new_item['item_description'] = $product->get_title();
				// $new_item['line_total'] = $item['line_total'];

				if ( ! empty( $item['variation_id'] ) ) {
					$product_variation = wc_get_product( $item['variation_id'] );

					// If product variation does not exist (i.e. was deleted) OR is virtual, skip it
					if ( empty( $product_variation ) || $product_variation->is_virtual() ) {
						continue;
					}

					// place 'sku' in a variable before validating using 'empty' to be compatible with PHP v5.4
					$product_sku = $product_variation->get_sku();
					// Ensure id is string and not int
					$new_item['product_id'] = intval( $item['variation_id'] );
					$new_item['sku']        = empty( $product_sku ) ? strval( $item['variation_id'] ) : $product_sku;

					// If value is empty due to discounts, set variation price instead
					if ( empty( $new_item['item_value'] ) ) {
						$new_item['item_value'] = $product_variation->get_price();
					}

					$new_item['item_weight'] = $product_variation->get_weight();

					$product_attribute             = wc_get_product_variation_attributes( $item['variation_id'] );
					$new_item['item_description'] .= ' : ' . current( $product_attribute );

				} else {
					// place 'sku' in a variable before validating using 'empty' to be compatible with PHP v5.4
					$product_sku = $product->get_sku();
					// Ensure id is string and not int
					$new_item['product_id'] = intval( $item['product_id'] );
					$new_item['sku']        = empty( $product_sku ) ? strval( $item['product_id'] ) : $product_sku;

					// If value is empty due to discounts, set product price instead
					if ( empty( $new_item['item_value'] ) ) {
						$new_item['item_value'] = $product->get_price();
					}

					$new_item['item_weight'] = $product->get_weight();
				}

				$new_item += $this->get_label_item_args( $item['product_id'], $args );
				// if( ! empty( $product->post->post_excerpt ) ) {
				// $new_item['item_description'] = $product->post->post_excerpt;
				// } elseif ( ! empty( $product->post->post_content ) ) {
				// $new_item['item_description'] = $product->post->post_content;
				// }

				array_push( $args['items'], $new_item );
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
			$order          = wc_get_order( $order_id );
			$payment_method = $order->get_payment_method();

			if ( $payment_method == 'cod' ) {
				return true;
			}

			return false;
		}

		protected function get_wc_currency( $order_id ) {
			$order = wc_get_order( $order_id );
			return $order->get_currency();
		}

		/**
		 * Prevents the DHL tracking meta being copied to subscription renewals.
		 *
		 * Used on older WooCommerce Subscriptions (subscriptions-core < 2.5.0), where the
		 * wcs_renewal_order_meta_query filter passes a SQL meta_query string.
		 *
		 * @param string $order_meta_query The SQL meta_query string used to copy meta to the renewal order.
		 * @return string
		 */
		public function remove_dhl_tracking_meta_query( $order_meta_query ) {
			$order_meta_query .= " AND `meta_key` NOT IN ( '_pr_shipment_dhl_label_tracking' )";

			return $order_meta_query;
		}

		/**
		 * Removes the DHL tracking meta from the data copied to subscription renewal and resubscribe orders.
		 *
		 * Used on WooCommerce Subscriptions / subscriptions-core 2.5.0+, where the
		 * wc_subscriptions_renewal_order_data and wc_subscriptions_resubscribe_order_data filters pass
		 * an array of meta data keyed by meta_key instead of the legacy SQL meta_query string.
		 *
		 * @param array $order_data Meta data to be copied to the new order, keyed by meta_key.
		 * @return array
		 */
		public function remove_dhl_tracking_meta( $order_data ) {
			unset( $order_data['_pr_shipment_dhl_label_tracking'] );

			return $order_data;
		}

		/**
		 * Bulk functions
		 */
		/**
		 * Whether the current admin request is the WooCommerce Orders list screen (HPOS or legacy).
		 *
		 * @return bool
		 */
		protected function is_orders_list_screen() {
			global $typenow, $pagenow, $current_screen;

			if ( API_Utils::is_HPOS() ) {
				return isset( $current_screen->id )
					&& wc_get_page_screen_id( 'shop-order' ) === $current_screen->id
					&& 'admin.php' === $pagenow;
			}

			return 'shop_order' === $typenow && 'edit.php' === $pagenow;
		}

		public function add_order_bulk_actions() {
			if ( ! $this->is_orders_list_screen() ) {
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
						<?php
						if ( $index ) {
							echo ','; }
						?>
					<?php endforeach; ?>
				);
			} );
		</script>
			<?php
		}

		public function process_orders_bulk_actions( $redirect, $doaction, $object_ids ) {

			if ( ! array_key_exists( $doaction, $this->get_bulk_actions() ) ) {
				return $redirect;
			}

			$array_messages = array( 'msg_user_id' => get_current_user_id() );

			$message = $this->validate_bulk_actions( $doaction, $object_ids );

			if ( ! empty( $message ) ) {
				array_push(
					$array_messages,
					array(
						'message' => $message,
						'type'    => 'error',
					)
				);
			} else {
				try {
					$array_messages += $this->process_bulk_actions( $doaction, $object_ids );
				} catch ( Exception $e ) {
					array_push(
						$array_messages,
						array(
							'message' => $e->getMessage(),
							'type'    => 'error',
						)
					);
				}
			}

			/*
			@see render_messages() */
			// update_option( '_pr_dhl_bulk_action_confirmation', array( get_current_user_id() => $message, 'is_error' => $is_error ) );
			update_option( '_pr_dhl_bulk_action_confirmation', $array_messages );

			return $redirect;
		}

		/**
		 * Display messages on order view screen
		 */
		public function render_messages() {
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

					foreach ( $bulk_action_message_opt as $key => $value ) {
						$message = wp_kses_post( $value['message'] );

						switch ( $value['type'] ) {
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

			if ( 'pr_dhl_create_labels' === $action ) {

				$dhl_obj = PR_DHL()->get_dhl_factory();

				// APIs exposing get_dhl_labels() create every selected order in a single request.
				$use_batch = method_exists( $dhl_obj, 'get_dhl_labels' );

				// Move bulk creation into background jobs whenever Action Scheduler is available. Batch-capable
				// APIs (DHL Paket) are chunked into batched requests; the rest (Deutsche Post, which has no batch
				// endpoint) run one background job per order. Either way the work leaves the user-facing request.
				if ( $this->is_action_scheduler_available() ) {
					return $use_batch
						? $this->enqueue_bulk_label_jobs( $order_ids, $dhl_force_product, $is_force_product_dom )
						: $this->enqueue_per_order_label_jobs( $order_ids );
				}

				$batch_args = array();

				foreach ( $order_ids as $order_id ) {
					$order = wc_get_order( $order_id );

					try {
						// Create label if one has not been created before
						if ( empty( $label_tracking_info = $this->get_dhl_label_tracking( $order_id ) ) ) {

							$this->save_default_dhl_label_items( $order_id );

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

							// Defer to a single batched request when the API supports it.
							if ( $use_batch ) {
								$batch_args[ $order_id ] = $args;
								continue;
							}

							// API request.
							$label_tracking_info = $dhl_obj->get_dhl_label( $args );
							$array_messages[]    = $this->save_created_dhl_label( $order_id, $label_tracking_info );
							++$label_count;
						}

						if ( ! empty( $label_tracking_info['label_path'] ) ) {
							array_push( $merge_files, PR_DHL()->resolve_label_file_path( $label_tracking_info['label_path'] ) );
						}
					} catch ( Exception $e ) {
						$order_number = $order ? $order->get_order_number() : $order_id;
						$array_messages[] = array(
							/* translators: %1$s is the order number, %2$s is the error message */
							'message' => sprintf( esc_html__( 'Order #%1$s: %2$s', 'dhl-for-woocommerce' ), esc_html( $order_number ), $e->getMessage() ),
							'type'    => 'error',
						);
					}
				}

				// Create every deferred order in one API request, then map the results back per order.
				if ( $use_batch && ! empty( $batch_args ) ) {
					$handled_orders = array();

					try {
						$labels_result = $dhl_obj->get_dhl_labels( array_values( $batch_args ) );
					} catch ( Exception $e ) {
						$labels_result = array(
							'labels' => array(),
							'errors' => array(),
						);

						// A failure of the whole request applies to every queued order.
						foreach ( array_keys( $batch_args ) as $failed_order_id ) {
							$failed_order     = wc_get_order( $failed_order_id );
							$order_number     = $failed_order ? $failed_order->get_order_number() : $failed_order_id;
							$array_messages[] = array(
								/* translators: %1$s is the order number, %2$s is the error message */
								'message' => sprintf( esc_html__( 'Order #%1$s: %2$s', 'dhl-for-woocommerce' ), esc_html( $order_number ), $e->getMessage() ),
								'type'    => 'error',
							);
							$handled_orders[ $failed_order_id ] = true;
						}
					}

					foreach ( $labels_result['labels'] as $label_tracking_info ) {
						$array_messages[] = $this->save_created_dhl_label( $label_tracking_info['order_id'], $label_tracking_info );
						++$label_count;

						if ( ! empty( $label_tracking_info['label_path'] ) ) {
							array_push( $merge_files, PR_DHL()->resolve_label_file_path( $label_tracking_info['label_path'] ) );
						}

						$handled_orders[ $label_tracking_info['order_id'] ] = true;
					}

					foreach ( $labels_result['errors'] as $error ) {
						$failed_order = wc_get_order( $error['order_id'] );
						$order_number = $failed_order ? $failed_order->get_order_number() : $error['order_id'];
						$array_messages[] = array(
							/* translators: %1$s is the order number, %2$s is the error message */
							'message' => wp_kses_post( sprintf( __( 'Order #%1$s: %2$s', 'dhl-for-woocommerce' ), $order_number, $error['message'] ) ),
							'type'    => 'error',
						);

						if ( ! empty( $error['order_id'] ) ) {
							$handled_orders[ $error['order_id'] ] = true;
						}
					}

					// Surface any queued order the API neither created nor reported, so nothing fails silently.
					foreach ( array_keys( $batch_args ) as $order_id ) {
						if ( empty( $handled_orders[ $order_id ] ) ) {
							$order            = wc_get_order( $order_id );
							$order_number     = $order ? $order->get_order_number() : $order_id;
							$array_messages[] = array(
								/* translators: %s is the order number */
								'message' => sprintf( esc_html__( 'Order #%s: DHL label could not be created.', 'dhl-for-woocommerce' ), esc_html( $order_number ) ),
								'type'    => 'error',
							);
						}
					}
				}
				$array_messages = array_merge( $array_messages, $this->build_merged_label_message( $merge_files ) );
			} elseif ( 'pr_dhl_retry_failed_labels' === $action ) {
				$array_messages = $this->retry_failed_label_jobs( $order_ids );
			} elseif ( 'pr_dhl_download_labels' === $action ) {
				$array_messages = $this->build_bulk_label_download( $order_ids );
			} elseif ( 'pr_dhl_delete_labels' === $action ) {
				$array_messages = $this->delete_label_in_bulk( $order_ids );
			}

			return $array_messages;
		}

		/**
		 * Re-queues background label creation for the selected orders whose last job failed. Orders that
		 * already have a label or never ran a job are left untouched, so the action only ever retries
		 * genuine failures. Retried orders flow through the same background path as the initial run.
		 *
		 * @param array $order_ids Selected order IDs.
		 *
		 * @return array Bulk-action feedback messages.
		 */
		protected function retry_failed_label_jobs( $order_ids ) {
			$failed_orders    = array();
			$purchased_orders = array();

			foreach ( $order_ids as $order_id ) {
				if ( ! empty( $this->get_dhl_label_tracking( $order_id ) ) ) {
					continue;
				}

				$job = $this->get_label_job_status( $order_id );

				if ( self::JOB_FAILED !== $job['status'] ) {
					continue;
				}

				// A label that was bought at DHL but failed to save must not be re-created blindly:
				// retrying would purchase a second label. Leave it for manual verification.
				if ( ! empty( $job['purchased'] ) ) {
					$purchased_orders[] = $order_id;
					continue;
				}

				$failed_orders[] = $order_id;
			}

			$array_messages = array();

			if ( ! empty( $purchased_orders ) ) {
				$array_messages[] = array(
					'message' => sprintf(
						/* translators: %d is the number of orders skipped */
						esc_html( _n( '%d order was not retried because a label may already exist at DHL — verify it before retrying.', '%d orders were not retried because a label may already exist at DHL — verify them before retrying.', count( $purchased_orders ), 'dhl-for-woocommerce' ) ),
						count( $purchased_orders )
					),
					'type'    => 'warning',
				);
			}

			if ( empty( $failed_orders ) ) {
				if ( empty( $array_messages ) ) {
					$array_messages[] = array(
						'message' => esc_html__( 'No failed DHL label jobs were found in the selected orders.', 'dhl-for-woocommerce' ),
						'type'    => 'warning',
					);
				}

				return $array_messages;
			}

			return array_merge( $array_messages, $this->process_bulk_actions( 'pr_dhl_create_labels', $failed_orders ) );
		}

		/**
		 * Builds a merged PDF of the labels already created for the selected orders and returns a
		 * download link. This is how a completed background batch is downloaded in one file: the bulk
		 * request no longer produces the merged PDF itself, so the labels are gathered from storage here.
		 *
		 * @param array $order_ids Selected order IDs.
		 *
		 * @return array Bulk-action feedback messages.
		 */
		protected function build_bulk_label_download( $order_ids ) {
			$merge_files = array();

			foreach ( $order_ids as $order_id ) {
				$label_tracking_info = $this->get_dhl_label_tracking( $order_id );

				if ( ! empty( $label_tracking_info['label_path'] ) ) {
					$merge_files[] = PR_DHL()->resolve_label_file_path( $label_tracking_info['label_path'] );
				}
			}

			if ( empty( $merge_files ) ) {
				return array(
					array(
						'message' => esc_html__( 'None of the selected orders have a DHL label to download yet.', 'dhl-for-woocommerce' ),
						'type'    => 'warning',
					),
				);
			}

			return $this->build_merged_label_message( $merge_files );
		}

		/**
		 * Merges the given label files into one PDF, stashes its path in a short-lived per-user transient
		 * and returns the download-link (or error) message. Shared by the synchronous bulk-create fallback
		 * and the "Download DHL Labels" bulk action so the transient key, TTL and copy live in one place.
		 *
		 * @param array $merge_files Absolute paths of the label files to merge.
		 *
		 * @return array Bulk-action feedback messages.
		 */
		protected function build_merged_label_message( $merge_files ) {
			try {
				$bulk_download_label_url = $this->prepare_merged_label_download_url( $merge_files );

				return array(
					array(
						/* translators: %1$s and %2$s are the opening and closing tags of the download link */
						'message' => wp_kses_post( sprintf( __( 'Bulk DHL labels file created - %1$sdownload file%2$s', 'dhl-for-woocommerce' ), '<a href="' . esc_url( $bulk_download_label_url ) . '" download>', '</a>' ) ),
						'type'    => 'success',
					),
				);
			} catch ( Exception $e ) {
				return array(
					array(
						'message' => wp_kses_post( $e->getMessage() ),
						'type'    => 'error',
					),
				);
			}
		}

		/**
		 * Merges the given label files into one PDF, stashes its path in a short-lived per-user transient
		 * and returns the URL of the bulk-download endpoint that serves it.
		 *
		 * @param array $merge_files Absolute paths of the label files to merge.
		 *
		 * @return string The bulk-download URL.
		 * @throws Exception If the files cannot be merged or the merged file is missing.
		 */
		protected function prepare_merged_label_download_url( $merge_files ) {
			$file_bulk = $this->merge_label_files( $merge_files );

			if ( ! file_exists( $file_bulk['file_bulk_path'] ) ) {
				throw new Exception( esc_html__( 'Could not create bulk DHL label file, download individually.', 'dhl-for-woocommerce' ) );
			}

			// Stash the merged file path for the download endpoint. Expires in 3 minutes: long enough
			// for the user to see the link and click it.
			set_transient(
				'_dhl_bulk_download_labels_file_' . get_current_user_id(),
				$file_bulk['file_bulk_path'],
				180
			);

			return $this->generate_download_url( '/' . self::DHL_DOWNLOAD_ENDPOINT . '/bulk' );
		}

		/**
		 * Transient key holding the current user's in-flight bulk-label batch (the set of order IDs the
		 * Orders-screen progress UI polls). Keyed per user so concurrent admins don't see each other's runs.
		 *
		 * @return string
		 */
		protected function label_batch_key() {
			return 'pr_dhl_label_batch_' . get_current_user_id();
		}

		/**
		 * Starts, or extends, the current user's tracked bulk-label batch. Order IDs from a batch that is
		 * still running are merged in, so queuing a second selection before the first finishes keeps both
		 * visible in the same progress bar rather than losing the earlier one.
		 *
		 * @param array $order_ids Order IDs just queued.
		 *
		 * @return void
		 */
		protected function start_label_batch( $order_ids ) {
			$order_ids = array_map( 'intval', (array) $order_ids );

			// Carry over only orders from a previous batch that are still in flight. A batch left
			// undismissed after it finished must not bleed its already-created orders into this run
			// (which would re-count them and re-merge their PDFs on "Download all").
			$existing = $this->get_label_batch();
			if ( ! empty( $existing['ids'] ) ) {
				foreach ( $existing['ids'] as $existing_id ) {
					if ( self::JOB_PENDING === $this->get_label_job_status( $existing_id )['status'] ) {
						$order_ids[] = $existing_id;
					}
				}
				$order_ids = array_values( array_unique( $order_ids ) );
			}

			set_transient( $this->label_batch_key(), array( 'ids' => $order_ids, 'ts' => time() ), DAY_IN_SECONDS );
		}

		/**
		 * Returns the current user's tracked bulk-label batch.
		 *
		 * @return array Empty array when none is tracked, otherwise array{ids: int[]}.
		 */
		protected function get_label_batch() {
			$batch = get_transient( $this->label_batch_key() );

			if ( ! is_array( $batch ) || empty( $batch['ids'] ) ) {
				return array();
			}

			$batch['ids'] = array_map( 'intval', (array) $batch['ids'] );

			return $batch;
		}

		/**
		 * Stops tracking the current user's bulk-label batch.
		 *
		 * @return void
		 */
		protected function clear_label_batch() {
			delete_transient( $this->label_batch_key() );
		}

		/**
		 * Aggregates the per-order job state of the tracked batch for the progress UI.
		 *
		 * @return array|null Null when no batch is tracked.
		 */
		public function get_label_batch_progress() {
			$batch = $this->get_label_batch();

			if ( empty( $batch['ids'] ) ) {
				return null;
			}

			$created   = 0;
			$failed    = 0;
			$pending   = 0;
			$purchased = 0;
			$failures  = array();

			// The background callbacks always record a terminal JOB_CREATED / JOB_FAILED for every queued
			// order, so the stored job status is authoritative here — no need for a second per-order load
			// of the label tracking meta.
			foreach ( $batch['ids'] as $order_id ) {
				$job    = $this->get_label_job_status( $order_id );
				$status = $job['status'];

				if ( self::JOB_CREATED === $status ) {
					++$created;
				} elseif ( self::JOB_FAILED === $status ) {
					++$failed;

					// A label bought at DHL but not saved locally must not be retried (it would double-buy);
					// it is surfaced separately so the merchant can verify it by hand.
					if ( ! empty( $job['purchased'] ) ) {
						++$purchased;
					}

					// Cap the detail list so a huge failing batch cannot bloat the polled payload; the
					// counts above still cover every order.
					if ( count( $failures ) < 100 ) {
						$order      = wc_get_order( $order_id );
						$failures[] = array(
							'order_id'  => $order_id,
							'number'    => $order ? (string) $order->get_order_number() : (string) $order_id,
							'message'   => '' !== $job['message'] ? $job['message'] : __( 'DHL label creation failed.', 'dhl-for-woocommerce' ),
							'purchased' => ! empty( $job['purchased'] ),
							'edit_url'  => $this->get_order_edit_url( $order_id ),
						);
					}
				} else {
					++$pending;
				}
			}

			$done       = 0 === $pending;
			$started_at = isset( $batch['ts'] ) ? (int) $batch['ts'] : 0;

			// If a grace period passes with nothing processed at all, the Action Scheduler queue is
			// almost certainly not running (no cron / no queue runner). Surface that instead of spinning
			// forever, so the merchant can act rather than wait indefinitely.
			$stalled = ! $done
				&& 0 === ( $created + $failed )
				&& $started_at > 0
				&& ( time() - $started_at ) > 120;

			return array(
				'total'        => count( $batch['ids'] ),
				'created'      => $created,
				'failed'       => $failed,
				'pending'      => $pending,
				'purchased'    => $purchased,
				'done'         => $done,
				'stalled'      => $stalled,
				'has_failed'   => $failed > 0,
				'retryable'    => ( $failed - $purchased ) > 0,
				'can_download' => $created > 0,
				'failures'     => $failures,
			);
		}

		/**
		 * Admin edit-screen URL for an order, on both HPOS and the legacy post table.
		 *
		 * @param int $order_id Order ID.
		 *
		 * @return string
		 */
		protected function get_order_edit_url( $order_id ) {
			if ( API_Utils::is_HPOS() ) {
				return admin_url( 'admin.php?page=wc-orders&action=edit&id=' . absint( $order_id ) );
			}

			return admin_url( 'post.php?post=' . absint( $order_id ) . '&action=edit' );
		}

		/**
		 * Shared guard for the batch AJAX endpoints: verifies the nonce and the order-management capability,
		 * ending the request with a JSON error when either fails.
		 *
		 * @return void
		 */
		protected function verify_label_batch_request() {
			check_ajax_referer( 'pr-dhl-label-batch', 'nonce' );

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to manage DHL labels.', 'dhl-for-woocommerce' ) ), 403 );
			}
		}

		/**
		 * AJAX: return the aggregated progress of the current user's bulk-label batch.
		 *
		 * @return void
		 */
		public function ajax_label_batch_progress() {
			$this->verify_label_batch_request();

			$progress = $this->get_label_batch_progress();

			if ( null === $progress ) {
				wp_send_json_success( array( 'active' => false ) );
			}

			$progress['active'] = true;
			wp_send_json_success( $progress );
		}

		/**
		 * AJAX: merge the labels created so far in the batch into one PDF and return its download URL.
		 *
		 * @return void
		 */
		public function ajax_label_batch_download() {
			$this->verify_label_batch_request();

			$batch = $this->get_label_batch();

			if ( empty( $batch['ids'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'No DHL label batch is in progress.', 'dhl-for-woocommerce' ) ) );
			}

			$merge_files = array();

			foreach ( $batch['ids'] as $order_id ) {
				$label_tracking_info = $this->get_dhl_label_tracking( $order_id );

				if ( ! empty( $label_tracking_info['label_path'] ) ) {
					$merge_files[] = PR_DHL()->resolve_label_file_path( $label_tracking_info['label_path'] );
				}
			}

			if ( empty( $merge_files ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'There are no created DHL labels to download yet.', 'dhl-for-woocommerce' ) ) );
			}

			try {
				wp_send_json_success( array( 'url' => $this->prepare_merged_label_download_url( $merge_files ) ) );
			} catch ( Exception $e ) {
				wp_send_json_error( array( 'message' => $e->getMessage() ) );
			}
		}

		/**
		 * AJAX: re-queue the failed orders in the batch (the shared retry path skips any label bought
		 * at DHL but not saved locally, so a retry never double-charges).
		 *
		 * @return void
		 */
		public function ajax_label_batch_retry() {
			$this->verify_label_batch_request();

			$batch = $this->get_label_batch();

			if ( empty( $batch['ids'] ) ) {
				wp_send_json_error( array( 'message' => esc_html__( 'No DHL label batch is in progress.', 'dhl-for-woocommerce' ) ) );
			}

			$this->retry_failed_label_jobs( $batch['ids'] );

			wp_send_json_success();
		}

		/**
		 * AJAX: stop tracking the batch once the user dismisses the progress panel.
		 *
		 * @return void
		 */
		public function ajax_label_batch_dismiss() {
			$this->verify_label_batch_request();
			$this->clear_label_batch();
			wp_send_json_success();
		}

		/**
		 * Enqueues the Orders-screen progress-bar script when the current user has a batch in flight.
		 *
		 * @return void
		 */
		public function enqueue_label_batch_assets() {
			if ( ! $this->is_orders_list_screen() || empty( $this->get_label_batch() ) ) {
				return;
			}

			wp_enqueue_script(
				'pr-dhl-label-batch',
				PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-bulk-progress.js',
				array( 'jquery' ),
				PR_DHL_VERSION,
				true
			);

			wp_localize_script(
				'pr-dhl-label-batch',
				'prDhlLabelBatch',
				array(
					'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( 'pr-dhl-label-batch' ),
					'interval' => 5000,
					'i18n'     => array(
						'creating'   => esc_html__( 'Creating DHL labels in the background…', 'dhl-for-woocommerce' ),
						/* translators: %1$d created, %2$d failed, %3$d still in progress */
						'status'     => esc_html__( '%1$d created, %2$d failed, %3$d still in progress', 'dhl-for-woocommerce' ),
						'doneOk'     => esc_html__( 'All DHL labels created.', 'dhl-for-woocommerce' ),
						'doneErrors' => esc_html__( 'DHL label creation finished — some orders failed.', 'dhl-for-woocommerce' ),
						'stalled'    => esc_html__( 'DHL labels are still queued but nothing is processing yet — your site may not be running background tasks. Check WooCommerce → Status → Scheduled Actions, or reload later.', 'dhl-for-woocommerce' ),
						'failTitle'  => esc_html__( 'Orders that could not be created:', 'dhl-for-woocommerce' ),
						'purchased'  => esc_html__( 'Some orders may already have a label at DHL — open them and verify before retrying. Retry does not re-create these.', 'dhl-for-woocommerce' ),
						'error'      => esc_html__( 'Something went wrong. Please reload the page.', 'dhl-for-woocommerce' ),
					),
				)
			);
		}

		/**
		 * Renders the (initially hidden) progress panel on the Orders screen; the enqueued script fills and
		 * updates it by polling ajax_label_batch_progress().
		 *
		 * @return void
		 */
		public function render_label_batch_progress() {
			if ( ! $this->is_orders_list_screen() || empty( $this->get_label_batch() ) ) {
				return;
			}
			?>
			<div id="pr-dhl-label-batch" class="notice notice-info pr-dhl-label-batch" style="display:none;">
				<p class="pr-dhl-label-batch__title"><strong></strong></p>
				<div class="pr-dhl-label-batch__bar"><span class="pr-dhl-label-batch__fill"></span></div>
				<p class="pr-dhl-label-batch__status"></p>
				<div class="pr-dhl-label-batch__details"></div>
				<p class="pr-dhl-label-batch__actions" style="display:none;">
					<a href="#" class="button button-primary pr-dhl-label-batch__download"><?php echo esc_html__( 'Download all labels', 'dhl-for-woocommerce' ); ?></a>
					<a href="#" class="button pr-dhl-label-batch__retry"><?php echo esc_html__( 'Retry failed', 'dhl-for-woocommerce' ); ?></a>
					<a href="#" class="button-link pr-dhl-label-batch__dismiss"><?php echo esc_html__( 'Dismiss', 'dhl-for-woocommerce' ); ?></a>
				</p>
			</div>
			<style>
				.pr-dhl-label-batch__bar{height:14px;max-width:480px;margin:8px 0;background:#e2e4e7;border-radius:7px;overflow:hidden}
				.pr-dhl-label-batch__fill{display:block;height:100%;width:0;background:#d40511;transition:width .4s ease}
				.pr-dhl-label-batch__actions .button{margin-right:6px}
			</style>
			<?php
		}

		/**
		 * Persist a freshly created DHL label: store its tracking data, add the order note
		 * and fire the label-created action.
		 *
		 * @param int   $order_id            The WooCommerce order ID.
		 * @param array $label_tracking_info The tracking data returned by the DHL API.
		 *
		 * @return array The success message entry for the bulk action feedback.
		 */
		protected function save_created_dhl_label( $order_id, $label_tracking_info ) {
			$this->save_dhl_label_tracking( $order_id, $label_tracking_info );

			$order = wc_get_order( $order_id );

			if ( $order ) {
				$tracking_note      = $this->get_tracking_note( $order_id );
				$tracking_note_type = $this->get_tracking_note_type();
				$tracking_note_type = empty( $tracking_note_type ) ? 0 : 1;

				$order->add_order_note( $tracking_note, $tracking_note_type, true );
			}

			do_action( 'pr_shipping_dhl_label_created', $order_id );

			$order_number = $order ? $order->get_order_number() : $order_id;

			return array(
				/* translators: %s is the order number */
				'message' => sprintf( esc_html__( 'Order #%s: DHL label created', 'dhl-for-woocommerce' ), $order_number ),
				'type'    => 'success',
			);
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

			foreach ( $order_ids as $order_id ) {
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
						function ( $order_note ) use ( $tracking_number ) {
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

					$array_messages[] = array(
						/* translators: %s is the order number */
						'message' => sprintf( esc_html__( 'Order #%s: DHL Label Deleted', 'dhl-for-woocommerce' ), esc_html( $order->get_order_number() ) ),
						'type'    => 'success',
					);
				} catch ( Exception $e ) {
					$array_messages[] = array(
						/* translators: %1$s is the order number, %2$s is the error message */
						'message' => wp_kses_post(
							sprintf( __( 'Order #%1$s: %2$s', 'dhl-for-woocommerce' ), $order->get_order_number(), $e->getMessage() )
						),
						'type'    => 'error',
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
				$result = wp_parse_url( site_url() );

				if ( ! empty( $result['scheme'] ) && ! empty( $result['host'] ) ) {
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

			if ( empty( $files ) ) {
				throw new Exception( esc_html__( 'There are no files to merge.', 'dhl-for-woocommerce' ) );
			}

			if ( ! empty( $files[0] ) ) {
				$base_ext = pathinfo( $files[0], PATHINFO_EXTENSION );
			} else {
				throw new Exception( esc_html__( 'The first file is empty.', 'dhl-for-woocommerce' ) );
			}

			if ( method_exists( $this, 'merge_label_files_' . $base_ext ) ) {
				return call_user_func( array( $this, 'merge_label_files_' . $base_ext ), $files );
			} else {
				throw new Exception( esc_html__( 'File format not supported.', 'dhl-for-woocommerce' ) );
			}
		}

		protected function merge_label_files_pdf( $files ) {

			if ( empty( $files ) ) {
				throw new Exception( esc_html__( 'There are no files to merge.', 'dhl-for-woocommerce' ) );
			}

			$loader    = PR_DHL_Libraryloader::instance();
			$pdfMerger = $loader->get_pdf_merger();

			if ( $pdfMerger === null ) {

				throw new Exception( esc_html__( 'Library conflict, could not merge PDF files. Please download PDF files individually.', 'dhl-for-woocommerce' ) );
			}

			foreach ( $files as $key => $value ) {

				if ( ! file_exists( $value ) ) {
					// throw new Exception( __('File does not exist', 'dhl-for-woocommerce') );
					continue;
				}

				$ext = pathinfo( $value, PATHINFO_EXTENSION );
				// if ( strncasecmp('pdf', $ext, strlen($ext) ) == 0 ) {
				if ( stripos( $ext, 'pdf' ) === false ) {
					throw new Exception( esc_html__( 'Not all the file formats are the same.', 'dhl-for-woocommerce' ) );
				}

				$pdfMerger->addPDF( $value, 'all' );
			}

			$filename       = 'dhl-label-bulk-' . time() . '.pdf';
			$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
			$file_bulk_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;
			$pdfMerger->merge( 'file', $file_bulk_path );

			return array(
				'file_bulk_path' => $file_bulk_path,
				'file_bulk_url'  => $file_bulk_url,
			);
		}

		/**
		 * Creates a custom endpoint to download the label
		 */
		public function add_download_label_endpoint() {
			add_rewrite_endpoint( self::DHL_DOWNLOAD_ENDPOINT, EP_ROOT );

			// Flush permalink if it is not flushed yet.
			if ( ! get_option( 'dhl_permalinks_flushed' ) ) {
				flush_rewrite_rules();
				update_option( 'dhl_permalinks_flushed', 1 );
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

			if ( ! isset( $wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ] ) ) {
				return;
			}

			// If we fail to add the "DHL_DOWNLOAD_ENDPOINT" then we bail, otherwise, we
			// will continue with the process below.
			$endpoint_param = $wp_query->query_vars[ self::DHL_DOWNLOAD_ENDPOINT ];
			if ( ! isset( $endpoint_param ) ) {
				return;
			}

			$array_messages = get_option( '_pr_dhl_bulk_action_confirmation' );
			if ( empty( $array_messages ) || ! is_array( $array_messages ) ) {
				$array_messages = array( 'msg_user_id' => get_current_user_id() );
			}

			if ( $endpoint_param == 'bulk' ) {

				$bulk_file_path = get_transient( '_dhl_bulk_download_labels_file_' . get_current_user_id() );

				if ( false == $this->download_label( $bulk_file_path ) ) {
					array_push(
						$array_messages,
						array(
							'message' => esc_html__( 'There are currently no bulk DHL label file to download or the download link for the bulk DHL label file has already expired. Please try again.', 'dhl-for-woocommerce' ),
							'type'    => 'error',
						)
					);
				}

				$redirect_url = admin_url( 'edit.php?post_type=shop_order' );
			} else {
				$order_id = $endpoint_param;

				// Get tracking info if it exists
				$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
				// Check whether the label has already been created or not
				if ( empty( $label_tracking_info ) ) {
					return;
				}

				// Serve the separate return label when requested, otherwise the shipping label.
				$label_type = isset( $_GET['dhl_label_type'] ) ? sanitize_key( wp_unslash( $_GET['dhl_label_type'] ) ) : '';
				if ( 'return' === $label_type && ! empty( $label_tracking_info['return_label_path'] ) ) {
					$label_path = $label_tracking_info['return_label_path'];
				} elseif ( ! empty( $label_tracking_info['label_path'] ) ) {
					$label_path = $label_tracking_info['label_path'];
				} elseif ( ! empty( $label_tracking_info['label_url'] ) ) {
					// Old "download style" labels stored only a public URL; resolve by file name.
					$label_path = PR_DHL()->get_dhl_label_folder_dir() . basename( $label_tracking_info['label_url'] );
				} else {
					$label_path = '';
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

				$redirect_url = admin_url( 'post.php?post=' . $order_id . '&action=edit' );
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
	   *
	   * @return boolean|void
	   */
	  protected function download_label( $file_path ) {
		  $file_path = PR_DHL()->resolve_label_file_path( $file_path );

		  if ( ! empty( $file_path ) && is_string( $file_path ) && file_exists( $file_path ) ) {
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
			  return FALSE;
		  }
	  }
	}

endif;
