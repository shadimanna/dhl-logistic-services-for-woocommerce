<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Shipment_Address')) :

class DHLPWC_Model_API_Data_Shipment_Address extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'name'    => 'DHLPWC_Model_API_Data_Shipment_Address_Name',
        'address' => 'DHLPWC_Model_API_Data_Address',
    );

    /** @var DHLPWC_Model_API_Data_Shipment_Address_Name $name */
    public $name;
    /** @var DHLPWC_Model_API_Data_Address $address */
    public $address;
    public $email;
    public $phone_number;

}

endif;
