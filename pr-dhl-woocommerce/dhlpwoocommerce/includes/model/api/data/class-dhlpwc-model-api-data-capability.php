<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Capability')) :

class DHLPWC_Model_API_Data_Capability extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'product'     => 'DHLPWC_Model_API_Data_Product',
        'parcel_type' => 'DHLPWC_Model_API_Data_Parceltype',
    );

    protected $array_class_map = array(
        'options'     => 'DHLPWC_Model_API_Data_Option'
    );

    public $rank;
    public $from_country_code;
    public $to_country_code;
    /** @var DHLPWC_Model_API_Data_Product $product */
    public $product;
    /** @var DHLPWC_Model_API_Data_Parceltype $parcel_type */
    public $parcel_type;
    /** @var DHLPWC_Model_API_Data_Option[] $options */
    public $options;

}

endif;
