<?php
/**
 * SOAP API Removed - post-upgrade migration notice.
 *
 * DHL retired its Business Customer Shipping (Geschaeftskundenversand) SOAP API, so this
 * plugin now talks to the DHL Parcel DE Shipping REST API only. Stores that were still on
 * SOAP are switched to REST automatically, so this one-time, dismissible notice asks the
 * admin to re-check their REST API credentials so label creation keeps working.
 *
 * Shown only to DE stores that had the plugin configured and were NOT already on REST.
 *
 * @package dhl-for-woocommerce
 */

use PR\DHL\Utils\API_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PR_DHL_WC_Notice_SOAP_Removed' ) ) :

	class PR_DHL_WC_Notice_SOAP_Removed {

		/**
		 * Option flag set once the admin dismisses the notice.
		 */
		const DISMISSED_OPTION = 'pr_dhl_soap_removed_notice_dismissed';

		/**
		 * Nonce action for the dismiss AJAX request.
		 */
		const NONCE_ACTION = 'pr-dhl-soap-removed-dismiss';

		/**
		 * Register hooks.
		 */
		public static function init() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
			add_action( 'wp_ajax_pr_dhl_dismiss_soap_removed_notice', array( __CLASS__, 'dismiss' ) );
		}

		/**
		 * Whether the migration notice should be shown to the current user.
		 *
		 * @return bool
		 */
		public static function should_show() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}

			// The DHL Paket REST flow is Germany-only.
			if ( 'DE' !== PR_DHL()->get_base_country() ) {
				return false;
			}

			// Already acknowledged.
			if ( 'yes' === get_option( self::DISMISSED_OPTION ) ) {
				return false;
			}

			$settings = get_option( 'woocommerce_pr_dhl_paket_settings', array() );

			// A brand-new store that never configured DHL never used SOAP - nothing to migrate.
			if ( ! is_array( $settings ) || empty( $settings ) ) {
				return false;
			}

			// Only stores that were NOT explicitly on the REST API need to re-check their
			// credentials. This mirrors the removed API_Utils::is_rest_api_enabled(): an
			// existing SOAP store has 'dhl_default_api' set to something other than
			// 'rest-api' (its old default was 'soap'), or unset on very old installs.
			$selected_api = isset( $settings['dhl_default_api'] ) ? $settings['dhl_default_api'] : '';

			return 'rest-api' !== $selected_api;
		}

		/**
		 * Output the admin notice.
		 */
		public static function render() {
			if ( ! self::should_show() ) {
				return;
			}

			$settings_url = admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pr_dhl_paket' );
			$guide_url    = defined( 'PR_DHL_PAKET_PARCEL_DE_SHIPPING_API_USER_GUIDE' ) ? PR_DHL_PAKET_PARCEL_DE_SHIPPING_API_USER_GUIDE : '';
			?>
			<div class="notice notice-warning is-dismissible pr-dhl-soap-removed-notice">
				<p><strong><?php esc_html_e( 'DHL for WooCommerce: SOAP API support has been removed', 'dhl-for-woocommerce' ); ?></strong></p>
				<p>
					<?php esc_html_e( 'DHL has retired its Business Customer Shipping (Geschäftskundenversand) SOAP API. This plugin now uses the DHL Parcel DE Shipping REST API only, and your store has been switched to REST automatically. Please open the DHL settings and confirm your REST API credentials so shipping label creation keeps working.', 'dhl-for-woocommerce' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( $settings_url ); ?>" class="button button-primary"><?php esc_html_e( 'Check DHL settings', 'dhl-for-woocommerce' ); ?></a>
					<?php if ( '' !== $guide_url ) : ?>
						<a href="<?php echo esc_url( $guide_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'REST API setup guide', 'dhl-for-woocommerce' ); ?></a>
					<?php endif; ?>
				</p>
			</div>
			<?php
		}

		/**
		 * Enqueue the dismiss handler on admin pages where the notice can appear.
		 */
		public static function enqueue() {
			if ( ! self::should_show() ) {
				return;
			}

			wp_enqueue_script(
				'pr-dhl-notice-soap-removed',
				PR_DHL_PLUGIN_DIR_URL . '/assets/js/pr-dhl-notice-soap-removed.js',
				array( 'jquery' ),
				PR_DHL_VERSION,
				true
			);

			wp_localize_script(
				'pr-dhl-notice-soap-removed',
				'pr_dhl_soap_removed_notice',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' ),
					'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
				)
			);
		}

		/**
		 * Persist dismissal so the notice does not return.
		 */
		public static function dismiss() {
			check_ajax_referer( self::NONCE_ACTION, 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( '', '', array( 'response' => 403 ) );
			}

			update_option( self::DISMISSED_OPTION, 'yes' );
			wp_die();
		}
	}

endif;
