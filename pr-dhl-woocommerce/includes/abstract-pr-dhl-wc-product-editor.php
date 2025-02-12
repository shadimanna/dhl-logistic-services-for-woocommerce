<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

/**
 * WooCommerce DHL Shipping Order.
 *
 * @package  PR_DHL_WC_Product_Editor
 * @category Product
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Product_Editor' ) ) :

	abstract class PR_DHL_WC_Product_Editor {

		protected $manufacture_tooltip       = '';
		protected $manufacture_country_label = '';
		protected $hs_code_label             = '';
		protected $hs_code_description       = '';

		/**
		 * Class constructor.
		 */
		public function __construct() {
			$this->manufacture_tooltip       = esc_html__( 'Country of Manufacture', 'dhl-for-woocommerce' );
			$this->manufacture_country_label = esc_html__( 'Country of Manufacture (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_label             = esc_html__( 'Harmonized Tariff Schedule (DHL)', 'dhl-for-woocommerce' );
			$this->hs_code_description       = esc_html__( 'Harmonized Tariff Schedule is a number assigned to every possible commodity that can be imported or exported from any country.', 'dhl-for-woocommerce' );

			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-variation-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
		}

		abstract public function save_shipping_blocks( $parent ): void;

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
						'label'    => $this->manufacture_country_label,
						'property' => 'meta_data._dhl_manufacture_country',
						'options'  => array_merge(
							array(
								array(
									'value' => '0',
									'label' => __( '- select country -', 'dhl-for-woocommerce' )
								)
							),
							array_map( function ( $key, $value ) {
								return array(
									'value' => $key,
									'label' => $value,
								);
							}, array_keys( WC()->countries->get_countries() ), WC()->countries->get_countries() )
						),
						'tooltip'  => $this->manufacture_tooltip,
					),
				)
			);

			// Add HS Tariff Code Text Block.
			$parent->add_block(
				array(
					'id'         => '_dhl_hs_code',
					'blockName'  => 'woocommerce/product-text-field',
					'attributes' => array(
						'label'       => $this->hs_code_label,
						'property'    => 'meta_data._dhl_hs_code',
						'placeholder' => esc_html__( 'HS Code', 'dhl-for-woocommerce' ),
						'tooltip'     => $this->hs_code_description,
					),
				)
			);

			$this->save_shipping_blocks( $parent );
		}
	}
	
endif;
