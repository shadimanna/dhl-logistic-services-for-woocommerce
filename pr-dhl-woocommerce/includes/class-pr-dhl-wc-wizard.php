<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Wizard.
 *
 * @package  PR_DHL_WC_Wizard
 * @category Admin
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Wizard' ) ) :

class PR_DHL_WC_Wizard {

    public function __construct() {

		if ( true ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'wizard_enqueue_scripts') );
			add_action( 'admin_footer', array( $this, 'display_wizard' ), 10 );
		}

		$this->init();
    }

	public function init() {}

	public function all_wizard_field_names() {
		return array();
	}

	public function wizard_enqueue_scripts() {
		wp_enqueue_style( 'wc-shipment-lib-wizard-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/wizard.library.css' );
		wp_enqueue_style( 'wc-shipment-dhl-wizard-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-wizard.css' );
		wp_enqueue_script(
			'wc-shipment-lib-wizard-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/wizard.library.js',
			array(),
			PR_DHL_VERSION
		);
		wp_enqueue_script(
			'wc-shipment-dhl-wizard-js',
			PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-wizard.js',
			array(),
			PR_DHL_VERSION,
			true
		);
		wp_localize_script( 'wc-shipment-dhl-wizard-js', 'dhl_wizard_obj', array( 
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'dhl-wizard-nonce' ),
			'all_fields' => $this->all_wizard_field_names(),
		) );
	}

	public function display_wizard() {}
}

endif;