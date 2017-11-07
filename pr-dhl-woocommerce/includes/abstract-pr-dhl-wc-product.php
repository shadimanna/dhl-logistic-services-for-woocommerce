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

if ( ! class_exists( 'PR_DHL_WC_Product' ) ) :

abstract class PR_DHL_WC_Product {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( ) {
		// priority is '8' because WC Subscriptions hides fields in the shipping tabs which hide the DHL fields here
		add_action( 'woocommerce_product_options_shipping', array($this,'additional_product_shipping_options'), 8 );
		add_action( 'woocommerce_process_product_meta', array( $this, 'save_additional_product_shipping_options' ) );
	}

	/**
	 * Add the meta box for shipment info on the order page
	 *
	 * @access public
	 */
	public function additional_product_shipping_options() {
	    global $thepostid, $post;

		$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
	
	    // $countries_obj   = new WC_Countries();
	    // $countries   = $countries_obj->__get('countries');
	    $countries = WC()->countries->get_countries();

	    $manufacture_tip = $this->get_manufacture_tooltip();
	    $countries = array_merge( array('0' => __( '- select country -', 'pr-shipping-dhl' )  ), $countries );

	    woocommerce_wp_select(
	    	array(
				'id' => '_dhl_manufacture_country',
				'label' => __('Country of Manufacture (DHL)', 'pr-shipping-dhl'),
				'description' => $manufacture_tip,
				'desc_tip' => 'true',
				/*'value' => $country_value,*/
				'options'    => $countries
			) 
	    );

		woocommerce_wp_text_input( 
			array(
				'id' => '_dhl_hs_code',
				'label' => __('Harmonized Tariff Schedule (DHL)', 'pr-shipping-dhl'),
				'description' => __('Harmonized Tariff Schedule is a number assigned to every possible commodity that can be imported or exported from any country.', 'pr-shipping-dhl'),
				'desc_tip' => 'true',
				'placeholder' => 'HS Code'
			) 
		);

		$this->additional_product_settings();

	}

	abstract public function get_manufacture_tooltip();
	abstract public function additional_product_settings();

	public function save_additional_product_shipping_options( $post_id ) {

	    //Country of manufacture
		if ( isset( $_POST['_dhl_manufacture_country'] ) ) {
			update_post_meta( $post_id, '_dhl_manufacture_country', wc_clean( $_POST['_dhl_manufacture_country'] ) );
		}

	    //HS code value
		if ( isset( $_POST['_dhl_hs_code'] ) ) {
			update_post_meta( $post_id, '_dhl_hs_code', wc_clean( $_POST['_dhl_hs_code'] ) );
		}

		$this->save_additional_product_settings( $post_id );
	}

	abstract public function save_additional_product_settings( $post_id );

}

endif;
