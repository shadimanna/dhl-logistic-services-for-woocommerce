<?php

use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;

/**
 * Class for integrating with WooCommerce Blocks.
 *
 * @package  Extend_Store_Endpoint
 * @category Shipping
 */

if ( ! class_exists( 'PR_DHL_Blocks_Integration' ) ) :

	class PR_DHL_Blocks_Integration implements IntegrationInterface {

		/**
		 * The name of the integration.
		 *
		 * @return string
		 */
		public function get_name() {
			return 'pr-dhl';
		}

		/**
		 * When called invokes any initialization/setup for the integration.
		 */
		public function initialize() {
			$this->register_pr_dhl_blocks_editor_scripts();
			$this->register_pr_dhl_blocks_editor_styles();
			$this->register_main_integration();
		}

		/**
		 * Registers the main JS file required to add filters and Slot/Fills.
		 */
		private function register_main_integration() {
			$script_path = '/build/index.js';
			$style_path  = '/build/style-index.css';

			$script_url = PR_DHL_PLUGIN_DIR_URL . $script_path;
			$style_url  = PR_DHL_PLUGIN_DIR_URL . $style_path;

			$script_asset_path = PR_DHL_PLUGIN_DIR_PATH . '/build/index.asset.php';
			$script_asset      = file_exists( $script_asset_path )
				? require $script_asset_path
				: [
					'dependencies' => [],
					'version'      => $this->get_file_version( PR_DHL_PLUGIN_DIR_PATH . $script_path ),
				];

			wp_enqueue_style(
				'pr-dhl-blocks-integration',
				$style_url,
				[],
				$this->get_file_version( PR_DHL_PLUGIN_DIR_PATH . $style_path )
			);

			wp_register_script(
				'pr-dhl-blocks-integration',
				$script_url,
				$script_asset['dependencies'],
				$script_asset['version'],
				true
			);
			wp_set_script_translations(
				'pr-dhl-blocks-integration',
				'dhl-for-woocommerce',
				PR_DHL_PLUGIN_DIR_PATH . 'languages'
			);
		}

		/**
		 * Returns an array of script handles to enqueue in the frontend context.
		 *
		 * @return string[]
		 */
		public function get_script_handles() {
			return [ 'pr-dhl-blocks-integration', 'pr-dhl-blocks-frontend' ];
		}

		/**
		 * Returns an array of script handles to enqueue in the editor context.
		 *
		 * @return string[]
		 */
		public function get_editor_script_handles() {
			return [ 'pr-dhl-blocks-integration', 'pr-dhl-blocks-editor' ];
		}

		/**
		 * An array of key, value pairs of data made available to the block on the client side.
		 *
		 * @return array
		 */
		public function get_script_data() {
			$data = [
				'pr-dhl-active' => true,
			];

			return $data;
		}

		public function register_pr_dhl_blocks_editor_styles() {

			$block_style_path    = '/build/style-index.css';
			$block_style_url     = PR_DHL_PLUGIN_DIR_URL . $block_style_path;
			$block_style_version = $this->get_file_version( PR_DHL_PLUGIN_DIR_PATH . $block_style_path );

			// Enqueue the editor style
			add_action( 'enqueue_block_editor_assets', function () use ( $block_style_url, $block_style_version ) {
				wp_enqueue_style(
					'pr-dhl-blocks-editor',
					$block_style_url,
					array(),
					$block_style_version
				);
			} );
		}

		public function register_pr_dhl_blocks_editor_scripts() {
			$block_path       = '/build/pr-dhl-blocks.js';
			$block_url        = PR_DHL_PLUGIN_DIR_URL . $block_path;
			$block_asset_path = PR_DHL_PLUGIN_DIR_PATH . '/build/pr-dhl-blocks.asset.php';
			$block_asset      = file_exists( $block_asset_path )
				? require $block_asset_path
				: [
					'dependencies' => [],
					'version'      => $this->get_file_version( PR_DHL_PLUGIN_DIR_PATH . $block_path ),
				];

			wp_register_script(
				'pr-dhl-blocks-editor',
				$block_url,
				$block_asset['dependencies'],
				$block_asset['version'],
				true
			);


			wp_set_script_translations(
				'pr-dhl-blocks-editor',
				'dhl-for-woocommerce',
				PR_DHL_PLUGIN_DIR_PATH . 'languages'
			);

			$block_frontend      = PR_DHL_PLUGIN_DIR_URL . '/build/pr-dhl-blocks-frontend.js';
			$frontend_asset_path = PR_DHL_PLUGIN_DIR_PATH . '/build/pr-dhl-blocks-frontend.asset.php';
			$frontend_asset      = file_exists( $frontend_asset_path )
				? require $frontend_asset_path
				: [
					'dependencies' => [],
					'version'      => $this->get_file_version( PR_DHL_PLUGIN_DIR_PATH . '/build/pr-dhl-blocks-frontend.js' ),
				];

			wp_enqueue_script(
				'pr-dhl-blocks-frontend',
				$block_frontend,
				$frontend_asset['dependencies'],
				$frontend_asset['version'],
				true
			);


			wp_set_script_translations(
				'pr-dhl-blocks-frontend',
				'dhl-for-woocommerce',
				PR_DHL_PLUGIN_DIR_PATH . 'languages'
			);

			// Localize scripts
			$this->localize_scripts();
		}


		/**
		 * Get the file modified time as a cache buster if we're in dev mode.
		 *
		 * @param string $file Local path to the file.
		 *
		 * @return string The cache buster value to use for the given file.
		 */
		protected function get_file_version( $file ) {
			if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file ) ) {
				return filemtime( $file );
			}

			return PR_DHL_VERSION;
		}

		private function localize_scripts() {
			// Load the settings from PHP and make them available to JavaScript.
			try {
				// Fetch the shipping settings
				$dhl_settings = PR_DHL()->get_shipping_dhl_settings();

				// Initialize preferred day time from session
				if ( isset( WC()->session ) ) {
					$preferred_day_time = WC()->session->get( 'dhl_preferred_day_time' );

					// If not found in session, we generate the preferred day time again based on settings
					if ( empty( $preferred_day_time ) && isset( $_POST['s_postcode'] ) ) {
						$shipping_postcode  = wc_clean( $_POST['s_postcode'] );
						$preferred_day_time = PR_DHL()->get_dhl_preferred_day_time( $shipping_postcode );

						// Store the new preferred day time in the session
						WC()->session->set( 'dhl_preferred_day_time', $preferred_day_time );
					}
				} else {
					// Default to an empty array if session or data is not available
					$preferred_day_time = [];
				}

			} catch ( Exception $e ) {
				// Default to empty arrays if an error occurs
				$dhl_settings       = [];
				$preferred_day_time = [];
			}


			$localize_data = array(
				'pluginUrl'     => PR_DHL_PLUGIN_DIR_URL,
				'logoUrl'       => PR_DHL_PLUGIN_DIR_URL . '/assets/img/dhl-official.png',
				'dhlSettings'   => $dhl_settings,
				'preferredDays' => isset( $preferred_day_time['preferred_day'] ) ? $preferred_day_time['preferred_day'] : [],
			);


			// Localize the editor script
			wp_localize_script(
				'pr-dhl-blocks-editor',
				'prDhlGlobals',
				$localize_data
			);

			// Localize the frontend script
			wp_localize_script(
				'pr-dhl-blocks-frontend',
				'prDhlGlobals',
				$localize_data
			);
		}


	}

endif;

