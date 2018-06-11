<?php
/**
 * Plugin Name: DHL for WooCommerce
 * Plugin URI: https://github.com/shadimanna/dhl-logistic-services-for-woocommerce
 * Description: WooCommerce integration for DHL eCommerce, DHL Paket and DHL Parcel Europe (Benelux and Iberia)
 * Author: DHL
 * Author URI: http://dhl.com/woocommerce
 * Version: 1.0.9
 * WC requires at least: 2.6.14
 * WC tested up to: 3.3
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'PR_DHL_WC' ) ) :

class PR_DHL_WC {

	private $version = "1.0.9";

	/**
	 * Instance to call certain functions globally within the plugin
	 *
	 * @var PR_DHL_WC
	 */
	protected static $_instance = null;
	
	/**
	 * DHL Shipping Order for label and tracking.
	 *
	 * @var PR_DHL_WC_Order
	 */
	public $shipping_dhl_order = null;

	/**
	 * DHL Shipping Front-end for DHL Paket
	 *
	 * @var PR_DHL_Paket_Front_End
	 */
	protected $shipping_dhl_frontend = null;
	
	/**
	 * DHL Shipping Order for label and tracking.
	 *
	 * @var PR_DHL_WC__Product
	 */
	protected $shipping_dhl_product = null;

	/**
	 * DHL Shipping Order for label and tracking.
	 *
	 * @var PR_DHL_Logger
	 */
	protected $logger = null;

	private $payment_gateway_titles = array();

	protected $base_country_code = '';

	// 'LI', 'CH', 'NO'
	protected $eu_iso2 = array( 'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'RO', 'SI', 'SK', 'ES', 'SE', 'GB');

	// These are all considered domestic by DHL
	protected $us_territories = array( 'US', 'GU', 'AS', 'PR', 'UM', 'VI' );
		
	/**
	* Construct the plugin.
	*/
	public function __construct() {
		// add_action( 'init', array( $this, 'init' ) );
		// add_action( 'plugins_loaded', array( $this, 'init' ) );
        add_action( 'init', array( $this, 'load_plugin' ), 0 );
    }

	/**
	 * Main WooCommerce Shipping DHL Instance.
	 *
	 * Ensures only one instance is loaded or can be loaded.
	 *
	 * @static
	 * @see PR_DHL()
	 * @return PR DHL - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Define WC Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir();

		// Path related defines
		$this->define( 'PR_DHL_PLUGIN_FILE', __FILE__ );
		$this->define( 'PR_DHL_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		$this->define( 'PR_DHL_PLUGIN_DIR_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
		$this->define( 'PR_DHL_PLUGIN_DIR_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );

		$this->define( 'PR_DHL_VERSION', $this->version );

		$this->define( 'PR_DHL_LOG_DIR', $upload_dir['basedir'] . '/wc-logs/' );

		// DHL specific URLs
		$this->define( 'PR_DHL_BUTTON_TEST_CONNECTION', __( 'Test Connection', 'pr-shipping-dhl' ) );

		// DHL eCommerce
		$this->define( 'PR_DHL_REST_AUTH_URL', 'https://api.dhlecommerce.com' );
		$this->define( 'PR_DHL_REST_AUTH_URL_QA', 'https://api-qa.dhlecommerce.com' );
		$this->define( 'PR_DHL_ECOMM_TRACKING_URL', 'https://webtrack.dhlglobalmail.com/?trackingnumber=' );

		// DHL Paket
		$this->define( 'PR_DHL_CIG_USR', 'dhl_woocommerce_plugin_1' );
		$this->define( 'PR_DHL_CIG_PWD', 'rlUxXgDR59Qk9cpnAUx2qNxozYvA6T' );
		$this->define( 'PR_DHL_CIG_AUTH', 'https://cig.dhl.de/services/production/soap' );

		$this->define( 'PR_DHL_CIG_USR_QA', 'shadim' );
		$this->define( 'PR_DHL_CIG_PWD_QA', 'm6jvtj{U)zH;\']' );
		$this->define( 'PR_DHL_CIG_AUTH_QA', 'https://cig.dhl.de/services/sandbox/soap' );

		$this->define( 'PR_DHL_PAKET_TRACKING_URL', 'https://nolp.dhl.de/nextt-online-public/report_popup.jsp?idc=' );
	}
	
	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		// Auto loader class
		include_once( 'includes/class-pr-dhl-autoloader.php' );
		// Load abstract classes
		include_once( 'includes/abstract-pr-dhl-wc-order.php' );
		include_once( 'includes/abstract-pr-dhl-wc-product.php' );
	}

	/**
	* Determine which plugin to load.
	*/
	public function load_plugin() {
		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Shipping_Method' ) ) {			
			$this->base_country_code = $this->get_base_country();

			// If NL selected, load DHL Parcel plugin.
			$dhl_parcel_countries = array( 'NL', 'BE', 'LU' );

			if ( in_array( $this->base_country_code, $dhl_parcel_countries ) ) {
				include( 'dhlpwoocommerce/dhlpwoocommerce.php' );
			} else {
                $this->define_constants();
                $this->includes();
			    $this->init_hooks();
			}

		} else {
			// Throw an admin error informing the user this plugin needs WooCommerce to function
			add_action( 'admin_notices', array( $this, 'notice_wc_required' ) );
		}

	}

    /**
     * Initialize the plugin.
     */
    public function init() {
        add_action( 'admin_notices', array( $this, 'environment_check' ) );

        $this->get_pr_dhl_wc_product();
        $this->get_pr_dhl_wc_order();
    }

    public function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 1 );
        add_action( 'init', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'set_payment_gateways' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'dhl_theme_enqueue_styles'), 20 );

        add_action( 'woocommerce_shipping_init', array( $this, 'includes' ) );
        add_filter( 'woocommerce_shipping_methods', array( $this, 'add_shipping_method' ) );
        // Test connection
        add_action( 'wp_ajax_test_dhl_connection', array( $this, 'test_dhl_connection_callback' ) );
    }
	
	public function get_pr_dhl_wc_order() {
		if ( ! isset( $this->shipping_dhl_order ) ){
			try {
				$dhl_obj = $this->get_dhl_factory();
				
				if( $dhl_obj->is_dhl_paket() ) {
					$this->shipping_dhl_order = new PR_DHL_WC_Order_Paket();
					$this->shipping_dhl_frontend = new PR_DHL_Front_End_Paket();
				} elseif( $dhl_obj->is_dhl_ecomm() ) {
					$this->shipping_dhl_order = new PR_DHL_WC_Order_Ecomm();
				}

				$dhl_obj_express = $this->get_dhl_factory( true );

				$this->shipping_dhl_order_express = new PR_DHL_WC_Order_Express();
				
			} catch (Exception $e) {
				add_action( 'admin_notices', array( $this, 'environment_check' ) );
			}
		}

		return $this->shipping_dhl_order;
	}

	public function get_pr_dhl_wc_product() {
		if ( ! isset( $this->shipping_dhl_product ) ){
			try {
				$dhl_obj = $this->get_dhl_factory();
				
				if( $dhl_obj->is_dhl_paket() ) {
					$this->shipping_dhl_product = new PR_DHL_WC_Product_Paket();
				} elseif( $dhl_obj->is_dhl_ecomm() ) {
					$this->shipping_dhl_product = new PR_DHL_WC_Product_Ecomm();
				}
				
			} catch (Exception $e) {
				add_action( 'admin_notices', array( $this, 'environment_check' ) );
			}
		}

		return $this->shipping_dhl_product;
	}

	/**
	 * Localisation
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'pr-shipping-dhl', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );
	}

	public function dhl_theme_enqueue_styles() {
		wp_enqueue_style( 'wc-shipment-dhl-label-css', PR_DHL_PLUGIN_DIR_URL . '/assets/css/pr-dhl-admin.css' );
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	/*
	public function include_shipping() {
		// Auto loader class
		include_once( 'includes/class-pr-dhl-ecomm-wc-method.php' );
	}*/

	/**
	 * Define constant if not already set.
	 *
	 * @param  string $name
	 * @param  string|bool $value
	 */
	public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}
	
	/**
	 * Add a new integration to WooCommerce.
	 */
	public function add_shipping_method( $shipping_method ) {
		// Check country somehow
		try {
			$dhl_obj = $this->get_dhl_factory();
			
			if( $dhl_obj->is_dhl_paket() ) {
				$pr_dhl_ship_meth = 'PR_DHL_WC_Method_Paket';
				$shipping_method['pr_dhl_paket'] = $pr_dhl_ship_meth;
			} elseif( $dhl_obj->is_dhl_ecomm() ) {
				$pr_dhl_ship_meth = 'PR_DHL_WC_Method_Ecomm';
				$shipping_method['pr_dhl_ecomm'] = $pr_dhl_ship_meth;
			}

			$shipping_method['pr_dhl_express'] = 'PR_DHL_WC_Method_Express';
		} catch (Exception $e) {
			// do nothing
		}

		return $shipping_method;
	}

	/**
	 * Admin error notifying user that WC is required
	 */
	public function notice_wc_required() {
	?>
		<div class="error">
			<p><?php _e( 'WooCommerce DHL Integration requires WooCommerce to be installed and activated!', 'pr-shipping-dhl' ); ?></p>
		</div>
	<?php
	}

	/**
	 * environment_check function.
	 */
	public function environment_check() {
		// Try to get the DHL object...if exception if thrown display to user, mainly to check country support.
		try {
			$this->get_dhl_factory();
		} catch (Exception $e) {
			echo '<div class="error"><p>' . $e->getMessage() . '</p></div>';
		}
	}

	public function get_base_country() {
		$country_code = wc_get_base_location();
		return $country_code['country'];
	}

	/**
	 * Create a DHL object from the factory based on country.
	 */
	public function get_dhl_factory( $is_express = false ) {

		// $this->base_country_code = $this->get_base_country();
		// $shipping_dhl_settings = $this->get_shipping_dhl_settings();
		// $client_id = isset( $shipping_dhl_settings['dhl_api_key'] ) ? $shipping_dhl_settings['dhl_api_key'] : '';
		// $client_secret = isset( $shipping_dhl_settings['dhl_api_secret'] ) ? $shipping_dhl_settings['dhl_api_secret'] : '';
		
		try {	

			if( $is_express ) {
				$dhl_obj = PR_DHL_API_Controller_Factory::make_dhl_express( $this->base_country_code );		
			} else {
				$dhl_obj = PR_DHL_API_Controller_Factory::make_dhl( $this->base_country_code );		
			}
		} catch (Exception $e) {
			throw $e;
		}

		return $dhl_obj;
	}

	public function get_api_url() {

		try {

			$dhl_obj = $this->get_dhl_factory();
			$shipping_dhl_settings = $this->get_shipping_dhl_settings();
			$dhl_sandbox = isset( $shipping_dhl_settings['dhl_sandbox'] ) ? $shipping_dhl_settings['dhl_sandbox'] : '';

			if ( $dhl_sandbox == 'yes' ) {
			
				if( $dhl_obj->is_dhl_paket() ) {
					$api_cred['user'] = PR_DHL_CIG_USR_QA;
					$api_cred['password'] = PR_DHL_CIG_PWD_QA;
					$api_cred['auth_url'] = PR_DHL_CIG_AUTH_QA;

					return $api_cred;
				} elseif( $dhl_obj->is_dhl_ecomm() ) {
					return PR_DHL_REST_AUTH_URL_QA;
				}

			} else {

				if( $dhl_obj->is_dhl_paket() ) {
					$api_cred['user'] = PR_DHL_CIG_USR;
					$api_cred['password'] = PR_DHL_CIG_PWD;
					$api_cred['auth_url'] = PR_DHL_CIG_AUTH;

					return $api_cred;
				} elseif( $dhl_obj->is_dhl_ecomm() ) {
					return PR_DHL_REST_AUTH_URL;
				}
			}
			
		} catch (Exception $e) {
			throw new Exception('Cannot get DHL api credentials!');			
		}
	}

	public function get_shipping_dhl_settings( $is_express = false ) {
		$dhl_settings = array();

		try {

			$dhl_obj = $this->get_dhl_factory( $is_express );

			if ( $is_express ) {			
				$dhl_settings = get_option('woocommerce_pr_dhl_express_settings');
			} else {
				if( $dhl_obj->is_dhl_paket() ) {
					$dhl_settings = get_option('woocommerce_pr_dhl_paket_settings');
				} elseif( $dhl_obj->is_dhl_ecomm() ) {
					$dhl_settings = get_option('woocommerce_pr_dhl_ecomm_settings');
				}
			}

		} catch (Exception $e) {
			throw $e;
		}

		return $dhl_settings;
	}

	public function test_dhl_connection_callback() {
		check_ajax_referer( 'pr-dhl-test-con', 'test_con_nonce' );
		try {

			$shipping_dhl_settings = $this->get_shipping_dhl_settings();
			
			$dhl_obj = $this->get_dhl_factory();

			if( $dhl_obj->is_dhl_paket() ) {
				$api_user = $shipping_dhl_settings['dhl_api_user']; 
				$api_pwd = $shipping_dhl_settings['dhl_api_pwd'];
			} elseif( $dhl_obj->is_dhl_ecomm() ) {
				$api_user = $shipping_dhl_settings['dhl_api_key']; 
				$api_pwd = $shipping_dhl_settings['dhl_api_secret'];
			} else {
				throw new Exception( __('Country not supported', 'pr-shipping-dhl') );
				
			}

			$connection = $dhl_obj->dhl_test_connection( $api_user, $api_pwd );
				
			$connection_msg = __('Connection Successful!', 'pr-shipping-dhl');
			$this->log_msg( $connection_msg );

			wp_send_json( array( 
				'connection_success' 	=> $connection_msg,
				'button_txt'			=> PR_DHL_BUTTON_TEST_CONNECTION
				) );

		} catch (Exception $e) {
			$this->log_msg($e->getMessage());

			wp_send_json( array( 
				'connection_error' => sprintf( __('Connected Failed: %s Make sure to save the settings before testing the connection. ', 'pr-shipping-dhl'), $e->getMessage() ),
				'button_txt'			=> PR_DHL_BUTTON_TEST_CONNECTION
				 ) );
		}

		wp_die();
	}

	public function log_msg( $msg )	{

		try {
			$shipping_dhl_settings = $this->get_shipping_dhl_settings();
			$dhl_debug = isset( $shipping_dhl_settings['dhl_debug'] ) ? $shipping_dhl_settings['dhl_debug'] : 'yes';
			
			if( ! $this->logger ) {
				$this->logger = new PR_DHL_Logger( $dhl_debug );
			}

			$this->logger->write( $msg );
			
		} catch (Exception $e) {
			// do nothing
		}
	}

	public function get_log_url( )	{

		try {
			$shipping_dhl_settings = $this->get_shipping_dhl_settings();
			$dhl_debug = isset( $shipping_dhl_settings['dhl_debug'] ) ? $shipping_dhl_settings['dhl_debug'] : 'yes';
			
			if( ! $this->logger ) {
				$this->logger = new PR_DHL_Logger( $dhl_debug );
			}
			
			return $this->logger->get_log_url( );
			
		} catch (Exception $e) {
			throw $e;
		}
	}

	public function generate_barcode( $text, $size = 60 ) {

		if ( empty( $text ) ) {
			return '';
		}

		ob_start();
		echo '<img src="'.plugin_dir_url(__FILE__).'lib/barcode.php?text='.$text.'&size='.$size.'" alt="barcode"/>';
		$view = ob_get_clean();
	    return $view;
	}

	public function get_dhl_preferred_days() {

		try {

		  $shipping_dhl_settings = PR_DHL()->get_shipping_dhl_settings();
		  $dhl_obj = PR_DHL()->get_dhl_factory();

		} catch (Exception $e) {
		    return;
		}

		if( ! $dhl_obj->is_dhl_paket() ) {
		  return;
		}

		$exclusion_work_day = array( );
		$work_days = array(
		            'Mon' => __('mon', 'pr-shipping-dhl'), 
		            'Tue' => __('tue', 'pr-shipping-dhl'), 
		            'Wed' => __('wed', 'pr-shipping-dhl'),
		            'Thu' => __('thu', 'pr-shipping-dhl'),
		            'Fri' => __('fri', 'pr-shipping-dhl'),
		            'Sat' => __('sat', 'pr-shipping-dhl') );

		foreach ($work_days as $key => $value) {
			$exclusion_day = 'dhl_preferred_exclusion_' . $value;

			if( isset($shipping_dhl_settings[ $exclusion_day ]) && $shipping_dhl_settings[ $exclusion_day ] == 'yes' ) {
			  $exclusion_work_day[ $key ] = $value;
			}
		}

		$cutoff_time = '12';
		if( ! empty( $shipping_dhl_settings[ 'dhl_preferred_day_cutoff' ] ) ) {
			$cutoff_time = $shipping_dhl_settings[ 'dhl_preferred_day_cutoff' ];
		}

		return $dhl_obj->get_dhl_preferred_days( $cutoff_time, $exclusion_work_day );
	}

	public function set_payment_gateways() {
		$wc_payment_gateways = WC()->payment_gateways()->payment_gateways();

		foreach ($wc_payment_gateways as $key => $gateway) {
			$this->payment_gateway_titles[ $key ] = $gateway->get_method_title();
		}
	}

	public function get_payment_gateways( ) {
		return $this->payment_gateway_titles;
	}

	public function is_shipping_domestic( $country_receiver ) {   	 
		// $this->base_country_code = PR_DHL()->get_base_country();

		// If base is US territory
		if( in_array( $this->base_country_code, $this->us_territories ) ) {
			
			// ...and destination is US territory, then it is "domestic"
			if( in_array( $country_receiver, $this->us_territories ) ) {
				return true;
			} else {
				return false;
			}

		} elseif( $country_receiver == $this->base_country_code ) {
			return true;
		} else {
			return false;
		}
	}

	public function is_crossborder_shipment( $country_receiver ) {
		
		if ($this->is_shipping_domestic( $country_receiver )) {
			return false;
		}

		if ( in_array( $this->base_country_code, $this->eu_iso2 ) ) {
			if ( in_array( $country_receiver, $this->eu_iso2 ) ) {
				return false;
			} else {
				return true;
			}
		} else {
			return true;
		}
	}

}

endif;

function PR_DHL() {
	return PR_DHL_WC::instance();
}

$PR_DHL_WC = PR_DHL();