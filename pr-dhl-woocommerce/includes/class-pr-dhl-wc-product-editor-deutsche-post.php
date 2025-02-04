<?php
/**
 * Product editor handler class.
 *
 * @package PR_DHL_WC_Product
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

/**
 * Product editor handler.
 */
if ( ! class_exists( 'PR_DHL_WC_Product_Editor_Deutsche_Post' ) ) :

	class PR_DHL_WC_Product_Editor_Deutsche_Post extends PR_DHL_WC_Product_Editor {

		public function __construct() {
			parent::__construct();

			$this->manufacture_tooltip       = esc_html__( 'Country of origin of goods. Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' );
			$this->manufacture_country_label = esc_html__( 'Country of origin (Deutsche Post International)', 'dhl-for-woocommerce' );
			$this->hs_code_label             = esc_html__( 'Harmonized Tariff code (Deutsche Post International)', 'dhl-for-woocommerce' );
			$this->hs_code_description       = esc_html__( 'Optional information for non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' );
		}

		public function save_shipping_blocks( $parent ): void {
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
