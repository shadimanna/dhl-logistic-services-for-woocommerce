<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Settings')) :

class DHLPWC_Controller_Admin_Settings
{

    const NOTICE_TAG_PREFIX = 'dhlpwc_';

    const NOTICE_TAG_COUNTRY = 'country';
    const NOTICE_TAG_API_SETTINGS = 'api_settings';
    const NOTICE_TAG_PARCELSHOP = 'parcelshop';

    public function __construct()
    {
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'load_styles'));
            add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

            if (defined('DHLPWC_PLUGIN_BASENAME')) {
                add_filter('plugin_action_links_' . DHLPWC_PLUGIN_BASENAME, array($this, 'add_settings_link'), 10, 1);
            }

            // Also try to hook to collaboration plugin, whenever this class is loaded through that
            $collaboration_name = 'dhl-for-woocommerce/pr-dhl-woocommerce.php';
            add_filter('plugin_action_links_' . $collaboration_name, array($this, 'add_settings_link'), 10, 1);

            $service = DHLPWC_Model_Service_Access_Control::instance();
            if ($service->check(DHLPWC_Model_Service_Access_Control::ACCESS_SUBMENU_LINK)) {
                add_action('admin_menu', array($this, 'add_submenu_link'));
            }

            add_action('admin_notices', array($this, 'check_for_notices'));

            add_action('wp_ajax_dhlpwc_dismiss_admin_notice', array($this, 'dismiss_admin_notice'));
            add_action('wp_ajax_dhlpwc_test_connection', array($this, 'test_connection'));
            add_action('wp_ajax_dhlpwc_dynamic_option_settings', array($this, 'dynamic_option_settings'));
            add_action('wp_ajax_dhlpwc_test_bulk_printing', array($this, 'test_bulk_printing'));
        }
    }

    public function add_submenu_link()
    {
        add_submenu_page(
            'woocommerce',
            __('DHL for WooCommerce', 'dhlpwc'),
            __('DHL for WooCommerce', 'dhlpwc'),
            'manage_options',
            'dhlpwc-menu-link',
            array($this, 'forward_settings_location')
        );
    }

    public function forward_settings_location()
    {
        wp_safe_redirect(admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc'));
    }

    public function dismiss_admin_notice()
    {
        $notice_tag = sanitize_text_field($_POST['notice_tag']);

        $json_response = new DHLPWC_Model_Response_JSON();

        if (substr($notice_tag, 0, strlen(self::NOTICE_TAG_PREFIX)) !== self::NOTICE_TAG_PREFIX) {
            $json_response->set_error(__('Unknown tag', 'dhlpwc'));
            wp_send_json($json_response->to_array(), 403);
            return;
        }

        // Remove prefix
        $notice_tag = substr($notice_tag, strlen(self::NOTICE_TAG_PREFIX));
        $value = true;
        $time = 7 * DAY_IN_SECONDS; // These are important messages, but we don't want to be too obnoxious. Make these messages return per week.
        set_site_transient($notice_tag, $value, $time);

        // Send JSON response
        wp_send_json($json_response->to_array(), 200);
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

    public function test_bulk_printing()
    {
        $library = DHLPWC_Libraryloader::instance();
        $pdf_merger = $library->get_pdf_merger();

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'success' => $pdf_merger ? 'true' : 'false',
            'message' => $pdf_merger ? __('Activated bulk PDF printing', 'dhlpwc') : __('PDFMerger cannot be initiated. To use bulk printing, please check and resolve any conflicting third plugins causing this issue.', 'dhlpwc'),
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    public function load_styles()
    {
        if ($this->is_plugin_screen() || $this->is_shipping_zone_screen()) {
            wp_enqueue_style('dhlpwc-admin-order-style', DHLPWC_PLUGIN_URL . 'assets/css/dhlpwc.admin.css');
        }
    }

    public function load_scripts()
    {
        wp_enqueue_script( 'dhlpwc-admin-notices', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.notices.js', array('jquery'));

        if ($this->is_plugin_screen() || $this->is_shipping_zone_screen()) {
            wp_enqueue_script( 'dhlpwc-settings-action', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.settings.js', array('jquery'));
            wp_localize_script( 'dhlpwc-settings-action', 'dhlpwc_settings_object', array(
                'test_connection_message' => __('Test connection and retrieve account data', 'dhlpwc'),
                'test_connection_loading_message' => __('Please wait...', 'dhlpwc'),
                'accounts_found_message' => __('Accounts found. Click to use.', 'dhlpwc'),
                'organization_found_message' => __('OrganizationID found. Click to use.', 'dhlpwc'),
            ));
        }

        if ($this->is_plugin_screen()) {
            wp_enqueue_script( 'dhlpwc-settings-usabilla', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.usabilla.js');
        }
    }

    protected function is_plugin_screen()
    {
        $screen = get_current_screen();
        if ($screen->base !== 'woocommerce_page_wc-settings') {
            return false;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
            return false;
        }

        if (!isset($_GET['tab']) || $_GET['tab'] !== 'shipping') {
            return false;
        }

        if (!isset($_GET['section']) || $_GET['section'] !== 'dhlpwc') {
            return false;
        }

        return true;
    }

    protected function is_shipping_zone_screen()
    {
        $screen = get_current_screen();
        if ($screen->base !== 'woocommerce_page_wc-settings') {
            return false;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
            return false;
        }

        if (!isset($_GET['tab']) || $_GET['tab'] !== 'shipping') {
            return false;
        }

        if (!isset($_GET['zone_id'])) {
            return false;
        }

        return true;
    }

    public function check_for_notices()
    {
        $access_service = DHLPWC_Model_Service_Access_Control::instance();
        $service = DHLPWC_Model_Service_Settings::instance();

        $messages = array();

        // Check if the plugin is enabled, but not allowed to access the API
        if ($service->plugin_is_enabled() && !$access_service->check(DHLPWC_Model_Service_Access_Control::ACCESS_API)) {
            // Add general reminders
            if (!$service->country_is_set()) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('Country / State', 'woocommerce'), __('General', 'woocommerce'));
            }
            if (!empty($messages) && !get_site_transient(self::NOTICE_TAG_COUNTRY)) {
                $this->show_notice(self::NOTICE_TAG_COUNTRY, $messages, admin_url('admin.php?page=wc-settings&tab=general'));
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
            if (!empty($messages) && !get_site_transient(self::NOTICE_TAG_API_SETTINGS)) {
                $this->show_notice(self::NOTICE_TAG_API_SETTINGS, $messages, admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc'));
            }

        } else if ($service->plugin_is_enabled()) {
            // Maps key
            if (empty($service->get_maps_key())) {
                $messages[] = sprintf(__('Missing %1$s from %2$s', 'dhlpwc'), __('Google Maps key', 'dhlpwc'), __('Plugin settings', 'dhlpwc'));
                $messages[] = __('To continue using DHL ServicePoint and show a visual map to customers, please add a Google Maps API key. If left empty, the DHL ServicePoint map will stop displaying starting from October 30th 10:00 PM CEST', 'dhlpwc');
            }
            if (!empty($messages) && !get_site_transient(self::NOTICE_TAG_PARCELSHOP)) {
                $this->show_notice(self::NOTICE_TAG_PARCELSHOP, $messages, admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc'));
            }
        }
    }

    public function show_notice($notice_tag, $messages, $admin_link = null)
    {
        $view = new DHLPWC_Template('admin.notice');
        $view->render(array(
            'notice_tag' => self::NOTICE_TAG_PREFIX.$notice_tag,
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
