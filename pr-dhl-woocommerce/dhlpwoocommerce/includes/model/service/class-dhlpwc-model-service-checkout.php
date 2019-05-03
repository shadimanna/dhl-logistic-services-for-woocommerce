<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Checkout')) :

class DHLPWC_Model_Service_Checkout extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_cart_shipping_country_code()
    {
        $customer = WC()->customer;

        if (empty($customer)) {
            return null;
        }

        $country = $customer->get_shipping_country();

        if (empty($country)) {
            return null;
        }

        if (!$this->validate_country($country)) {
            return null;
        }

        return $country;
    }


    public function get_cart_shipping_postal_code($numbers_only = false)
    {
        $customer = WC()->customer;

        if (empty($customer)) {
            return null;
        }

        $country = $customer->get_shipping_country();
        if (empty($country)) {
            return null;
        }

        $postcode = $customer->get_shipping_postcode();
        if (empty($postcode)) {
            return null;
        }

        if ($numbers_only) {
            $postcode = preg_replace('~\D~', '', $postcode);
        } else {
            $postcode = WC_Validation::format_postcode($postcode, $country);
        }

        return $postcode;
    }

    protected function validate_country($country)
    {
        if (!ctype_upper($country)) {
            return false;
        }

        if (strlen($country) != 2) {
            return false;
        }

        return true;
    }

}

endif;
