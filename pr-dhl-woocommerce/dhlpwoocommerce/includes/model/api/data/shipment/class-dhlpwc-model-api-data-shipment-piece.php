<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Shipment_Piece')) :

class DHLPWC_Model_API_Data_Shipment_Piece extends DHLPWC_Model_API_Data_Abstract
{

    protected $class_map = array(
        'dimensions'     => 'DHLPWC_Model_API_Data_Shipment_Piece_Dimensions',
    );

    protected $ignore_null_map = array(
        'weight',
        'dimensions',
    );

    public $parcel_type;
    public $quantity;
    public $weight;
    /** @var DHLPWC_Model_API_Data_Shipment_Piece_Dimensions $dimensions */
    public $dimensions;

}

endif;
