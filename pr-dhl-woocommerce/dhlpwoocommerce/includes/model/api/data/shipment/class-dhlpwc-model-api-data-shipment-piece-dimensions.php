<?php

if (!defined('ABSPATH')) { exit; }

if (!class_exists('DHLPWC_Model_API_Data_Shipment_Piece_Dimensions')) :

class DHLPWC_Model_API_Data_Shipment_Piece_Dimensions extends DHLPWC_Model_API_Data_Abstract
{

    public $length;
    public $width;
    public $height;

}

endif;
