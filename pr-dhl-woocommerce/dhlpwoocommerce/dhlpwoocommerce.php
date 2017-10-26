<?php
/*
Plugin Name: DHL Parcel for WooCommmerce (BETA)
Plugin URI: http://www.google.com
Text Domain: dhlpwc
Domain Path: /languages
Description: This is the official DHL Parcel for WooCommerce plugin, currently in BETA.
Author: Shin Ho
Version: 0.1.0-beta
*/

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC')) :

class DHLPWC
{

    public function __construct()
    {
        // Only load this plugin if WooCommerce is loaded
        if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
            add_action('init', array($this, 'init'));
        }
    }

    protected function load_textdomain()
    {
        load_plugin_textdomain('dhlpwc', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function init()
    {
        // Load text domain manually
        $this->load_textdomain();

        // Autoloader
        include_once('includes/class-dhlpwc-autoloader.php');

        // Set constants
        $this->define('DHLPWC_PLUGIN_DIR', plugin_dir_path(__FILE__));
        $this->define('DHLPWC_RELATIVE_PLUGIN_DIR', basename(dirname(__FILE__)));
        $this->define('DHLPWC_PLUGIN_URL', plugins_url('/', __FILE__));
        // TODO change to asset URL and template path (the only ones using these constants)

        // Load translation
        load_plugin_textdomain('dhlpwc', false, DHLPWC_RELATIVE_PLUGIN_DIR. '/languages' );

        // Load controllers

        // This controller will not be encapsulated in an availability check, due to it providing screens
        // necessary to enable the plugin and setting up the plugin.
        new DHLPWC_Controller_Settings();

        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_API)) {
            new DHLPWC_Controller_Admin_Order_Metabox();
            new DHLPWC_Controller_Admin_Order();

            new DHLPWC_Controller_Checkout();
            new DHLPWC_Controller_Checkout_Options();
        }
    }

    protected function define($name, $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

// Run immediately
$DHLPWC = new DHLPWC();

endif;
