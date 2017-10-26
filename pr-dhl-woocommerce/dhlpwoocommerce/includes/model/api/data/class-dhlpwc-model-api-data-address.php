<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Address')) :

class DHLPWC_Model_API_Data_Address extends DHLPWC_Model_API_Data_Abstract
{

    protected $rename_map = array(
        'zip_code' => 'postal_code',
    );

    public $country_code;
    public $postal_code;
    public $city;
    public $street;
    public $number;
    public $is_business = false;
    public $addition = '';

}

endif;
