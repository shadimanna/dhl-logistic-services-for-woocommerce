<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Settings')) :

class DHLPWC_Model_Service_Settings extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_api_account()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();
        return $shipping_methods['dhlpwc']->settings['account_id'];
    }

    public function get_api_organization()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();
        return $shipping_methods['dhlpwc']->settings['organization_id'];
    }

    public function get_api_user()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();
        return $shipping_methods['dhlpwc']->settings['user_id'];
    }

    public function get_api_key()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();
        return $shipping_methods['dhlpwc']->settings['key'];
    }

    public function country_is_set()
    {
        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        return $logic->check_application_country();
    }

    public function plugin_is_enabled()
    {
        $logic = DHLPWC_Model_Logic_Access_Control::instance();
        return $logic->check_enabled();
    }

    public function get_default_address()
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        return new DHLPWC_Model_Meta_Address(array(
            'first_name' => $shipping_methods['dhlpwc']->settings['first_name'],
            'last_name'  => $shipping_methods['dhlpwc']->settings['last_name'],
            'company'    => $shipping_methods['dhlpwc']->settings['company'],

            'country'    => $shipping_methods['dhlpwc']->settings['country'],
            'postcode'   => $shipping_methods['dhlpwc']->settings['postcode'],
            'city'       => $shipping_methods['dhlpwc']->settings['city'],
            'street'     => $shipping_methods['dhlpwc']->settings['street'],
            'number'     => $shipping_methods['dhlpwc']->settings['number'],

            'email'      => $shipping_methods['dhlpwc']->settings['email'],
            'phone'      => $shipping_methods['dhlpwc']->settings['phone'],
        ));
    }

}

endif;
