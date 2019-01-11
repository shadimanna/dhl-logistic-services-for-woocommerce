<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Shipment_Request')) :

class DHLPWC_Model_API_Data_Shipment_Request extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'receiver'     => 'DHLPWC_Model_API_Data_Shipment_Address',
        'shipper'      => 'DHLPWC_Model_API_Data_Shipment_Address',
        'on_behalf_of' => 'DHLPWC_Model_API_Data_Shipment_Address',
    );

    protected $array_class_map = array(
        'pieces' => 'DHLPWC_Model_API_Data_Shipment_Piece',
    );

    protected $ignore_null_map = array(
        'return_label',
        'on_behalf_of',
    );

    public $shipment_id;
    public $order_reference;
    /** @var DHLPWC_Model_API_Data_Shipment_Address $receiver */
    public $receiver;
    /** @var DHLPWC_Model_API_Data_Shipment_Address $shipper */
    public $shipper;
    /** @var DHLPWC_Model_API_Data_Shipment_Address $on_behalf_of */
    public $on_behalf_of;
    public $account_id;
    public $options;
    public $return_label;
    public $pieces;
    public $application;

}

endif;
