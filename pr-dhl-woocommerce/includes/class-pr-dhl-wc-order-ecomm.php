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

if ( ! class_exists( 'PR_DHL_WC_Order_Ecomm' ) ) :

	class PR_DHL_WC_Order_Ecomm extends PR_DHL_WC_Order {

		protected $carrier = 'DHL eCommerce';

		public function init_hooks() {
			parent::init_hooks();

			// add 'Label Created' orders page column header
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'add_order_label_column_header' ), 30 );

			// add 'Label Created' orders page column content
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'add_order_label_column_content' ) );

			// print DHL handover document
			add_action( 'admin_init', array( $this, 'print_document_action' ), 1 );

			// add bulk order filter for printed / non-printed orders
			add_action( 'restrict_manage_posts', array( $this, 'filter_orders_by_label_created' ), 20 );
			add_filter( 'request', array( $this, 'filter_orders_by_label_created_query' ) );
		}

		public function additional_meta_box_fields( $order_id, $is_disabled, $dhl_label_items, $dhl_obj ) {

			if ( $this->is_crossborder_shipment( $order_id ) ) {

				// Duties drop down
				$duties_opt = $dhl_obj->get_dhl_duties();
				woocommerce_wp_select(
					array(
						'id'                => 'pr_dhl_duties',
						'label'             => esc_html__( 'Incoterms:', 'dhl-for-woocommerce' ),
						'description'       => '',
						'value'             => isset( $dhl_label_items['pr_dhl_duties'] ) ? $dhl_label_items['pr_dhl_duties'] : $this->shipping_dhl_settings['dhl_duties_default'],
						'options'           => $duties_opt,
						'custom_attributes' => array( $is_disabled => $is_disabled ),
					)
				);

				// Get saved package description, otherwise generate the text based on settings
				if ( ! empty( $dhl_label_items['pr_dhl_description'] ) ) {
					$selected_dhl_desc = $dhl_label_items['pr_dhl_description'];
				} else {
					$selected_dhl_desc = $this->get_package_description( $order_id );
				}

				woocommerce_wp_textarea_input(
					array(
						'id'                => 'pr_dhl_description',
						'label'             => esc_html__( 'Package description for customs (50 characters max): ', 'dhl-for-woocommerce' ),
						'placeholder'       => '',
						'description'       => '',
						'value'             => $selected_dhl_desc,
						'custom_attributes' => array(
							$is_disabled => $is_disabled,
							'maxlength'  => '50',
						),
					)
				);

			}

			if ( $this->is_cod_payment_method( $order_id ) ) {

				woocommerce_wp_checkbox(
					array(
						'id'                => 'pr_dhl_is_cod',
						'label'             => esc_html__( 'COD Enabled:', 'dhl-for-woocommerce' ),
						'placeholder'       => '',
						'description'       => '',
						'value'             => isset( $dhl_label_items['pr_dhl_is_cod'] ) ? $dhl_label_items['pr_dhl_is_cod'] : 'yes',
						'custom_attributes' => array( $is_disabled => $is_disabled ),
					)
				);
			}
		}


		/**
		 * Order Tracking Save
		 *
		 * Function for saving tracking items
		 */
		public function get_additional_meta_ids() {

			return array( 'pr_dhl_duties', 'pr_dhl_description', 'pr_dhl_is_cod' );
		}

		protected function get_tracking_url() {
			return PR_DHL_ECOMM_TRACKING_URL;
		}

		protected function get_package_description( $order_id ) {
			// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
			$dhl_desc_default = $this->shipping_dhl_settings['dhl_desc_default'];
			$order            = wc_get_order( $order_id );
			$ordered_items    = $order->get_items();

			$desc_array = array();
			foreach ( $ordered_items as $key => $item ) {
				$product_id = $item['product_id'];
				$product    = wc_get_product( $product_id );

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
			$desc_text  = implode( ', ', $desc_array );
			$desc_text  = mb_substr( $desc_text, 0, 50, 'UTF-8' );

			return $desc_text;
		}

		protected function get_label_args_settings( $order_id, $dhl_label_items ) {
			// $this->shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
			$order = wc_get_order( $order_id );

			// Get DHL pickup and distribution center
			$args['dhl_settings']['dhl_api_key']    = $this->shipping_dhl_settings['dhl_api_key'];
			$args['dhl_settings']['dhl_api_secret'] = $this->shipping_dhl_settings['dhl_api_secret'];
			$args['dhl_settings']['pickup']         = $this->shipping_dhl_settings['dhl_pickup'];
			$args['dhl_settings']['distribution']   = $this->shipping_dhl_settings['dhl_distribution'];
			$args['dhl_settings']['handover']       = $this->get_label_handover_num();
			$args['dhl_settings']['label_format']   = $this->shipping_dhl_settings['dhl_label_format'];
			$args['dhl_settings']['label_size']     = $this->shipping_dhl_settings['dhl_label_size'];
			$args['dhl_settings']['label_page']     = $this->shipping_dhl_settings['dhl_label_page'];
			$args['dhl_settings']['label_layout']   = $this->shipping_dhl_settings['dhl_label_layout'];

			// Get package prefix
			$args['order_details']['prefix'] = $this->shipping_dhl_settings['dhl_prefix'];

			if ( ! empty( $dhl_label_items['pr_dhl_description'] ) ) {
				$args['order_details']['description'] = $dhl_label_items['pr_dhl_description'];
			} else {
				// If description is empty and it is an international shipment throw an error
				if ( $this->is_crossborder_shipment( $order_id ) ) {
					throw new Exception( esc_html__( 'The package description cannot be empty!', 'dhl-for-woocommerce' ) );

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

			$new_item        = array();
			$dangerous_goods = get_post_meta( $product_id, '_dhl_dangerous_goods', true );
			if ( ! empty( $dangerous_goods ) ) {

				if ( isset( $args['order_details']['dangerous_goods'] ) ) {
					// if more than one item id DG, make sure to take the minimum value
					$args['order_details']['dangerous_goods'] = min( $args['order_details']['dangerous_goods'], $dangerous_goods );
				} else {
					$args['order_details']['dangerous_goods'] = $dangerous_goods;
				}
			}

			$new_item['item_export'] = get_post_meta( $product_id, '_dhl_export_description', true );

			return $new_item;
		}

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
		}

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
			return '8' . wp_rand( 1000000000, 9999999999 );
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
					$new_columns['dhl_label_created']   = esc_html__( 'DHL Label Created', 'dhl-for-woocommerce' );
					$new_columns['dhl_tracking_number'] = esc_html__( 'DHL Tracking Number', 'dhl-for-woocommerce' );
					$new_columns['dhl_handover_note']   = esc_html__( 'DHL Handover Created', 'dhl-for-woocommerce' );
				}
			}

			return $new_columns;
		}

		public function add_order_label_column_content( $column ) {
			global $post, $theorder;

			$order_id = API_Utils::is_HPOS() ? $theorder->get_id() : $post->ID;

			if ( $order_id ) {
				if ( 'dhl_label_created' === $column ) {
					echo esc_html( $this->get_print_status( $order_id ) );
				}

				if ( 'dhl_tracking_number' === $column ) {
					$tracking_link = $this->get_tracking_link( $order_id );
					echo empty( $tracking_link ) ? '<strong>&ndash;</strong>' : esc_html( $tracking_link );
				}

				if ( 'dhl_handover_note' === $column ) {
					echo esc_html( $this->get_hangover_status( $order_id ) );
				}
			}
		}

		private function get_print_status( $order_id ) {
			$label_tracking_info = $this->get_dhl_label_tracking( $order_id );

			if ( empty( $label_tracking_info ) ) {
				return '<strong>&ndash;</strong>';
			} else {
				return '&#10004';
			}
		}

		private function get_hangover_status( $order_id ) {
			$order    = wc_get_order( $order_id );
			$handover = $order->get_meta( '_pr_shipment_dhl_handover_note' );

			if ( empty( $handover ) ) {
				return '<strong>&ndash;</strong>';
			} else {
				return '&#10004';
			}
		}

		public function get_bulk_actions() {

			$shop_manager_actions = array();

			$shop_manager_actions = array(
				'pr_dhl_create_labels' => esc_html__( 'DHL Create Labels', 'dhl-for-woocommerce' ),
			);

			if ( isset( $this->shipping_dhl_settings['dhl_bulk_product_int'] ) && ( $bulk_product_int = $this->shipping_dhl_settings['dhl_bulk_product_int'] ) ) {
				foreach ( $bulk_product_int as $key => $value ) {
					$shop_manager_actions += array(
						/* translators: %s is the DHL product integer value */
						"pr_dhl_create_labels:int:$value" => sprintf( esc_html__( 'DHL Create Labels - %s', 'dhl-for-woocommerce' ), esc_html( $value ) ),
					);
				}
			}

			if ( isset( $this->shipping_dhl_settings['dhl_bulk_product_dom'] ) && ( $bulk_product_dom = $this->shipping_dhl_settings['dhl_bulk_product_dom'] ) ) {
				foreach ( $bulk_product_dom as $key => $value ) {
					$shop_manager_actions += array(
						/* translators: %s is the DHL domestic product identifier */
						"pr_dhl_create_labels:dom:$value" => sprintf( esc_html__( 'DHL Create Labels - %s', 'dhl-for-woocommerce' ), esc_html( $value ) ),
					);
				}
			}

			$shop_manager_actions += array(
				'pr_dhl_handover' => esc_html__( 'DHL Print Handover', 'dhl-for-woocommerce' ),
			);

			return $shop_manager_actions;
		}

		public function validate_bulk_actions( $action, $order_ids ) {

			$orders_count = count( $order_ids );

			if ( 'pr_dhl_create_labels' === $action ) {

				if ( $orders_count < 1 ) {

					return esc_html__( 'No orders selected for the DHL bulk action, please select orders before performing the DHL action.', 'dhl-for-woocommerce' );

				}
			} elseif ( 'pr_dhl_handover' === $action ) {

				if ( $orders_count < 1 ) {

					return esc_html__( 'No orders selected for the DHL bulk action, please select orders before performing the DHL action.', 'dhl-for-woocommerce' );

				}

				// Ensure the selected orders have a label created, otherwise don't create handover
				foreach ( $order_ids as $order_id ) {
					$label_tracking_info = $this->get_dhl_label_tracking( $order_id );
					if ( empty( $label_tracking_info ) ) {
						return esc_html__( 'One or more orders do not have a DHL label created, please ensure all DHL labels are created for each order before creating a handoff document.', 'dhl-for-woocommerce' );
					}
				}
			}

			return '';
		}

		public function process_bulk_actions( $action, $order_ids, $dhl_force_product = false, $is_force_product_dom = false ) {

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

			$array_messages += parent::process_bulk_actions( $action, $order_ids, $dhl_force_product, $is_force_product_dom );

			if ( 'pr_dhl_handover' === $action ) {
				$redirect_url   = admin_url( 'edit.php?post_type=shop_order' );
				$order_ids_hash = md5( wp_json_encode( $order_ids ) );
				// Save the order IDs in a option.
				// Initially we were using a transient, but this seemed to cause issues
				// on some hosts (mainly GoDaddy) that had difficulty in implementing a
				// proper object cache override.
				update_option( "pr_dhl_handover_order_ids_{$order_ids_hash}", $order_ids );

				$action_url = wp_nonce_url(
					add_query_arg(
						array(
							'pr_dhl_action' => 'print',
							'order_id'      => $order_ids[0],
							'order_ids'     => $order_ids_hash,
						),
						'' !== $redirect_url ? $redirect_url : admin_url()
					),
					'pr_dhl_handover'
				);

				$print_link = '<a href="' . $action_url . '" target="_blank">' . esc_html__( 'Print DHL handover.', 'dhl-for-woocommerce' ) . '</a>';

				/* translators: 1: number of orders, 2: print link */
				$message = sprintf( esc_html__( 'DHL handover for %1$s order(s) created. %2$s', 'dhl-for-woocommerce' ), $orders_count, $print_link );

				array_push(
					$array_messages,
					array(
						'message' => $message,
						'type'    => 'success',
					)
				);
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
					die( esc_html__( 'You are not allowed to view this page.', 'dhl-for-woocommerce' ) );
				}

				$order_id = isset( $_GET['order_id'] ) ? (int) $_GET['order_id'] : 0;

				// Get order IDs temporary option.
				$order_ids_hash = isset( $_GET['order_ids'] ) ? $_GET['order_ids'] : '';
				$order_ids      = empty( $order_ids_hash ) ? array() : get_option( "pr_dhl_handover_order_ids_{$order_ids_hash}" );
				$order_ids      = false === $order_ids ? array() : $order_ids;

				if ( empty( $order_ids ) ) {
					die( esc_html__( 'The DHL handover is not valid, please regenerate anothor one!', 'dhl-for-woocommerce' ) );
				}

				// Since this is not a transient, we delete it manually.
				delete_option( "pr_dhl_handover_order_ids_{$order_ids_hash}" );

				// Generate the handover id random number (10 digits) with prefix '8'
				$handover_id  = $this->get_handover_num();
				$total_weight = 0;
				$dhl_products = array();
				$items_qty    = sizeof( $order_ids );

				try {

					// Get list of all DHL products and change key to name
					$dhl_obj          = PR_DHL()->get_dhl_factory();
					$dhl_product_list = $dhl_obj->get_dhl_products_domestic() + $dhl_obj->get_dhl_products_international();
				} catch ( Exception $e ) {
					/* translators: %s: error message */
					die( sprintf( esc_html__( 'Cannot generate handover %s', 'dhl-for-woocommerce' ), esc_html( $e->getMessage() ) ) );
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
					$order = wc_get_order( $order_id );
					$order->update_meta_data( '_pr_shipment_dhl_handover_note', 1 );
					$order->save();
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
				die( esc_html__( 'The DHL handover cannot be generated, arguments missing.', 'dhl-for-woocommerce' ) );
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
					'dhl_label_not_created' => esc_html__( 'DHL Label Not Created', 'dhl-for-woocommerce' ),
					'dhl_label_created'     => esc_html__( 'DHL Label Created', 'dhl-for-woocommerce' ),
				);

				$selected = isset( $_GET['_shop_order_dhl_label_created'] ) ? $_GET['_shop_order_dhl_label_created'] : '';

				?>
			<select name="_shop_order_dhl_label_created" id="dropdown_shop_order_dhl_label_created">
				<option value=""><?php esc_html_e( 'Show all DHL label statuses', 'dhl-for-woocommerce' ); ?></option>
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
					case 'dhl_label_not_created':
						$meta    = '_pr_shipment_dhl_label_tracking';
						$compare = 'NOT EXISTS';
						break;
					case 'dhl_label_created':
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

			if ( empty( $files ) ) {
				throw new Exception( esc_html__( 'There are no files to merge.', 'dhl-for-woocommerce' ) );
			}

			if ( ! class_exists( 'Imagick' ) ) {
				throw new Exception( esc_html__( '"Imagick" must be installed on the server to merge png files.', 'dhl-for-woocommerce' ) );
			}

			$all = new Imagick();
			foreach ( $files as $key => $value ) {

				if ( ! file_exists( $value ) ) {
					// throw new Exception( esc_html__('File does not exist', 'dhl-for-woocommerce') );
					continue;
				}

				$ext = pathinfo( $value, PATHINFO_EXTENSION );
				if ( stripos( $ext, 'png' ) === false ) {
					throw new Exception( esc_html__( 'Not all the file formats are the same.', 'dhl-for-woocommerce' ) );
				}

				$im = new Imagick( $value );
				$all->addImage( $im );
			}

			$filename       = 'dhl-label-bulk-' . time() . '.png';
			$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
			$file_bulk_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;

			/* Append the images into one */
			$all->resetIterator();
			$combined = $all->appendImages( true );
			// $ima = $im1->appendImages(true);
			$combined->setImageFormat( 'png' );
			$combined->writeimage( $file_bulk_path );

			return array(
				'file_bulk_path' => $file_bulk_path,
				'file_bulk_url'  => $file_bulk_url,
			);
		}

		protected function merge_label_files_zpl( $files ) {

			if ( empty( $files ) ) {
				throw new Exception( esc_html__( 'There are no files to merge.', 'dhl-for-woocommerce' ) );
			}

			$files_content = '';
			foreach ( $files as $key => $value ) {

				if ( ! file_exists( $value ) ) {
					// throw new Exception( esc_html__('File does not exist', 'dhl-for-woocommerce') );
					continue;
				}

				$ext = pathinfo( $value, PATHINFO_EXTENSION );
				if ( stripos( $ext, 'zpl' ) === false ) {
					throw new Exception( esc_html__( 'Not all the file formats are the same.', 'dhl-for-woocommerce' ) );
				}

				// Check if the file is a remote URL or a local file
				if ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
					// Use wp_remote_get() for remote files
					$response = wp_remote_get( $value );

					if ( is_wp_error( $response ) ) {
						throw new Exception( esc_html__( 'Failed to retrieve remote file.', 'dhl-for-woocommerce' ) );
					}

					$files_content .= wp_remote_retrieve_body( $response );
				} else {
					// Use file_get_contents for local files
					$files_content .= file_get_contents( $value );
				}
			}

			$filename       = 'dhl-label-bulk-' . time() . '.zpl';
			$file_bulk_path = PR_DHL()->get_dhl_label_folder_dir() . $filename;
			$file_bulk_url  = PR_DHL()->get_dhl_label_folder_url() . $filename;

			// Ensure the WP_Filesystem is initialized
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();

			global $wp_filesystem;

			// Write files content using WP_Filesystem methods
			if ( $wp_filesystem->exists( $file_bulk_path ) ) {
				// Append content if the file exists
				$wp_filesystem->append( $file_bulk_path, $files_content );
			} else {
				// Create the file and write content if it does not exist
				$wp_filesystem->put_contents( $file_bulk_path, $files_content, FS_CHMOD_FILE );
			}

			return array(
				'file_bulk_path' => $file_bulk_path,
				'file_bulk_url'  => $file_bulk_url,
			);
		}
	}

endif;
