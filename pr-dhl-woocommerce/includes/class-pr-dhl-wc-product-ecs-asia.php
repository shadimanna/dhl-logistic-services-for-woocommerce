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

if ( ! class_exists( 'PR_DHL_WC_Product_eCS_Asia' ) ) :

class PR_DHL_WC_Product_eCS_Asia extends PR_DHL_WC_Product {

	public function get_manufacture_tooltip() {
		return __('Country of Manufacture. Mandatory for shipments exporting from China.', 'pr-shipping-dhl');
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function additional_product_settings() {
	    
	    $content_indicators = array( '0' => __('- select content indicator -', 'pr-shipping-dhl' ) );

	    try {
	    	
	    	$dhl_obj = PR_DHL()->get_dhl_factory();

	   		$content_indicators += $dhl_obj->get_dhl_content_indicator();

	    } catch (Exception $e) {

	    	echo '<p class="wc_dhl_error">' . $e->getMessage() . '</p>';
	    }
	    
	    woocommerce_wp_select(
	    	array(
				'id' => '_dhl_dangerous_goods',
				'label' => __('Content Indicator (DHL)', 'pr-shipping-dhl'),
				'description' => __('The content indicator is used as dangerous goods classifier required for air transport.', 'pr-shipping-dhl'),
				'desc_tip' => 'true',
				/*'value' => $country_value,*/
				'options'    => $content_indicators
			) 
		);

		woocommerce_wp_textarea_input( 
			array(
				'id' => '_dhl_export_description',
				'label' => __('Export Description (DHL)', 'pr-shipping-dhl'),
				'description' => __('Product description to faciliate cross-border shipments. This field is limited to 50 charaters. Mandatory for shipments exporting from China, must be in Chinese characters.', 'pr-shipping-dhl'),
				'desc_tip' => 'true',
				'placeholder' => 'Product export description...',
				'custom_attributes'	=> array( 'maxlength' => '50' )
			) 
		);
	}

	public function save_additional_product_settings( $post_id ) {

		 //Country of manufacture
		if ( isset( $_POST['_dhl_dangerous_goods'] ) ) {
			update_post_meta( $post_id, '_dhl_dangerous_goods', wc_clean( $_POST['_dhl_dangerous_goods'] ) );
		}
	    
		 //HS code value
		if ( isset( $_POST['_dhl_export_description'] ) ) {
			update_post_meta( $post_id, '_dhl_export_description', wc_clean( $_POST['_dhl_export_description'] ) );
		}
	}

}

endif;
