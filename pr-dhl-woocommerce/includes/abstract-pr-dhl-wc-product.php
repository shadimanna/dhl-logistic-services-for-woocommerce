<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Shipping Order.
 *
 * @package  PR_DHL_WC_Product
 * @category Product
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Product' ) ) :

	abstract class PR_DHL_WC_Product {

		protected $manufacture_country_label = '';
		protected $hs_code_label             = '';
		protected $hs_code_description       = '';

		/**
		 * Init and hook in the integration.
		 */
		public function __construct() {

			$this->manufacture_country_label = esc_html__( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_label             = esc_html__( 'Harmonized Tariff Schedule (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_description       = esc_html__( 'Harmonized Tariff Schedule is a number assigned to every possible commodity that can be imported or exported from any country.', 'dhl-for-woocommerce' );

			// priority is '8' because WC Subscriptions hides fields in the shipping tabs which hide the DHL fields here
			add_action( 'woocommerce_product_options_shipping', array( $this, 'additional_product_shipping_options' ), 8 );
			add_action( 'woocommerce_process_product_meta', array( $this, 'save_additional_product_shipping_options' ) );
			add_action( 'woocommerce_product_bulk_edit_end', array( $this, 'product_shipping_bulk_edit_input' ) );
			add_action( 'woocommerce_product_bulk_edit_save', array( $this, 'save_product_shipping_bulk_edit' ) );
			add_action( 'woocommerce_product_quick_edit_end', array( $this, 'product_shipping_bulk_edit_input' ) );
			add_action( 'woocommerce_product_quick_edit_save', array( $this, 'save_product_shipping_bulk_edit' ) );
			add_action( 'manage_product_posts_custom_column', array( $this, 'product_shipping_hidden_input_value' ), 20, 2 );
			add_action( 'admin_enqueue_scripts', array( $this, 'product_enqueue_scripts' ), 20, 1 );
		}

		public function product_enqueue_scripts( $hook ) {

			$screen = get_current_screen();

			if ( 'edit-product' == $screen->id ) {

				wp_enqueue_script(
					'wc-shipment-dhl-product-list-js',
					PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-product-list.js',
					array( 'jquery' ),
					PR_DHL_VERSION
				);

			}
		}

		/**
		 * Add the meta box for shipment info on the order page
		 *
		 * @access public
		 */
		public function additional_product_shipping_options() {
			global $thepostid, $post;

			$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;

			// $countries_obj   = new WC_Countries();
			// $countries   = $countries_obj->esc_html__get('countries');
			$countries = WC()->countries->get_countries();

			$manufacture_tip = $this->get_manufacture_tooltip();
			$countries       = array_merge( array( '0' => esc_html__( '- select country -', 'dhl-for-woocommerce' ) ), $countries );

			woocommerce_wp_select(
				array(
					'id'          => '_dhl_manufacture_country',
					'label'       => $this->manufacture_country_label,
					'description' => $manufacture_tip,
					'desc_tip'    => 'true',
					/*'value' => $country_value,*/
					'options'     => $countries,
				)
			);

			woocommerce_wp_text_input(
				array(
					'id'          => '_dhl_hs_code',
					'label'       => $this->hs_code_label,
					'description' => $this->hs_code_description,
					'desc_tip'    => 'true',
					'placeholder' => esc_html__( 'HS Code', 'dhl-for-woocommerce' ),
				)
			);

			$this->additional_product_settings();
		}

		public function product_shipping_bulk_edit_input() {

			$countries = WC()->countries->get_countries();
			$countries = array_merge( array( '0' => esc_html__( '- No change -', 'dhl-for-woocommerce' ) ), $countries );
			?>
		<div class="inline-edit-group dhl_manufacture_country_inline">
			<label class="alignleft">
				<span class="title"><?php echo esc_html__( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' ); ?></span>
				<span class="input-text-wrap">
					<select class="change_dhl_manufacture_country change_to" name="change_dhl_manufacture_country">
					<?php foreach ( $countries as $value => $text ) { ?>
						<option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_attr( $text ); ?></option>
					<?php } ?>
					</select>
				</span>
			</label>
		</div>

		<div class="inline-edit-group dhl_hs_code_inline">
			<label class="alignleft">
				<span class="title"><?php echo esc_html__( 'Harmonized Tariff Schedule (DHL)', 'dhl-for-woocommerce' ); ?></span>
				<span class="input-text-wrap">
					<input type="text" name="change_dhl_hs_code" class="change_dhl_hs_code text" value="" />
				</span>
			</label>
		</div>
			<?php
		}

		abstract public function get_manufacture_tooltip();
		abstract public function additional_product_settings();

		public function save_additional_product_shipping_options( $post_id ) {

			// Country of manufacture
			if ( isset( $_POST['_dhl_manufacture_country'] ) ) {
				update_post_meta( $post_id, '_dhl_manufacture_country', wc_clean( $_POST['_dhl_manufacture_country'] ) );
			}

			// HS code value
			if ( isset( $_POST['_dhl_hs_code'] ) ) {
				update_post_meta( $post_id, '_dhl_hs_code', wc_clean( $_POST['_dhl_hs_code'] ) );
			}

			$this->save_additional_product_settings( $post_id );
		}

		public function save_product_shipping_bulk_edit( $product ) {
			$post_id = $product->get_id();
			if ( ! empty( $_REQUEST['change_dhl_hs_code'] ) ) {
				update_post_meta( $post_id, '_dhl_hs_code', wc_clean( $_REQUEST['change_dhl_hs_code'] ) );
			}

			if ( isset( $_REQUEST['change_dhl_manufacture_country'] ) && '0' != $_REQUEST['change_dhl_manufacture_country'] ) {
				update_post_meta( $post_id, '_dhl_manufacture_country', wc_clean( $_REQUEST['change_dhl_manufacture_country'] ) );
			}
		}

		public function product_shipping_hidden_input_value( $column, $post_id ) {
			switch ( $column ) {
				case 'name':
					?>
				<div class="hidden dhl_hs_code_inline" id="dhl_hs_code_inline_<?php echo esc_attr( $post_id ); ?>">
					<div id="dhl_hs_code"><?php echo esc_html( get_post_meta( $post_id, '_dhl_hs_code', true ) ); ?></div>
				</div>
				<div class="hidden dhl_manufacture_country_inline" id="dhl_manufacture_country_inline_<?php echo esc_attr( $post_id ); ?>">
					<div id="dhl_manufacture_country"><?php echo esc_html( get_post_meta( $post_id, '_dhl_manufacture_country', true ) ); ?></div>
				</div>
					<?php

					break;

				default:
					break;
			}
		}

		abstract public function save_additional_product_settings( $post_id );
	}

endif;
