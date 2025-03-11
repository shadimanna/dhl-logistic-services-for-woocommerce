<?php
/**
 * PR_DHL_WC_Product_Editor_Paket class file.
 * Adds DHL Paket product fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PR_DHL_WC_Product_Paket_Editor' ) ) :
	/**
	 * PR_DHL_WC_Product_Editor_Paket class.
	 */
	class PR_DHL_WC_Product_Editor_Paket extends PR_DHL_WC_Product_Editor {
		/**
		 * Class constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->manufacture_tooltip       = esc_html__( 'Country of Manufacture', 'dhl-for-woocommerce' );
			$this->manufacture_country_label = esc_html__( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_label             = esc_html__( 'Harmonized Tariff Schedule (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_description       = esc_html__( 'Harmonized Tariff Schedule is a number assigned to every possible commodity that can be imported or exported from any country.', 'dhl-for-woocommerce' );
		}

		/**
		 * Add additional product fields.
		 *
		 * @param $parent
		 *
		 * @return void
		 */
		public function additional_product_fields( $parent ): void {
			// Add shipment info on the order page Checkbox Block.
			$parent->add_block(
				array(
					'id'         => '_dhl_no_same_day_transfer',
					'blockName'  => 'woocommerce/product-checkbox-field',
					'attributes' => array(
						'label'          => __( 'Cannot Transfer On Day Of Order (DHL)', 'dhl-for-woocommerce' ),
						'property'       => 'meta_data._dhl_no_same_day_transfer',
						'tooltip'        => __( 'This product cannot be transfered to DHL on the same day of the order.  Checking this disables preferred services on the checkout page.', 'dhl-for-woocommerce' ),
						'checkedValue'   => 'yes',
						'uncheckedValue' => '',
					),
				)
			);
		}
	}

endif;
