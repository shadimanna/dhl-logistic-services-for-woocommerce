<?php


use Automattic\WooCommerce\Blocks\Integrations\IntegrationInterface;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for integrating with WooCommerce Blocks.
 *
 * @package PR_DHL\Checkout_Blocks
 * @category Shipping
 */
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
	 * When called, invokes any initialization/setup for the integration.
	 */
	public function initialize() {
		$this->register_scripts_and_styles();
		$this->register_ajax_actions();
		$this->localize_scripts();
	}

	/**
	 * Registers scripts and styles for both editor and frontend.
	 */
	private function register_scripts_and_styles() {
		// Register main integration script and style
		$this->register_main_integration();

		// Register block editor scripts
		$this->register_block_script(
			'pr-dhl-preferred-services-editor',
			'/build/pr-dhl-preferred-services.js',
			'/build/pr-dhl-preferred-services.asset.php'
		);

		// Register frontend scripts for blocks
		$this->register_frontend_script(
			'pr-dhl-preferred-services-frontend',
			'/build/pr-dhl-preferred-services-frontend.js',
			'/build/pr-dhl-preferred-services-frontend.asset.php'
		);

		// Register parcel finder editor scripts
		$this->register_block_script(
			'pr-dhl-parcel-finder-editor',
			'/build/pr-dhl-parcel-finder.js',
			'/build/pr-dhl-parcel-finder.asset.php'
		);

		// Register frontend scripts for parcel finder
		$this->register_frontend_script(
			'pr-dhl-parcel-finder-frontend',
			'/build/pr-dhl-parcel-finder-frontend.js',
			'/build/pr-dhl-parcel-finder-frontend.asset.php'
		);


		// Register block styles
		$this->register_styles();
	}

	/**
	 * Registers the main JS and CSS files required for the integration.
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
				'version'      => $this->get_file_version( $script_path ),
			];

		wp_enqueue_style(
			'pr-dhl-preferred-services-integration',
			$style_url,
			[],
			$this->get_file_version( $style_path )
		);

		wp_register_script(
			'pr-dhl-preferred-services-integration',
			$script_url,
			$script_asset['dependencies'],
			$script_asset['version'],
			true
		);

		wp_set_script_translations(
			'pr-dhl-preferred-services-integration',
			'dhl-for-woocommerce',
			PR_DHL_PLUGIN_DIR_PATH . '/languages'
		);
	}

	/**
	 * Registers block styles for the block editor.
	 */
	private function register_styles() {
		$block_style_path = '/build/style-index.css';
		$block_style_url  = PR_DHL_PLUGIN_DIR_URL . $block_style_path;

		wp_enqueue_style(
			'pr-dhl-blocks-style',
			$block_style_url,
			[],
			$this->get_file_version( $block_style_path )
		);
	}

	/**
	 * Helper method to register block editor scripts.
	 *
	 * @param string $handle      Script handle name.
	 * @param string $script_path Path to the JS file.
	 * @param string $asset_path  Path to the asset file.
	 */
	private function register_block_script( $handle, $script_path, $asset_path ) {
		$script_url = PR_DHL_PLUGIN_DIR_URL . $script_path;
		$asset_file = PR_DHL_PLUGIN_DIR_PATH . $asset_path;
		$asset      = file_exists( $asset_file ) ? require $asset_file : [
			'dependencies' => [],
			'version'      => $this->get_file_version( $script_path ),
		];

		wp_register_script(
			$handle,
			$script_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations(
			$handle,
			'dhl-for-woocommerce',
			PR_DHL_PLUGIN_DIR_PATH . '/languages'
		);
	}

	/**
	 * Helper method to register frontend scripts.
	 *
	 * @param string $handle      Script handle name.
	 * @param string $script_path Path to the JS file.
	 * @param string $asset_path  Path to the asset file.
	 */
	private function register_frontend_script( $handle, $script_path, $asset_path ) {
		$script_url = PR_DHL_PLUGIN_DIR_URL . $script_path;
		$asset_file = PR_DHL_PLUGIN_DIR_PATH . $asset_path;
		$asset      = file_exists( $asset_file ) ? require $asset_file : [
			'dependencies' => [],
			'version'      => $this->get_file_version( $script_path ),
		];
		// Enqueue Google Maps
		$dhl_settings = PR_DHL()->get_shipping_dhl_settings();


		wp_enqueue_script(
			$handle,
			$script_url,
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_set_script_translations(
			$handle,
			'dhl-for-woocommerce',
			PR_DHL_PLUGIN_DIR_PATH . '/languages'
		);
	}

	/**
	 * Registers AJAX actions.
	 */
	private function register_ajax_actions() {
		add_action( 'wp_ajax_pr_dhl_set_checkout_post_data', array( $this, 'handle_set_checkout_post_data' ) );
		add_action( 'wp_ajax_nopriv_pr_dhl_set_checkout_post_data', array( $this, 'handle_set_checkout_post_data' ) );
		add_action( 'wp_ajax_pr_dhl_get_preferred_days', array( $this, 'handle_get_preferred_days' ) );
		add_action( 'wp_ajax_nopriv_pr_dhl_get_preferred_days', array( $this, 'handle_get_preferred_days' ) );
	}



	/**
	 * Returns an array of script handles to enqueue in the frontend context.
	 *
	 * @return string[]
	 */
	public function get_script_handles() {
		return [
			'pr-dhl-preferred-services-integration',
			'pr-dhl-preferred-services-frontend',
			'pr-dhl-parcel-finder-frontend',
		];
	}

	/**
	 * Returns an array of script handles to enqueue in the editor context.
	 *
	 * @return string[]
	 */
	public function get_editor_script_handles() {
		return [
			'pr-dhl-preferred-services-integration',
			'pr-dhl-preferred-services-editor',
			'pr-dhl-parcel-finder-editor',
		];
	}

	/**
	 * An array of key, value pairs of data made available to the block on the client side.
	 *
	 * @return array
	 */
	public function get_script_data() {
		return [
			'pr-dhl-active' => true,
		];
	}

	/**
	 * Localizes scripts with necessary data.
	 */
	private function localize_scripts() {
		// Fetch the shipping settings.
		$dhl_settings = $this->get_dhl_settings();

		$localize_data = array(
			'pluginUrl'                     => PR_DHL_PLUGIN_DIR_URL,
			'dhlSettings'                   => $dhl_settings,
			'ajax_url'                      => admin_url( 'admin-ajax.php' ),
			'nonce'                         => wp_create_nonce( 'pr_dhl_nonce' ),
			'parcel_nonce'                  => wp_create_nonce( 'dhl_parcelfinder' ),
			'DHL_ENGLISH_REGISTRATION_LINK' => DHL_ENGLISH_REGISTRATION_LINK,
			'DHL_GERMAN_REGISTRATION_LINK'  => DHL_GERMAN_REGISTRATION_LINK,
			'locale'                        => get_locale(),
		);

		// Localize the editor script.
		wp_localize_script(
			'pr-dhl-preferred-services-editor',
			'prDhlGlobals',
			$localize_data
		);


		// Localize the frontend scripts.
		wp_localize_script(
			'pr-dhl-preferred-services-frontend',
			'prDhlGlobals',
			$localize_data
		);

	}

	/**
	 * Get the DHL settings that are relevant to the blocks.
	 *
	 * @return array
	 * @throws Exception
	 */
	private function get_dhl_settings(): array {
		$dhl_settings = PR_DHL()->get_shipping_dhl_settings();

		$settings_keys = array(
			'dhl_display_packstation',
			'dhl_display_parcelshop',
			'dhl_display_post_office',
			'dhl_preferred_day',
			'dhl_preferred_location',
			'dhl_preferred_neighbour',
			'dhl_preferred_day_cost',
			'dhl_display_google_maps',
		);

		$filtered_settings = array();

		foreach ( $settings_keys as $key ) {
			$value = $dhl_settings[ $key ] ?? false;

			if ( 'yes' === $value ) {
				$value = true;
			} elseif ( ! is_numeric( $value ) ) {
				$value = false;
			}

			// Remove 'dhl_' prefix from the key.
			$clean_key                       = str_replace( 'dhl_', '', $key );
			$filtered_settings[ $clean_key ] = $value;
		}

		return $filtered_settings;
	}

	/**
	 * Get the file modified time as a cache buster if we're in dev mode.
	 *
	 * @param string $file Local path to the file.
	 *
	 * @return string The cache buster value to use for the given file.
	 */
	protected function get_file_version( $file ) {
		$file_path = PR_DHL_PLUGIN_DIR_PATH . $file;
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG && file_exists( $file_path ) ) {
			return filemtime( $file_path );
		}

		return PR_DHL_VERSION;
	}


	public function handle_set_checkout_post_data() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pr_dhl_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 400 );
			wp_die();
		}

		// Check if data is provided
		if ( ! isset( $_POST['data'] ) || ! is_array( $_POST['data'] ) ) {
			wp_send_json_error( array( 'message' => 'No data provided.' ), 400 );
			wp_die();
		}

		// Sanitize data
		$sanitized_data = array_map( 'sanitize_text_field', wp_unslash( $_POST['data'] ) );

		// Store data in WooCommerce session
		WC()->session->set( 'pr_dhl_checkout_post_data', $sanitized_data );

		wp_send_json_success( array( 'message' => 'Data saved successfully.' ), 200 );
		wp_die();
	}

	public function handle_get_preferred_days() {
		// Verify nonce
		if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'pr_dhl_nonce' ) ) {
			wp_send_json_error( array( 'message' => 'Invalid nonce' ), 400 );
			wp_die();
		}

		// Retrieve post_data from WooCommerce session
		$order_data = WC()->session->get( 'pr_dhl_checkout_post_data' );
		if ( empty( $order_data ) || ! is_array( $order_data ) ) {
			wp_send_json_error( array( 'message' => 'No checkout data found.' ), 400 );
			wp_die();
		}

		// Use the order_data to get the preferred days
		$postcode = isset( $order_data['shipping_postcode'] ) ? $order_data['shipping_postcode'] : '';
		$shipping_country = isset( $order_data['shipping_country'] ) ? $order_data['shipping_country'] : '';

		if ( empty( $postcode ) ) {
			wp_send_json_error( array( 'message' => 'Postcode not found in checkout data.' ), 400 );
			wp_die();
		}

		try {
			$front_end_packet = new PR_DHL_Front_End_Paket();

			// Call the validate_extra_services_available function with parameters
			$display_preferred = $front_end_packet->validate_extra_services_available(
				true,
				$shipping_country);

			if ( ! $display_preferred ) {
				wp_send_json_error( array( 'message' => 'Preferred services not available.' ), 400 );
				wp_die();
			}

			$preferred_day_time = PR_DHL()->get_dhl_preferred_day_time( $postcode );
			wp_send_json_success( array( 'preferredDays' => $preferred_day_time['preferred_day'] ), 200 );
		} catch ( Exception $e ) {
			wp_send_json_error( array( 'message' => $e->getMessage() ), 500 );
		}

		wp_die();
	}
}
