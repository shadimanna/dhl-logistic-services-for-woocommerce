<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WooCommerce DHL Add Shipping Notice
 *
 * @package  PR_DHL_WC_Notice
 * @category Admin Notice
 * @author   Shadi Manna
 */

if ( ! class_exists( 'PR_DHL_WC_Notice' ) ) :

class PR_DHL_WC_Notice {

	/**
	 * Init and hook in the integration.
	 */
	public function __construct( ) {
		add_action( 'admin_enqueue_scripts', array( $this, 'load_admin_scripts' ) );

        // Add eComm optin notice
        add_action( 'admin_notices', array( $this, 'dhl_optin_notice' ) );

        add_action( 'wp_ajax_dhl_dismissed_notice_handler', array( $this,'ajax_notice_handler' ) );

		add_action( 'admin_init', array( $this, 'dhl_optin_user' ) );
	}
	
	public function load_admin_scripts( $hook ) {
	    
	    $dismiss_data = array( 
	    					'ajax_url' => admin_url( 'admin-ajax.php' ),
	    					'security' => wp_create_nonce( 'pr-dhl-dismiss-notice' ) 
	    				);

		wp_enqueue_script( 'wc-shipment-dhl-dismiss-notice-js', PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-notice.js', array('jquery'), PR_DHL_VERSION );
		wp_localize_script( 'wc-shipment-dhl-dismiss-notice-js', 'dhl_dismiss_notice', $dismiss_data );
	}

	/**
	 * Admin error notifying user that WC is required
	 */
	public function dhl_optin_notice() {
		// Message NOT dismissed AND user NOT opted in...show notice
		if ( ! get_transient('dhl_dismiss_notice' ) && ! get_option( 'dhl_user_optedin' ) ) { 
		?>
				<div id="dhl-optin-notice" class="notice notice-warning is-dismissible">
					<form class="dhl-optin-notice-form" action="" method="post">
						<label for="dhl-optin-user"><?php _e( 'Would you like DHL to contact you to help setup the Official DHL plugin?', 'dhl-for-woocommerce' ); ?></label>
						<input name="dhl-optin-user" type="hidden" value="1" />
						<input class="button-primary" type="submit" value="<?php _e( 'Yes', 'dhl-for-woocommerce' ); ?>" />
					</form>
				</div>
		<?php
		}
	}

	/**
	 * AJAX handler to store the state of dismissible notices.
	 */
	public function ajax_notice_handler() {
	    check_ajax_referer( 'pr-dhl-dismiss-notice', 'security' );
		set_transient( 'dhl_dismiss_notice', 1, 24 * HOUR_IN_SECONDS );
	}

	public function dhl_optin_user() {

		if ( isset( $_POST['dhl-optin-user'] ) ) {

			$current_user = wp_get_current_user();
			if ( empty( $current_user->user_firstname ) && empty( $current_user->user_lastname) ) {
				$email_name = $current_user->user_login;
			} else {
				$email_name = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			}

			$subject = __('DHL Support Request from DHL WooCommerce plugin', 'dhl-for-woocommerce');
			$message = __('Please contact me to help me setup the plugin.', 'dhl-for-woocommerce');
			$headers[] = 'From: ' . $email_name . ' <' . $current_user->user_email . '>';
			// send email to 'integration@dhl.com'
			if( ! wp_mail( 'wp@progressus.io', $subject, $message, $headers ) ) {
				PR_DHL()->log_msg( 'Email failure: DHL notice "wp_mail" failed to send' );
			}

			update_option( 'dhl_user_optedin', 1 );

			wp_redirect( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pr_dhl_ecomm' ) );
		}
	}
}

endif;
