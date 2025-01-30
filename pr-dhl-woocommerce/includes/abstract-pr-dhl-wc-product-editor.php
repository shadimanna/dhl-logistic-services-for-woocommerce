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

		 /**
		 * Class constructor.
		 */
		public function __construct() {
			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
			add_action( 'woocommerce_block_template_area_product-form_after_add_block_product-variation-shipping-dimensions', array( $this, 'add_shipping_blocks' ) );
		}

		abstract public function add_shipping_blocks( BlockInterface $shipping_dimensions_block ): void;

	}

endif;