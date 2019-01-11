<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_Service_Parceltypes')) :

/**
 * Get available parceltypes from the API
 */
class DHLPWC_Model_Service_Parceltypes extends DHLPWC_Model_Core_Singleton_Abstract
{

    protected $parcel_types = array();

    public function __construct()
    {
        $connector = DHLPWC_Model_API_Connector::instance();
        $sender_type = 'business'; // A webshop is always a business, not a regular consumer type nor a parcelshop. Will leave this as hardcoded for now.
        $parcel_types = $connector->get(sprintf('parcel-types/%s', $sender_type), 1 * HOUR_IN_SECONDS);
        foreach($parcel_types as $parcel_type)
        {
            $this->parcel_types[$parcel_type['key']] = new DHLPWC_Model_API_Data_Parceltype($parcel_type);;
        }
    }

    public function get_names()
    {
        return array_keys($this->parcel_types);
    }

    public function get_all()
    {
        return $this->parcel_types;
    }

}

endif;
