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

            if (defined('DHLPWC_PLUGIN_BASENAME')) {
                add_filter('plugin_action_links_' . DHLPWC_PLUGIN_BASENAME, array($this, 'add_settings_link'), 10, 1);
            }
            if (defined('PR_DHL_PLUGIN_BASENAME')) {
                add_filter('plugin_action_links_' . PR_DHL_PLUGIN_BASENAME, array($this, 'add_settings_link'), 10, 1);
            }

            add_action('admin_notices', array($this, 'check_for_notices'));

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
                'test_connection_message' => __('Test connection and retrieve account data', 'dhlpwc'),
                'test_connection_loading_message' => __('Please wait...', 'dhlpwc'),
                'accounts_found_message' => __('Accounts found. Click to use.', 'dhlpwc'),
                'organization_found_message' => __('OrganizationID found. Click to use.', 'dhlpwc'),
            ));
        }
    }

    public function check_for_notices()
    {
        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        $service = DHLPWC_Model_Service_Settings::instance();

        // Check if the plugin is enabled, but not allowed to access the API
        if (!$access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_API) && $service->plugin_is_enabled()) {
            // Add general reminders
            $messages = array();
            if (!$service->country_is_set()) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('Country / State', 'woocommerce'), __('General', 'woocommerce'));
            }
            if (!empty($messages)) {
                $this->show_notice($messages, admin_url('admin.php?page=wc-settings&tab=general'));
            }

            // Add plugin reminders
            $messages = array();
            if (empty($service->get_api_user())) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('UserID', 'dhlpwc'), __('Account details', 'dhlpwc'));
            }
            if (empty($service->get_api_key())) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('Key', 'dhlpwc'), __('Account details', 'dhlpwc'));
            }
            if (empty($service->get_api_account())) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('AccountID', 'dhlpwc'), __('Account details', 'dhlpwc'));
            }
            if (empty($service->get_api_organization())) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('OrganizationID', 'dhlpwc'), __('Account details', 'dhlpwc'));
            }
            if (!empty($messages)) {
                $this->show_notice($messages, admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc'));
            }
        }
    }

    public function show_notice($messages, $admin_link = null)
    {
        $view = new DHLPWC_Template('admin.notice');
        $view->render(array(
            'messages'   => $messages,
            'admin_link' => $admin_link,
        ));
    }

    public function add_settings_link($links)
    {
        $action_links = array(
            'settings' => '<a href="' . admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc') . '" aria-label="' . esc_attr__('View DHL for WooCommerce settings', 'dhlpwc') . '">' . esc_html__('Settings', 'woocommerce') . '</a>',
        );

        return array_merge($action_links, $links);
    }

}

endif;
