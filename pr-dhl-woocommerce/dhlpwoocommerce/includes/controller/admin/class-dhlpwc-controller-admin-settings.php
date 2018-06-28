<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Settings')) :

class DHLPWC_Controller_Admin_Settings
{

    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'load_styles'));
            add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

            add_action('wp_ajax_dhlpwc_test_connection', array($this, 'test_connection'));
        }
    }

    public function test_connection()
    {
        $user_id = isset($_POST['user_id']) ? wc_clean($_POST['user_id']) : null;
        $key = isset($_POST['key']) ? wc_clean($_POST['key']) : null;

        $connector = DHLPWC_Model_API_Connector::instance();
        $authentication = $connector->test_authenticate($user_id, $key);

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'success' => $authentication ? 'true' : 'false',
            'message' => $authentication ? __('Connection successful', 'dhlpwc') : __('Authentication failed', 'dhlpwc'),
            'info'    => $authentication,
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    public function load_styles()
    {
        $screen = get_current_screen();
        if ($screen->base == 'woocommerce_page_wc-settings') {
            wp_enqueue_style('dhlpwc-admin-order-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
        }
    }

    public function load_scripts()
    {
        $screen = get_current_screen();
        if ($screen->base == 'woocommerce_page_wc-settings') {
            wp_enqueue_script( 'dhlpwc-settings-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.settings.js', array('jquery'));
            wp_localize_script( 'dhlpwc-settings-action', 'dhlpwc_settings_object', array(
                'test_connection_message' => __('Test connection', 'dhlpwc'),
                'test_connection_loading_message' => __('Please wait...', 'dhlpwc'),
                'accounts_found_message' => __('Accounts found. Click to use.', 'dhlpwc'),
                'organization_found_message' => __('OrganizationID found. Click to use.', 'dhlpwc'),
            ));
        }
    }

}

endif;
