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

if ( ! class_exists( 'PR_DHL_WC_Product_Ecomm' ) ) :

	class PR_DHL_WC_Product_Ecomm extends PR_DHL_WC_Product {

		public function get_manufacture_tooltip() {
			return esc_html__( 'Country of Manufacture. Mandatory for shipments exporting from China.', 'dhl-for-woocommerce' );
		}

		/**
		 * Add the meta box for shipment info on the order page
		 *
		 * @access public
		 */
		public function additional_product_settings() {

			$content_indicators = array( '0' => esc_html__( '- select content indicator -', 'dhl-for-woocommerce' ) );

			try {

				$dhl_obj = PR_DHL()->get_dhl_factory();

				$content_indicators += $dhl_obj->get_dhl_content_indicator();

			} catch ( Exception $e ) {
				echo '<p class="wc_dhl_error">' . esc_html( $e->getMessage() ) . '</p>';
			}

			woocommerce_wp_select(
				array(
					'id'          => '_dhl_dangerous_goods',
					'label'       => esc_html__( 'Content Indicator (DHL)', 'dhl-for-woocommerce' ),
					'description' => esc_html__( 'The content indicator is used as dangerous goods classifier required for air transport.', 'dhl-for-woocommerce' ),
					'desc_tip'    => 'true',
					/*'value' => $country_value,*/
					'options'     => $content_indicators,
				)
			);

			woocommerce_wp_textarea_input(
				array(
					'id'                => '_dhl_export_description',
					'label'             => esc_html__( 'Export Description (DHL)', 'dhl-for-woocommerce' ),
					'description'       => esc_html__( 'Product description to faciliate cross-border shipments. This field is limited to 50 charaters. Mandatory for shipments exporting from China, must be in Chinese characters.', 'dhl-for-woocommerce' ),
					'desc_tip'          => 'true',
					'placeholder'       => 'Product export description...',
					'custom_attributes' => array( 'maxlength' => '50' ),
				)
			);
		}

		public function save_additional_product_settings( $post_id ) {

			// Country of manufacture
			if ( isset( $_POST['_dhl_dangerous_goods'] ) ) {
				update_post_meta( $post_id, '_dhl_dangerous_goods', wc_clean( $_POST['_dhl_dangerous_goods'] ) );
			}

			// HS code value
			if ( isset( $_POST['_dhl_export_description'] ) ) {
				update_post_meta( $post_id, '_dhl_export_description', wc_clean( $_POST['_dhl_export_description'] ) );
			}
		}
	}

endif;
