<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Label_Address')) :

class DHLPWC_Model_API_Data_Label_Address extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'name'    => 'DHLPWC_Model_API_Data_Label_Address_Name',
        'address' => 'DHLPWC_Model_API_Data_Address',
    );

    public $name;
    public $address;
    public $email;
    public $phone_number;

}

endif;
