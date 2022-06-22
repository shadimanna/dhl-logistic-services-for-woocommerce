<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Controller_Admin_Migrate')) :

class DHLPWC_Controller_Admin_Migrate
{

    const NOTICE_TAG_MIGRATE = 'dhlpwc_migrate_notice';
    const NOTICE_TAG_MIGRATE_FOREVER = 'dhlpwc_migrate_notice_forever';

    public function __construct()
    {
        if (!is_admin()) {
            return;
        }

        $service = DHLPWC_Model_Service_Access_Control::instance();
        if (DHLPWC_IS_STANDALONE && !$service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_MIGRATE)) {
            return;
        }

        if (get_option(self::NOTICE_TAG_MIGRATE_FOREVER, null) && !$service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_MIGRATE)) {
            return;
        }

        add_action('admin_enqueue_scripts', array($this, 'load_scripts'));

        add_action('admin_notices', array($this, 'show_migrate_notice'));
        add_action('wp_ajax_dhlpwc_dismiss_migrate_notice', array($this, 'dismiss_notice'));
        add_action('wp_ajax_dhlpwc_dismiss_migrate_notice_forever', array($this, 'dismiss_notice_forever'));
    }

    public function show_migrate_notice()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if (get_site_transient(self::NOTICE_TAG_MIGRATE) && !$service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_MIGRATE)) {
            return;
        }

        if (
            $this->is_home_screen() ||
            $this->is_ordergrid_screen() ||
            $this->is_order_screen() ||
            $this->is_wc_settings_screen()
        ) {
            $view = new DHLPWC_Template('admin.migrate');
            $view->render(array(
                'priority' => $this->get_priority_level()
            ));
        }
    }

    public function dismiss_notice()
    {
        $json_response = new DHLPWC_Model_Response_JSON();

        $value = true;

        $priority = $this->get_priority_level();

        // Low level priority
        $time = 14 * DAY_IN_SECONDS;
        if ($priority === 1) {
            // High level priority
            $time = 13 * HOUR_IN_SECONDS;
        } else if ($priority === 0) {
            // Mid level priority
            $time = 2 * DAY_IN_SECONDS;
        }

        set_site_transient(self::NOTICE_TAG_MIGRATE, $value, $time);

        // Send JSON response
        wp_send_json($json_response->to_array(), 200);
    }

    public function dismiss_notice_forever()
    {
        update_option(self::NOTICE_TAG_MIGRATE_FOREVER, true);
        $json_response = new DHLPWC_Model_Response_JSON();
        wp_send_json($json_response->to_array(), 200);
    }

    protected function get_priority_level()
    {
        $service = DHLPWC_Model_Service_Access_Control::instance();
        if ($debug_level = $service->check(DHLPWC_Model_Service_Access_Control::ACCESS_DEBUG_MIGRATE)) {
            if ($debug_level === 'mid') {
                return 0;
            }
            if ($debug_level === 'high') {
                return 1;
            }
            return -1;
        }

        $current_timestamp = current_time( 'timestamp');

        $start_date = DateTime::createFromFormat('d-m-Y', '1-3-2022');
        $start_date->setTime(0, 0, 0);
        $start_date_timestamp = $start_date->getTimestamp();

        if ($current_timestamp <= $start_date_timestamp) {
            // Time calculation doesn't seem to work, return low level priority
            return -1;
        }

        $mid_date = DateTime::createFromFormat('d-m-Y', '1-5-2022');
        $mid_date->setTime(0, 0, 0);
        $mid_date_timestamp = $mid_date->getTimestamp();

        if ($current_timestamp <= $mid_date_timestamp) {
            // Mid level priority
            return 0;
        }

        $end_date = DateTime::createFromFormat('d-m-Y', '1-9-2022');
        $end_date->setTime(0, 0, 0);
        $end_date_timestamp = $end_date->getTimestamp();

        if ($current_timestamp <= $end_date_timestamp) {
            // High level priority
            return 1;
        }

        // Default low level priority
        return -1;

    }

    protected function is_home_screen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        if ($screen->base !== 'dashboard') {
            return false;
        }

        return true;
    }

    protected function is_ordergrid_screen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        if ($screen->base !== 'edit' || $screen->post_type !== 'shop_order') {
            return false;
        }

        return true;
    }

    protected function is_order_screen()
    {
        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();
        if (!isset($screen)) {
            return false;
        }

        if ($screen->base !== 'post' || $screen->post_type !== 'shop_order') {
            return false;
        }

        return true;
    }

    protected function is_wc_settings_screen()
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

        return true;
    }

    public function load_scripts()
    {
        if (
            $this->is_home_screen() ||
            $this->is_ordergrid_screen() ||
            $this->is_order_screen() ||
            $this->is_wc_settings_screen()
        ) {
            wp_enqueue_script('dhlpwc-admin-migrate', DHLPWC_PLUGIN_URL . 'assets/js/dhlpwc.migrate.js', array('jquery'));
        }
    }

}

endif;
