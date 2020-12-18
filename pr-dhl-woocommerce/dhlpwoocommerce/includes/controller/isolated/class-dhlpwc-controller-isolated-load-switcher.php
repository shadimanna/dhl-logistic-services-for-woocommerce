<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Isolated_Load_Switcher')) :

class DHLPWC_Controller_Isolated_Load_Switcher
{

    const SECTION_DPI = 'pr_dhl_dp';

    public function __construct()
    {
        add_filter('pr_shipping_dhl_bypass_load_plugin', array($this, 'load_alternative_plugin'), 10, 1);

        if (!is_admin()) {
            return;
        }

        add_action('admin_enqueue_scripts', array($this, 'load_scripts'));
        add_action('admin_notices', array($this, 'show_load_switcher'));

        add_action('wp_ajax_dhlpwc_load_switcher', array($this, 'load_switcher'));
        add_action('wp_ajax_dhlpwc_inject_switcher', array($this, 'inject_switcher'));
    }

    public function load_alternative_plugin()
    {
        $switch_loading = get_option('woocommerce_dhlpwc_switch_loading');
        return boolval($switch_loading);
    }

    public function load_switcher()
    {
        if (get_option('woocommerce_dhlpwc_switch_loading')) {
            delete_option('woocommerce_dhlpwc_switch_loading');
            $admin_link = admin_url('admin.php?page=wc-settings&tab=shipping&section=dhlpwc');
        } else {
            add_option('woocommerce_dhlpwc_switch_loading', true);
            $admin_link = apply_filters('dhlpwc_dpi_admin_link', admin_url('admin.php?page=wc-settings&tab=shipping&section='.apply_filters('dhlpwc_dpi_section_id', self::SECTION_DPI)));
        }

        // Send JSON response
        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array(
            'admin_link' => $admin_link,
        ));
        wp_send_json($json_response->to_array(), 200);
    }

    public function inject_switcher()
    {
        $message = wc_clean($_POST['message']);
        if (!$message) {
            return;
        }

        $view = new DHLPWC_Template('admin.load-switcher');
        $switcher_view = $view->render(array('message' => $message), false);

        $json_response = new DHLPWC_Model_Response_JSON();
        $json_response->set_data(array('view' => $switcher_view));
        wp_send_json($json_response->to_array(), 200);
    }

    public function show_load_switcher()
    {
        $message = $this->get_message();
        if (!$message) {
            return;
        }

        $view = new DHLPWC_Template('admin.load-switcher');
        $view->render(array('message' => $message));
    }

    public function load_scripts()
    {
        $message = $this->get_message();
        if (!$message) {
            return;
        }

        wp_enqueue_script( 'dhlpwc-load-switcher', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.admin.load-switcher.js');
        wp_localize_script( 'dhlpwc-load-switcher', 'dhlpwc_load_switcher_object', array(
            'message' => $message
        ));
    }

    protected function is_plugin_screen($parcel = null)
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        if ($screen->base !== 'woocommerce_page_wc-settings') {
            return false;
        }

        if (!isset($_GET['page']) || $_GET['page'] !== 'wc-settings') {
            return false;
        }

        if (!isset($_GET['tab']) || $_GET['tab'] !== 'shipping') {
            return false;
        }

        if (!isset($_GET['section']) || ($_GET['section'] !== 'dhlpwc' && $_GET['section'] !== apply_filters('dhlpwc_dpi_section_id', self::SECTION_DPI))) {
            return false;
        }

        if ($parcel === true && $_GET['section'] !== 'dhlpwc') {
            return false;
        }

        if ($parcel === false && $_GET['section'] !== apply_filters('dhlpwc_dpi_section_id', self::SECTION_DPI)) {
            return false;
        }

        return true;
    }

    protected function get_message()
    {
        if (!$this->is_plugin_screen()) {
            return null;
        }

        if ($this->is_plugin_screen(false) && !boolval(apply_filters('dhlpwc_dpi_is_configured', $this->check_dpi_configured()))) {
            // Show load switcher on DPI settings
            $message = __('Were you looking to use DHL Parcel instead? %sClick here to switch%s (this will turn off Deutsche Post International).', 'dhlpwc');
        } else if ($this->is_plugin_screen(true) && !$this->check_parcel_configured()) {
            // Show load switcher on Parcel settings
            $message = __('Were you looking to use Deutsche Post International instead? %sClick here to switch%s (this will turn off DHL Parcel).', 'dhlpwc');
        } else {
            return null;
        }

        return $message;
    }

    protected function check_dpi_configured()
    {
        $dpi_array = get_option('woocommerce_pr_dhl_dp_settings', array());

        if (!isset($dpi_array) || !is_array($dpi_array)) {
            return false;
        }

        if (empty($dpi_array['dhl_api_key']) || empty($dpi_array['dhl_api_secret'])) {
            return false;
        }

        return true;
    }

    protected function check_parcel_configured()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        return $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_API);
    }

}

endif;
