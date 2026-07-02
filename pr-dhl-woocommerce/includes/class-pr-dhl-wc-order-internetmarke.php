<?php

use PR\DHL\Utils\API_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce order metabox for Deutsche Post INTERNETMARKE labels.
 *
 * Separate from the DHL Paket metabox — both can appear on the same order page.
 * Does NOT extend PR_DHL_WC_Order to avoid AJAX action conflicts with Paket.
 */
if ( ! class_exists( 'PR_DHL_WC_Order_Internetmarke' ) ) :

	class PR_DHL_WC_Order_Internetmarke {

		const METABOX_ID        = 'woocommerce-shipment-internetmarke-label';
		const LABEL_ITEMS_META  = '_pr_dhl_im_label_items';
		const LABEL_TRACKING_META = '_pr_dhl_im_label_tracking';
		const NONCE_ACTION      = 'create-internetmarke-label';
		const NONCE_FIELD       = 'pr_dhl_im_label_nonce';

		public function __construct() {
			$this->init_hooks();
		}

		public function init_hooks() {
			// Register the metabox at priority 21 so it appears after the Paket metabox (priority 20).
			add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ), 21, 2 );

			// Save on standard order save.
			add_action( 'woocommerce_process_shop_order_meta', array( $this, 'save_meta_box' ), 0, 2 );

			// AJAX handlers — separate actions from Paket to avoid double-firing.
			add_action( 'wp_ajax_wc_shipment_internetmarke_gen_label', array( $this, 'generate_label_ajax' ) );
			add_action( 'wp_ajax_wc_shipment_internetmarke_delete_label', array( $this, 'delete_label_ajax' ) );
			add_action( 'wp_ajax_wc_shipment_internetmarke_download_label', array( $this, 'download_label_ajax' ) );

			// Clean up local label PDF and order meta when an order is deleted.
			add_action( 'woocommerce_before_delete_order', array( $this, 'cleanup_on_order_delete' ) );
			add_action( 'before_delete_post', array( $this, 'cleanup_on_post_delete' ) );
		}
        
		/**
		 * Base products shown in the dropdown.
		 *
		 * Derived from the Deutsche Post `ppl_v590_PARTNER` product list — yellow-highlighted
		 * rows only, confirmed by task owner. The yellow subset covers:
		 *
		 * @return array
		 */
		public static function get_base_products() {
			return array(
				// Domestic letter products
				'11'    => 'Kompaktbrief',
				'21'    => 'Großbrief',
				'31'    => 'Maxibrief',
				'41'    => 'Maxibrief bis 2000 g + Zusatzentgelt MBf',
				'290'   => 'Warensendung',
				'331'   => 'Warensendung 2.000 + Gewichtszuschlag',
				// International letter products
				'10011' => 'Kompaktbrief Intern. GK',
				'10051' => 'Großbrief Intern. GK',
				'10071' => 'Maxibrief Intern. bis 1.000g GK',
				'10091' => 'Maxibrief Intern. bis 2.000g GK',
			);
		}

		/**
		 * Services available per base product (drives checkbox visibility).
		 * Service keys are internal identifiers; display labels use original Deutsche Post naming.
		 *
		 * @return array  product-key => string[] service keys
		 */
		public static function get_product_services_map() {
			return array(
				// Domestic — EINSCHREIBEN EINWURF, EINSCHREIBEN, RÜCKSCHEIN
				'11'    => array( 'einschreiben_einwurf', 'einschreiben', 'rueckschein' ),
				'21'    => array( 'einschreiben_einwurf', 'einschreiben', 'rueckschein' ),
				'31'    => array( 'einschreiben_einwurf', 'einschreiben', 'rueckschein' ),
				'41'    => array( 'einschreiben_einwurf', 'einschreiben', 'rueckschein' ),
				// Warensendung products have no registered-mail variants in the approved list.
				'290'   => array(),
				'331'   => array(),
				// International — EINSCHREIBEN only (no EINWURF or RÜCKSCHEIN variants in the approved list).
				'10011' => array( 'einschreiben' ),
				'10051' => array( 'einschreiben' ),
				'10071' => array( 'einschreiben' ),
				'10091' => array( 'einschreiben' ),
			);
		}

		/**
		 * Service display labels — original Deutsche Post naming, no English translation.
		 *
		 * @return array  service-key => display label
		 */
		public static function get_service_labels() {
			return array(
				'einschreiben_einwurf' => 'EINSCHREIBEN EINWURF',
				'einschreiben'         => 'EINSCHREIBEN',
				'rueckschein'          => 'RÜCKSCHEIN',
			);
		}

		/**
		 * Resolve a product key + selected services to the actual INTERNETMARKE product ID.
		 *
		 * Rules (from approved spreadsheet):
		 * - EINSCHREIBEN EINWURF is mutually exclusive with EINSCHREIBEN.
		 * - RÜCKSCHEIN is only valid together with EINSCHREIBEN (not with EINWURF).
		 *
		 * @param  string   $product_key  Key from get_base_products().
		 * @param  string[] $services     Selected service keys.
		 * @return int|null               INTERNETMARKE product ID, or null if the combination is invalid.
		 */
		public static function resolve_product_id( $product_key, array $services ) {
			// product-key => variant-key => product_id
			$map = array(
				'11'    => array(
					'none'         => 11,
					'einwurf'      => 1012,
					'einschreiben' => 1017,
					'rueckschein'  => 1018,
				),
				'21'    => array(
					'none'         => 21,
					'einwurf'      => 1022,
					'einschreiben' => 1027,
					'rueckschein'  => 1028,
				),
				'31'    => array(
					'none'         => 31,
					'einwurf'      => 1032,
					'einschreiben' => 1037,
					'rueckschein'  => 1038,
				),
				'41'    => array(
					'none'         => 41,
					'einwurf'      => 1042,
					'einschreiben' => 1047,
					'rueckschein'  => 1048,
				),
				'290'   => array( 'none' => 290 ),
				'331'   => array( 'none' => 331 ),
				'10011' => array( 'none' => 10011, 'einschreiben' => 11016 ),
				'10051' => array( 'none' => 10051, 'einschreiben' => 11056 ),
				'10071' => array( 'none' => 10071, 'einschreiben' => 11076 ),
				'10091' => array( 'none' => 10091, 'einschreiben' => 11096 ),
			);

			if ( ! isset( $map[ $product_key ] ) ) {
				return null;
			}

			// Only honor services allowed for this product; client-side visibility is
			// not authoritative, so a crafted request cannot force an invalid combination.
			$allowed  = self::get_product_services_map();
			$services = isset( $allowed[ $product_key ] )
				? array_values( array_intersect( $services, $allowed[ $product_key ] ) )
				: array();

			$variants         = $map[ $product_key ];
			$has_einwurf      = in_array( 'einschreiben_einwurf', $services, true );
			$has_einschreiben = in_array( 'einschreiben', $services, true );
			$has_rueckschein  = in_array( 'rueckschein', $services, true );

			if ( $has_einwurf ) {
				return isset( $variants['einwurf'] ) ? $variants['einwurf'] : null;
			}

			if ( $has_einschreiben && $has_rueckschein ) {
				return isset( $variants['rueckschein'] ) ? $variants['rueckschein'] : null;
			}

			if ( $has_einschreiben ) {
				return isset( $variants['einschreiben'] ) ? $variants['einschreiben'] : null;
			}

			return isset( $variants['none'] ) ? $variants['none'] : null;
		}


		/**
		 * Register the INTERNETMARKE metabox on the order page.
		 *
		 * @param string           $post_type
		 * @param WP_Post|WC_Order $post_or_order_object
		 */
		public function add_meta_box( $post_type, $post_or_order_object ) {
			// Internetmarke is only available for German stores.
			if ( 'DE' !== WC()->countries->get_base_country() ) {
				return;
			}

			// Hide metabox until credentials are saved.
			$settings = get_option( 'woocommerce_pr_dhl_paket_settings', array() );
			if ( empty( $settings['internetmarke_api_user'] ) || empty( $settings['internetmarke_api_password'] ) ) {
				return;
			}

			$order = $this->init_order_object( $post_or_order_object );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$screen = API_Utils::is_HPOS() ? wc_get_page_screen_id( 'shop-order' ) : 'shop_order';

			add_meta_box(
				self::METABOX_ID,
				esc_html__( 'INTERNETMARKE Label', 'dhl-for-woocommerce' ),
				array( $this, 'meta_box' ),
				$screen,
				'side',
				'default'
			);
		}

		/**
		 * Normalise the metabox callback parameter to a WC_Order (supports HPOS and legacy).
		 *
		 * @param  WP_Post|WC_Order $metabox_object
		 * @return WC_Order|false
		 */
		protected function init_order_object( $metabox_object ) {
			if ( is_a( $metabox_object, 'WP_Post' ) ) {
				return wc_get_order( $metabox_object->ID );
			}

			if ( is_a( $metabox_object, 'WC_Order' ) ) {
				return $metabox_object;
			}

			return false;
		}

		// -------------------------------------------------------------------------
		// Metabox render
		// -------------------------------------------------------------------------

		/**
		 * Render the INTERNETMARKE metabox content.
		 *
		 * Follows the same structural pattern as the Paket metabox:
		 * - button HTML is built in PHP and passed via wp_localize_script
		 * - JS uses those strings in the AJAX success handler
		 *
		 * @param WP_Post|WC_Order $post_or_order_object
		 */
		public function meta_box( $post_or_order_object ) {
			$order    = $this->init_order_object( $post_or_order_object );
			$order_id = $order->get_id();

			$label_items    = $this->get_label_items( $order_id );
			$label_tracking = $this->get_label_tracking( $order_id );

			$selected_product = ! empty( $label_items['pr_dhl_im_product'] )
				? $label_items['pr_dhl_im_product']
				: '11'; // default to Kompaktbrief

			$saved_services = ! empty( $label_items['pr_dhl_im_services'] ) && is_array( $label_items['pr_dhl_im_services'] )
				? $label_items['pr_dhl_im_services']
				: array();

			$has_label   = ! empty( $label_tracking );
			$is_disabled = $has_label ? 'disabled' : '';

			$base_products = self::get_base_products();
			$services_map  = self::get_product_services_map();
			$service_labels = self::get_service_labels();

			$domestic_products = array( '11', '21', '31', '41', '290', '331' );
			$intl_products     = array( '10011', '10051', '10071', '10091' );

			// Build button HTML in PHP — same pattern as Paket metabox — so the JS
			// success handler can inject pre-translated strings without string literals in JS.
			$main_button = '<button id="im-label-button" class="button button-primary">'
				. esc_html__( 'Generate label', 'dhl-for-woocommerce' )
				. '</button>';

			$label_url    = $has_label ? esc_url( $this->get_download_label_url( $order_id ) ) : '#';
			$print_button = '<a href="' . $label_url . '" id="im-label-print" class="button button-primary" download target="_blank">'
				. esc_html__( 'Download label', 'dhl-for-woocommerce' )
				. '</a>';

			$delete_label = '<span class="wc_dhl_delete"><a href="#" id="im-delete-label">'
				. esc_html__( 'Delete label', 'dhl-for-woocommerce' )
				. '</a></span>';

			// Data passed to JS — mirrors the pattern in pr-dhl.js / dhl_label_data.
			$im_order_data = array(
				'ajax_url'     => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( self::NONCE_ACTION ),
				'order_id'     => $order_id,
				'services_map' => $services_map,
				'has_label'    => $has_label,
				'ajax_error'   => esc_html__( 'The label request failed or timed out. Please check the order and try again.', 'dhl-for-woocommerce' ),
			);

			$im_label_data = array(
				'main_button'  => $main_button,
				'print_button' => $print_button,
				'delete_label' => $delete_label,
			);

			?>
			<div id="shipment-im-label-form">

				<?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD ); ?>

				<div class="shipment-dhl-row-container shipment-im-row-product">
					<div class="shipment-dhl-icon-container">
						<span class="shipment-dhl-icon shipment-dhl-icon-service"></span>
						<?php esc_html_e( 'Product', 'dhl-for-woocommerce' ); ?>
					</div>

					<p class="form-field">
						<label for="pr_dhl_im_product"><?php esc_html_e( 'Product selected:', 'dhl-for-woocommerce' ); ?></label>
						<select id="pr_dhl_im_product" name="pr_dhl_im_product" <?php echo esc_attr( $is_disabled ); ?>>
							<optgroup label="<?php esc_attr_e( 'Domestic', 'dhl-for-woocommerce' ); ?>">
								<?php foreach ( $domestic_products as $key ) : ?>
									<?php if ( isset( $base_products[ $key ] ) ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_product, $key ); ?>>
											<?php echo esc_html( $base_products[ $key ] ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</optgroup>
							<optgroup label="<?php esc_attr_e( 'International', 'dhl-for-woocommerce' ); ?>">
								<?php foreach ( $intl_products as $key ) : ?>
									<?php if ( isset( $base_products[ $key ] ) ) : ?>
										<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $selected_product, $key ); ?>>
											<?php echo esc_html( $base_products[ $key ] ); ?>
										</option>
									<?php endif; ?>
								<?php endforeach; ?>
							</optgroup>
						</select>
					</p>
				</div>

				<div class="shipment-dhl-row-container shipment-im-row-services" id="im-services-container">
					<div class="shipment-dhl-icon-container">
						<span class="shipment-dhl-icon shipment-dhl-icon-additional-services"></span>
						<?php esc_html_e( 'Additional services', 'dhl-for-woocommerce' ); ?>
					</div>

					<?php foreach ( $service_labels as $service_key => $service_label ) : ?>
						<?php
						$available_for_default = in_array( $service_key, $services_map[ $selected_product ] ?? array(), true );
						$checked               = in_array( $service_key, $saved_services, true );
						$row_style             = $available_for_default ? '' : 'display:none;';

						// RÜCKSCHEIN is further hidden if EINSCHREIBEN is not checked; JS manages this on interaction.
						if ( 'rueckschein' === $service_key && $available_for_default && ! in_array( 'einschreiben', $saved_services, true ) ) {
							$row_style = 'display:none;';
						}
						?>
						<p class="form-field im-service-row im-service-row-<?php echo esc_attr( $service_key ); ?>"
						   data-service="<?php echo esc_attr( $service_key ); ?>"
						   style="<?php echo esc_attr( $row_style ); ?>">
							<label>
								<input
									type="checkbox"
									id="pr_dhl_im_service_<?php echo esc_attr( $service_key ); ?>"
									name="pr_dhl_im_services[]"
									value="<?php echo esc_attr( $service_key ); ?>"
									<?php checked( $checked ); ?>
									<?php echo esc_attr( $is_disabled ); ?>
								/>
								<?php echo esc_html( $service_label ); // Original Deutsche Post name — no English translation. ?>
							</label>
						</p>
					<?php endforeach; ?>
				</div>

				<?php if ( $has_label ) : ?>

					<?php
					echo $print_button; // phpcs:ignore WordPress.Security.EscapeOutput
					echo ' ';
					echo $delete_label; // phpcs:ignore WordPress.Security.EscapeOutput
					?>

					<?php if ( ! empty( $label_tracking['tracking_number'] ) ) : ?>
						<p class="im-tracking-number">
							<?php
							echo esc_html__( 'Tracking number:', 'dhl-for-woocommerce' ) . ' ';
							echo esc_html( $label_tracking['tracking_number'] );
							?>
						</p>
					<?php endif; ?>

				<?php else : ?>

					<?php echo $main_button; // phpcs:ignore WordPress.Security.EscapeOutput ?>

				<?php endif; ?>

			</div>
			<?php

			wp_enqueue_script(
				'wc-shipment-im-order-js',
				PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-internetmarke-order.js',
				array( 'jquery' ),
				PR_DHL_VERSION,
				true
			);
			wp_localize_script( 'wc-shipment-im-order-js', 'dhl_im_order_data', $im_order_data );
			wp_localize_script( 'wc-shipment-im-order-js', 'dhl_im_label_data', $im_label_data );
		}


		/**
		 * Persist selected product and services when the order is saved via the standard form.
		 *
		 * @param int          $post_id
		 * @param WP_Post|null $post
		 */
		public function save_meta_box( $post_id, $post = null ) {
			if ( empty( $_POST[ self::NONCE_FIELD ] ) ) {
				return;
			}

			$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) );
			if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
				return;
			}

			$args = array();

			if ( isset( $_POST['pr_dhl_im_product'] ) ) {
				$args['pr_dhl_im_product'] = sanitize_text_field( wp_unslash( $_POST['pr_dhl_im_product'] ) );
			}

			// Services come as a checkbox array or may be absent when none are checked.
			$args['pr_dhl_im_services'] = array();
			if ( isset( $_POST['pr_dhl_im_services'] ) && is_array( $_POST['pr_dhl_im_services'] ) ) {
				$args['pr_dhl_im_services'] = array_map( 'sanitize_text_field', wp_unslash( $_POST['pr_dhl_im_services'] ) );
			}

			if ( ! empty( $args ) ) {
				$this->save_label_items( $post_id, $args );
			}
		}

		// -------------------------------------------------------------------------
		// AJAX: Generate label
		// -------------------------------------------------------------------------

		/**
		 * AJAX handler: save selections and attempt label generation via the Internetmarke API.
		 * Follows the same error-handling pattern as PR_DHL_WC_Order::save_meta_box_ajax().
		 */
		public function generate_label_ajax() {
			ob_start();

			check_ajax_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				$this->send_json_response( array( 'error' => esc_html__( 'You do not have permission to generate labels.', 'dhl-for-woocommerce' ) ) );
				wp_die();
			}

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

			if ( ! $order_id ) {
				$this->send_json_response( array( 'error' => esc_html__( 'Invalid order ID.', 'dhl-for-woocommerce' ) ) );
				wp_die();
			}

			// Save form inputs first — same order as Paket's save_meta_box_ajax().
			$this->save_meta_box( $order_id );

			$label_items = $this->get_label_items( $order_id );
			$product_key = isset( $label_items['pr_dhl_im_product'] ) ? $label_items['pr_dhl_im_product'] : '';
			$services    = isset( $label_items['pr_dhl_im_services'] ) && is_array( $label_items['pr_dhl_im_services'] )
				? $label_items['pr_dhl_im_services']
				: array();

			// Resolve product + services to the canonical INTERNETMARKE product ID.
			$product_id = self::resolve_product_id( $product_key, $services );

			if ( null === $product_id ) {
				$this->send_json_response(
					array(
						'error' => esc_html__( 'Invalid product / service combination. Please check your selection.', 'dhl-for-woocommerce' ),
					)
				);
				wp_die();
			}

			try {
				$im_api     = new PR_DHL_API_Internetmarke();
				$label_info = $im_api->generate_label( $order_id, $product_id );

				$this->save_label_tracking( $order_id, $label_info );

				$label_url = ! empty( $label_info['label_url'] ) ? $this->get_download_label_url( $order_id ) : '';

				$this->send_json_response(
					array(
						'label_url'       => $label_url ? esc_url_raw( $label_url ) : '',
						'tracking_number' => isset( $label_info['tracking_number'] ) ? esc_html( $label_info['tracking_number'] ) : '',
						'download_msg'    => esc_html__( 'Your INTERNETMARKE label is ready. Click "Download label" to save it.', 'dhl-for-woocommerce' ),
					)
				);

			} catch ( \Throwable $e ) {
				$this->send_json_response( array( 'error' => $e->getMessage() ) );
			}

			wp_die();
		}

		/**
		 * AJAX handler: remove the stored INTERNETMARKE label tracking data.
		 * Follows the same response pattern as PR_DHL_WC_Order::delete_label_ajax().
		 */
		public function delete_label_ajax() {
			ob_start();

			check_ajax_referer( self::NONCE_ACTION, self::NONCE_FIELD );

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				$this->send_json_response( array( 'error' => esc_html__( 'You do not have permission to delete labels.', 'dhl-for-woocommerce' ) ) );
				wp_die();
			}

			$order_id = isset( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : 0;

			if ( ! $order_id ) {
				$this->send_json_response( array( 'error' => esc_html__( 'Invalid order ID.', 'dhl-for-woocommerce' ) ) );
				wp_die();
			}

			$this->delete_label_tracking( $order_id );
			$this->delete_label_file( $order_id );

			$this->send_json_response(
				array(
					'button_txt' => esc_html__( 'Generate label', 'dhl-for-woocommerce' ),
				)
			);
			wp_die();
		}

		/**
		 * Send a JSON AJAX response, discarding any stray output first.
		 *
		 * PHP notices/warnings, or wpdb database-error prints emitted when a site
		 * runs with WP_DEBUG_DISPLAY enabled, would otherwise be written to the
		 * response body before the JSON — breaking JSON parsing on the client and
		 * making the label request appear to fail even though it succeeded. The
		 * matching ob_start() at the top of each handler captures that output; here
		 * we drop it so the body is always valid JSON, whatever the site's debug config.
		 *
		 * @param mixed $data Response payload passed straight to wp_send_json().
		 */
		protected function send_json_response( $data ) {
			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			wp_send_json( $data );
		}

		// -------------------------------------------------------------------------
		// Order meta helpers
		// -------------------------------------------------------------------------

		/**
		 * Merge and save label field values to order meta.
		 *
		 * @param int   $order_id
		 * @param array $items
		 */
		protected function save_label_items( $order_id, array $items ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$existing = $order->get_meta( self::LABEL_ITEMS_META );
			if ( is_array( $existing ) ) {
				$items = array_merge( $existing, $items );
			}

			$order->update_meta_data( self::LABEL_ITEMS_META, $items );
			$order->save();
		}

		/**
		 * Retrieve saved label field values from order meta.
		 *
		 * @param  int   $order_id
		 * @return array
		 */
		public function get_label_items( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return array();
			}

			$items = $order->get_meta( self::LABEL_ITEMS_META );

			return is_array( $items ) ? $items : array();
		}

		/**
		 * Save label tracking data (tracking number, label URL, etc.) to order meta.
		 *
		 * @param int   $order_id
		 * @param array $tracking_info
		 */
		public function save_label_tracking( $order_id, array $tracking_info ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$order->update_meta_data( self::LABEL_TRACKING_META, $tracking_info );
			$order->save_meta_data();
		}

		/**
		 * Retrieve stored label tracking data.
		 *
		 * @param  int   $order_id
		 * @return array
		 */
		public function get_label_tracking( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return array();
			}

			$tracking = $order->get_meta( self::LABEL_TRACKING_META );

			return is_array( $tracking ) ? $tracking : array();
		}

		/**
		 * Remove label tracking data so the metabox reverts to its initial state.
		 *
		 * @param int $order_id
		 */
		public function delete_label_tracking( $order_id ) {
			$order = wc_get_order( $order_id );

			if ( ! is_a( $order, 'WC_Order' ) ) {
				return;
			}

			$order->delete_meta_data( self::LABEL_TRACKING_META );
			$order->save();
		}

		/**
		 * Delete local label file and clear order meta when a HPOS order is deleted.
		 *
		 * @param int $order_id
		 */
		public function cleanup_on_order_delete( $order_id ) {
			$this->delete_label_file( $order_id );
		}

		/**
		 * Delete local label file and clear order meta when a legacy post-based order is deleted.
		 *
		 * @param int $post_id
		 */
		public function cleanup_on_post_delete( $post_id ) {
			if ( 'shop_order' !== get_post_type( $post_id ) ) {
				return;
			}
			$this->delete_label_file( $post_id );
		}

		/**
		 * Remove the locally-stored label PDF for an order (if it exists).
		 *
		 * @param int $order_id
		 */
		protected function delete_label_file( $order_id ) {
			$file_path = $this->get_label_file_path( $order_id );

			if ( file_exists( $file_path ) ) {
				wp_delete_file( $file_path );
			}
		}

		/**
		 * Absolute path to the locally-stored label PDF for an order.
		 *
		 * @param int $order_id
		 * @return string
		 */
		protected function get_label_file_path( $order_id ) {
			$upload_dir = wp_upload_dir();
			return $upload_dir['basedir'] . '/woocommerce_dhl_label/dhl-im-label-' . (int) $order_id . '.pdf';
		}

		/**
		 * Build a protected admin-ajax URL that streams the label PDF.
		 *
		 * The label folder is protected by `deny from all`, so the raw uploads URL
		 * returns 403. Serving the file via readfile() through this endpoint bypasses
		 * that — the same approach as the Paket download endpoint.
		 *
		 * @param int $order_id
		 * @return string
		 */
		protected function get_download_label_url( $order_id ) {
			return add_query_arg(
				array(
					'action'   => 'wc_shipment_internetmarke_download_label',
					'order_id' => (int) $order_id,
					'nonce'    => wp_create_nonce( 'download-internetmarke-label-' . (int) $order_id ),
				),
				admin_url( 'admin-ajax.php' )
			);
		}

		/**
		 * AJAX handler: stream the stored label PDF to an authorised user.
		 *
		 * Bypasses the label folder's `deny from all` protection by reading the file
		 * server-side after verifying the nonce and capability.
		 */
		public function download_label_ajax() {
			// Capture stray output so it cannot corrupt the PDF binary or send
			// premature headers; discarded before the file is streamed below.
			ob_start();

			$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
			$nonce    = isset( $_GET['nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['nonce'] ) ) : '';

			if ( ! $order_id || ! wp_verify_nonce( $nonce, 'download-internetmarke-label-' . $order_id ) ) {
				wp_die( esc_html__( 'Invalid or expired label download link.', 'dhl-for-woocommerce' ), '', array( 'response' => 403 ) );
			}

			if ( ! current_user_can( 'edit_shop_orders' ) ) {
				wp_die( esc_html__( 'You do not have permission to download labels.', 'dhl-for-woocommerce' ), '', array( 'response' => 403 ) );
			}

			$file_path = $this->get_label_file_path( $order_id );

			if ( ! file_exists( $file_path ) ) {
				wp_die( esc_html__( 'Label file not found.', 'dhl-for-woocommerce' ), '', array( 'response' => 404 ) );
			}

			while ( ob_get_level() > 0 ) {
				ob_end_clean();
			}

			nocache_headers();
			header( 'Content-Type: application/pdf' );
			header( 'Content-Disposition: inline; filename="dhl-im-label-' . $order_id . '.pdf"' );
			header( 'Content-Length: ' . filesize( $file_path ) );
			readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		}
	}

endif;
