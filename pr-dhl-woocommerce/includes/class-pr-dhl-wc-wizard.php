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
		add_action( 'admin_footer', array( $this, 'display_wizard' ), 10 );

		$this->init();
    }

	public function init() {}

	public static function all_wizard_field_names() {
		return array();
	}

	public function display_wizard() {}
}

endif;