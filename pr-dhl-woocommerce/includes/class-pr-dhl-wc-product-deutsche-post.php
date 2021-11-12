<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Shipping Product.
 *
 * @package  PR_DHL_WC_Product
 * @category Product
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Product_Deutsche_Post' ) ) :

class PR_DHL_WC_Product_Deutsche_Post extends PR_DHL_WC_Product {

	public function __construct( ) {
		parent::__construct( );

		$this->manufacture_country_label = __('Country of origin (Deutsche Post International)', 'dhl-for-woocommerce');
		$this->hs_code_label = __('Harmonized Tariff code (Deutsche Post International)', 'dhl-for-woocommerce');
		$this->hs_code_description = __('Optional information for non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce');
	}

	public function get_manufacture_tooltip() {
		return __('Country of origin of goods. Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce');
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function additional_product_settings() {
	    
	    $content_indicators = array( '0' => __('- select content indicator -', 'dhl-for-woocommerce' ) );

	    try {
	    	
	    	$dhl_obj = PR_DHL()->get_dhl_factory();

	   		$content_indicators += $dhl_obj->get_dhl_content_indicator();

	    } catch (Exception $e) {

	    	echo '<p class="wc_dhl_error">' . $e->getMessage() . '</p>';
	    }
	    /*
	    woocommerce_wp_select(
	    	array(
				'id' => '_dhl_dangerous_goods',
				'label' => __('Content Indicator (DHL)', 'dhl-for-woocommerce'),
				'description' => __('The content indicator is used as dangerous goods classifier required for air transport.', 'dhl-for-woocommerce'),
				'desc_tip' => 'true',
				//'value' => $country_value,
				'options'    => $content_indicators
			) 
		);
		*/
		woocommerce_wp_textarea_input( 
			array(
				'id' => '_dhl_export_description',
				'label' => __('Content description (Deutsche Post International)', 'dhl-for-woocommerce'),
				'description' => __('Description of goods (max 33 characters). Mandatory for all non-EU shipments. Appears on CN22 (Deutsche Post International).', 'dhl-for-woocommerce'),
				'desc_tip' => 'true',
				'custom_attributes'	=> array( 'maxlength' => '50' )
			) 
		);
	}

	public function save_additional_product_settings( $post_id ) {

		 //Country of manufacture
		/*if ( isset( $_POST['_dhl_dangerous_goods'] ) ) {
			update_post_meta( $post_id, '_dhl_dangerous_goods', wc_clean( $_POST['_dhl_dangerous_goods'] ) );
		}*/
	    
		 //HS code value
		if ( isset( $_POST['_dhl_export_description'] ) ) {
			update_post_meta( $post_id, '_dhl_export_description', wc_clean( $_POST['_dhl_export_description'] ) );
		}
	}

}

endif;
