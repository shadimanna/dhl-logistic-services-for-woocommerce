<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Checkout')) :

class DHLPWC_Model_Service_Checkout extends DHLPWC_Model_Core_Singleton_Abstract
{

    public function get_options()
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        return $connector->get('shipment-options/business');
    }

    /**
     * Return an array of closest Parcelshops based on postalcode.
     *
     * @param $postalcode
     * @param int $limit
     * @return DHLPWC_Model_API_Data_Parcelshop[]
     */
    public function get_parcelshops($postalcode, $country, $limit = 13)
    {
        $connector = DHLPWC_Model_API_Connector::instance();

        // TODO do country-based zip coded checks
        // TODO whitelist countries
//        $service = DHLPWC_Model_Service_Postcode::instance();
//        $validation = $service->get_validation($country);
        if (!$this->validate_country($country)) {
            return array();
        }

        $parcelshops_data = $connector->get('parcel-shop-locations/'.$country, array(
            'limit' => $limit,
            'zipCode' => $postalcode,
        ));
        if (!$parcelshops_data || !is_array($parcelshops_data)) {
            return array();
        }

        $parcelshops = array();
        foreach ($parcelshops_data as $parcelshop_data) {
            $parcelshop = new DHLPWC_Model_API_Data_Parcelshop($parcelshop_data);
            $parcelshop->country = $country;
            $parcelshops[] = $parcelshop;
        }
        return $parcelshops;
    }

    public function get_parcelshop($parcelshop_id, $country)
    {
        if (!$this->validate_country($country)) {
            return null;
        }

        $connector = DHLPWC_Model_API_Connector::instance();
        $parcelshop_data = $connector->get(sprintf('parcel-shop-locations/'.$country.'/%s', $parcelshop_id));
        if (!$parcelshop_data) {
            return null;
        }
        $parcelshop = new DHLPWC_Model_API_Data_Parcelshop($parcelshop_data);
        $parcelshop->country = $country;

        return $parcelshop;
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
