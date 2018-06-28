<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Label')) :

class DHLPWC_Model_API_Data_Label extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'receiver' => 'DHLPWC_Model_API_Data_Label_Address',
        'shipper'  => 'DHLPWC_Model_API_Data_Label_Address',
    );

    public $label_id;
    public $order_reference;
    public $parcel_type_key;
    /** @var DHLPWC_Model_API_Data_Label_Address $receiver */
    public $receiver;
    /** @var DHLPWC_Model_API_Data_Label_Address $shipper */
    public $shipper;
    public $account_id;
    public $options;
    public $piece_number;
    public $quantity;
    public $application;

}

endif;
