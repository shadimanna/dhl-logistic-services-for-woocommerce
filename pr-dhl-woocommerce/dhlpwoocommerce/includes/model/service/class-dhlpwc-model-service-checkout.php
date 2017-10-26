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
    public function get_parcelshops($postalcode, $limit = 13)
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        $parcelshops_data = $connector->get('parcel-shop-locations/NL', array(
            'limit' => $limit,
            'zipCode' => $postalcode,
        ));
        if (!$parcelshops_data || !is_array($parcelshops_data)) {
            return array();
        }
        $parcelshops = array();
        foreach ($parcelshops_data as $parcelshop_data) {
            $parcelshops[] = new DHLPWC_Model_API_Data_Parcelshop($parcelshop_data);
        }
        return $parcelshops;
    }

    public function get_parcelshop($parcelshop_id)
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        $parcelshop_data = $connector->get(sprintf('parcel-shop-locations/NL/%s', $parcelshop_id));
        if (!$parcelshop_data) {
            return null;
        }
        return new DHLPWC_Model_API_Data_Parcelshop($parcelshop_data);
    }

}

endif;
