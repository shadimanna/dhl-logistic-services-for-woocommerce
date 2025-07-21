<?php
/**
 * SOAP API Deprecation Admin Notice.
 *
 * @package dhl-for-woocommerce
 */


use PR\DHL\Utils\API_Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'PR_DHL_WC_Notice_SOAP_Deprecation' ) ) :

	class PR_DHL_WC_Notice_SOAP_Deprecation {

		/**
		 * Register hooks.
		 */
		public static function init() {
			// Run late enough that WooCommerce + settings are available.
			add_action( 'admin_notices', array( __CLASS__, 'render' ) );
		}

		/**
		 * Decide whether to show the notice.
		 *
		 * @return bool
		 */
		protected static function should_show() {
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				return false;
			}

			// If REST already enabled we are done (hides notice).
			if ( API_Utils::is_rest_api_enabled() ) {
				return false;
			}

			return true;
		}

		/**
		 * Output the admin notice.
		 */
		public static function render() {
			if ( ! self::should_show() ) {
				return;
			}

			$doc_url = 'https://github.com/shadimanna/dhl-logistic-services-for-woocommerce/wiki/Documentation#settings';
			?>
			<div class="notice notice-warning">
				<style>
                    .pr-dhl-soap-deprecation-notice ol{margin:0 0 1em 1.4em;padding:0}
                    .pr-dhl-soap-deprecation-notice li{margin:0 0 .4em 0}
				</style>
				<div class="pr-dhl-soap-deprecation-notice">
					<p><strong><?php esc_html_e( 'DHL SOAP API Support Will Be Removed Soon', 'dhl-for-woocommerce' ); ?></strong></p>
					<p><?php esc_html_e( 'DHL has officially deprecated their SOAP API, and it will be removed from this plugin in an upcoming release.', 'dhl-for-woocommerce' ); ?></p>
					<p><strong><?php esc_html_e( 'What You Need to Do:', 'dhl-for-woocommerce' ); ?></strong></p>
					<ol>
						<li><?php esc_html_e( 'Go to WooCommerce → Settings → Shipping → DHL.', 'dhl-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Select REST API as the connection method.', 'dhl-for-woocommerce' ); ?></li>
						<li><?php esc_html_e( 'Enter your REST API credentials and save changes.', 'dhl-for-woocommerce' ); ?></li>
					</ol>
					<p>
						<?php esc_html_e( 'Need help?', 'dhl-for-woocommerce' ); ?>
						<a href="<?php echo esc_url( $doc_url ); ?>" target="_blank" rel="noopener noreferrer">
							<?php esc_html_e( 'Read the setup guide →', 'dhl-for-woocommerce' ); ?>
						</a>
					</p>
				</div>
			</div>
			<?php
		}
	}

endif;
