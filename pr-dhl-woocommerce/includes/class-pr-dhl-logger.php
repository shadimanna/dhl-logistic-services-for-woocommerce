<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class PR_DHL_Logger {

	/**
	 * WC_DHL_Logger constructor.
	 *
	 * @param WC_XR_debug $debug
	 */
	public function __construct() {}

	/**
	 * Write the message to log
	 *
	 * @param String $message
	 */
	public function write( $message ) {
		// Logger object
		$wc_logger = new WC_Logger();

		// Add to logger
		$wc_logger->add( 'DHL', $message );
	}

	public function get_log_url() {
		return admin_url('admin.php?page=wc-status&tab=logs');
	}

}