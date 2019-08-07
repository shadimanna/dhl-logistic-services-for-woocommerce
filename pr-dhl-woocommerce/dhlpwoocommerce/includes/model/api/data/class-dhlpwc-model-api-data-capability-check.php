<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Capability_Check')) :

class DHLPWC_Model_API_Data_Capability_Check extends DHLPWC_Model_API_Data_Abstract
{

    protected $ignore_null_map = array(
        'from_country',
        'to_country',
        'to_business',
        'return_product',
        'parcel_type',
        'option',
        'to_postal_code',
        'account_number'
    );

    public $from_country;
    public $to_country;
    public $to_business;
    public $return_product;
    public $parcel_type;
    public $option;
    public $to_postal_code;
    public $account_number;

}

endif;
