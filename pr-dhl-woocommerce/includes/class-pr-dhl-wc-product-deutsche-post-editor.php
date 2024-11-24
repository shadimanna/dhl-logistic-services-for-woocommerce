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
if ( ! class_exists( 'PR_DHL_WC_Product_Deutsche_Post_Editor' ) ) :

class PR_DHL_WC_Product_Deutsche_Post_Editor {

    /**
	 * Class constructor.
	 */
	public function __construct() {
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
		add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-variation-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
	}

	/**
	 * Add custom blocks to the product editor shipping section.
	 *
	 * @param BlockInterface $shipping_dimensions_block The shipping dimensions block.
	 */
	public function add_shipping_blocks( BlockInterface $shipping_dimensions_block ) {
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
					'label'    => __( 'Country of origin (Deutsche Post International)', 'dhl-for-woocommerce' ),
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
					'tooltip'  => __( 'Country of origin of goods. Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' ),
				),
			)
		);

		// Add HS Tariff Code Text Block.
		$parent->add_block(
			array(
				'id'         => '_dhl_hs_code',
				'blockName'  => 'woocommerce/product-text-field',
				'attributes' => array(
					'label'       => __( 'Harmonized Tariff code (Deutsche Post International)', 'dhl-for-woocommerce' ),
					'property'    => 'meta_data._dhl_hs_code',
					'placeholder' => __( 'HS Code', 'dhl-for-woocommerce' ),
					'tooltip'     => __( 'Optional information for non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' ),
				),
			)
		);

		// Add shipment info on the order page Checkbox Block.
		$parent->add_block(
			array(
				'id'         => '_dhl_export_description',
				'blockName'  => 'woocommerce/product-text-area-field',
				'attributes' => array(
					'label'       	 => __( 'Content description (Deutsche Post International)', 'dhl-for-woocommerce' ),
					'property'    	 => 'meta_data._dhl_export_description',
					'tooltip'     	 => __( 'Description of goods (max 33 characters). Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce' ),
				),
			)
		);

	}
}

endif;
