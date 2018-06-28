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

    public function get_price_free()
    {
        return $this->get_price('free');
    }

    public function get_price_home()
    {
        return $this->get_price('home');
    }

    public function get_price_no_neighbour()
    {
        return $this->get_price('no_neighbour');
    }

    public function get_price_evening()
    {
        return $this->get_price('evening');
    }

    public function get_price_parcelshop()
    {
        return $this->get_price('parcelshop');
    }

    protected function get_price($price_option_string)
    {
        $shipping_methods = WC_Shipping::instance()->load_shipping_methods();

        if (!isset($shipping_methods['dhlpwc'])) {
            return 0;
        }

        if (!isset($shipping_methods['dhlpwc']->settings['price_option_'.$price_option_string])) {
            return 0;
        }

        if (isset($shipping_methods['dhlpwc']->settings['price_tax_assistance'])) {
            $tax_rate = intval($shipping_methods['dhlpwc']->settings['price_tax_assistance']);
            if ($tax_rate > 0) {
                $price = $shipping_methods['dhlpwc']->settings['price_option_' . $price_option_string];
                return $price / ($tax_rate / 100 + 1);
            }
        }

        return $shipping_methods['dhlpwc']->settings['price_option_'.$price_option_string];
    }

}

endif;