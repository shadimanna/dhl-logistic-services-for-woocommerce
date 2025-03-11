<?php
/**
 * PR_DHL_WC_Product_Editor_Deutsche_Post class file.
 * Adds Deutsche Post product fields.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PR_DHL_WC_Product_Editor_Deutsche_Post' ) ) :
	/**
	 * PR_DHL_WC_Product_Editor_Deutsche_Post class.
	 */
	class PR_DHL_WC_Product_Editor_Deutsche_Post extends PR_DHL_WC_Product_Editor {
		/**
		 * Class constructor.
		 */
		public function __construct() {
			parent::__construct();

			$this->manufacture_tooltip       = esc_html__( 'Country of origin of goods. Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' );
			$this->manufacture_country_label = esc_html__( 'Country of origin (Deutsche Post International)', 'dhl-for-woocommerce' );
			$this->hs_code_label             = esc_html__( 'Harmonized Tariff code (Deutsche Post International)', 'dhl-for-woocommerce' );
			$this->hs_code_description       = esc_html__( 'Optional information for non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' );
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
					'id'         => '_dhl_export_description',
					'blockName'  => 'woocommerce/product-text-area-field',
					'attributes' => array(
						'label'    => __( 'Content description (Deutsche Post International)', 'dhl-for-woocommerce' ),
						'property' => 'meta_data._dhl_export_description',
						'tooltip'  => __( 'Description of goods (max 33 characters). Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' ),
					),
				)
			);
		}
	}

endif;
