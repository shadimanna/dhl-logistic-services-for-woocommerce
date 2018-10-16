<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Settings')) :

class DHLPWC_Model_Service_Settings extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $temporary_key = 'AIzaSyAV9qJVXDBnVHWwU01bjHO3wJCUxffYZyw';

    public function get_maps_key()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (empty($shipping_methods['dhlpwc']->settings['google_maps_key'])) {
            return !empty($this->temporary_key) ? $this->temporary_key : null;
        }
        return $shipping_methods['dhlpwc']->settings['google_maps_key'];
    }

    public function get_api_account()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (!isset($shipping_methods['dhlpwc']->settings['account_id'])) {
            return null;
        }
        return $shipping_methods['dhlpwc']->settings['account_id'];
    }

    public function get_api_organization()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (!isset($shipping_methods['dhlpwc']->settings['organization_id'])) {
            return null;
        }
        return $shipping_methods['dhlpwc']->settings['organization_id'];
    }

    public function get_api_user()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (!isset($shipping_methods['dhlpwc']->settings['user_id'])) {
            return null;
        }
        return $shipping_methods['dhlpwc']->settings['user_id'];
    }

    public function get_api_key()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (!isset($shipping_methods['dhlpwc']->settings['key'])) {
            return null;
        }
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

    public function get_default_address($prefix = null)
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();

        return new DHLPWC_Model_Meta_Address(array(
            'first_name' => $shipping_methods['dhlpwc']->settings[$prefix.'first_name'],
            'last_name'  => $shipping_methods['dhlpwc']->settings[$prefix.'last_name'],
            'company'    => $shipping_methods['dhlpwc']->settings[$prefix.'company'],

            'country'    => $prefix !== 'hide_sender_address_' ? $shipping_methods['dhlpwc']->settings[$prefix.'country'] : $shipping_methods['dhlpwc']->settings['country'],
            'postcode'   => $shipping_methods['dhlpwc']->settings[$prefix.'postcode'],
            'city'       => $shipping_methods['dhlpwc']->settings[$prefix.'city'],
            'street'     => $shipping_methods['dhlpwc']->settings[$prefix.'street'],
            'number'     => $shipping_methods['dhlpwc']->settings[$prefix.'number'],

            'email'      => $shipping_methods['dhlpwc']->settings[$prefix.'email'],
            'phone'      => $shipping_methods['dhlpwc']->settings[$prefix.'phone'],
        ));
    }

    public function get_return_address()
    {
        return $this->get_default_address('return_address_');
    }

    public function get_hide_sender_address()
    {
        return $this->get_default_address('hide_sender_address_');
    }

    public function get_bulk_size()
    {
        $shipping_methods = WC_Shipping::instance()->get_shipping_methods();
        if (!isset($shipping_methods['dhlpwc']->settings['bulk_label_creation'])) {
            return null;
        }
        return $shipping_methods['dhlpwc']->settings['bulk_label_creation'];
    }

}

endif;
