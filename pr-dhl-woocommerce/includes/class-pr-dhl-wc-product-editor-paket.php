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
if ( ! class_exists( 'PR_DHL_WC_Product_Paket_Editor' ) ) :

	class PR_DHL_WC_Product_Editor_Paket extends PR_DHL_WC_Product_Editor {

		/**
		 * Add custom blocks to the product editor shipping section.
		 *
		 */
		public function save_shipping_blocks( $parent ): void {

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
