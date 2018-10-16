<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Label')) :

class DHLPWC_Model_API_Data_Label extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'receiver'     => 'DHLPWC_Model_API_Data_Label_Address',
        'shipper'      => 'DHLPWC_Model_API_Data_Label_Address',
        'on_behalf_of' => 'DHLPWC_Model_API_Data_Label_Address',
    );

    protected $ignore_null_map = array(
        'return_label',
        'on_behalf_of',
    );

    public $label_id;
    public $order_reference;
    public $parcel_type_key;
    /** @var DHLPWC_Model_API_Data_Label_Address $receiver */
    public $receiver;
    /** @var DHLPWC_Model_API_Data_Label_Address $shipper */
    public $shipper;
    /** @var DHLPWC_Model_API_Data_Label_Address $on_behalf_of */
    public $on_behalf_of;
    public $account_id;
    public $options;
    public $return_label;
    public $piece_number;
    public $quantity;
    public $application;

}

endif;
