<?php
/**
 * PR_DHL_WC_Product_Editor class file.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

use Automattic\WooCommerce\Admin\BlockTemplates\BlockInterface;

if ( ! class_exists( 'PR_DHL_WC_Product_Editor' ) ) :
	/**
	 * PR_DHL_WC_Product_Editor Class.
	 */
	abstract class PR_DHL_WC_Product_Editor {
		/**
		 * Manufacture country tooltip.
		 *
		 * @var string
		 */
		protected $manufacture_tooltip = '';

		/**
		 * Manufacture country label.
		 *
		 * @var string
		 */
		protected $manufacture_country_label = '';

		/**
		 * HS Code label.
		 *
		 * @var string
		 */
		protected $hs_code_label = '';

		/**
		 * HS Code description.
		 *
		 * @var string
		 */
		protected $hs_code_description = '';

		/**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-shipping-dimensions', array( $this, 'add_product_shipping_fields' ) );
			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-variation-shipping-dimensions', array( $this, 'add_product_shipping_fields' ) );
		}

		/**
		 * Add additional product fields.
		 *
		 * @param $parent
		 *
		 * @return void
		 */
		abstract public function additional_product_fields( $parent ): void;

		/**
		 * Add DHL product fields.
		 *
		 * @param BlockInterface $shipping_dimensions_block
		 *
		 * @return void
		 */
		public function add_product_shipping_fields( BlockInterface $shipping_dimensions_block ): void {
			if ( ! method_exists( $shipping_dimensions_block, 'get_parent' ) ) {
				return;
			}

			$parent = $shipping_dimensions_block->get_parent();

			// Add Country of Origin Select Block.
			$countries = WC()->countries->get_countries();
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
									'label' => __( '- select country -', 'dhl-for-woocommerce' ),
								)
							),
							array_map( function ( $key, $value ) {
								return array(
									'value' => $key,
									'label' => $value,
								);
							}, array_keys( $countries ), $countries )
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

			$this->additional_product_fields( $parent );
		}
	}

endif;
