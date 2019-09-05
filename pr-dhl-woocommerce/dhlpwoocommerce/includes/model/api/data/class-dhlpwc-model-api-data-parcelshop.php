<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Parcelshop')) :

class DHLPWC_Model_API_Data_Parcelshop extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'address'     => 'DHLPWC_Model_API_Data_Address',
        'geo_location' => 'DHLPWC_Model_API_Data_GeoLocation',
    );

    protected $array_class_map = array(
        'opening_times'     => 'DHLPWC_Model_API_Data_ParcelShop_OpeningTimes'
    );

    public $id;
    public $name;
    public $keyword;
    /** @var DHLPWC_Model_API_Data_Address $address */
    public $address;
    /** @var DHLPWC_Model_API_Data_GeoLocation $geo_location */
    public $geo_location;
    public $distance;
    /** @var DHLPWC_Model_API_Data_ParcelShop_OpeningTimes[] $opening_times */
    public $opening_times;
    public $shop_type;

    public $country;

}

endif;
