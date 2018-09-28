<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Logic_Access_Control')) :

class DHLPWC_Model_Logic_Access_Control extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function check_enabled()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

        if (empty($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['user_id'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['key'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['account_id'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['organization_id'])) {
            return false;
        }

        return true;
    }

    public function check_default_shipping_address()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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

    public function check_open_label_links_external()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['open_label_links_external'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['open_label_links_external'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_track_trace_mail()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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

    public function check_debug()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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

    public function check_debug_external()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['enable_debug'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['enable_debug'] != 'yes') {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['debug_external_url'])) {
            return false;
        }

        if (empty($shipping_methods['dhlpwc']->settings['debug_external_url'])) {
            return false;
        }

        if (filter_var($shipping_methods['dhlpwc']->settings['debug_external_url'], FILTER_VALIDATE_URL) === false) {
            return false;
        }

        return $shipping_methods['dhlpwc']->settings['debug_external_url'];
    }

    public function check_debug_mail()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

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

    public function check_default_send_signature()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return false;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['check_default_send_signature'])) {
            return false;
        }

        if ($shipping_methods['dhlpwc']->settings['check_default_send_signature'] != 'yes') {
            return false;
        }

        return true;
    }

    public function check_parcelshop_enabled()
    {
        $cart = WC()->cart;
        if (!$cart) {
            return false;
        }

        $customer = $cart->get_customer();

        if (!$customer) {
            return false;
        }

        if (!$customer->get_shipping_country()) {
            return false;
        }

        $shipping_methods = WC_Shipping::instance()->load_shipping_methods(array(
            'destination' => array(
                'country'  => $customer->get_shipping_country(),
                'state'    => $customer->get_shipping_state(),
                'postcode' => $customer->get_shipping_postcode(),
            ),
        ));

        if (empty($shipping_methods)) {
            return false;
        }

        $continue = false;
        foreach($shipping_methods as $shipping_method) {
            if ($shipping_method->id === 'dhlpwc') {

                $continue = true;
                break;
            }
        }

        if (!$continue) {
            return false;
        }

        /** @var DHLPWC_Model_WooCommerce_Settings_Shipping_Method $shipping_method */
        if ($shipping_method->get_option('enable_option_parcelshop') !== 'yes') {
            return false;
        }

        return true;
    }

}

endif;

