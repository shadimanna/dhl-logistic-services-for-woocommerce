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
	 * @param BlockInterface $shipping_dimensions_block The shipping dimensions block.
	 */
	public function add_shipping_blocks( BlockInterface $shipping_dimensions_block ): void {
		if ( ! method_exists( $shipping_dimensions_block, 'get_parent' ) ) {
			return;
		}

		$parent = $shipping_dimensions_block->get_parent();

		// Add Country of Origin Select Block.
		$parent->add_block(
			array(
				'id'         => '_dhl_manufacture_country',
				'blockName'  => 'woocommerce/product-select-field',
				'attributes' => array(
					'label'    => __( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' ),
					'property' => 'meta_data._dhl_manufacture_country',
					'options'  => array_merge(
						array( array( 'value' => '0', 'label' => __( '- select country -', 'dhl-for-woocommerce' ) ) ),
						array_map( function( $key, $value ) {
							return array(
								'value' => $key,
								'label' => $value,
							);
						}, array_keys( WC()->countries->get_countries() ), WC()->countries->get_countries() )
					),
					'tooltip'  => __( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' ),
				),
			)
		);

		// Add HS Tariff Code Text Block.
		$parent->add_block(
			array(
				'id'         => '_dhl_hs_code',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => array(
					'label'       => __( 'Harmonized Tariff Schedule (DHL)', 'dhl-for-woocommerce' ),
					'property'    => 'meta_data._dhl_hs_code',
					'placeholder' => __( 'HS Code', 'dhl-for-woocommerce' ),
					'tooltip'     => __( 'Harmonized Tariff Schedule is a number assigned to every possible commodity that can be imported or exported from any country.', 'dhl-for-woocommerce' ),
				),
			)
		);

		// Add shipment info on the order page Checkbox Block.
		$parent->add_block(
			array(
				'id'         => '_dhl_no_same_day_transfer',
				'blockName'  => 'woocommerce/product-checkbox-field',
				'attributes' => array(
					'label'       	 => __( 'Cannot Transfer On Day Of Order (DHL)', 'dhl-for-woocommerce' ),
					'property'    	 => 'meta_data._dhl_no_same_day_transfer',
					'tooltip'     	 => __( 'This product cannot be transfered to DHL on the same day of the order.  Checking this disables preferred services on the checkout page.', 'dhl-for-woocommerce' ),
					'checkedValue'   => 'yes',
					'uncheckedValue' => '',
				),
			)
		);

	}
}

endif;
