<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Access_Control')) :

class DHLPWC_Model_Logic_Access_Control extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function check_enabled()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_all'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_all'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_application_country()
    {
        $country_code = wc_get_base_location();

        if (!in_array($country_code['country'], array(
            'NL',
            'BE',
            'LU',
            'PT',
            'ES',
        ))) {
            return false;
        }

        return true;
    }

    public function check_account()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['user_id'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['key'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['account_id'])) {
            return false;
        }

        return true;
    }

    public function check_default_shipping_address()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['first_name'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['last_name'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['company'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['country'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['postcode'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['city'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['street'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['number'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['email'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['phone'])) {
            return false;
        }

        return true;

    }

    public function check_column_info()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_column_info'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_column_info'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_track_trace_mail()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_track_trace_mail'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_track_trace_mail'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_track_trace_component()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_track_trace_component'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_track_trace_component'] != 'yes') {
            return false;
        }

        return true;
    }

    protected function check_option($enable_option_string)
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_option_'.$enable_option_string])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_option_'.$enable_option_string] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_enable_free()
    {
        return $this->check_option('free');
    }

    public function check_enable_home()
    {
        return $this->check_option('home');
    }

    public function check_enable_no_neighbour()
    {
        return $this->check_option('no_neighbour');
    }

    public function check_enable_evening()
    {
        return $this->check_option('evening');
    }

    public function check_enable_parcelshop()
    {
        return $this->check_option('parcelshop');
    }

    public function check_debug()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_debug'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_debug'] != 'yes') {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['debug_url'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['debug_url'])) {
            return false;
        }

        if (filter_var($shipping_methods['dhlpwc']->settings['debug_url'], FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return $shipping_methods['dhlpwc']->settings['debug_url'];
    }

    public function check_debug_mail()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_debug'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_debug'] != 'yes') {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_debug_mail'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_default_send_to_business()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['default_send_to_business'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['default_send_to_business'] != 'yes') {
            return false;
        }

        return true;
    }

}

endif;
