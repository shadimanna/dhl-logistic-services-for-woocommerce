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

if ( ! class_exists( 'PR_DHL_WC_Product_Paket' ) ) :

class PR_DHL_WC_Product_Paket extends PR_DHL_WC_Product {

	public function get_manufacture_tooltip() {
		return __('Country of Manufacture', 'dhl-for-woocommerce');
	}
	
	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function additional_product_settings() {
	    woocommerce_wp_checkbox( 
			array(
				'id' => '_dhl_no_same_day_transfer',
				'label' => __('Cannot Transfer On Day Of Order (DHL)', 'dhl-for-woocommerce'),
				'description' => __('This product cannot be transfered to DHL on the same day of the order.  Checking this disables preferred services on the checkout page.', 'dhl-for-woocommerce'),
				// 'desc_tip' => 'false',
			) 
		);
	}

	public function save_additional_product_settings( $post_id ) {
		 
		// If the same day checkbox is set then it is 'yes', if it is not set (i.e. not sent via $_POST), then it is 'no' unchecked!
		if ( isset( $_POST['_dhl_no_same_day_transfer'] ) ) {
			update_post_meta( $post_id, '_dhl_no_same_day_transfer', wc_clean( $_POST['_dhl_no_same_day_transfer'] ) );
		} else {
			update_post_meta( $post_id, '_dhl_no_same_day_transfer', 'no' );

		}
	}

}

endif;
